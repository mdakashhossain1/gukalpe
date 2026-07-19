<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Daily "your investments grew today" digest, dispatched by the
 * plans:send-daily-returns-email scheduled command. Same ShouldQueue/
 * User::hasRealEmail() reasoning as DepositRequestReceivedMail - queued
 * onto the app's database queue, and only ever sent to accounts with a
 * genuine deliverable address (phone/OTP signups get a synthetic
 * "{phone}@phone.gullakpe.local" placeholder that can't receive mail).
 */
class DailyReturnsMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{title: string, icon: string, amount: float}>  $holdings  one row per plan that accrued today
     */
    public function __construct(
        public User $user,
        public array $holdings,
        public float $totalDailyReturn,
        public float $portfolioValue,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your GullakPe investments grew today - +₹'.number_format($this->totalDailyReturn, 2),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-returns',
        );
    }
}
