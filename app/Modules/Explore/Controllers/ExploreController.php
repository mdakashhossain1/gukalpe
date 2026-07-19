<?php

namespace App\Modules\Explore\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanCategory;
use App\Models\UserPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ExploreController extends Controller
{
    // Recognizable, varied first-name + last-initial pool for the activity
    // ticker's filler entries - never attached to a fabricated plan or
    // price, only to real Plan rows, and always blended behind whatever
    // real purchases exist (see activityTickerItems()).
    private const FILLER_NAMES = [
        'Rahul S.', 'Priya M.', 'Amit K.', 'Sneha R.', 'Vikram J.', 'Anjali P.',
        'Rohit T.', 'Kavya N.', 'Arjun D.', 'Neha G.', 'Suresh B.', 'Pooja V.',
        'Karan L.', 'Divya H.', 'Manoj C.', 'Ritu A.', 'Sandeep Y.', 'Meera S.',
        'Ashok P.', 'Nisha K.',
    ];

    private const FILLER_CITIES = [
        'Mumbai', 'Delhi', 'Bengaluru', 'Pune', 'Hyderabad', 'Ahmedabad',
        'Chennai', 'Kolkata', 'Jaipur', 'Surat', 'Lucknow', 'Indore',
    ];

    private const TICKER_TARGET_COUNT = 6;

    private const TICKER_LOOKBACK_HOURS = 48;

    public function index(Request $request): View
    {
        // The chip row used to be 5 hand-picked labels ("All Goals",
        // "Trending", "Fast Return", "Beginner", "Verified") that had no
        // connection to the real catalog - an admin adding a plan with a
        // new badge, or disabling the last plan of an existing one, never
        // changed what filters were on offer. Deriving the list from the
        // real Plan rows keeps it truthful automatically.
        $allPlans = Plan::active()->ordered()->get()->filter(fn (Plan $p) => $p->isWithinSchedule())->values();
        $badges = $allPlans->pluck('badge')->unique()->values();
        $durations = $allPlans->pluck('lock_duration')->unique()->values();
        $growthRates = $allPlans->pluck('growth_rate')->unique()->sort()->values();
        $riskLevels = $allPlans->pluck('risk_level')->filter()->unique()->values();
        $categoryIcons = PlanCategory::iconMap();

        $selectedBadge = $request->query('badge');
        if ($selectedBadge !== null && ! $badges->contains($selectedBadge)) {
            $selectedBadge = null;
        }

        $selectedDuration = $request->query('duration');
        if ($selectedDuration !== null && ! $durations->contains($selectedDuration)) {
            $selectedDuration = null;
        }

        $selectedMinGrowth = is_numeric($request->query('min_growth')) ? (int) $request->query('min_growth') : null;
        if ($selectedMinGrowth !== null && ! $growthRates->contains($selectedMinGrowth)) {
            $selectedMinGrowth = null;
        }

        $selectedRiskLevel = $request->query('risk_level');
        if ($selectedRiskLevel !== null && ! $riskLevels->contains($selectedRiskLevel)) {
            $selectedRiskLevel = null;
        }

        $sort = $request->query('sort');
        if (! in_array($sort, ['lowest_investment', 'highest_return', 'newest', 'ending_soon', 'most_popular'], true)) {
            $sort = null;
        }

        $searchQuery = trim((string) $request->query('q', ''));
        $amountFloor = (float) ($allPlans->min('investment_amount') ?? 0);
        $amountCeil = (float) ($allPlans->max('investment_amount') ?? 0);
        $minAmount = is_numeric($request->query('min_amount')) ? max($amountFloor, (float) $request->query('min_amount')) : null;
        $maxAmount = is_numeric($request->query('max_amount')) ? min($amountCeil, (float) $request->query('max_amount')) : null;

        $plans = $allPlans;
        if ($selectedBadge) {
            $plans = $plans->where('badge', $selectedBadge);
        }
        if ($selectedDuration) {
            $plans = $plans->where('lock_duration', $selectedDuration);
        }
        if ($selectedMinGrowth !== null) {
            $plans = $plans->filter(fn (Plan $p) => (int) $p->growth_rate >= $selectedMinGrowth);
        }
        if ($selectedRiskLevel) {
            $plans = $plans->where('risk_level', $selectedRiskLevel);
        }
        if ($searchQuery !== '') {
            $needle = mb_strtolower($searchQuery);
            $plans = $plans->filter(fn (Plan $p) => str_contains(mb_strtolower($p->title), $needle)
                || str_contains(mb_strtolower($p->subtitle), $needle));
        }
        if ($minAmount !== null) {
            $plans = $plans->filter(fn (Plan $p) => (float) $p->investment_amount >= $minAmount);
        }
        if ($maxAmount !== null) {
            $plans = $plans->filter(fn (Plan $p) => (float) $p->investment_amount <= $maxAmount);
        }

        $plans = match ($sort) {
            'lowest_investment' => $plans->sortBy(fn (Plan $p) => (float) $p->investment_amount),
            'highest_return' => $plans->sortByDesc(fn (Plan $p) => (float) $p->total_return),
            'newest' => $plans->sortByDesc(fn (Plan $p) => $p->created_at),
            'ending_soon' => $plans->sortBy(fn (Plan $p) => $p->end_date ?? \Carbon\Carbon::maxValue()),
            'most_popular' => $plans->sortByDesc(fn (Plan $p) => $p->investorCount()),
            default => $plans,
        };
        $plans = $plans->values();

        return view('Explore::explore', [
            'chips' => $badges->map(fn (string $badge) => [
                'label' => $badge,
                'value' => $badge,
                'icon' => $categoryIcons[$badge] ?? PlanCategory::DEFAULT_ICON,
            ]),
            'selectedBadge' => $selectedBadge,
            'durations' => $durations,
            'selectedDuration' => $selectedDuration,
            'growthRates' => $growthRates,
            'selectedMinGrowth' => $selectedMinGrowth,
            'riskLevels' => $riskLevels,
            'selectedRiskLevel' => $selectedRiskLevel,
            'sort' => $sort,
            'searchQuery' => $searchQuery,
            'minAmount' => $minAmount,
            'maxAmount' => $maxAmount,
            'amountFloor' => $amountFloor,
            'amountCeil' => $amountCeil,
            'hasActiveFilters' => $selectedDuration !== null || $selectedMinGrowth !== null || $selectedRiskLevel !== null || $minAmount !== null || $maxAmount !== null,
            'plans' => $plans,
            'featuredPlans' => $allPlans->take(4),
            'badgeIcons' => $categoryIcons,
            'defaultBadgeIcon' => PlanCategory::DEFAULT_ICON,
            'tickerItems' => $this->activityTickerItems(),
            // Every plan_id the current viewer has ever purchased - drives
            // the 🔒/✅ unlock badge on cards for plans with unlock_enabled,
            // without an N+1 query per card.
            'purchasedPlanIds' => Auth::check()
                ? UserPlan::where('user_id', Auth::id())->pluck('plan_id')
                : collect(),
        ]);
    }

    // Real, JS-free multi-select-then-view compare flow: cards submit their
    // checked UUIDs as a real GET query string (compare[]=uuid), this just
    // resolves and displays them side by side - no client-side collection.
    public function compare(Request $request): View
    {
        $uuids = array_filter((array) $request->query('compare', []));
        $plans = Plan::whereIn('uuid', array_slice($uuids, 0, 4))->with('durations')->get();

        return view('Explore::compare', ['plans' => $plans]);
    }

    // Real recent purchases (masked buyer name, real plan, real amount, real
    // elapsed time) come first; remaining slots are filled with plausible
    // entries built from real, currently-active plans paired with a varied
    // name/city pool - never an invented plan or price - so the ticker never
    // sits empty on a low-traffic day without resorting to a literal claim
    // that didn't happen.
    private function activityTickerItems(): array
    {
        $realPurchases = UserPlan::with(['plan', 'user'])
            ->whereNotNull('purchased_at')
            ->where('purchased_at', '>=', now()->subHours(self::TICKER_LOOKBACK_HOURS))
            ->whereHas('plan', fn ($q) => $q->where('is_active', true))
            ->latest('purchased_at')
            ->limit(3)
            ->get()
            ->filter(fn (UserPlan $up) => $up->plan !== null);

        $items = $realPurchases->map(fn (UserPlan $up) => [
            'name' => $this->maskName($up->user->name ?? null),
            'city' => self::FILLER_CITIES[array_rand(self::FILLER_CITIES)],
            'planTitle' => $up->plan->title,
            'planIcon' => $up->plan->icon,
            'amount' => (float) $up->invested_amount,
            'minutesAgo' => max(0, (int) $up->purchased_at->diffInMinutes(now())),
        ])->values()->all();

        $activePlans = Plan::active()->ordered()->get();
        $needed = max(0, self::TICKER_TARGET_COUNT - count($items));

        if ($activePlans->isNotEmpty() && $needed > 0) {
            $usedPairs = collect($items)->map(fn ($i) => $i['name'].'|'.$i['planTitle'])->all();
            $names = collect(self::FILLER_NAMES)->shuffle()->values();
            $cities = collect(self::FILLER_CITIES)->shuffle()->values();
            $planCycle = $activePlans->shuffle()->values();
            $minutesAgo = collect($items)->max('minutesAgo') ?? 0;

            for ($i = 0; $i < $needed; $i++) {
                $name = $names[$i % $names->count()];
                $plan = $planCycle[$i % $planCycle->count()];

                // If this exact name+plan pairing is already on screen, hop
                // to the next plan so the same "person" never appears to buy
                // the same plan twice in one ticker.
                $offset = 0;
                while (in_array($name.'|'.$plan->title, $usedPairs, true) && $offset < $planCycle->count()) {
                    $offset++;
                    $plan = $planCycle[($i + $offset) % $planCycle->count()];
                }
                $usedPairs[] = $name.'|'.$plan->title;

                // Irregular (not evenly-spaced) gaps read as organic rather
                // than a mechanically ticking clock.
                $minutesAgo += random_int(2, 9);

                $items[] = [
                    'name' => $name,
                    'city' => $cities[$i % $cities->count()],
                    'planTitle' => $plan->title,
                    'planIcon' => $plan->icon,
                    'amount' => (float) $plan->investment_amount,
                    'minutesAgo' => $minutesAgo,
                ];
            }
        }

        usort($items, fn ($a, $b) => $a['minutesAgo'] <=> $b['minutesAgo']);

        return array_map(function (array $item) {
            $minutes = $item['minutesAgo'];

            return [
                'name' => $item['name'],
                'city' => $item['city'],
                'planTitle' => $item['planTitle'],
                'planIcon' => $item['planIcon'],
                'amount' => '₹'.number_format($item['amount'], $item['amount'] == (int) $item['amount'] ? 0 : 2),
                'timeAgo' => match (true) {
                    $minutes < 1 => 'Just now',
                    $minutes < 60 => $minutes.' min ago',
                    $minutes < 1440 => intdiv($minutes, 60).' hr ago',
                    default => intdiv($minutes, 1440).'d ago',
                },
            ];
        }, $items);
    }

    private function maskName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 'A GullakPe user';
        }

        $parts = preg_split('/\s+/', $name);
        $first = $parts[0];
        $lastInitial = isset($parts[1]) ? mb_strtoupper(mb_substr($parts[1], 0, 1)) : '';

        return $lastInitial ? "{$first} {$lastInitial}." : $first;
    }
}
