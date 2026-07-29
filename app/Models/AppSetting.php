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
        // Method-level amount routing for Add Money. The deposit amount decides
        // which METHOD is shown; the specific account stays a random pick among
        // all active accounts of that method (see DepositRequestController).
        // A range is [min, max]; blank max = no upper limit. A method with BOTH
        // blank is disabled (never shown). Default = UPI for every amount, bank
        // off - matching the old "UPI only" default.
        'upi_min_amount' => '0',
        'upi_max_amount' => '',
        'bank_min_amount' => '',
        'bank_max_amount' => '',
        // Withdrawal policy limits — enforced on every withdrawal request submission
        'withdrawal_min_amount' => '300',
        'withdrawal_daily_limit' => '5000',
        'withdrawal_max_per_day' => '3',
        // System kill-switches (plan.md Section 41). 'true'/'false' strings.
        // maintenance_mode gates the whole public app (admin panel stays open);
        // the allow_* switches gate their specific flow at submission time.
        'maintenance_mode' => 'false',
        'allow_registration' => 'true',
        'allow_investment' => 'true',
        'allow_withdrawals' => 'true',
    ];

    // Convenience: read a 'true'/'false' setting as a real bool.
    public static function enabled(string $key): bool
    {
        return self::get($key, self::DEFAULTS[$key] ?? 'false') === 'true';
    }

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
