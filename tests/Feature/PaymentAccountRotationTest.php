<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\PaymentUpiAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentAccountRotationTest extends TestCase
{
    use RefreshDatabase;

    public function test_upi_accounts_rotate_least_recently_used_first(): void
    {
        AppSetting::set('upi_min_amount', '0');
        AppSetting::set('upi_max_amount', '');

        $first = PaymentUpiAccount::create(['upi_id' => 'first@okhdfcbank', 'qr_image' => 'assets/payment-qr/first.png', 'is_active' => true, 'sort_order' => 0]);
        $second = PaymentUpiAccount::create(['upi_id' => 'second@okhdfcbank', 'qr_image' => 'assets/payment-qr/second.png', 'is_active' => true, 'sort_order' => 1]);

        $user = User::factory()->create(['phone' => '9990001212']);

        // First load: both accounts have never been used (last_used_at is
        // null on both) - either could legally come first, but whichever is
        // picked must now have a non-null last_used_at.
        $this->actingAs($user)
            ->withSession(['deposit_amount_prefill' => 500])
            ->get(route('deposits.create'))
            ->assertOk();

        $first->refresh();
        $second->refresh();
        $usedFirst = $first->last_used_at !== null;
        $this->assertTrue($usedFirst xor $second->last_used_at !== null, 'Exactly one account should be marked used after the first load.');

        // Second load must rotate to the OTHER account, not repeat the same
        // one - so after two loads both accounts should now be marked used.
        $this->actingAs($user)
            ->withSession(['deposit_amount_prefill' => 500])
            ->get(route('deposits.create'))
            ->assertOk();

        $first->refresh();
        $second->refresh();
        $this->assertNotNull($first->last_used_at);
        $this->assertNotNull($second->last_used_at);
    }
}
