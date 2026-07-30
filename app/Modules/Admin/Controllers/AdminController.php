<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AdminAuthenticate;
use App\Models\AdminNotification;
use App\Models\AppSetting;
use App\Models\DepositRequest;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\UserPlan;
use App\Models\WalletBalance;
use App\Models\WalletTransaction;
use App\Models\WithdrawRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
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
        $remember = (string) $request->cookie(AdminAuthenticate::REMEMBER_COOKIE, '');
        if ($request->session()->get('admin_authenticated')
            || ($remember !== '' && hash_equals(AdminAuthenticate::rememberToken(), $remember))) {
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

        // Keep the admin logged in for 30 days via a long-lived remember cookie
        // (see AdminAuthenticate). The session alone expires far sooner.
        Cookie::queue(Cookie::make(
            AdminAuthenticate::REMEMBER_COOKIE,
            AdminAuthenticate::rememberToken(),
            AdminAuthenticate::REMEMBER_MINUTES,
        ));

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

        // Money flow - all real, approved-only sums so they mean "actually
        // credited / actually paid out", not just "requested".
        $totalDeposited = (float) DepositRequest::status(DepositRequest::STATUS_APPROVED)->sum('amount');
        $totalWithdrawn = (float) WithdrawRequest::status(WithdrawRequest::STATUS_APPROVED)->sum('amount');
        $totalInvested = (float) UserPlan::where('status', UserPlan::STATUS_ACTIVE)->sum('invested_amount');

        return view('Admin::dashboard', [
            'pendingDepositCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
            'pendingWithdrawalCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
            'totalUsers' => User::count(),
            'totalWalletBalance' => (float) WalletBalance::sum('balance'),
            'series' => $series,

            // Money
            'totalDeposited' => $totalDeposited,
            'totalWithdrawn' => $totalWithdrawn,
            'netInflow' => $totalDeposited - $totalWithdrawn,
            'totalInvested' => $totalInvested,
            'activeHoldings' => UserPlan::where('status', UserPlan::STATUS_ACTIVE)->count(),

            // Users
            'signupsToday' => User::whereDate('created_at', today())->count(),
            'signups7d' => User::where('created_at', '>=', now()->subDays(7))->count(),
            'bannedUsers' => User::whereNotNull('banned_at')->count(),
            'googleUsers' => User::whereNotNull('google_id')->count(),

            // Requests breakdown
            'depApprovedCount' => DepositRequest::status(DepositRequest::STATUS_APPROVED)->count(),
            'depRejectedCount' => DepositRequest::status(DepositRequest::STATUS_REJECTED)->count(),
            'pendingDepositAmount' => (float) DepositRequest::status(DepositRequest::STATUS_PENDING)->sum('amount'),
            'wdApprovedCount' => WithdrawRequest::status(WithdrawRequest::STATUS_APPROVED)->count(),
            'wdRejectedCount' => WithdrawRequest::status(WithdrawRequest::STATUS_REJECTED)->count(),
            'pendingWithdrawalAmount' => (float) WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->sum('amount'),

            // Plans
            'totalPlans' => Plan::count(),
            'activePlans' => Plan::active()->count(),
        ]);
    }

    /**
     * Full registered-user list. Wallet balances are keyed by phone (see
     * WalletBalance), so pull them once as a phone=>balance map and attach
     * to each row instead of a per-user query (no N+1). Referral count comes
     * from withCount on the self-referencing referrals() relation.
     */
    public function users(): View
    {
        $balances = WalletBalance::pluck('balance', 'phone');

        $users = User::withCount('referrals')
            ->latest()
            ->get()
            ->each(function (User $user) use ($balances) {
                $user->wallet_balance = (float) ($balances[$user->phone] ?? 0);
            });

        return view('Admin::users', [
            'users' => $users,
            'pendingDepositCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
            'pendingWithdrawalCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
        ]);
    }

    /**
     * Ban or unban a user (toggle). A ban sets banned_at=now(); the user is
     * refused at every login path and force-logged-out on their next request
     * (EnsureUserNotBanned). Unban clears it.
     */
    public function toggleBanUser(User $user): RedirectResponse
    {
        $nowBanned = ! $user->isBanned();
        $user->banned_at = $nowBanned ? now() : null;
        $user->save();

        Log::channel('admin_security')->info($nowBanned ? 'User banned' : 'User unbanned', [
            'user_id' => $user->id,
            'phone' => $user->phone,
        ]);

        $label = $user->name ?: ($user->phone ?: '#'.$user->id);

        return back()->with('success', $nowBanned
            ? "Banned {$label}. They can no longer log in."
            : "Unbanned {$label}. They can log in again.");
    }

    /**
     * Manually increase or decrease a real user's wallet balance (DB-backed,
     * shows on Overview). Mirrors the deposit-approval flow: adjust the
     * WalletBalance, notify the user, and log to the admin_security channel.
     * A decrease is re-checked against the live balance so it can't drive the
     * wallet negative.
     */
    public function adjustWallet(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'regex:/^\d{10}$/'],
            'direction' => ['required', 'in:increase,decrease'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ], [
            'phone.regex' => 'Enter a valid 10-digit phone number.',
            'amount.gt' => 'Enter an amount greater than zero.',
        ]);

        $phone = $validated['phone'];
        $amount = round((float) $validated['amount'], 2);
        $increase = $validated['direction'] === 'increase';

        $user = User::where('phone', $phone)->first();
        if (! $user) {
            return back()->withInput()->with('error', "No user found with phone {$phone}.");
        }

        if (! $increase) {
            $available = WalletBalance::balanceFor($phone);
            if ($amount > $available) {
                return back()->withInput()->with('error', 'Cannot decrease: current balance (₹'.number_format($available, 2).') is less than ₹'.number_format($amount, 2).'.');
            }
        }

        $wallet = $increase
            ? WalletBalance::credit($phone, $amount, 'manual_credit', ['source' => 'admin_adjustment'])
            : WalletBalance::debit($phone, $amount, 'manual_debit', ['source' => 'admin_adjustment']);

        $newBalance = (float) $wallet->balance;

        UserNotification::notify(
            $user,
            'wallet_adjustment',
            $increase ? 'Wallet credited' : 'Wallet debited',
            $increase
                ? '₹'.number_format($amount, 2).' has been added to your wallet by support. New balance: ₹'.number_format($newBalance, 2).'.'
                : '₹'.number_format($amount, 2).' has been deducted from your wallet by support. New balance: ₹'.number_format($newBalance, 2).'.'
        );

        Log::channel('admin_security')->info('Wallet manually adjusted', [
            'phone' => $phone,
            'direction' => $validated['direction'],
            'amount' => $amount,
            'new_balance' => $newBalance,
            'ip' => $request->ip(),
        ]);

        $verb = $increase ? 'Increased' : 'Decreased';

        return back()->with('success', "{$verb} {$phone}'s wallet by ₹".number_format($amount, 2).'. New balance ₹'.number_format($newBalance, 2).'.');
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
            'withdrawal_min_amount' => ['nullable', 'numeric', 'min:0'],
            'withdrawal_daily_limit' => ['nullable', 'numeric', 'min:0'],
            'withdrawal_max_per_day' => ['nullable', 'integer', 'min:1'],
        ]);

        AppSetting::set('cashback_amount', (string) $validated['cashback_amount']);
        AppSetting::set('commission_percent', (string) $validated['commission_percent']);
        AppSetting::set('settlement_time', $validated['settlement_time']);
        AppSetting::set('max_deposit_limit', (string) $validated['max_deposit_limit']);

        if (isset($validated['withdrawal_min_amount'])) {
            AppSetting::set('withdrawal_min_amount', (string) $validated['withdrawal_min_amount']);
        }
        if (isset($validated['withdrawal_daily_limit'])) {
            AppSetting::set('withdrawal_daily_limit', (string) $validated['withdrawal_daily_limit']);
        }
        if (isset($validated['withdrawal_max_per_day'])) {
            AppSetting::set('withdrawal_max_per_day', (string) $validated['withdrawal_max_per_day']);
        }

        // System kill-switches (plan.md Section 41). Checkboxes: absent = off.
        foreach (['maintenance_mode', 'allow_registration', 'allow_investment', 'allow_withdrawals'] as $switch) {
            AppSetting::set($switch, $request->boolean($switch) ? 'true' : 'false');
        }

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

        $wallet = WalletBalance::credit($deposit->phone, (float) $deposit->amount, 'add_money', ['deposit_id' => $deposit->id]);

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

    /**
     * Unified wallet ledger (plan.md Section 30 — Transaction Management).
     * Reads the wallet_transactions table written by WalletBalance::credit/debit.
     */
    public function transactions(Request $request): View
    {
        $type = $request->query('type', 'all');

        $query = WalletTransaction::query()->latest('id');
        if ($type !== 'all' && array_key_exists($type, WalletTransaction::TYPE_LABELS)) {
            $query->where('type', $type);
        } else {
            $type = 'all';
        }

        // Cap the rendered set; the client-side datatable paginates/searches it.
        $transactions = $query->limit(1000)->get();
        $names = User::whereIn('phone', $transactions->pluck('phone')->unique())->pluck('name', 'phone');

        return view('Admin::transactions', [
            'type' => $type,
            'transactions' => $transactions,
            'names' => $names,
            'typeLabels' => WalletTransaction::TYPE_LABELS,
            'totalCredit' => WalletTransaction::where('direction', 'credit')->sum('amount'),
            'totalDebit' => WalletTransaction::where('direction', 'debit')->sum('amount'),
            'pendingDepositCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
            'pendingWithdrawalCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
        ]);
    }

    /**
     * Per-plan analytics (plan.md Section 27): views + purchases + conversion,
     * plus investment/running/completed derived from user_plans in one grouped
     * query (no N+1).
     */
    public function planAnalytics(): View
    {
        $agg = UserPlan::selectRaw(
            'plan_id,
             count(*) as holdings,
             coalesce(sum(invested_amount), 0) as invested,
             sum(case when status = ? then 1 else 0 end) as running,
             sum(case when status = ? then 1 else 0 end) as completed',
            [UserPlan::STATUS_ACTIVE, UserPlan::STATUS_WITHDRAWN]
        )->groupBy('plan_id')->get()->keyBy('plan_id');

        $rows = Plan::orderByDesc('total_purchases_count')->orderBy('sort_order')->get()->map(function (Plan $p) use ($agg) {
            $a = $agg->get($p->id);
            $purchases = (int) ($a->holdings ?? 0);
            $views = (int) $p->views;

            return [
                'title' => $p->title,
                'views' => $views,
                'purchases' => $purchases,
                'conversion' => $views > 0 ? round($purchases / $views * 100, 1) : 0.0,
                'invested' => (float) ($a->invested ?? 0),
                'running' => (int) ($a->running ?? 0),
                'completed' => (int) ($a->completed ?? 0),
            ];
        });

        return view('Admin::plan-analytics', [
            'rows' => $rows,
            'totals' => [
                'views' => $rows->sum('views'),
                'purchases' => $rows->sum('purchases'),
                'invested' => $rows->sum('invested'),
            ],
            'pendingDepositCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
            'pendingWithdrawalCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
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

        $wallet = WalletBalance::debit($withdraw->phone, (float) $withdraw->amount, 'withdrawal', ['withdraw_id' => $withdraw->id]);

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

        // Kill the 30-day remember cookie so logout is a real logout.
        Cookie::queue(Cookie::forget(AdminAuthenticate::REMEMBER_COOKIE));

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
