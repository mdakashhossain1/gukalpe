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
 * Notifies the admin by email that a user submitted a deposit (UTR) request
 * awaiting manual review - mirrors the in-app AdminNotification::notify()
 * call fired alongside this from DepositRequestController::store(), just on
 * the email channel. ShouldQueue: dispatched onto the app's existing
 * database queue (QUEUE_CONNECTION=database, already run via `composer run
 * dev`'s queue listener) rather than sent inline during the request, so a
 * slow/unreachable mail server never delays the user's own submission.
 */
class NewDepositRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public DepositRequest $deposit) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New deposit request - ₹{$this->deposit->amount} ({$this->deposit->phone})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.deposit-request',
        );
    }
}
