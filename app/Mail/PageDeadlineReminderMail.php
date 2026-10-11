<?php

namespace App\Mail;

use App\Models\Page;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Przypomnienie dla redakcji o terminie naboru / zapytania ofertowego
 * (komenda strony:przypomnienia). $kind: rec_end_soon | rec_results |
 * tender_deadline_soon | tender_result.
 */
class PageDeadlineReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Page $page,
        public string $kind,
        public string $headline,
        public string $body,
        public string $editUrl,
        public string $publicUrl,
        public ?string $siteName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->headline.': '.$this->page->title);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.page-deadline-reminder');
    }
}
