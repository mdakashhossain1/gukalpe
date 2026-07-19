<?php

namespace App\Modules\Deposits\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\DepositRequestReceivedMail;
use App\Mail\NewDepositRequestMail;
use App\Models\AdminNotification;
use App\Models\AppSetting;
use App\Models\DepositRequest;
use App\Models\PaymentBankAccount;
use App\Models\PaymentUpiAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepositRequestController extends Controller
{
    public function start(Request $request): RedirectResponse
    {
        // May still contain thousands-separator commas from the input's own
        // formatting, so strip anything non-numeric before storing.
        $amount = preg_replace('/\D/', '', (string) $request->input('amount', ''));

        // Flash (not a regular session value): survives exactly this
        // redirect and is gone after create() reads it - never sits in the
        // URL, and never lingers in the session past the one hand-off.
        $request->session()->flash('deposit_amount_prefill', $amount !== '' ? (int) $amount : null);

        return redirect()->route('deposits.create');
    }

    public function create(Request $request): View|RedirectResponse
    {
        // Depositing has to credit a specific wallet, so it requires a
        // signed-in user - unlike Home, which supports guest browsing.
        // Reusing the field, not asking for it again, is the whole point of
        // this check: the user is already identified by their session.
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login')->with('error', 'Please log in to add money to your wallet.');
        }

        // A just-completed submission takes priority over the normal form -
        // store() flashes structured data here (instead of a plain string
        // message) so this page can render the full "Payment Received"
        // confirmation modal. Checked before the amount guard below because
        // the amount-prefill flash was already consumed by the request that
        // got here in the first place; there's nothing left for that guard
        // to find, and there doesn't need to be - the deposit already went
        // through.
        if ($request->session()->has('depositSuccess')) {
            return view('Deposits::success', $request->session()->get('depositSuccess'));
        }

        // The amount is chosen once, on Home's "Quick Add Amount" card, and
        // carried here via session flash (see start()) - or, after a failed
        // store() submission, via old() - so this page never asks for it
        // again. If neither is present (e.g. a stale/direct link to this
        // URL with no amount ever chosen), there's nothing to show a
        // payment method for, so send the user back to pick one.
        $amount = old('amount', $request->session()->get('deposit_amount_prefill'));
        if (! is_numeric($amount) || (float) $amount < 1) {
            return redirect()->route('home')->with('error', 'Please choose an amount to add first.');
        }

        // A single flat choice - 'upi' | 'bank' - exactly one is ever
        // active, so there's no user-facing tab/switch to resolve here.
        $mode = AppSetting::get('payment_mode', AppSetting::DEFAULTS['payment_mode']);
        $activeMethod = in_array($mode, ['upi', 'bank'], true) ? $mode : null;

        // Re-queried fresh on every request (no caching/session pinning) -
        // this is what makes the shown account genuinely random each time
        // the page is opened, per the admin-facing requirement.
        $upiAccount = $activeMethod === 'upi' ? PaymentUpiAccount::active()->inRandomOrder()->first() : null;
        $bankAccount = $activeMethod === 'bank' ? PaymentBankAccount::active()->inRandomOrder()->first() : null;

        $noAccountAvailable = ($activeMethod === 'upi' && ! $upiAccount) || ($activeMethod === 'bank' && ! $bankAccount);

        if ($activeMethod === null || $noAccountAvailable) {
            return view('Deposits::payment-unavailable', [
                'title' => 'Deposits temporarily unavailable',
                'message' => 'No payment method is available right now. Please try again shortly.',
            ]);
        }

        return view('Deposits::create', [
            'amount' => (int) $amount,
            'activeMethod' => $activeMethod,
            'upiAccount' => $upiAccount,
            'bankAccount' => $bankAccount,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Same identity check as create() - and the phone that actually
        // gets credited comes from this authenticated user below, never
        // from a posted form field (which would let anyone type an
        // arbitrary phone number and credit someone else's wallet).
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login')->with('error', 'Please log in to add money to your wallet.');
        }

        // Only the currently-active method may be submitted - whatever the
        // admin has set payment_mode to is the only accepted value here too.
        $mode = AppSetting::get('payment_mode', AppSetting::DEFAULTS['payment_mode']);
        $allowedMethods = in_array($mode, ['upi', 'bank'], true) ? [$mode] : [];

        // Bank transfer references (NEFT/RTGS UTRs, IMPS ref numbers) aren't
        // reliably 12 digits the way a UPI UTR is, so the format rule
        // branches by method instead of forcing the UPI shape on both.
        $utrRules = $request->input('method') === 'bank'
            ? ['required', 'string', 'min:4', 'max:30']
            : ['required', 'digits:12'];

        $utrRules[] = Rule::unique('deposit_requests', 'utr')->where(
            fn ($query) => $query->where('status', '!=', DepositRequest::STATUS_REJECTED)
        );

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', Rule::in($allowedMethods)],
            // Rendered server-side from whichever account create() actually
            // displayed - purely a human-readable hint for admin review, not
            // a source of truth (the admin still verifies the real bank/UPI
            // statement before approving), so it's optional/free text.
            'pay_to_label' => ['nullable', 'string', 'max:150'],
            'utr' => $utrRules,
        ], [
            'utr.unique' => 'This UTR/reference number has already been used for another deposit.',
            'utr.digits' => 'Enter the 12-digit UTR/reference number exactly as shown in your UPI app.',
            'utr.min' => 'Enter the transaction reference number exactly as shown in your bank statement.',
        ]);

        $methodLabel = $validated['method'] === 'upi' ? 'UPI' : 'Bank Transfer';
        if (! empty($validated['pay_to_label'])) {
            $methodLabel .= ' · '.$validated['pay_to_label'];
        }

        // The unique validation rule above covers the common case; this
        // catches the narrow race window between two near-simultaneous
        // submissions of the same UTR, which the database's own partial
        // unique index (not just the validator) is the real backstop for.
        try {
            $deposit = DepositRequest::create([
                'phone' => $user->phone,
                'amount' => $validated['amount'],
                'method' => $validated['method'],
                'method_label' => $methodLabel,
                'utr' => $validated['utr'],
                'status' => DepositRequest::STATUS_PENDING,
                'submitted_at' => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'utr')) {
                return back()->withInput()->withErrors([
                    'utr' => 'This UTR/reference number has already been used for another deposit.',
                ]);
            }
            throw $e;
        }

        // In-app notification - same pattern WithdrawRequestController
        // already uses for withdrawal requests; deposits never had it.
        AdminNotification::notify(
            'deposit_request',
            'New deposit request',
            '₹'.number_format($deposit->amount, 2)." · {$deposit->phone} · {$deposit->method_label}"
        );

        // Email channel alongside it - skipped (not an error) until a real
        // ADMIN_NOTIFICATION_EMAIL is configured. Queued (NewDepositRequestMail
        // implements ShouldQueue) onto the app's existing database queue so
        // an unreachable/slow mail server never delays this response.
        if ($adminEmail = config('admin.notification_email')) {
            Mail::to($adminEmail)->queue(new NewDepositRequestMail($deposit));
        }

        // User-facing confirmation - only when there's a real address to
        // send it to. Most accounts are phone/OTP signups with a synthetic,
        // non-deliverable placeholder email (see User::hasRealEmail()), so
        // this quietly skips for them rather than "sending" to nowhere.
        if ($user->hasRealEmail()) {
            Mail::to($user->email)->queue(new DepositRequestReceivedMail($deposit));
        }

        return redirect()->route('deposits.create')->with('depositSuccess', [
            'amount' => $validated['amount'],
            'methodLabel' => $methodLabel,
            'utr' => $validated['utr'],
            'submittedAt' => now(),
        ]);
    }
}
