<?php

namespace App\Modules\Withdrawals\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\AppSetting;
use App\Models\WalletBalance;
use App\Models\WithdrawRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WithdrawRequestController extends Controller
{
    public function create(Request $request): View
    {
        $phone = $request->user()?->phone;

        return view('Withdrawals::create', [
            'balance' => $phone ? WalletBalance::balanceFor($phone) : 0.0,
            'methodsEnabled' => [
                WithdrawRequest::METHOD_BANK => AppSetting::enabled('withdrawal_method_bank_enabled'),
                WithdrawRequest::METHOD_UPI => AppSetting::enabled('withdrawal_method_upi_enabled'),
                WithdrawRequest::METHOD_USDT => AppSetting::enabled('withdrawal_method_usdt_enabled'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // System kill-switch (plan.md Section 41): admin can pause all withdrawals.
        if (! AppSetting::enabled('allow_withdrawals')) {
            return back()->withInput()->withErrors([
                'amount' => 'Withdrawals are temporarily disabled. Please try again later.',
            ]);
        }

        // Defaults to UPI when omitted - keeps any pre-existing/older client
        // that only ever knew about payout_upi_id (no method selector) working.
        $request->merge(['method' => $request->input('method', WithdrawRequest::METHOD_UPI)]);

        $validated = $request->validate([
            'phone' => ['required', 'digits:10'],
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', 'in:'.implode(',', WithdrawRequest::METHODS)],
        ]);

        $methodEnabledKey = 'withdrawal_method_'.$validated['method'].'_enabled';
        if (! AppSetting::enabled($methodEnabledKey)) {
            return back()->withInput()->withErrors([
                'method' => 'This withdrawal method is not available right now. Please choose another.',
            ]);
        }

        $methodFields = $this->validateMethodFields($request, $validated['method']);

        $available = WalletBalance::balanceFor($validated['phone']);
        if ($validated['amount'] > $available) {
            return back()->withInput()->withErrors([
                'amount' => 'Insufficient balance. Available: ₹'.number_format($available, 2),
            ]);
        }

        $settings = AppSetting::many([
            'withdrawal_min_amount' => '300',
            'withdrawal_daily_limit' => '5000',
            'withdrawal_max_per_day' => '3',
            'withdrawal_max_per_transaction' => '5000',
        ]);

        $minAmount = (float) $settings['withdrawal_min_amount'];
        $dailyLimit = (float) $settings['withdrawal_daily_limit'];
        $maxPerDay = (int) $settings['withdrawal_max_per_day'];
        $maxPerTransaction = (float) $settings['withdrawal_max_per_transaction'];

        if ($validated['amount'] < $minAmount) {
            return back()->withInput()->withErrors([
                'amount' => 'Minimum withdrawal amount is ₹'.number_format($minAmount, 0).'.',
            ]);
        }

        if ($maxPerTransaction > 0 && $validated['amount'] > $maxPerTransaction) {
            return back()->withInput()->withErrors([
                'amount' => 'Maximum per transaction is ₹'.number_format($maxPerTransaction, 0).'.',
            ]);
        }

        $todayTotal = WithdrawRequest::where('phone', $validated['phone'])
            ->whereDate('created_at', today())
            ->sum('amount');

        if (($todayTotal + $validated['amount']) > $dailyLimit) {
            $remaining = max(0, $dailyLimit - $todayTotal);

            return back()->withInput()->withErrors([
                'amount' => 'Daily withdrawal limit is ₹'.number_format($dailyLimit, 0).'. You can withdraw up to ₹'.number_format($remaining, 0).' more today.',
            ]);
        }

        $todayCount = WithdrawRequest::where('phone', $validated['phone'])
            ->whereDate('created_at', today())
            ->count();

        if ($todayCount >= $maxPerDay) {
            return back()->withInput()->withErrors([
                'amount' => "You have reached the maximum of {$maxPerDay} withdrawal requests for today.",
            ]);
        }

        $withdraw = WithdrawRequest::create(array_merge([
            'phone' => $validated['phone'],
            'amount' => $validated['amount'],
            'method' => $validated['method'],
            'status' => WithdrawRequest::STATUS_PENDING,
            'submitted_at' => now(),
        ], $methodFields));

        AdminNotification::notify(
            'withdrawal_request',
            'New withdrawal request',
            '₹'.number_format($withdraw->amount, 2)." · {$withdraw->phone} · to {$withdraw->destinationLabel()}"
        );

        return redirect()->route('withdrawals.create')->with(
            'success',
            "Your ₹{$validated['amount']} withdrawal request has been submitted and is under review."
        );
    }

    /**
     * Method-specific required fields, validated separately from the common
     * phone/amount/method block above so each method's error messages stay
     * readable (a single combined rule array would force every field to be
     * "nullable" and lose real validation).
     *
     * @return array<string, mixed> attributes ready to merge into WithdrawRequest::create()
     */
    private function validateMethodFields(Request $request, string $method): array
    {
        return match ($method) {
            WithdrawRequest::METHOD_BANK => $this->extractBankFields($request),
            WithdrawRequest::METHOD_USDT => $this->extractUsdtFields($request),
            default => $this->extractUpiFields($request),
        };
    }

    private function extractUpiFields(Request $request): array
    {
        $validated = $request->validate([
            'payout_upi_id' => ['required', 'string', 'max:100', 'regex:/^[\w.\-]{2,256}@[a-zA-Z]{2,64}$/'],
            // Both optional (client spec: "UPI Number — Optional", "UPI QR — Optional").
            'upi_number' => ['nullable', 'digits:10'],
            'upi_qr' => ['nullable', 'image', 'max:4096'],
        ], [
            'payout_upi_id.regex' => 'Enter a valid UPI ID, e.g. name@bank.',
            'upi_number.digits' => 'Enter a valid 10-digit mobile number.',
        ]);

        $fields = ['payout_upi_id' => $validated['payout_upi_id'], 'upi_number' => $validated['upi_number'] ?? null];
        if ($request->hasFile('upi_qr')) {
            $fields['upi_qr'] = $this->storeUploadedQr($request, 'upi_qr');
        }

        return $fields;
    }

    private function extractBankFields(Request $request): array
    {
        // Normalize case before validating the format, not after - an admin
        // typing an all-lowercase IFSC shouldn't be rejected for casing.
        if ($request->filled('bank_ifsc')) {
            $request->merge(['bank_ifsc' => strtoupper((string) $request->input('bank_ifsc'))]);
        }

        return $request->validate([
            'bank_account_holder' => ['required', 'string', 'max:100'],
            'bank_account_number' => ['required', 'digits_between:6,20', 'confirmed'],
            'bank_ifsc' => ['required', 'string', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'bank_name' => ['required', 'string', 'max:100'],
            'bank_branch' => ['nullable', 'string', 'max:100'],
        ], [
            'bank_account_number.confirmed' => 'Account number and confirmation do not match.',
            'bank_account_number.digits_between' => 'Enter a valid bank account number.',
            'bank_ifsc.regex' => 'Enter a valid IFSC code, e.g. HDFC0001234.',
        ]);
    }

    private function extractUsdtFields(Request $request): array
    {
        $validated = $request->validate([
            'usdt_address' => ['required', 'string', 'regex:/^T[1-9A-HJ-NP-Za-km-z]{33}$/'],
            // Client spec: "QR Code — Optional".
            'usdt_qr' => ['nullable', 'image', 'max:4096'],
        ], [
            'usdt_address.regex' => 'Enter a valid TRC20 (Tron) wallet address - starts with T, 34 characters.',
        ]);

        $fields = ['usdt_address' => $validated['usdt_address']];
        if ($request->hasFile('usdt_qr')) {
            $fields['usdt_qr'] = $this->storeUploadedQr($request, 'usdt_qr');
        }

        return $fields;
    }

    // Same public/assets convention as DepositRequestController::storeUploadedScreenshot()
    // - this app is served directly out of public/ via a custom index.php, no
    // storage:link symlink involved anywhere else either.
    private function storeUploadedQr(Request $request, string $field): string
    {
        $file = $request->file($field);
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $directory = public_path('assets/withdrawal-proofs');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file->move($directory, $filename);

        return 'assets/withdrawal-proofs/'.$filename;
    }
}
