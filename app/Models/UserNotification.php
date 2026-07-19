<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'body', 'read_at'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function notify(User $user, string $type, string $title, ?string $body = null): self
    {
        return self::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
        ]);
    }

    // Fans out one row per existing user rather than a single shared
    // broadcast row - keeps read_at correct per-recipient (one user
    // reading it must never mark it read for everyone else) and keeps the
    // poll/read endpoints identical for both broadcast and targeted
    // notifications, no special-casing needed on the read side.
    public static function broadcast(string $type, string $title, ?string $body = null): int
    {
        $now = now();
        $rows = User::query()->pluck('id')->map(fn (int $userId) => [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($rows === []) {
            return 0;
        }

        self::insert($rows);

        return count($rows);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }
}
