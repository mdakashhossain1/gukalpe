<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Closes a gap found after the initial Admin Audit Log rollout: Plan changes
 * and Admin/Role changes are explicitly listed in the client's spec as
 * required audit categories (item 7) but were never wired into
 * AdminAuditLog::record() - only PlanManagementController/RoleController's
 * existing Log::channel('admin_security') file-log calls covered them.
 */
class PlanAndRoleAuditLogTest extends TestCase
{
    use RefreshDatabase;

    private array $adminSession = ['admin_authenticated' => true, 'admin_role' => 'super_admin'];

    public function test_plan_create_toggle_and_delete_are_audit_logged(): void
    {
        $fields = [
            'title' => 'Audit Test Plan',
            'subtitle' => 'Sub',
            'badge' => 'General',
            'badge_icon' => '',
            'plan_type' => '',
            'status' => 'active',
            'investment_mode' => 'fixed',
            'growth_rate' => 12,
            'term_days' => 90,
            'lock_duration' => '90 Days',
            'investment_amount' => 500,
            'sort_order' => 0,
            'image' => UploadedFile::fake()->image('plan.jpg'),
        ];

        $this->withSession($this->adminSession)->post(route('admin.plans.store'), $fields)->assertRedirect();
        $plan = Plan::where('title', 'Audit Test Plan')->firstOrFail();
        $this->assertNotNull(AdminAuditLog::where('action', 'plan_created')->where('target_id', $plan->id)->first());

        $this->withSession($this->adminSession)->post(route('admin.plans.toggle-active', $plan))->assertRedirect();
        $this->assertNotNull(AdminAuditLog::where('action', 'plan_toggled')->where('target_id', $plan->id)->first());

        $this->withSession($this->adminSession)->post(route('admin.plans.delete', $plan))->assertRedirect();
        $this->assertNotNull(AdminAuditLog::where('action', 'plan_deleted')->first());

        // store() writes the fake upload straight into public/assets/plans
        // (no storage:link involved in this app) - clean it up, same as
        // DepositPaymentScreenshotTest does for its own upload.
        @unlink(public_path($plan->image));
    }

    public function test_admin_user_create_and_toggle_are_audit_logged(): void
    {
        $this->withSession($this->adminSession)
            ->post(route('admin.roles.store'), [
                'name' => 'Audit Test Admin', 'username' => 'audittestadmin',
                'password' => 'password123', 'role' => 'support',
            ])
            ->assertRedirect();

        $adminUser = AdminUser::where('username', 'audittestadmin')->firstOrFail();
        $this->assertNotNull(AdminAuditLog::where('action', 'admin_user_created')->where('target_id', $adminUser->id)->first());

        $this->withSession($this->adminSession)->post(route('admin.roles.toggle-active', $adminUser))->assertRedirect();
        $this->assertNotNull(AdminAuditLog::where('action', 'admin_user_toggled')->where('target_id', $adminUser->id)->first());

        $this->withSession($this->adminSession)->post(route('admin.roles.delete', $adminUser))->assertRedirect();
        $this->assertNotNull(AdminAuditLog::where('action', 'admin_user_deleted')->first());
    }
}
