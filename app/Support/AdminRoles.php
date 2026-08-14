<?php

namespace App\Support;

/**
 * Admin roles + permission matrix (plan.md Section 39 — Role Management).
 * The master ADMIN_PANEL_PASSWORD always logs in as super_admin; additional
 * admin_users log in with their assigned role. Permission checks read the
 * role stored in the session (defaulting to super_admin for backward
 * compatibility with sessions created before roles existed).
 */
class AdminRoles
{
    public const ROLES = [
        'super_admin' => 'Super Admin',
        'manager' => 'Manager',
        'finance' => 'Finance',
        'support' => 'Support',
        'marketing' => 'Marketing',
    ];

    public const PERMISSIONS = [
        'manage_plans' => 'Manage plans',
        'approve_deposits' => 'Approve deposits',
        'approve_withdrawals' => 'Approve withdrawals',
        'wallet_adjust' => 'Adjust wallets',
        'manage_referrals' => 'Manage referral commissions',
        'manage_banners' => 'Manage banners',
        'view_reports' => 'View reports & analytics',
        'manage_settings' => 'Manage settings',
        'manage_roles' => 'Manage roles & admins',
        'manage_backups' => 'Database backups',
    ];

    /** @var array<string, string[]> role => granted permission keys */
    public const MATRIX = [
        'super_admin' => ['manage_plans', 'approve_deposits', 'approve_withdrawals', 'wallet_adjust', 'manage_referrals', 'manage_banners', 'view_reports', 'manage_settings', 'manage_roles', 'manage_backups'],
        'manager' => ['manage_plans', 'approve_deposits', 'approve_withdrawals', 'manage_referrals', 'manage_banners', 'view_reports'],
        'finance' => ['approve_deposits', 'approve_withdrawals', 'wallet_adjust', 'manage_referrals', 'view_reports'],
        'support' => ['view_reports'],
        'marketing' => ['manage_plans', 'manage_banners', 'view_reports'],
    ];

    public static function label(string $role): string
    {
        return self::ROLES[$role] ?? ucfirst($role);
    }

    /** @return string[] */
    public static function permissionsFor(string $role): array
    {
        return self::MATRIX[$role] ?? [];
    }

    public static function can(string $role, string $permission): bool
    {
        return in_array($permission, self::permissionsFor($role), true);
    }

    // Role of the currently-authenticated admin (session-backed).
    public static function current(): string
    {
        $role = session('admin_role');

        return array_key_exists($role, self::ROLES) ? $role : 'super_admin';
    }

    public static function currentCan(string $permission): bool
    {
        return self::can(self::current(), $permission);
    }
}
