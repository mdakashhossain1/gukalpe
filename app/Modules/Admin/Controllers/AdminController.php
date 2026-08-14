<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AdminAuthenticate;
use App\Models\AdminAuditLog;
use App\Models\AdminBroadcastLog;
use App\Models\AdminNotification;
use App\Models\AdminUser;
use App\Models\AppSetting;
use App\Models\DepositRequest;
use App\Models\Plan;
use App\Models\ReferralCommission;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\UserPlan;
use App\Models\WalletBalance;
use App\Models\WalletTransaction;
use App\Models\WithdrawRequest;
use App\Support\AdminRoles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        $request->validate(['username' => 'nullable|string', 'password' => 'required|string']);

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

        // Two ways in (plan.md Section 39): a named admin_user by username, or
        // the shared master password (always super_admin). Master path is
        // unchanged when no username is supplied.
        $role = 'super_admin';
        $username = trim((string) $request->input('username', ''));
        $adminUser = null;

        if ($username !== '') {
            $adminUser = AdminUser::where('username', $username)->where('is_active', true)->first();
            $correct = $adminUser && Hash::check((string) $request->input('password'), $adminUser->password);
            if ($correct) {
                $role = $adminUser->role;
            } else {
                $adminUser = null;
            }
        } else {
            $configured = (string) config('admin.password');
            $correct = $configured !== '' && hash_equals($configured, (string) $request->input('password'));
        }

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
        $request->session()->put('admin_role', $role);
        $request->session()->put('admin_user_id', $adminUser?->id);
        $request->session()->put('admin_label', $adminUser?->name ?: 'Master Admin');

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
        return view('Admin::users', [
            'users' => $this->usersWithWalletBalances(),
            'pendingDepositCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
            'pendingWithdrawalCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
        ]);
    }

    /**
     * Shared by users() and logs() - both render the same user list, each
     * row's wallet balance attached from a single phone=>balance map (no
     * N+1), just with a different set of row actions.
     */
    private function usersWithWalletBalances()
    {
        $balances = WalletBalance::pluck('balance', 'phone');

        return User::withCount('referrals')
            ->latest()
            ->get()
            ->each(function (User $user) use ($balances) {
                $user->wallet_balance = (float) ($balances[$user->phone] ?? 0);
            });
    }

    /**
     * Users -> View Profile (client item 1). Financial summary + investment
     * summary + recent activity for one user, plus the expanded Actions menu
     * (Wallet Management, Ban/Unban, Send Notification here; Transactions
     * deep-links to admin.transactions?phone=; Deposits/Withdrawals/Referral
     * Details are shown inline below rather than as separate pages, since no
     * per-user filter exists on those list pages).
     */
    public function showUser(User $user): View
    {
        $phone = $user->phone;

        $recentTransactions = $phone
            ? WalletTransaction::where('phone', $phone)->latest('id')->limit(15)->get()
            : collect();

        // Every admin action "about this user" - not just entries that
        // target the User row itself (bans, wallet adjustments), but also
        // deposit/withdrawal approve/reject entries, which target the
        // DepositRequest/WithdrawRequest row instead. Missing those here
        // silently hid real audit-log rows that do exist in the database -
        // this user only ever had their own request IDs pulled in for the
        // OR-matching, so it stays scoped to this user's own records.
        $depositIds = $phone ? DepositRequest::where('phone', $phone)->pluck('id') : collect();
        $withdrawIds = $phone ? WithdrawRequest::where('phone', $phone)->pluck('id') : collect();

        $recentAudit = AdminAuditLog::where(function ($query) use ($user, $depositIds, $withdrawIds) {
            $query->where(fn ($q) => $q->where('target_type', 'User')->where('target_id', $user->id))
                ->orWhere(fn ($q) => $q->where('target_type', 'DepositRequest')->whereIn('target_id', $depositIds))
                ->orWhere(fn ($q) => $q->where('target_type', 'WithdrawRequest')->whereIn('target_id', $withdrawIds));
        })->latest('id')->limit(15)->get();

        $holdings = UserPlan::where('user_id', $user->id)->with('plan')->latest('purchased_at')->get();

        return view('Admin::user-profile', [
            'user' => $user,
            'walletBalance' => $phone ? WalletBalance::balanceFor($phone) : 0.0,
            'totalDeposited' => $phone ? (float) DepositRequest::where('phone', $phone)->status(DepositRequest::STATUS_APPROVED)->sum('amount') : 0.0,
            'totalWithdrawn' => $phone ? (float) WithdrawRequest::where('phone', $phone)->status(WithdrawRequest::STATUS_APPROVED)->sum('amount') : 0.0,
            'totalInvested' => (float) $holdings->where('status', UserPlan::STATUS_ACTIVE)->sum('invested_amount'),
            'holdings' => $holdings,
            'recentTransactions' => $recentTransactions,
            'recentAudit' => $recentAudit,
            'referrals' => $user->referrals()->latest()->limit(20)->get(),
            'recentDeposits' => $phone ? DepositRequest::where('phone', $phone)->latest('submitted_at')->limit(10)->get() : collect(),
            'recentWithdrawals' => $phone ? WithdrawRequest::where('phone', $phone)->latest('submitted_at')->limit(10)->get() : collect(),
            'pendingDepositCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
            'pendingWithdrawalCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
        ]);
    }

    /**
     * Ban or unban a user (toggle). A ban sets banned_at=now(); the user is
     * refused at every login path and force-logged-out on their next request
     * (EnsureUserNotBanned). Unban clears it. Banning requires a reason
     * (client requirement: "Ban -> Reason -> Confirm"); unbanning does not,
     * since there's nothing to justify about restoring access.
     */
    public function toggleBanUser(Request $request, User $user): RedirectResponse
    {
        $nowBanned = ! $user->isBanned();

        $reason = null;
        if ($nowBanned) {
            $validated = $request->validate([
                'reason' => ['required', 'string', 'max:255'],
            ], [
                'reason.required' => 'Enter a reason for banning this user.',
            ]);
            $reason = trim($validated['reason']);
        }

        $user->banned_at = $nowBanned ? now() : null;
        $user->ban_reason = $nowBanned ? $reason : null;
        $user->save();

        Log::channel('admin_security')->info($nowBanned ? 'User banned' : 'User unbanned', [
            'user_id' => $user->id,
            'phone' => $user->phone,
            'reason' => $reason,
        ]);

        AdminAuditLog::record($request, $nowBanned ? 'user_banned' : 'user_unbanned', $user, $reason);

        $label = $user->name ?: ($user->phone ?: '#'.$user->id);

        if ($user->phone) {
            UserNotification::notify(
                $user,
                $nowBanned ? 'account_banned' : 'account_unbanned',
                $nowBanned ? 'Account restricted' : 'Account restored',
                $nowBanned
                    ? 'Your account has been banned.'.($reason ? " Reason: {$reason}" : '')
                    : 'Your account has been unbanned. You can log in again.'
            );
        }

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
        abort_unless(AdminRoles::currentCan('wallet_adjust'), 403);

        $validated = $request->validate([
            'phone' => ['required', 'regex:/^\d{10}$/'],
            'direction' => ['required', 'in:increase,decrease'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'phone.regex' => 'Enter a valid 10-digit phone number.',
            'amount.gt' => 'Enter an amount greater than zero.',
            'reason.required' => 'Enter a reason for this wallet adjustment.',
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

        $reason = trim($validated['reason']);
        $balanceBefore = WalletBalance::balanceFor($phone);

        // admin_label lives in the ledger row's own meta (not just the audit
        // log) so the Transactions page can show Reason/Balance
        // Before/Admin directly, without joining to admin_audit_logs.
        $adjustmentMeta = ['source' => 'admin_adjustment', 'reason' => $reason, 'admin_label' => session('admin_label', 'Master Admin')];

        $wallet = $increase
            ? WalletBalance::credit($phone, $amount, 'manual_credit', $adjustmentMeta)
            : WalletBalance::debit($phone, $amount, 'manual_debit', $adjustmentMeta);

        $newBalance = (float) $wallet->balance;

        AdminAuditLog::record($request, 'wallet_adjustment', $user, $reason, [
            'direction' => $validated['direction'],
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $newBalance,
        ]);

        UserNotification::notify(
            $user,
            'wallet_adjustment',
            $increase ? 'Wallet credited' : 'Wallet debited',
            ($increase
                ? '₹'.number_format($amount, 2).' has been added to your wallet by support. New balance: ₹'.number_format($newBalance, 2).'.'
                : '₹'.number_format($amount, 2).' has been deducted from your wallet by support. New balance: ₹'.number_format($newBalance, 2).'.')
                .' Reason: '.$reason
        );

        Log::channel('admin_security')->info('Wallet manually adjusted', [
            'phone' => $phone,
            'direction' => $validated['direction'],
            'amount' => $amount,
            'new_balance' => $newBalance,
            'reason' => $reason,
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
        abort_unless(AdminRoles::currentCan('manage_settings'), 403);

        return view('Admin::settings', [
            'pendingDepositCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
            'pendingWithdrawalCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
            'settings' => AppSetting::current(),
        ]);
    }

    /**
     * Activity Logs page: every registered user, with a Details ("i") button
     * per row opening that user's full profile (wallet, deposits,
     * withdrawals, investments, referrals, recent admin actions - everything
     * already aggregated on admin.users.show). Not a chronological
     * action-by-action audit table - the AdminAuditLog trail itself is still
     * written on every money/state-changing action (see AdminAuditLog) and
     * still shown on each user's own profile page under "Recent admin
     * actions"; this page is the manual per-user lookup view instead.
     */
    public function logs(): View
    {
        return view('Admin::logs', [
            'users' => $this->usersWithWalletBalances(),
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
            // History of admin-sent broadcasts (client item 8) - not the
            // per-recipient UserNotification inbox rows, one summary row
            // per send action here.
            'history' => AdminBroadcastLog::latest('id')->limit(50)->get(),
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

            AdminBroadcastLog::create([
                'target_description' => 'All users',
                'title' => $validated['title'],
                'body' => $validated['body'] ?? null,
                'sent_by' => session('admin_label', 'Master Admin'),
                'status' => 'sent',
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

        AdminBroadcastLog::create([
            'target_description' => $validated['phone'],
            'title' => $validated['title'],
            'body' => $validated['body'] ?? null,
            'sent_by' => session('admin_label', 'Master Admin'),
            'status' => 'sent',
            'recipient_count' => 1,
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

        AdminAuditLog::record($request, 'referral_program_toggled', null, null, ['enabled' => $enabled]);

        return redirect()->route('admin.settings')
            ->with('success', 'Referral program '.($enabled ? 'enabled' : 'disabled').'.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        abort_unless(AdminRoles::currentCan('manage_settings'), 403);

        $validated = $request->validate([
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_deposit_limit' => ['required', 'numeric', 'min:0'],
            'referral_min_qualifying_amount' => ['nullable', 'numeric', 'min:0'],
            'referral_max_qualifying_amount' => ['nullable', 'numeric', 'min:0', 'gte:referral_min_qualifying_amount'],
        ]);

        AppSetting::set('commission_percent', (string) $validated['commission_percent']);
        AppSetting::set('max_deposit_limit', (string) $validated['max_deposit_limit']);
        // Blank = unbounded, same convention as upi_min_amount/upi_max_amount -
        // stored as an empty string, not '0', so AppSetting::get()'s '' check
        // in the commission-eligibility code treats it as "no bound" not "$0".
        AppSetting::set('referral_min_qualifying_amount', $validated['referral_min_qualifying_amount'] !== null ? (string) $validated['referral_min_qualifying_amount'] : '');
        AppSetting::set('referral_max_qualifying_amount', $validated['referral_max_qualifying_amount'] !== null ? (string) $validated['referral_max_qualifying_amount'] : '');
        foreach (['referral_source_plan_purchase_enabled', 'referral_source_deposit_enabled'] as $switch) {
            AppSetting::set($switch, $request->boolean($switch) ? 'true' : 'false');
        }
        // Only 'manual' exists (no payment gateway in this app - see AppSetting::DEFAULTS
        // comment); still write it explicitly so the setting round-trips through the form.
        AppSetting::set('withdrawal_processing_mode', 'manual');

        // System kill-switches (plan.md Section 41). Withdrawal limits/method
        // toggles moved to their own page - see WithdrawalSettingsController
        // (client item 6: "Do not keep these mixed inside Referral Program").
        // Checkboxes: absent = off.
        foreach (['maintenance_mode', 'allow_registration', 'allow_investment', 'allow_withdrawals'] as $switch) {
            AppSetting::set($switch, $request->boolean($switch) ? 'true' : 'false');
        }

        Log::channel('admin_security')->info('Program settings updated', [
            'ip' => $request->ip(),
            'settings' => $validated,
        ]);

        AdminAuditLog::record($request, 'settings_updated', null, null, $validated);

        return redirect()->route('admin.settings')
            ->with('success', 'Settings saved.');
    }

    public function deposits(Request $request): View
    {
        $status = $request->query('status', DepositRequest::STATUS_PENDING);
        if (! in_array($status, [DepositRequest::STATUS_PENDING, DepositRequest::STATUS_APPROVED, DepositRequest::STATUS_REJECTED], true)) {
            $status = DepositRequest::STATUS_PENDING;
        }
        $phone = trim((string) $request->query('phone', ''));

        $query = DepositRequest::status($status)->latest('submitted_at');
        if ($phone !== '') {
            $query->where('phone', $phone);
        }

        return view('Admin::deposits', [
            'status' => $status,
            'phone' => $phone,
            'deposits' => $query->get(),
            'pendingCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
            'pendingWithdrawalCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
        ]);
    }

    public function approveDeposit(Request $request, DepositRequest $deposit): RedirectResponse
    {
        abort_unless(AdminRoles::currentCan('approve_deposits'), 403);

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

        $this->creditReferralCommissionForDeposit($deposit);

        AdminAuditLog::record($request, 'deposit_approved', $deposit, null, [
            'phone' => $deposit->phone,
            'amount' => (float) $deposit->amount,
            'utr' => $deposit->utr,
            'new_balance' => (float) $wallet->balance,
        ]);

        return back()->with('success', "Approved. ₹{$deposit->amount} credited to {$deposit->phone}.");
    }

    /**
     * Refer & Earn, deposit source: a one-time PENDING commission for
     * whoever referred the depositor, created only on their first-ever
     * qualifying approved deposit (checked via the unique
     * deposit_request_id on referral_commissions plus this exists() check).
     * Mirrors PlanPurchaseController::creditReferralCommissionIfEligible()
     * - same eligibility shape, different trigger event and own source
     * toggle/enable flag. Only ever called from here (deposit approval),
     * never on submission, so a rejected deposit structurally can't
     * produce a commission.
     */
    private function creditReferralCommissionForDeposit(DepositRequest $deposit): void
    {
        $user = User::where('phone', $deposit->phone)->first();
        if (! $user
            || ! $user->referred_by
            || AppSetting::get('referral_enabled', 'true') !== 'true'
            || ! AppSetting::enabled('referral_source_deposit_enabled')) {
            return;
        }

        $amount = (float) $deposit->amount;
        $min = AppSetting::get('referral_min_qualifying_amount', '');
        $max = AppSetting::get('referral_max_qualifying_amount', '');
        if (($min !== '' && $amount < (float) $min) || ($max !== '' && $amount > (float) $max)) {
            return;
        }

        $hadEarlierQualifyingDeposit = ReferralCommission::where('referred_user_id', $user->id)
            ->where('source', ReferralCommission::SOURCE_DEPOSIT)
            ->exists();
        if ($hadEarlierQualifyingDeposit) {
            return;
        }

        $referrer = User::find($user->referred_by);
        if (! $referrer || ! $referrer->phone) {
            return;
        }

        $percent = (float) AppSetting::get('commission_percent', '5');
        $commissionAmount = round($amount * $percent / 100, 2);
        if ($commissionAmount <= 0) {
            return;
        }

        $commission = ReferralCommission::create([
            'referrer_id' => $referrer->id,
            'referred_user_id' => $user->id,
            'deposit_request_id' => $deposit->id,
            'source' => ReferralCommission::SOURCE_DEPOSIT,
            'status' => ReferralCommission::STATUS_PENDING,
            'amount' => $commissionAmount,
            'commission_percent' => $percent,
        ]);

        AdminNotification::notify(
            'referral_commission',
            'Referral commission pending review',
            "{$referrer->name} earned ₹".number_format($commissionAmount, 2)." for referring {$user->name} - awaiting approval"
        );

        Log::info('Referral commission created (pending, deposit source)', [
            'referral_commission_id' => $commission->id,
            'referrer_id' => $referrer->id,
            'referred_user_id' => $user->id,
            'deposit_request_id' => $deposit->id,
            'amount' => $commissionAmount,
            'commission_percent' => $percent,
        ]);
    }

    public function rejectDeposit(Request $request, DepositRequest $deposit): RedirectResponse
    {
        abort_unless(AdminRoles::currentCan('approve_deposits'), 403);

        if ($deposit->status !== DepositRequest::STATUS_PENDING) {
            return back()->with('error', 'This request has already been reviewed.');
        }

        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);
        $note = trim((string) ($validated['admin_note'] ?? ''));

        $deposit->update([
            'status' => DepositRequest::STATUS_REJECTED,
            'reviewed_at' => now(),
            'admin_note' => $note !== '' ? $note : null,
        ]);

        if ($user = User::where('phone', $deposit->phone)->first()) {
            UserNotification::notify(
                $user,
                'deposit_rejected',
                'Deposit request rejected',
                "Your ₹{$deposit->amount} deposit (UTR {$deposit->utr}) couldn't be verified. You can submit it again if the details were wrong."
                    .($note !== '' ? " Reason: {$note}" : '')
            );
        }

        Log::channel('admin_security')->info('Deposit request rejected', [
            'deposit_id' => $deposit->id,
            'phone' => $deposit->phone,
            'amount' => (float) $deposit->amount,
            'utr' => $deposit->utr,
            'admin_note' => $note,
        ]);

        AdminAuditLog::record($request, 'deposit_rejected', $deposit, $note !== '' ? $note : null, [
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
        $phone = trim((string) $request->query('phone', ''));

        $query = WithdrawRequest::status($status)->latest('submitted_at');
        if ($phone !== '') {
            $query->where('phone', $phone);
        }

        return view('Admin::withdrawals', [
            'status' => $status,
            'phone' => $phone,
            'withdrawals' => $query->get(),
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
        $phone = trim((string) $request->query('phone', ''));

        $query = WalletTransaction::query()->latest('id');
        if ($type !== 'all' && array_key_exists($type, WalletTransaction::TYPE_LABELS)) {
            $query->where('type', $type);
        } else {
            $type = 'all';
        }
        if ($phone !== '') {
            $query->where('phone', $phone);
        }

        // Cap the rendered set; the client-side datatable paginates/searches it.
        $transactions = $query->limit(1000)->get();
        $names = User::whereIn('phone', $transactions->pluck('phone')->unique())->pluck('name', 'phone');

        return view('Admin::transactions', [
            'type' => $type,
            'phone' => $phone,
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
     * query (no N+1). Extended per client item 11 with a custom date range
     * (scoping purchases by purchased_at and profit/maturity by
     * wallet_transactions.created_at) plus Total Profit / Total Maturity /
     * Average Investment KPI tiles.
     */
    public function planAnalytics(Request $request): View
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate = $to ? Carbon::parse($to)->endOfDay() : null;

        $holdingsQuery = UserPlan::query();
        if ($fromDate) {
            $holdingsQuery->where('purchased_at', '>=', $fromDate);
        }
        if ($toDate) {
            $holdingsQuery->where('purchased_at', '<=', $toDate);
        }

        $agg = (clone $holdingsQuery)->selectRaw(
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

        // Total Profit / Total Maturity (client item 11). Only
        // plan_maturity_credit exists going forward (principal + profit
        // bundled in one credit, with the profit portion in meta); the
        // legacy profit_credit type predates that and stored the profit
        // amount directly as the transaction amount - see WalletTransaction
        // TYPE_LABELS comment.
        $ledgerQuery = fn () => WalletTransaction::query()
            ->when($fromDate, fn ($q) => $q->where('created_at', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->where('created_at', '<=', $toDate));

        $maturityRows = $ledgerQuery()->where('type', 'plan_maturity_credit')->get();
        $totalMaturity = (float) $maturityRows->sum('amount');
        $totalProfit = $maturityRows->sum(fn (WalletTransaction $t) => (float) ($t->meta['profit_amount'] ?? 0))
            + (float) $ledgerQuery()->where('type', 'profit_credit')->sum('amount');

        $totals = [
            'views' => $rows->sum('views'),
            'purchases' => $rows->sum('purchases'),
            'invested' => $rows->sum('invested'),
            'active_investors' => $rows->sum('running'),
            'completed_investors' => $rows->sum('completed'),
            'profit' => $totalProfit,
            'maturity' => $totalMaturity,
        ];
        $totals['average_investment'] = $totals['purchases'] > 0 ? $totals['invested'] / $totals['purchases'] : 0.0;

        return view('Admin::plan-analytics', [
            'rows' => $rows,
            'totals' => $totals,
            'from' => $from,
            'to' => $to,
            'pendingDepositCount' => DepositRequest::status(DepositRequest::STATUS_PENDING)->count(),
            'pendingWithdrawalCount' => WithdrawRequest::status(WithdrawRequest::STATUS_PENDING)->count(),
        ]);
    }

    public function approveWithdrawal(Request $request, WithdrawRequest $withdraw): RedirectResponse
    {
        abort_unless(AdminRoles::currentCan('approve_withdrawals'), 403);

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
                "₹{$withdraw->amount} is on its way to {$withdraw->destinationLabel()}."
            );
        }

        Log::channel('admin_security')->info('Withdrawal request approved', [
            'withdraw_id' => $withdraw->id,
            'phone' => $withdraw->phone,
            'amount' => (float) $withdraw->amount,
            'method' => $withdraw->method,
            'destination' => $withdraw->destinationLabel(),
            'new_balance' => (float) $wallet->balance,
        ]);

        AdminAuditLog::record($request, 'withdrawal_approved', $withdraw, null, [
            'phone' => $withdraw->phone,
            'amount' => (float) $withdraw->amount,
            'method' => $withdraw->method,
            'destination' => $withdraw->destinationLabel(),
            'new_balance' => (float) $wallet->balance,
        ]);

        return back()->with('success', "Approved. ₹{$withdraw->amount} debited from {$withdraw->phone} - pay out to {$withdraw->destinationLabel()} manually.");
    }

    public function rejectWithdrawal(Request $request, WithdrawRequest $withdraw): RedirectResponse
    {
        abort_unless(AdminRoles::currentCan('approve_withdrawals'), 403);

        if ($withdraw->status !== WithdrawRequest::STATUS_PENDING) {
            return back()->with('error', 'This request has already been reviewed.');
        }

        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);
        $note = trim((string) ($validated['admin_note'] ?? ''));

        $withdraw->update([
            'status' => WithdrawRequest::STATUS_REJECTED,
            'reviewed_at' => now(),
            'admin_note' => $note !== '' ? $note : null,
        ]);

        if ($user = User::where('phone', $withdraw->phone)->first()) {
            UserNotification::notify(
                $user,
                'withdrawal_rejected',
                'Withdrawal request rejected',
                "Your ₹{$withdraw->amount} withdrawal request was rejected. The amount was not deducted from your wallet."
                    .($note !== '' ? " Reason: {$note}" : '')
            );
        }

        Log::channel('admin_security')->info('Withdrawal request rejected', [
            'withdraw_id' => $withdraw->id,
            'phone' => $withdraw->phone,
            'amount' => (float) $withdraw->amount,
            'method' => $withdraw->method,
            'destination' => $withdraw->destinationLabel(),
            'admin_note' => $note,
        ]);

        AdminAuditLog::record($request, 'withdrawal_rejected', $withdraw, $note !== '' ? $note : null, [
            'phone' => $withdraw->phone,
            'amount' => (float) $withdraw->amount,
            'method' => $withdraw->method,
            'destination' => $withdraw->destinationLabel(),
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
