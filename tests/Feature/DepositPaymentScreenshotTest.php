<?php

namespace Tests\Feature;

use App\Models\DepositRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DepositPaymentScreenshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_can_be_submitted_without_a_screenshot(): void
    {
        $user = User::factory()->create(['phone' => '9990002020']);

        $this->actingAs($user)
            ->post(route('deposits.store'), [
                'amount' => 500, 'method' => 'upi', 'utr' => '111122223333',
            ])
            ->assertSessionHasNoErrors();

        $deposit = DepositRequest::where('phone', '9990002020')->first();
        $this->assertNotNull($deposit);
        $this->assertNull($deposit->payment_screenshot);
        $this->assertNull($deposit->paymentScreenshotUrl());
    }

    public function test_deposit_screenshot_is_stored_and_visible_to_admin_before_approval(): void
    {
        $user = User::factory()->create(['phone' => '9990002021']);
        $file = UploadedFile::fake()->image('proof.jpg');

        $this->actingAs($user)
            ->post(route('deposits.store'), [
                'amount' => 500, 'method' => 'upi', 'utr' => '444455556666',
                'payment_screenshot' => $file,
            ])
            ->assertSessionHasNoErrors();

        $deposit = DepositRequest::where('phone', '9990002021')->first();
        $this->assertNotNull($deposit->payment_screenshot);
        $this->assertStringStartsWith('assets/deposit-proofs/', $deposit->payment_screenshot);
        $this->assertFileExists(public_path($deposit->payment_screenshot));
        $this->assertNotNull($deposit->paymentScreenshotUrl());

        $this->withSession(['admin_authenticated' => true, 'admin_role' => 'super_admin'])
            ->get(route('admin.deposits'))
            ->assertOk()
            ->assertSee($deposit->paymentScreenshotUrl(), false);

        // Cleanup the file this test wrote to public/assets/deposit-proofs.
        @unlink(public_path($deposit->payment_screenshot));
    }
}
