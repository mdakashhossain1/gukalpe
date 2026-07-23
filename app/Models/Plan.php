<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Plan extends Model
{
    protected $fillable = [
        'title', 'subtitle', 'image', 'icon', 'badge', 'growth_rate',
        'lock_duration', 'investment_amount', 'min_investment_amount', 'max_investment_amount', 'allow_topups',
        'daily_profit', 'total_return',
        'min_goal', 'is_active', 'sort_order',
        'plan_type', 'max_purchase_per_user', 'cooldown_days', 'requires_plan_id',
        'unlock_enabled', 'unlock_message', 'marketing_badge', 'marketing_badge_icon',
        'marketing_badge_color', 'risk_level',
        'max_slots', 'start_date', 'end_date', 'auto_mature', 'early_close_allowed',
        'terms', 'faqs', 'highlights',
    ];

    // Curated colour schemes for the marketing badge ribbon (Explore/Plan
    // Details) - a closed preset list rather than a free-text hex field, so
    // every admin-picked combination stays legible (soft tint bg + matching
    // border/text) instead of risking low-contrast or clashing colours.
    public const MARKETING_BADGE_COLORS = [
        'amber' => ['bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'text' => 'text-amber-600'],
        'teal' => ['bg' => 'bg-[#0A5C66]/8', 'border' => 'border-[#0A5C66]/25', 'text' => 'text-[#0A5C66]'],
        'green' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'text' => 'text-emerald-600'],
        'rose' => ['bg' => 'bg-rose-50', 'border' => 'border-rose-200', 'text' => 'text-rose-600'],
        'violet' => ['bg' => 'bg-violet-50', 'border' => 'border-violet-200', 'text' => 'text-violet-600'],
        'slate' => ['bg' => 'bg-slate-100', 'border' => 'border-slate-300', 'text' => 'text-slate-600'],
    ];

    protected $casts = [
        'investment_amount' => 'decimal:2',
        'min_investment_amount' => 'decimal:2',
        'max_investment_amount' => 'decimal:2',
        'allow_topups' => 'boolean',
        'daily_profit' => 'decimal:2',
        'total_return' => 'decimal:2',
        'min_goal' => 'decimal:2',
        'is_active' => 'boolean',
        'unlock_enabled' => 'boolean',
        'auto_mature' => 'boolean',
        'early_close_allowed' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'faqs' => 'array',
        'highlights' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $plan) {
            $plan->uuid ??= (string) Str::uuid();
        });
    }

    // /plan-details/{plan} and /plans/{plan}/purchase would otherwise
    // resolve against the auto-increment id, exposing a sequential,
    // guessable value in the URL and letting anyone enumerate the whole
    // catalog by walking /plan-details/1, /2, /3... Same fix already
    // applied to DepositRequest/WithdrawRequest: route on a random uuid
    // instead, id stays purely internal (foreign keys, ordering).
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function userPlans(): HasMany
    {
        return $this->hasMany(UserPlan::class);
    }

    public function durations(): HasMany
    {
        return $this->hasMany(PlanDuration::class)->orderBy('sort_order');
    }

    public function requiresPlan(): BelongsTo
    {
        return $this->belongsTo(self::class, 'requires_plan_id');
    }

    // Preselected duration on Plan Details' calculator - the admin-flagged
    // default, or simply the first by sort_order if none was flagged.
    public function defaultDuration(): ?PlanDuration
    {
        return $this->durations->firstWhere('is_default', true) ?? $this->durations->first();
    }

    // Falls back to the 'amber' preset for plans saved before this column
    // existed (or left blank) so the ribbon still renders with a sane style.
    public function marketingBadgeColorClasses(): array
    {
        return self::MARKETING_BADGE_COLORS[$this->marketing_badge_color] ?? self::MARKETING_BADGE_COLORS['amber'];
    }

    // null = unlimited slots (no cap configured). Never negative - a plan
    // that's oversold by an admin lowering max_slots after the fact just
    // shows 0 remaining, not a confusing negative count.
    public function availableSlots(): ?int
    {
        if ($this->max_slots === null) {
            return null;
        }

        return max(0, $this->max_slots - $this->investorCount());
    }

    // A plan only gets the drag-slider "invest any amount" UI on Plan
    // Details when an admin has explicitly set a real range - every plan
    // seeded so far (Trust Builder ₹199, Growth ₹499, the 5 legacy demo
    // plans) leaves these null and keeps its single fixed investment_amount
    // exactly as before. min==max would be a degenerate/pointless "range",
    // so it's treated as fixed too.
    public function isFlexibleAmount(): bool
    {
        return $this->min_investment_amount !== null
            && $this->max_investment_amount !== null
            && (float) $this->max_investment_amount > (float) $this->min_investment_amount;
    }

    // "SIP-style" pot mode - a user's first contribution opens one ongoing
    // holding for this plan, and every later contribution tops that SAME
    // holding up (shared maturity date, cumulative total capped at
    // max_investment_amount) instead of starting a new independent
    // purchase. Only meaningful on top of a real flexible-amount range -
    // allow_topups alone (no real min/max range) does nothing.
    public function isTopupPot(): bool
    {
        return $this->allow_topups && $this->isFlexibleAmount();
    }

    // Both bounds are optional - a plan with neither is always in schedule,
    // matching every plan's behavior before this column existed.
    public function isWithinSchedule(): bool
    {
        $now = now();

        if ($this->start_date && $now->lessThan($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->greaterThan($this->end_date)) {
            return false;
        }

        return true;
    }

    // Real count for "Trusted by N investors" on the Explore cards - the
    // old hardcoded markup showed a fabricated number per card (12.4k+,
    // etc.) with no data behind it at all.
    public function investorCount(): int
    {
        return $this->userPlans()->distinct('user_id')->count('user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    // `image` holds either a full external URL (the original 5 seeded
    // plans, still pointing at Unsplash) or a path relative to public/
    // (admin-uploaded images, saved straight into public/assets/plans by
    // PlanManagementController - no storage/ symlink involved). This
    // resolves either into a URL the frontend can use directly.
    public function imageUrl(): string
    {
        return str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')
            ? $this->image
            : asset($this->image);
    }

    // Reproduces the exact shape of the old hardcoded `plansData[title]`
    // entries in resources/js/modules/animations.js, including its
    // redundant derived fields (expectedGrowth/expectedReturn duplicate
    // growth/return in a different format; unlockDate/timelineEnd both
    // just restate lock_duration) - so window.plansData built from this
    // stays a drop-in replacement and openPlanDetails() needed zero changes.
    public function toLegacyArray(): array
    {
        $investment = (float) $this->investment_amount;
        $dailyProfit = (float) $this->daily_profit;
        $totalReturn = (float) $this->total_return;
        $minGoal = (float) $this->min_goal;

        $planInvestment = $this->isFlexibleAmount()
            ? '₹'.number_format((float) $this->min_investment_amount, 0).' - ₹'.number_format((float) $this->max_investment_amount, 0)
            : '₹'.number_format($investment, $investment == (int) $investment ? 0 : 2).' One-Time';

        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'image' => $this->imageUrl(),
            'icon' => $this->icon,
            'badge' => $this->badge,
            'expectedGrowth' => "Up to {$this->growth_rate}% yearly",
            'growthRate' => $this->growth_rate,
            'lockDuration' => $this->lock_duration,
            'planInvestment' => $planInvestment,
            'investmentAmt' => $investment,
            'dailyProfit' => '+₹'.number_format($dailyProfit, 2).'/day',
            'dailyProfitVal' => $dailyProfit,
            'totalReturn' => '₹'.number_format($totalReturn, 0),
            'minGoal' => '₹'.number_format($minGoal, 0),
            'minGoalVal' => $minGoal,
            'expectedReturn' => '₹'.number_format($totalReturn, 0),
            'unlockDate' => $this->lock_duration,
            'timelineEnd' => $this->lock_duration === 'Flexible' ? 'Flexible Withdrawal' : 'Month '.preg_replace('/\D/', '', $this->lock_duration).' Unlock',
        ];
    }
}
