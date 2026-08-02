<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Models\WalletBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanStatusTest extends TestCase
{
    use RefreshDatabase;

    private function makePlan(string $status): Plan
    {
        return Plan::create([
            'title' => 'Status Test Plan '.$status,
            'subtitle' => 'Sub',
            'image' => 'assets/plans/test.jpg',
            'icon' => 'bi-piggy-bank',
            'investment_amount' => 500,
            'daily_profit' => 5,
            'total_return' => 600,
            'growth_rate' => 10,
            'lock_duration' => '1 Year',
            'badge' => 'General',
            'status' => $status,
            'is_active' => in_array($status, ['active', 'hidden'], true),
        ]);
    }

    public function test_only_active_status_plans_are_listed_on_explore(): void
    {
        $active = $this->makePlan('active');
        $hidden = $this->makePlan('hidden');
        $draft = $this->makePlan('draft');
        $expired = $this->makePlan('expired');

        $response = $this->get(route('explore'));

        $response->assertSee($active->title);
        $response->assertDontSee($hidden->title);
        $response->assertDontSee($draft->title);
        $response->assertDontSee($expired->title);
    }

    public function test_hidden_plan_still_renders_on_direct_link_and_is_purchasable(): void
    {
        $hidden = $this->makePlan('hidden');
        $user = User::factory()->create(['phone' => '9990005555']);
        WalletBalance::credit('9990005555', 1000, 'add_money');

        $this->get(route('plan-details', $hidden))->assertOk();

        $this->actingAs($user)->post(route('plans.purchase', $hidden, absolute: false), [
            'amount' => 500,
        ])->assertRedirect(route('portfolio'));
    }

    public function test_draft_plan_404s_on_direct_link(): void
    {
        $draft = $this->makePlan('draft');

        $this->get(route('plan-details', $draft))->assertNotFound();
    }

    public function test_expired_plan_renders_but_purchase_is_blocked(): void
    {
        $expired = $this->makePlan('expired');
        $user = User::factory()->create(['phone' => '9990006666']);
        WalletBalance::credit('9990006666', 1000, 'add_money');

        $this->get(route('plan-details', $expired))->assertOk();

        $this->actingAs($user)->post(route('plans.purchase', $expired, absolute: false), [
            'amount' => 500,
        ])->assertSessionHasErrors('plan');
    }
}
