<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanCategory extends Model
{
    public const DEFAULT_ICON = 'bi-tag-fill';

    protected $fillable = ['name', 'icon'];

    // Every known category as {name => icon} - the admin-editable
    // replacement for the old hardcoded Plan::BADGE_ICONS constant.
    public static function iconMap(): array
    {
        return static::query()->pluck('icon', 'name')->all();
    }

    public static function iconFor(?string $name): string
    {
        if (! $name) {
            return self::DEFAULT_ICON;
        }

        return static::query()->where('name', $name)->value('icon') ?? self::DEFAULT_ICON;
    }
}
