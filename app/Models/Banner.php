<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Banner extends Model
{
    protected $fillable = [
        'placement', 'title', 'image', 'redirect_link',
        'is_active', 'priority', 'start_date', 'end_date',
    ];

    public const PLACEMENTS = [
        'home' => 'Home Banner',
        'offer' => 'Offer Banner',
        'explore' => 'Explore Banner',
        'popup' => 'Popup Banner',
    ];

    public function placementLabel(): string
    {
        return self::PLACEMENTS[$this->placement] ?? ucfirst($this->placement);
    }

    // Same optional-window semantics as Plan::isWithinSchedule(): a banner with
    // neither bound is always in schedule.
    #[Scope]
    protected function withinSchedule(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where(fn (Builder $q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $now));
    }

    // Active + in-schedule banners for a placement, highest priority first.
    public static function activeFor(string $placement): Collection
    {
        return self::where('placement', $placement)
            ->where('is_active', true)
            ->withinSchedule()
            ->orderByDesc('priority')
            ->orderByDesc('id')
            ->get();
    }

    public function imageUrl(): string
    {
        // Uploaded straight into public/assets/... (see BannerController),
        // matching how plan images are stored; absolute URLs pass through.
        return str_starts_with($this->image, 'http') ? $this->image : asset($this->image);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }
}
