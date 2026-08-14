<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Basic "does it render without error" coverage for the pages added while
 * implementing the client's Admin Panel change requests - catches Blade
 * syntax errors and missing view variables that narrower feature tests
 * elsewhere wouldn't necessarily hit.
 */
class AdminNewPagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    private array $adminSession = ['admin_authenticated' => true, 'admin_role' => 'super_admin'];

    public function test_withdrawal_settings_page_renders(): void
    {
        $this->withSession($this->adminSession)
            ->get(route('admin.withdrawal-settings'))
            ->assertOk()
            ->assertSee('Withdrawal settings');
    }

    public function test_payment_gateway_page_renders_with_reorder_controls(): void
    {
        $this->withSession($this->adminSession)
            ->get(route('admin.payment-gateway'))
            ->assertOk();
    }

    public function test_banners_index_renders_with_preview_and_duplicate(): void
    {
        $this->withSession($this->adminSession)
            ->get(route('admin.banners'))
            ->assertOk();
    }

    public function test_push_notification_page_renders_with_history(): void
    {
        $this->withSession($this->adminSession)
            ->get(route('admin.push-notification'))
            ->assertOk()
            ->assertSee('Send history');
    }
}
