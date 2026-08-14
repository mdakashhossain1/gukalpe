<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    protected $fillable = ['type', 'title', 'body', 'read_at'];

    public static function notify(string $type, string $title, ?string $body = null): self
    {
        return self::create(['type' => $type, 'title' => $title, 'body' => $body]);
    }

    #[Scope]
    protected function unread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }
}
