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
