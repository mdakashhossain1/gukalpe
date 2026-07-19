<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\AppSetting;
use App\Models\DepositRequest;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WalletBalance;
use App\Models\WithdrawRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Progressive lockout: a flat "5 attempts per 60 seconds" only costs an
     * attacker a 1-minute wait, forever - 5/min is still ~7,200 guesses/day
     * against a single password. Lockout duration instead grows with the
     * *persistent* failure count (kept for 24h, not just a rolling window),
     * so sustained attempts get materially more expensive, not just delayed.
     *
     * @return array<int, array{failures: int, lockout: int}> highest threshold first
     */
    private const LOCKOUT_TIERS = [
        ['failures' => 20, 'lockout' => 86400], // 24 hours
        ['failures' => 15, 'lockout' => 3600],  // 1 hour
        ['failures' => 10, 'lockout' => 900],   // 15 minutes
        ['failures' => 5, 'lockout' => 60],     // 1 minute
    ];

    private const FAILURE_WINDOW_SECONDS = 86400; // failure count itself resets after 24h of no attempts

    public function login(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('admin_authenticated')) {
            return redirect()->route('admin.dashboard');
        }

        return view('Admin::login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $request->validate(['password' => 'required|string']);

        $ip = $request->ip();
        $failureKey = "admin-login-failures:{$ip}";
        $lockedUntilKey = "admin-login-locked-until:{$ip}";

        $lockedUntil = Cache::get($lockedUntilKey);
        if ($lockedUntil && $lockedUntil > now()->timestamp) {
            $seconds = $lockedUntil - now()->timestamp;
            Log::channel('admin_security')->warning('Admin login attempt while locked out', [
                'ip' => $ip,
                'seconds_remaining' => $seconds,
            ]);

            return back()->withErrors([
                'password' => 'Too many attempts. Try again in '.$this->humanizeSeconds($seconds).'.',
            ]);
        }

        $configured = (string) config('admin.password');
        $correct = $configured !== '' && hash_equals($configured, (string) $request->input('password'));

        if (! $correct) {
            $failures = (int) Cache::get($failureKey, 0) + 1;
            Cache::put($failureKey, $failures, self::FAILURE_WINDOW_SECONDS);

            $lockoutSeconds = 0;
            foreach (self::LOCKOUT_TIERS as $tier) {
                if ($failures >= $tier['failures']) {
                    $lockoutSeconds = $tier['lockout'];
                    break;
                }
            }

            Log::channel('admin_security')->warning('Admin login failed', [
                'ip' => $ip,
                'failures_in_24h' => $failures,
                'lockout_seconds' => $lockoutSeconds,
                'user_agent' => $request->userAgent(),
            ]);

            if ($lockoutSeconds > 0) {
                Cache::put($lockedUntilKey, now()->timestamp + $lockoutSeconds, $lockoutSeconds);

                return back()->withErrors([
                    'password' => "Too many attempts. Try again in {$this->humanizeSeconds($lockoutSeconds)}.",
                ]);
            }

            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        Cache::forget($failureKey);
        Cache::forget($lockedUntilKey);
        Log::channel('admin_security')->info('Admin login succeeded', ['ip' => $ip]);

        $request->session()->regenerate();
        $request->session()->put('admin_authenticated', true);

        return redirect()->route('admin.dashboard');
    }

    /**
     * Real analytics landing page - stat tiles + two trend charts, all built
     * from actual DepositRequest/WithdrawRequest/User/WalletBalance rows.
     * Wallet adjustment, Simulations, and Activity logs deliberately do NOT
     * get charts here or on their own pages - they're documented
     * localStorage-only demo tooling with no real backend (see admin.js),
     * so a chart behind them would just be decorative fiction.
     */
    public function dashboard(): View
    {
        $days = 14;
        $since = now()->subDays($days - 1)->startOfDay();

        $depositsByDay = DB::table('deposit_requests')
            ->selectRaw("strftime('%Y-%m-%d', submitted_at) as day, SUM(amount) as total")
            ->where('submitted_at', '>=', $since)
            ->groupBy('day')
            ->pluck('total', 'day');

        $withdrawalsByDay = DB::table('withdraw_requests')
            ->selectRaw("strftime('%Y-%m-%d', submitted_at) as day, SUM(amount) as total")
            ->where('submitted_at', '>=', $since)
            ->groupBy('day')
            ->pluck('total', 'day');

        $signupsByDay = DB::table('users')
            ->selectRaw("strftime('%Y-%m-%d', created_at) as day, COUNT(*) as total")
            ->where('created_at', '>=', $since)
            ->groupBy('day')
            ->pluck('total', 'day');

        $series = collect(range(0, $days - 1))->map(function (int $i) use ($since, $depositsByDay, $withdrawalsByDay, $signupsByDay) {
            $date = $since->copy()->addDays($i);
            $key = $date->format('Y-m-d');

            return [
                'date' => $date,
                'deposits' => (float) ($depositsByDay[$key] ?? 0),
                'withdrawals' => (float) ($withdrawalsByDay[$key] ?? 0),
                'signups' => (int) ($signupsByDay[$key] ?? 0),
            ];
        })->values();

        return view('Admin::dashboard', [
            'pendingDepositCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
            'pendingWithdrawalCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
            'totalUsers' => User::count(),
            'totalWalletBalance' => (float) WalletBalance::sum('balance'),
            'series' => $series,
        ]);
    }

    public function walletTools(): View
    {
        return view('Admin::wallet-tools', [
            'pendingDepositCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
            'pendingWithdrawalCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
        ]);
    }

    public function simulations(): View
    {
        return view('Admin::simulations', [
            'pendingDepositCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
            'pendingWithdrawalCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
            'settings' => AppSetting::current(),
        ]);
    }

    public function settingsPage(): View
    {
        return view('Admin::settings', [
            'pendingDepositCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
            'pendingWithdrawalCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
            'settings' => AppSetting::current(),
        ]);
    }

    public function logs(): View
    {
        return view('Admin::logs', [
            'pendingDepositCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
            'pendingWithdrawalCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
        ]);
    }

    public function pushNotificationForm(): View
    {
        return view('Admin::push-notification', [
            'pendingDepositCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
            'pendingWithdrawalCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
            'totalUsers' => User::count(),
        ]);
    }

    public function sendPushNotification(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'target' => ['required', 'in:all,specific'],
            'phone' => ['required_if:target,specific', 'nullable', 'digits:10'],
            'title' => ['required', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['target'] === 'all') {
            $sent = UserNotification::broadcast('admin_broadcast', $validated['title'], $validated['body'] ?? null);

            if ($sent === 0) {
                return back()->withInput()->with('error', 'No registered users to notify yet.');
            }

            Log::channel('admin_security')->info('Push notification broadcast to all users', [
                'title' => $validated['title'],
                'recipient_count' => $sent,
            ]);

            return redirect()->route('admin.push-notification')
                ->with('success', "Sent to all {$sent} users.");
        }

        $user = User::where('phone', $validated['phone'])->first();
        if (! $user) {
            return back()->withInput()->withErrors(['phone' => 'No account found with this phone number.']);
        }

        UserNotification::notify($user, 'admin_broadcast', $validated['title'], $validated['body'] ?? null);

        Log::channel('admin_security')->info('Push notification sent to specific user', [
            'title' => $validated['title'],
            'user_id' => $user->id,
            'phone' => $validated['phone'],
        ]);

        return redirect()->route('admin.push-notification')
            ->with('success', "Sent to {$user->name} ({$validated['phone']}).");
    }

    public function toggleReferral(Request $request): RedirectResponse
    {
        $enabled = AppSetting::get('referral_enabled', AppSetting::DEFAULTS['referral_enabled']) !== 'true';
        AppSetting::set('referral_enabled', $enabled ? 'true' : 'false');

        Log::channel('admin_security')->info('Referral program toggled', [
            'ip' => $request->ip(),
            'enabled' => $enabled,
        ]);

        return redirect()->route('admin.settings')
            ->with('success', 'Referral program '.($enabled ? 'enabled' : 'disabled').'.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cashback_amount' => ['required', 'numeric', 'min:0'],
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'settlement_time' => ['required', 'string', 'max:50'],
            'max_deposit_limit' => ['required', 'numeric', 'min:0'],
        ]);

        AppSetting::set('cashback_amount', (string) $validated['cashback_amount']);
        AppSetting::set('commission_percent', (string) $validated['commission_percent']);
        AppSetting::set('settlement_time', $validated['settlement_time']);
        AppSetting::set('max_deposit_limit', (string) $validated['max_deposit_limit']);

        Log::channel('admin_security')->info('Program settings updated', [
            'ip' => $request->ip(),
            'settings' => $validated,
        ]);

        return redirect()->route('admin.settings')
            ->with('success', 'Settings saved.');
    }

    public function deposits(Request $request): View
    {
        $status = $request->query('status', DepositRequest::STATUS_PENDING);
        if (! in_array($status, [DepositRequest::STATUS_PENDING, DepositRequest::STATUS_APPROVED, DepositRequest::STATUS_REJECTED], true)) {
            $status = DepositRequest::STATUS_PENDING;
        }

        return view('Admin::deposits', [
            'status' => $status,
            'deposits' => DepositRequest::status($status)->latest('submitted_at')->get(),
            'pendingCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
            'pendingWithdrawalCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
        ]);
    }

    public function approveDeposit(DepositRequest $deposit): RedirectResponse
    {
        if ($deposit->status !== DepositRequest::STATUS_PENDING) {
            return back()->with('error', 'This request has already been reviewed.');
        }

        $deposit->update([
            'status' => DepositRequest::STATUS_APPROVED,
            'reviewed_at' => now(),
        ]);

        $wallet = WalletBalance::credit($deposit->phone, (float) $deposit->amount);

        if ($user = User::where('phone', $deposit->phone)->first()) {
            UserNotification::notify(
                $user,
                'deposit_approved',
                'Money added to your wallet',
                "₹{$deposit->amount} has been credited to your wallet. New balance: ₹{$wallet->balance}."
            );
        }

        Log::channel('admin_security')->info('Deposit request approved', [
            'deposit_id' => $deposit->id,
            'phone' => $deposit->phone,
            'amount' => (float) $deposit->amount,
            'utr' => $deposit->utr,
            'new_balance' => (float) $wallet->balance,
        ]);

        return back()->with('success', "Approved. ₹{$deposit->amount} credited to {$deposit->phone}.");
    }

    public function rejectDeposit(DepositRequest $deposit): RedirectResponse
    {
        if ($deposit->status !== DepositRequest::STATUS_PENDING) {
            return back()->with('error', 'This request has already been reviewed.');
        }

        $deposit->update([
            'status' => DepositRequest::STATUS_REJECTED,
            'reviewed_at' => now(),
        ]);

        if ($user = User::where('phone', $deposit->phone)->first()) {
            UserNotification::notify(
                $user,
                'deposit_rejected',
                'Deposit request rejected',
                "Your ₹{$deposit->amount} deposit (UTR {$deposit->utr}) couldn't be verified. You can submit it again if the details were wrong."
            );
        }

        Log::channel('admin_security')->info('Deposit request rejected', [
            'deposit_id' => $deposit->id,
            'phone' => $deposit->phone,
            'amount' => (float) $deposit->amount,
            'utr' => $deposit->utr,
        ]);

        return back()->with('success', "Rejected deposit request for {$deposit->phone}.");
    }

    public function withdrawals(Request $request): View
    {
        $status = $request->query('status', WithdrawRequest::STATUS_PENDING);
        if (! in_array($status, [WithdrawRequest::STATUS_PENDING, WithdrawRequest::STATUS_APPROVED, WithdrawRequest::STATUS_REJECTED], true)) {
            $status = WithdrawRequest::STATUS_PENDING;
        }

        return view('Admin::withdrawals', [
            'status' => $status,
            'withdrawals' => WithdrawRequest::status($status)->latest('submitted_at')->get(),
            'pendingCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
            'pendingDepositCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
        ]);
    }

    public function approveWithdrawal(WithdrawRequest $withdraw): RedirectResponse
    {
        if ($withdraw->status !== WithdrawRequest::STATUS_PENDING) {
            return back()->with('error', 'This request has already been reviewed.');
        }

        // Balance can have moved since the request was submitted (other
        // withdrawals/deposits in between) - re-check right before debiting,
        // not just at submission time.
        $available = WalletBalance::balanceFor($withdraw->phone);
        if ((float) $withdraw->amount > $available) {
            return back()->with('error', "Cannot approve: current balance (₹{$available}) is less than the requested ₹{$withdraw->amount}.");
        }

        $withdraw->update([
            'status' => WithdrawRequest::STATUS_APPROVED,
            'reviewed_at' => now(),
        ]);

        $wallet = WalletBalance::debit($withdraw->phone, (float) $withdraw->amount);

        if ($user = User::where('phone', $withdraw->phone)->first()) {
            UserNotification::notify(
                $user,
                'withdrawal_approved',
                'Withdrawal approved',
                "₹{$withdraw->amount} is on its way to {$withdraw->payout_upi_id}."
            );
        }

        Log::channel('admin_security')->info('Withdrawal request approved', [
            'withdraw_id' => $withdraw->id,
            'phone' => $withdraw->phone,
            'amount' => (float) $withdraw->amount,
            'payout_upi_id' => $withdraw->payout_upi_id,
            'new_balance' => (float) $wallet->balance,
        ]);

        return back()->with('success', "Approved. ₹{$withdraw->amount} debited from {$withdraw->phone} - pay out to {$withdraw->payout_upi_id} manually.");
    }

    public function rejectWithdrawal(WithdrawRequest $withdraw): RedirectResponse
    {
        if ($withdraw->status !== WithdrawRequest::STATUS_PENDING) {
            return back()->with('error', 'This request has already been reviewed.');
        }

        $withdraw->update([
            'status' => WithdrawRequest::STATUS_REJECTED,
            'reviewed_at' => now(),
        ]);

        if ($user = User::where('phone', $withdraw->phone)->first()) {
            UserNotification::notify(
                $user,
                'withdrawal_rejected',
                'Withdrawal request rejected',
                "Your ₹{$withdraw->amount} withdrawal request was rejected. The amount was not deducted from your wallet."
            );
        }

        Log::channel('admin_security')->info('Withdrawal request rejected', [
            'withdraw_id' => $withdraw->id,
            'phone' => $withdraw->phone,
            'amount' => (float) $withdraw->amount,
            'payout_upi_id' => $withdraw->payout_upi_id,
        ]);

        return back()->with('success', "Rejected withdrawal request for {$withdraw->phone}.");
    }

    public function pollNotifications(): JsonResponse
    {
        return response()->json([
            'unread_count' => AdminNotification::unread()->count(),
            'items' => AdminNotification::latest()->limit(20)->get()->map(fn (AdminNotification $n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'unread' => is_null($n->read_at),
                'created_at' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }

    public function markNotificationsRead(): JsonResponse
    {
        AdminNotification::unread()->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Log::channel('admin_security')->info('Admin logged out', ['ip' => $request->ip()]);

        $request->session()->forget('admin_authenticated');
        $request->session()->regenerate();

        return redirect()->route('admin.login');
    }

    private function humanizeSeconds(int $seconds): string
    {
        if ($seconds >= 3600) {
            return round($seconds / 3600).' hour(s)';
        }
        if ($seconds >= 60) {
            return round($seconds / 60).' minute(s)';
        }

        return "{$seconds} second(s)";
    }
}
