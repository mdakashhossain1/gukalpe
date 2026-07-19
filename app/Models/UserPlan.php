<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;

class UserPlan extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_WITHDRAWN = 'withdrawn';

    protected $fillable = [
        'user_id', 'plan_id', 'plan_duration_id', 'invested_amount', 'daily_profit_val',
        'total_return', 'duration_label', 'status', 'purchased_at', 'matures_at', 'withdrawn_at',
        'last_daily_return_email_sent_at',
    ];

    protected $casts = [
        'invested_amount' => 'decimal:2',
        'daily_profit_val' => 'decimal:2',
        'total_return' => 'decimal:2',
        'purchased_at' => 'datetime',
        'matures_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'last_daily_return_email_sent_at' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function planDuration(): BelongsTo
    {
        return $this->belongsTo(PlanDuration::class);
    }

    public function topups(): HasMany
    {
        return $this->hasMany(PlanTopup::class)->latest();
    }

    // The one ongoing pot (if any) a user has open for a top-up-enabled
    // plan - null means their next contribution opens a brand new pot
    // rather than adding to an existing one. Deliberately not scoped to
    // "not yet matured" - once matures_at passes, PlanPurchaseController
    // should treat that as no active pot (the maturity scheduler will
    // withdraw it shortly; a late-arriving top-up shouldn't silently
    // re-extend an already-finished pot's shared maturity date).
    public static function activePotFor(User $user, Plan $plan): ?self
    {
        return static::where('user_id', $user->id)
            ->where('plan_id', $plan->id)
            ->active()
            ->where(function (Builder $q) {
                $q->whereNull('matures_at')->orWhere('matures_at', '>', now());
            })
            ->latest('purchased_at')
            ->first();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    // Holdings the maturity scheduler (plans:mature-holdings) should act on -
    // matures_at is only ever set on purchases made against a plan that has
    // real durations (Phase 0), so plans without durations simply never match.
    public function scopeMatured(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('matures_at')
            ->where('matures_at', '<=', now());
    }

    // Used by the Rewards/VIP badge (Phase 6) and by the purchase-limit /
    // cooldown guards in PlanPurchaseController - every purchase ever made
    // by this user, regardless of plan or status.
    public static function completedPurchaseCount(User $user): int
    {
        return static::where('user_id', $user->id)->count();
    }

    // Holdings the daily-returns emailer (plans:send-daily-returns-email)
    // should act on: at least one full day old (day-0 purchases haven't
    // accrued anything yet, matching currentHolding()'s daysElapsed math),
    // and not already emailed today - a date-only comparison so it's a
    // simple "not equal to today" check no matter what time the scheduled
    // command actually runs at.
    public function scopeDueForDailyReturnEmail(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('purchased_at', '<=', now()->subDay())
            ->where(function (Builder $q) {
                $q->whereNull('last_daily_return_email_sent_at')
                    ->orWhere('last_daily_return_email_sent_at', '<', now()->toDateString());
            });
    }

    // Shared by PortfolioController and HomeController so both pages show
    // the exact same portfolio value/gains - was previously computed twice
    // with the risk of the two copies drifting apart.
    public static function holdingsFor(?User $user): SupportCollection
    {
        if (! $user) {
            return collect();
        }

        return static::with(['plan', 'planDuration'])
            ->where('user_id', $user->id)
            ->active()
            ->get()
            ->filter(fn (self $up) => $up->plan !== null)
            ->map(fn (self $up) => $up->currentHolding())
            ->values();
    }

    // Current value of a holding is what was invested plus every full day of
    // daily_profit_val earned since purchase, capped at the return that was
    // actually promised at purchase time. Three-level fallback, most
    // specific first:
    //   1. $this->total_return - a per-holding snapshot, only ever set for
    //      flexible-amount purchases (PlanPurchaseController), because the
    //      shared plan_durations row's own total_return is calibrated for a
    //      *different* reference amount and can't be reused per-user.
    //   2. planDuration->total_return - fixed-amount purchases against a
    //      plan with real duration options (Growth Plan etc.).
    //   3. plan->total_return - legacy purchases with no duration row.
    public function currentHolding(): array
    {
        $plan = $this->plan;
        $invested = (float) $this->invested_amount;
        $dailyProfit = (float) $this->daily_profit_val;
        $totalReturn = (float) ($this->total_return ?? $this->planDuration?->total_return ?? $plan->total_return);
        $daysElapsed = max(0, (int) $this->purchased_at->diffInDays(now()));
        $maxProfit = max(0.0, $totalReturn - $invested);
        $accrued = min($dailyProfit * $daysElapsed, $maxProfit);

        return [
            'id' => $this->id,
            'plan' => $plan,
            'title' => $plan->title,
            'subtitle' => $plan->subtitle,
            'image' => $plan->imageUrl(),
            'icon' => $plan->icon,
            'invested' => $invested,
            'dailyProfit' => $dailyProfit,
            'totalReturn' => $totalReturn,
            'accruedProfit' => $accrued,
            'currentValue' => $invested + $accrued,
            'purchasedAt' => $this->purchased_at,
            'lockDuration' => $plan->lock_duration,
        ];
    }

    // Sums every holding's value-at-day-N across the trailing $days window,
    // using the same capped-accrual math as currentHolding() but frozen at
    // each historical day rather than today - a real reconstruction of
    // portfolio value over time from purchase records, not a random walk.
    public static function chartPointsFor(SupportCollection $holdings, int $days): array
    {
        $today = Carbon::today();
        $points = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = $today->copy()->subDays($i);
            $value = 0.0;

            foreach ($holdings as $holding) {
                $purchasedAt = $holding['purchasedAt']->copy()->startOfDay();
                if ($purchasedAt->greaterThan($day)) {
                    continue;
                }

                $daysElapsed = max(0, $purchasedAt->diffInDays($day));
                $maxProfit = max(0.0, $holding['totalReturn'] - $holding['invested']);
                $accrued = min($holding['dailyProfit'] * $daysElapsed, $maxProfit);
                $value += $holding['invested'] + $accrued;
            }

            $points[] = ['date' => $day, 'value' => $value];
        }

        return $points;
    }
}
