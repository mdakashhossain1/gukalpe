<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\DepositRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_above_max_limit_is_rejected(): void
    {
        AppSetting::set('max_deposit_limit', '1000');
        $user = User::factory()->create(['phone' => '9990001111']);

        $this->actingAs($user)
            ->post(route('deposits.store'), [
                'amount' => 5000, 'method' => 'upi', 'utr' => '123456789012',
            ])
            ->assertSessionHasErrors(['amount']);

        $this->assertEquals(0, DepositRequest::count());
    }
}
