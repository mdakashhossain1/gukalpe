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
use App\Models\PaymentUsdtAccount;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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
        $amountValue = (float) $amount;

        // Global per-transaction deposit cap (admin Settings → Max deposit limit).
        $maxDeposit = (float) AppSetting::get('max_deposit_limit', AppSetting::DEFAULTS['max_deposit_limit']);
        if ($maxDeposit > 0 && $amountValue > $maxDeposit) {
            return redirect()->route('home')->with('error', 'Maximum deposit is ₹'.number_format($maxDeposit, 0).' per transaction.');
        }

        // Method-level amount routing: the admin sets ONE amount range each for
        // UPI/Bank/USDT (app_settings). The deposit amount decides which
        // METHOD is eligible; the specific account is then a random pick among
        // ALL active accounts of that method. When more than one method's
        // range covers the amount, one method is chosen at random.
        $settings = AppSetting::many([
            'upi_min_amount' => AppSetting::DEFAULTS['upi_min_amount'],
            'upi_max_amount' => AppSetting::DEFAULTS['upi_max_amount'],
            'bank_min_amount' => AppSetting::DEFAULTS['bank_min_amount'],
            'bank_max_amount' => AppSetting::DEFAULTS['bank_max_amount'],
            'usdt_min_amount' => AppSetting::DEFAULTS['usdt_min_amount'],
            'usdt_max_amount' => AppSetting::DEFAULTS['usdt_max_amount'],
        ]);

        // Least-recently-used rotation (client item 9.2/9.3: "each retry
        // should show the next UPI/bank, not the same one" - extended to
        // USDT for parity). NULLs (never shown yet) sort first in SQLite's
        // default ASC ordering, so a freshly added account is picked before
        // any reused one; picking touches last_used_at so the next request
        // naturally rotates to a different account without a separate
        // mutable pointer/counter that would race under concurrent requests.
        $candidates = [];
        if ($this->rangeCoversAmount($settings['upi_min_amount'], $settings['upi_max_amount'], $amountValue)
            && ($upiAccount = PaymentUpiAccount::active()->orderBy('last_used_at', 'asc')->first())) {
            $upiAccount->update(['last_used_at' => now()]);
            $candidates['upi'] = $upiAccount;
        }
        if ($this->rangeCoversAmount($settings['bank_min_amount'], $settings['bank_max_amount'], $amountValue)
            && ($bankAccount = PaymentBankAccount::active()->orderBy('last_used_at', 'asc')->first())) {
            $bankAccount->update(['last_used_at' => now()]);
            $candidates['bank'] = $bankAccount;
        }
        if ($this->rangeCoversAmount($settings['usdt_min_amount'], $settings['usdt_max_amount'], $amountValue)
            && ($usdtAccount = PaymentUsdtAccount::active()->orderBy('last_used_at', 'asc')->first())) {
            $usdtAccount->update(['last_used_at' => now()]);
            $candidates['usdt'] = $usdtAccount;
        }

        if ($candidates === []) {
            return view('Deposits::payment-unavailable', [
                'title' => 'Deposits temporarily unavailable',
                'message' => 'No payment method is available for this amount right now. Please try a different amount or again shortly.',
            ]);
        }

        $activeMethod = array_rand($candidates);

        return view('Deposits::create', [
            'amount' => (int) $amount,
            'activeMethod' => $activeMethod,
            'upiAccount' => $activeMethod === 'upi' ? $candidates['upi'] : null,
            'bankAccount' => $activeMethod === 'bank' ? $candidates['bank'] : null,
            'usdtAccount' => $activeMethod === 'usdt' ? $candidates['usdt'] : null,
        ]);
    }

    /**
     * A method's amount range is [min, max] where a blank max means "no upper
     * limit". A method with BOTH bounds blank is disabled (never matches) - so
     * an admin can turn a method off entirely just by clearing its range.
     */
    private function rangeCoversAmount(?string $min, ?string $max, float $amount): bool
    {
        $minSet = $min !== null && $min !== '';
        $maxSet = $max !== null && $max !== '';

        if (! $minSet && ! $maxSet) {
            return false;
        }
        if ($minSet && $amount < (float) $min) {
            return false;
        }
        if ($maxSet && $amount > (float) $max) {
            return false;
        }

        return true;
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

        // Routing is now per-account by amount (see create()), so any of the
        // three methods can legitimately reach this submit depending on
        // which account the user was shown - all are accepted here and the
        // real check stays manual verification by an admin.
        $allowedMethods = ['upi', 'bank', 'usdt'];

        // Reference-number shape differs per method: a UPI UTR is always 12
        // digits; bank transfer references (NEFT/RTGS UTRs, IMPS ref
        // numbers) aren't reliably numeric or a fixed length; a USDT (TRC20)
        // transaction hash is a 64-character hex string, longer than either.
        $utrRules = match ($request->input('method')) {
            'bank' => ['required', 'string', 'min:4', 'max:30'],
            'usdt' => ['required', 'string', 'regex:/^[0-9a-fA-F]{16,80}$/'],
            default => ['required', 'digits:12'],
        };

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
            // Optional (client's "Payment Proof" ask lists UTR + Screenshot,
            // but only marks adjacent fields like QR Code explicitly
            // Optional) - kept non-mandatory so submission never blocks a
            // user who can't attach one right now; the admin still verifies
            // the UTR against a real bank/UPI statement either way.
            'payment_screenshot' => ['nullable', 'image', 'max:4096'],
        ], [
            'utr.unique' => 'This UTR/reference number has already been used for another deposit.',
            'utr.digits' => 'Enter the 12-digit UTR/reference number exactly as shown in your UPI app.',
            'utr.min' => 'Enter the transaction reference number exactly as shown in your bank statement.',
            'utr.regex' => 'Enter the full transaction hash (TXID) from the USDT transfer.',
        ]);

        // Enforce the global deposit cap again at submission — the amount here
        // comes from a posted field, so it must be re-checked, not trusted.
        $maxDeposit = (float) AppSetting::get('max_deposit_limit', AppSetting::DEFAULTS['max_deposit_limit']);
        if ($maxDeposit > 0 && (float) $validated['amount'] > $maxDeposit) {
            return back()->withErrors(['amount' => 'Maximum deposit is ₹'.number_format($maxDeposit, 0).' per transaction.']);
        }

        $methodLabel = match ($validated['method']) {
            'bank' => 'Bank Transfer',
            'usdt' => 'USDT (TRC20)',
            default => 'UPI',
        };
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
                'payment_screenshot' => $request->hasFile('payment_screenshot')
                    ? $this->storeUploadedScreenshot($request)
                    : null,
                'status' => DepositRequest::STATUS_PENDING,
                'submitted_at' => now(),
            ]);
        } catch (QueryException $e) {
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

    // Same public/assets convention as PlanManagementController/BannerController's
    // storeUploadedImage() - this app is served directly out of public/ via
    // a custom index.php, no storage:link symlink involved anywhere else.
    private function storeUploadedScreenshot(Request $request): string
    {
        $file = $request->file('payment_screenshot');
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $directory = public_path('assets/deposit-proofs');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file->move($directory, $filename);

        return 'assets/deposit-proofs/'.$filename;
    }
}
