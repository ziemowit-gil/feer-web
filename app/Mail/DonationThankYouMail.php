<?php

namespace App\Mail;

use App\Models\Donation;
use App\Models\SiteSetting;
use App\Support\FundraisingTexts;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Podziękowanie za wpłatę. Temat, nagłówek, treść, grafika (z ALT), odnośnik
 * i podpis pochodzą z FundraisingTexts (kampania → ustawienia globalne → domyślne).
 */
class DonationThankYouMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Donation $donation) {}

    private function texts(): FundraisingTexts
    {
        return FundraisingTexts::for($this->donation->campaign);
    }

    public function envelope(): Envelope
    {
        $texts = $this->texts();
        $contact = SiteSetting::current()->contact_email;

        return new Envelope(
            subject: $texts->get('email_subject', $texts->vars($this->donation)),
            replyTo: filled($contact) ? [$contact] : [],
        );
    }

    public function content(): Content
    {
        $texts = $this->texts();
        $vars = $texts->vars($this->donation);

        return new Content(
            view: 'emails.donation-thank-you',
            with: [
                'texts' => $texts,
                'vars' => $vars,
                'imageUrl' => $texts->imageUrl('email'),
                'receiptUrl' => route('donation.receipt', $this->donation),
            ],
        );
    }
}
