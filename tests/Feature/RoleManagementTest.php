<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\WithdrawRequest;
use App\Support\AdminRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_matrix(): void
    {
        $this->assertTrue(AdminRoles::can('super_admin', 'manage_roles'));
        $this->assertFalse(AdminRoles::can('manager', 'manage_roles'));
        $this->assertTrue(AdminRoles::can('manager', 'approve_withdrawals'));
        $this->assertTrue(AdminRoles::can('finance', 'wallet_adjust'));
        $this->assertFalse(AdminRoles::can('marketing', 'approve_withdrawals'));
        $this->assertFalse(AdminRoles::can('support', 'manage_banners'));
    }

    public function test_master_password_logs_in_as_super_admin(): void
    {
        config(['admin.password' => 'master-secret-123']);

        $this->post(route('admin.authenticate'), ['password' => 'master-secret-123'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertTrue((bool) session('admin_authenticated'));
        $this->assertEquals('super_admin', session('admin_role'));
    }

    public function test_named_admin_login_sets_their_role(): void
    {
        AdminUser::create(['name' => 'Meena', 'username' => 'meena', 'password' => 'password123', 'role' => 'finance', 'is_active' => true]);

        $this->post(route('admin.authenticate'), ['username' => 'meena', 'password' => 'password123'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertEquals('finance', session('admin_role'));
    }

    public function test_inactive_admin_cannot_login(): void
    {
        AdminUser::create(['name' => 'Off', 'username' => 'offuser', 'password' => 'password123', 'role' => 'manager', 'is_active' => false]);

        $this->post(route('admin.authenticate'), ['username' => 'offuser', 'password' => 'password123'])
            ->assertSessionHasErrors(['password']);

        $this->assertNull(session('admin_authenticated'));
    }

    public function test_roles_page_requires_manage_roles_permission(): void
    {
        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'support'])
            ->get(route('admin.roles'))->assertForbidden();

        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin'])
            ->get(route('admin.roles'))->assertOk();
    }

    public function test_role_without_permission_cannot_approve_withdrawals(): void
    {
        $wd = WithdrawRequest::create(['phone' => '9990000001', 'amount' => 500, 'payout_upi_id' => 'x@ybank', 'status' => 'pending', 'submitted_at' => now()]);

        // Marketing role lacks approve_withdrawals → 403
        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'marketing'])
            ->post(route('admin.withdrawals.approve', $wd))->assertForbidden();

        $this->assertEquals('pending', $wd->fresh()->status);
    }
}
