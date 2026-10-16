<?php

declare(strict_types=1);

namespace Modules\Newsletter\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Alert o problemie z kolejką (wysyłany synchronicznie — bez ShouldQueue). */
class QueueAlertMail extends Mailable
{
    public function __construct(public array $state, public string $siteName) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "[{$this->siteName}] Kolejka zadań nie działa poprawnie — wysyłki mogą stać");
    }

    public function content(): Content
    {
        return new Content(view: 'newsletter::mail.queue-alert', with: ['state' => $this->state, 'siteName' => $this->siteName, 'url' => route('admin.newsletter.dashboard')]);
    }
}
