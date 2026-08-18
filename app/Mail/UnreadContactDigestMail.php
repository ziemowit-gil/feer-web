<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UnreadContactDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Collection $messages,
        public string     $siteName,
    ) {}

    public function envelope(): Envelope
    {
        $count = $this->messages->count();

        return new Envelope(
            subject: "[{$this->siteName}] {$count} nieprzeczytanych wiadomości kontaktowych",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.unread-contact-digest');
    }
}
