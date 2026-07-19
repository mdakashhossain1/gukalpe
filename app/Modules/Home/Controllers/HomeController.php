<?php

namespace App\Modules\Home\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\UserPlan;
use App\Models\WalletBalance;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

class HomeController extends Controller
{
    // Sparkline on the hero card shows the trailing week, not the full 30
    // days Portfolio's own chart uses - a small header card reads better as
    // a quick pulse than a dense month-long line.
    private const SPARKLINE_DAYS = 7;

    public function index(Request $request): View
    {
        $user = Auth::user();
        $phone = $user?->phone;

        $this->captureReferralCode($request, $user);

        $holdings = UserPlan::holdingsFor($user);
        $totalInvested = $holdings->sum('invested');
        $totalCurrentValue = $holdings->sum('currentValue');
        $totalReturns = $totalCurrentValue - $totalInvested;
        $returnsPct = $totalInvested > 0 ? ($totalReturns / $totalInvested) * 100 : 0.0;
        $todayProfit = $holdings->sum('dailyProfit');
        $todayProfitPct = $totalInvested > 0 ? ($todayProfit / $totalInvested) * 100 : 0.0;

        return view('Home::home', [
            'user' => $user,
            'balance' => $phone ? WalletBalance::balanceFor($phone) : 0.0,
            'totalInvested' => $totalInvested,
            'totalCurrentValue' => $totalCurrentValue,
            'totalReturns' => $totalReturns,
            'returnsPct' => $returnsPct,
            'todayProfit' => $todayProfit,
            'todayProfitPct' => $todayProfitPct,
            'chartPoints' => UserPlan::chartPointsFor($holdings, self::SPARKLINE_DAYS),
            'featuredPlans' => Plan::active()->ordered()->take(4)->get(),
            'totalInvestors' => UserPlan::query()->distinct('user_id')->count('user_id'),
            'unreadNotificationCount' => $user
                ? UserNotification::where('user_id', $user->id)->unread()->count()
                : 0,
        ]);
    }

    /**
     * Stashes a valid ?ref= referral token in the session so it survives the
     * multi-step phone/OTP/MPIN (or Google) signup flow - mirrors the
     * session-flash approach Deposits/routes.php already uses for
     * deposits.start, rather than carrying the code through the URL of
     * every subsequent request.
     *
     * The token itself is Crypt::encryptString($referralCode), not the
     * plain code - registration is only ever meant to happen through a
     * shared link, so the raw code must never be visible, guessable, or
     * separately copyable from that link. Only this app's own APP_KEY can
     * decrypt a token back into a real code.
     */
    private function captureReferralCode(Request $request, ?User $user): void
    {
        $token = $request->query('ref');
        if (! $token || AppSetting::get('referral_enabled', 'true') !== 'true') {
            return;
        }

        try {
            $code = Crypt::decryptString($token);
        } catch (DecryptException $e) {
            return;
        }

        $referrer = User::where('referral_code', $code)->first();
        if ($referrer && (! $user || $user->id !== $referrer->id)) {
            $request->session()->put('pending_referral_code', $referrer->referral_code);
        }
    }
}
