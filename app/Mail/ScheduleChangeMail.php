<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScheduleChangeMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{when_label: string, where: string, note: string, is_next: bool}>  $items
     */
    public function __construct(
        public array $items,
        public string $siteName,
        public string $scheduleTitle,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Zmiana terminu spotkań — {$this->siteName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.schedule-change',
        );
    }
}
