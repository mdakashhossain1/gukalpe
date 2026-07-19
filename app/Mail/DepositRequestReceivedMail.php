<?php

namespace App\Mail;

use App\Models\DepositRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * User-facing counterpart to NewDepositRequestMail - confirms their deposit
 * request was received and is under verification. Same reasoning as that
 * class for ShouldQueue (dispatched onto the app's database queue, never
 * sent inline). Only ever dispatched when User::hasRealEmail() is true -
 * see the guard in DepositRequestController::store() - most accounts are
 * phone/OTP signups with a synthetic, non-deliverable placeholder email.
 */
class DepositRequestReceivedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public DepositRequest $deposit) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment received - your GullakPe deposit is under verification',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.deposit-request-received',
        );
    }
}
