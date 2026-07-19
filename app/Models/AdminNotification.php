<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    protected $fillable = ['type', 'title', 'body', 'read_at'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public static function notify(string $type, string $title, ?string $body = null): self
    {
        return self::create(['type' => $type, 'title' => $title, 'body' => $body]);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }
}
