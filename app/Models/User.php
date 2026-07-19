<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'phone',
        'mpin',
        'referral_code',
        'referred_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'mpin',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'mpin' => 'hashed',
        ];
    }

    /**
     * Phone/OTP signups (this app's primary auth flow) never collect a real
     * email - PhoneAuthController fills the required-but-unused `email`
     * column with a synthetic "{phone}@phone.gullakpe.local" placeholder
     * instead. Only Google-linked accounts have a genuine deliverable
     * address. Anything that emails the user (deposit confirmations, etc.)
     * needs this check first, or it'd "send" to an address that was never
     * meant to receive mail.
     */
    public function hasRealEmail(): bool
    {
        return $this->email && ! str_ends_with($this->email, '@phone.gullakpe.local');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by');
    }

    /**
     * Generated lazily instead of backfilled, so accounts created before
     * this feature existed just get a code the first time they need one
     * (e.g. visiting Rewards) rather than requiring a data migration.
     */
    public function referralCode(): string
    {
        if ($this->referral_code) {
            return $this->referral_code;
        }

        do {
            $code = 'GUL'.Str::upper(Str::random(6));
        } while (self::where('referral_code', $code)->exists());

        $this->referral_code = $code;
        $this->save();

        return $code;
    }
}
