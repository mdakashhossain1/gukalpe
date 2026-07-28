<?php

namespace App\Modules\Withdrawals\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\WalletBalance;
use App\Models\WithdrawRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WithdrawRequestController extends Controller
{
    public function create(Request $request): View
    {
        $phone = $request->user()?->phone;

        return view('Withdrawals::create', [
            'balance' => $phone ? WalletBalance::balanceFor($phone) : 0.0,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'digits:10'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payout_upi_id' => ['required', 'string', 'max:100', 'regex:/^[\w.\-]{2,256}@[a-zA-Z]{2,64}$/'],
        ], [
            'payout_upi_id.regex' => 'Enter a valid UPI ID, e.g. name@bank.',
        ]);

        $available = WalletBalance::balanceFor($validated['phone']);
        if ($validated['amount'] > $available) {
            return back()->withInput()->withErrors([
                'amount' => 'Insufficient balance. Available: ₹'.number_format($available, 2),
            ]);
        }

        $settings = \App\Models\AppSetting::many([
            'withdrawal_min_amount' => '300',
            'withdrawal_daily_limit' => '5000',
            'withdrawal_max_per_day' => '3',
        ]);

        $minAmount = (float) $settings['withdrawal_min_amount'];
        $dailyLimit = (float) $settings['withdrawal_daily_limit'];
        $maxPerDay = (int) $settings['withdrawal_max_per_day'];

        if ($validated['amount'] < $minAmount) {
            return back()->withInput()->withErrors([
                'amount' => 'Minimum withdrawal amount is ₹'.number_format($minAmount, 0).'.',
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

        $withdraw = WithdrawRequest::create([
            'phone' => $validated['phone'],
            'amount' => $validated['amount'],
            'payout_upi_id' => $validated['payout_upi_id'],
            'status' => WithdrawRequest::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        AdminNotification::notify(
            'withdrawal_request',
            'New withdrawal request',
            '₹'.number_format($withdraw->amount, 2)." · {$withdraw->phone} · to {$withdraw->payout_upi_id}"
        );

        return redirect()->route('withdrawals.create')->with(
            'success',
            "Your ₹{$validated['amount']} withdrawal request has been submitted and is under review."
        );
    }
}
