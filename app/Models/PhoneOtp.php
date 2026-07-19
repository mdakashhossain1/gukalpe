<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class PhoneOtp extends Model
{
    private const MAX_ATTEMPTS = 5;
    private const TTL_MINUTES = 5;

    protected $fillable = ['phone', 'otp_hash', 'attempts', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Creates (or replaces) the pending OTP for a phone and returns the
     * plaintext code - the one place a real SMS gateway integration would
     * hook in later. There is no SMS provider configured in this app, so
     * callers are expected to surface this value back to the user directly
     * ("demo mode") rather than actually sending a text message.
     */
    public static function generateFor(string $phone): string
    {
        $code = (string) random_int(100000, 999999);

        self::updateOrCreate(
            ['phone' => $phone],
            [
                'otp_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            ]
        );

        return $code;
    }

    /**
     * @return 'ok'|'expired'|'locked'|'invalid'|'not_found'
     */
    public static function attemptVerify(string $phone, string $submittedCode): string
    {
        $otp = self::where('phone', $phone)->first();

        if (! $otp) {
            return 'not_found';
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            return 'locked';
        }

        if ($otp->expires_at->isPast()) {
            $otp->delete();

            return 'expired';
        }

        if (! Hash::check($submittedCode, $otp->otp_hash)) {
            $otp->increment('attempts');

            return 'invalid';
        }

        $otp->delete();

        return 'ok';
    }
}
