<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Real, database-backed record of every money- or state-changing admin
 * action - who did it (admin_user_id + admin_label, since the shared master
 * password has no individual account), what the action was, what it acted
 * on, why (reason), and any extra detail (meta). Append-only: no
 * updated_at, entries are never edited after the fact.
 *
 * This is deliberately separate from the existing Log::channel('admin_security')
 * file log (kept as-is everywhere it's already called). It IS what the
 * "Activity Logs" nav item shows (AdminController::logs()/logs.blade.php) -
 * the pre-existing localStorage referral/commission simulation debug
 * console lives on that same page in its own section below, unrelated to
 * this trail.
 */
class AdminAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'admin_user_id', 'admin_label', 'action', 'target_type', 'target_id', 'reason', 'meta', 'ip',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public static function record(Request $request, string $action, ?Model $target = null, ?string $reason = null, array $meta = []): self
    {
        return self::create([
            'admin_user_id' => session('admin_user_id'),
            'admin_label' => session('admin_label', 'Master Admin'),
            'action' => $action,
            'target_type' => $target ? class_basename($target) : null,
            'target_id' => $target?->getKey(),
            'reason' => $reason,
            'meta' => $meta ?: null,
            'ip' => $request->ip(),
        ]);
    }

    public function actionLabel(): string
    {
        return ucfirst(str_replace('_', ' ', $this->action));
    }
}
