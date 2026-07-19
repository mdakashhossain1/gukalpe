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

        $commissions = ReferralCommission::where('referrer_id', $user->id)->get()
            ->keyBy('referred_user_id');

        $referralHistory = $referrals->map(function (User $referred) use ($investedUserIds, $commissions) {
            return [
                'name' => $referred->name,
                'maskedPhone' => $referred->phone ? '+91 ******'.substr($referred->phone, -4) : null,
                'joinedAt' => $referred->created_at,
                'hasInvested' => $investedUserIds->contains($referred->id),
                'commissionEarned' => $commissions->get($referred->id)?->amount,
            ];
        });

        $totalInvites = $referrals->count();
        $totalInvested = $investedUserIds->count();

        return view('Rewards::rewards', [
            'user' => $user,
            'referralLink' => $referralLink,
            'qrCodeUrl' => $qrCodeUrl,
            'totalInvites' => $totalInvites,
            'totalRegistered' => $totalInvites - $totalInvested,
            'totalDeposited' => $depositedPhones->count(),
            'totalInvested' => $totalInvested,
            'totalCommission' => $commissions->sum('amount'),
            'walletBalance' => $user->phone ? WalletBalance::balanceFor($user->phone) : 0.0,
            'commissionPercent' => AppSetting::get('commission_percent', '5'),
            'referralHistory' => $referralHistory,
        ]);
    }
}
