<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\DepositRequest;
use App\Models\ReferralCommission;
use App\Models\User;
use App\Models\WalletBalance;
use App\Models\WithdrawRequest;
use App\Support\AdminRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ReferralCommissionController extends Controller
{
    private function sidebarCounts(): array
    {
        return [
            'pendingDepositCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
            'pendingWithdrawalCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
            'pendingReferralCommissionCount' => ReferralCommission::status(ReferralCommission::STATUS_PENDING)->count(),
        ];
    }

    /**
     * Dashboard stats + the full history table for one status tab at a
     * time - same shape as AdminController::deposits(): server filters by
     * status/phone, hands the whole filtered set to the view, search/sort/
     * paging happen client-side via simpleDatatables.
     */
    public function index(Request $request): View
    {
        abort_unless(AdminRoles::currentCan('manage_referrals'), 403);

        $status = $request->query('status', ReferralCommission::STATUS_PENDING);
        $validStatuses = [
            ReferralCommission::STATUS_PENDING,
            ReferralCommission::STATUS_PAID,
            ReferralCommission::STATUS_REJECTED,
            ReferralCommission::STATUS_REVERSED,
        ];
        if (! in_array($status, $validStatuses, true)) {
            $status = ReferralCommission::STATUS_PENDING;
        }

        $phone = trim((string) $request->query('phone', ''));

        $query = ReferralCommission::status($status)->with(['referrer', 'referredUser'])->latest();
        if ($phone !== '') {
            $query->where(function ($q) use ($phone) {
                $q->whereHas('referrer', fn ($r) => $r->where('phone', $phone))
                    ->orWhereHas('referredUser', fn ($r) => $r->where('phone', $phone));
            });
        }

        // "Active Referral" = a referred user who has at least one
        // qualifying transaction that earned (or would earn) commission -
        // i.e. they appear as referred_user_id on any commission row,
        // regardless of that row's current status (pending still counts,
        // since the qualifying transaction already happened).
        $totalReferrals = User::whereNotNull('referred_by')->count();
        $activeReferrals = ReferralCommission::distinct('referred_user_id')->count('referred_user_id');
        $pendingCommission = (float) ReferralCommission::status(ReferralCommission::STATUS_PENDING)->sum('amount');
        $paidCommission = (float) ReferralCommission::status(ReferralCommission::STATUS_PAID)->sum('amount');

        return view('Admin::referral-commissions', [
            'status' => $status,
            'phone' => $phone,
            'commissions' => $query->get(),
            'totalReferrals' => $totalReferrals,
            'activeReferrals' => $activeReferrals,
            'totalCommission' => $pendingCommission + $paidCommission,
            'pendingCommission' => $pendingCommission,
            'paidCommission' => $paidCommission,
            ...$this->sidebarCounts(),
        ]);
    }

    // No mandatory reason - mirrors AdminController::approveDeposit(), which
    // also moves money on Approve with no reason required (asymmetric with
    // Reject/Adjust/Reverse below, same asymmetry as Ban vs Unban).
    public function approve(Request $request, ReferralCommission $referralCommission): RedirectResponse
    {
        abort_unless(AdminRoles::currentCan('manage_referrals'), 403);

        if ($referralCommission->status !== ReferralCommission::STATUS_PENDING) {
            return back()->with('error', 'This commission has already been reviewed.');
        }

        $referrer = $referralCommission->referrer;
        if (! $referrer || ! $referrer->phone) {
            return back()->with('error', 'Referrer has no phone/wallet on file.');
        }

        $wallet = WalletBalance::credit(
            $referrer->phone,
            (float) $referralCommission->amount,
            'referral_commission',
            ['referral_commission_id' => $referralCommission->id]
        );

        $referralCommission->update([
            'status' => ReferralCommission::STATUS_PAID,
            'reviewed_at' => now(),
        ]);

        Log::channel('admin_security')->info('Referral commission approved', [
            'referral_commission_id' => $referralCommission->id,
            'referrer_id' => $referrer->id,
            'amount' => (float) $referralCommission->amount,
            'new_balance' => (float) $wallet->balance,
        ]);

        AdminAuditLog::record($request, 'referral_commission_approved', $referralCommission, null, [
            'referrer_phone' => $referrer->phone,
            'amount' => (float) $referralCommission->amount,
            'new_balance' => (float) $wallet->balance,
        ]);

        return back()->with('success', "Approved. ₹{$referralCommission->amount} credited to {$referrer->phone}.");
    }

    public function reject(Request $request, ReferralCommission $referralCommission): RedirectResponse
    {
        abort_unless(AdminRoles::currentCan('manage_referrals'), 403);

        if ($referralCommission->status !== ReferralCommission::STATUS_PENDING) {
            return back()->with('error', 'This commission has already been reviewed.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'Enter a reason for rejecting this commission.',
        ]);
        $reason = trim($validated['reason']);

        $referralCommission->update([
            'status' => ReferralCommission::STATUS_REJECTED,
            'reason' => $reason,
            'reviewed_at' => now(),
        ]);

        Log::channel('admin_security')->info('Referral commission rejected', [
            'referral_commission_id' => $referralCommission->id,
            'reason' => $reason,
        ]);

        AdminAuditLog::record($request, 'referral_commission_rejected', $referralCommission, $reason);

        return back()->with('success', 'Commission rejected. No money was moved.');
    }

    // Only allowed pre-payout (status still pending) - corrects the amount
    // before it's ever credited. Reversing an already-paid commission is a
    // separate action (reverse() below) since it has to move money back out.
    public function adjust(Request $request, ReferralCommission $referralCommission): RedirectResponse
    {
        abort_unless(AdminRoles::currentCan('manage_referrals'), 403);

        if ($referralCommission->status !== ReferralCommission::STATUS_PENDING) {
            return back()->with('error', 'Only a pending commission can be adjusted.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'Enter a reason for adjusting this commission.',
        ]);
        $reason = trim($validated['reason']);
        $oldAmount = (float) $referralCommission->amount;
        $newAmount = round((float) $validated['amount'], 2);

        $referralCommission->update([
            'amount' => $newAmount,
            'reason' => $reason,
        ]);

        Log::channel('admin_security')->info('Referral commission adjusted', [
            'referral_commission_id' => $referralCommission->id,
            'old_amount' => $oldAmount,
            'new_amount' => $newAmount,
            'reason' => $reason,
        ]);

        AdminAuditLog::record($request, 'referral_commission_adjusted', $referralCommission, $reason, [
            'old_amount' => $oldAmount,
            'new_amount' => $newAmount,
        ]);

        return back()->with('success', "Adjusted to ₹{$newAmount}.");
    }

    // Only allowed on an already-paid commission - claws back the wallet
    // credit. Same tradeoff as WalletBalance::debit() everywhere else in
    // this app: it does not block on insufficient balance, so a reversal
    // can leave the referrer's wallet negative if they've already spent the
    // commission - that's an accepted admin-override risk, not a bug, same
    // as the manual wallet-adjust tool.
    public function reverse(Request $request, ReferralCommission $referralCommission): RedirectResponse
    {
        abort_unless(AdminRoles::currentCan('manage_referrals'), 403);

        if ($referralCommission->status !== ReferralCommission::STATUS_PAID) {
            return back()->with('error', 'Only a paid commission can be reversed.');
        }

        $referrer = $referralCommission->referrer;
        if (! $referrer || ! $referrer->phone) {
            return back()->with('error', 'Referrer has no phone/wallet on file.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'Enter a reason for reversing this commission.',
        ]);
        $reason = trim($validated['reason']);

        $wallet = WalletBalance::debit(
            $referrer->phone,
            (float) $referralCommission->amount,
            'referral_reversal',
            ['referral_commission_id' => $referralCommission->id]
        );

        $referralCommission->update([
            'status' => ReferralCommission::STATUS_REVERSED,
            'reason' => $reason,
            'reviewed_at' => now(),
        ]);

        Log::channel('admin_security')->info('Referral commission reversed', [
            'referral_commission_id' => $referralCommission->id,
            'referrer_id' => $referrer->id,
            'amount' => (float) $referralCommission->amount,
            'new_balance' => (float) $wallet->balance,
            'reason' => $reason,
        ]);

        AdminAuditLog::record($request, 'referral_commission_reversed', $referralCommission, $reason, [
            'referrer_phone' => $referrer->phone,
            'amount' => (float) $referralCommission->amount,
            'new_balance' => (float) $wallet->balance,
        ]);

        return back()->with('success', "Reversed. ₹{$referralCommission->amount} debited from {$referrer->phone}.");
    }
}
