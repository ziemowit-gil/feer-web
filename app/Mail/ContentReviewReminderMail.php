<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

/** Cotygodniowe przypomnienie: treści nieaktualizowane od wielu miesięcy (App\Support\ContentReview). */
class ContentReviewReminderMail extends Mailable
{
    use Queueable;

    public function __construct(
        public Collection $rows,
        public string $siteName,
        public int $months,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "[{$this->siteName}] Treści do przejrzenia: {$this->rows->count()}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.content-review-reminder');
    }
}
