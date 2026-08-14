<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * History of admin-sent push notifications (client item 8: "After sending
 * notifications, maintain: User/Target, Title, Message, Sent By, Date/Time,
 * Status"). Separate from UserNotification, which is the per-recipient
 * inbox row(s) actually delivered - this is one summary row per admin send
 * action, whether it fanned out to all users or just one.
 */
class AdminBroadcastLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['target_description', 'title', 'body', 'sent_by', 'status', 'recipient_count'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
