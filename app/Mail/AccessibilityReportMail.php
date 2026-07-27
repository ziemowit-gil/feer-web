<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccessibilityReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $senderEmail,
        public string $messageBody,
        public ?string $senderName = null,
        public ?string $pageUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        $who = $this->senderName ?: $this->senderEmail;

        return new Envelope(
            subject: "Zgłoszenie bariery dostępności od {$who}",
            replyTo: [$this->senderEmail],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.accessibility-report',
        );
    }
}
