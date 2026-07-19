<?php

namespace App\Modules\Portfolio\Controllers;

use App\Http\Controllers\Controller;
use App\Models\UserPlan;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    // Days shown on the growth line - a fixed window rather than the old
    // JS's switchable 7D/30D/12M tabs, since there's no JS left to switch
    // tabs with. 30 real days beats 3 fake ranges.
    private const CHART_DAYS = 30;

    // plans.md's "Complete 5 Investments -> Unlock VIP Badge" - computed
    // live from real purchase count, never stored, so it can never drift
    // from what actually happened.
    private const VIP_PURCHASE_THRESHOLD = 5;

    public function index(): View
    {
        $user = Auth::user();

        $userPlans = $user
            ? UserPlan::with('plan')->where('user_id', $user->id)->orderByDesc('purchased_at')->get()
            : collect();

        $holdings = UserPlan::holdingsFor($user);

        $totalInvested = $holdings->sum('invested');
        $totalCurrentValue = $holdings->sum('currentValue');
        $todayProfit = $holdings->sum('dailyProfit');

        $transactions = $userPlans->flatMap(function (UserPlan $up) {
            $entries = [[
                'type' => 'purchase',
                'title' => $up->plan->title ?? 'Plan',
                'icon' => $up->plan->icon ?? 'bi-piggy-bank',
                'amount' => (float) $up->invested_amount,
                'date' => $up->purchased_at,
            ]];

            if ($up->withdrawn_at) {
                $entries[] = [
                    'type' => 'withdrawal',
                    'title' => $up->plan->title ?? 'Plan',
                    'icon' => $up->plan->icon ?? 'bi-piggy-bank',
                    'amount' => (float) $up->invested_amount,
                    'date' => $up->withdrawn_at,
                ];
            }

            return $entries;
        })->sortByDesc('date')->take(10)->values();

        $purchaseCount = $user ? UserPlan::completedPurchaseCount($user) : 0;

        return view('Portfolio::portfolio', [
            'holdings' => $holdings,
            'totalInvested' => $totalInvested,
            'totalCurrentValue' => $totalCurrentValue,
            'todayProfit' => $todayProfit,
            'activeCount' => $holdings->count(),
            'transactions' => $transactions,
            'hasActivity' => $userPlans->isNotEmpty(),
            'chartPoints' => UserPlan::chartPointsFor($holdings, self::CHART_DAYS),
            'purchaseCount' => $purchaseCount,
            'isVip' => $purchaseCount >= self::VIP_PURCHASE_THRESHOLD,
            'vipThreshold' => self::VIP_PURCHASE_THRESHOLD,
        ]);
    }
}
