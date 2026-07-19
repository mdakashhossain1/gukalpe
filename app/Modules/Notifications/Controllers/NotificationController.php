<?php

namespace App\Modules\Notifications\Controllers;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public const ICON_BY_TYPE = [
        'deposit_approved' => 'fa-circle-check',
        'deposit_rejected' => 'fa-circle-xmark',
        'withdrawal_approved' => 'fa-circle-check',
        'withdrawal_rejected' => 'fa-circle-xmark',
        'admin_broadcast' => 'fa-bullhorn',
    ];

    // Real page replacing the old JS-driven header dropdown (which polled
    // /notifications/poll and rendered the list client-side) - now the
    // route the header bell links straight to.
    public function index(): View
    {
        $notifications = Auth::check()
            ? UserNotification::where('user_id', Auth::id())->latest()->get()
            : collect();

        return view('Notifications::index', [
            'notifications' => $notifications->map(fn (UserNotification $n) => [
                'id' => $n->id,
                'icon' => self::ICON_BY_TYPE[$n->type] ?? 'fa-circle-info',
                'title' => $n->title,
                'body' => $n->body,
                'unread' => is_null($n->read_at),
                'createdAt' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }

    public function markRead(): RedirectResponse
    {
        if (Auth::check()) {
            UserNotification::where('user_id', Auth::id())->unread()->update(['read_at' => now()]);
        }

        return redirect()->route('notifications');
    }
}
