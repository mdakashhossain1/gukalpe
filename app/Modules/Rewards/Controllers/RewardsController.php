<?php

namespace App\Modules\Rewards\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\DepositRequest;
use App\Models\ReferralCommission;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\WalletBalance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

class RewardsController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        if (! $user) {
            return view('Rewards::rewards', ['user' => null]);
        }

        // Registration is only ever meant to happen through a shared link,
        // never by someone typing in a "code" - so the code itself is
        // encrypted before it ever reaches the link, and is never exposed
        // to the view as plain text. HomeController::captureReferralCode()
        // is the only place that decrypts it back, using this app's own
        // APP_KEY, so nobody can read or forge a token without it.
        $referralToken = Crypt::encryptString($user->referralCode());
        $referralLink = route('home', ['ref' => $referralToken]);
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&margin=8&data='.urlencode($referralLink);

        $referrals = User::where('referred_by', $user->id)
            ->latest()
            ->get(['id', 'name', 'phone', 'created_at']);

        $invitedUserIds = $referrals->pluck('id');
        $referredPhones = $referrals->pluck('phone')->filter()->values();

        $investedUserIds = $invitedUserIds->isEmpty()
            ? collect()
            : UserPlan::whereIn('user_id', $invitedUserIds)->distinct()->pluck('user_id');

        $depositedPhones = $referredPhones->isEmpty()
            ? collect()
            : DepositRequest::whereIn('phone', $referredPhones)->status(DepositRequest::STATUS_APPROVED)->distinct()->pluck('phone');

        // A referred user can now earn the referrer TWO commission rows
        // (plan-purchase and deposit are independent sources) - group
        // rather than keyBy so a second row never silently overwrites the
        // first, and sum per referred user for the history table.
        $commissions = ReferralCommission::where('referrer_id', $user->id)->get();
        $commissionsByReferred = $commissions->groupBy('referred_user_id');

        $referralHistory = $referrals->map(fn (User $referred) => [
            'name' => $referred->name,
            'maskedPhone' => $referred->phone ? '+91 ******'.substr($referred->phone, -4) : null,
            'joinedAt' => $referred->created_at,
            'hasInvested' => $investedUserIds->contains($referred->id),
            'commissionEarned' => $commissionsByReferred->get($referred->id)?->sum('amount'),
        ]);

        $totalInvites = $referrals->count();
        $totalInvested = $investedUserIds->count();

        // Commissions are no longer credited instantly - split by status so
        // the page can be honest about what's actually in the wallet
        // (paid) vs still awaiting admin review (pending). Total = both,
        // since both represent commission genuinely earned.
        $pendingCommission = (float) $commissions->where('status', ReferralCommission::STATUS_PENDING)->sum('amount');
        $paidCommission = (float) $commissions->where('status', ReferralCommission::STATUS_PAID)->sum('amount');

        return view('Rewards::rewards', [
            'user' => $user,
            'referralLink' => $referralLink,
            'qrCodeUrl' => $qrCodeUrl,
            'totalInvites' => $totalInvites,
            'totalRegistered' => $totalInvites - $totalInvested,
            'totalDeposited' => $depositedPhones->count(),
            'totalInvested' => $totalInvested,
            'totalCommission' => $pendingCommission + $paidCommission,
            'pendingCommission' => $pendingCommission,
            'paidCommission' => $paidCommission,
            'walletBalance' => $user->phone ? WalletBalance::balanceFor($user->phone) : 0.0,
            'commissionPercent' => AppSetting::get('commission_percent', '5'),
            'referralHistory' => $referralHistory,
        ]);
    }
}
