<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Simple key/value store for global, admin-controlled app configuration
 * (referral toggle, commission rate, etc.) - replaces the localStorage
 * flags the Ops Console used to write, which only ever applied to the
 * admin's own browser and could never actually reach a real user's
 * device. See MEMORY.md's "real settings entry" note for why this exists.
 */
class AppSetting extends Model
{
    /**
     * Canonical keys + defaults, matching the values the removed JS admin
     * panel used to default to (see MEMORY.md entry "Removed the Admin
     * Settings debug panel entirely").
     */
    public const DEFAULTS = [
        'referral_enabled' => 'true',
        'commission_percent' => '5',
        'cashback_amount' => '100',
        'settlement_time' => '00:00',
        'max_deposit_limit' => '50000',
        // A single flat choice - 'upi' | 'bank' - only one manual method
        // can ever be active at a time, so one key is enough.
        'payment_mode' => 'upi',
    ];

    protected $fillable = ['key', 'value'];

    /**
     * @return array<string, string>
     */
    public static function current(): array
    {
        return self::many(self::DEFAULTS);
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, string $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * @param  array<string, string>  $keysWithDefaults
     * @return array<string, string>
     */
    public static function many(array $keysWithDefaults): array
    {
        $rows = self::whereIn('key', array_keys($keysWithDefaults))->pluck('value', 'key');

        $result = [];
        foreach ($keysWithDefaults as $key => $default) {
            $result[$key] = $rows[$key] ?? $default;
        }

        return $result;
    }
}
