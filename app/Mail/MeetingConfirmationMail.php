<?php

namespace App\Mail;

use App\Models\MeetingSignup;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MeetingConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MeetingSignup $signup,
        public string $siteName,
        public string $contactEmail,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Potwierdzenie spotkania — {$this->siteName}",
            replyTo: [$this->contactEmail],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.meeting-confirmation',
        );
    }
}
