<?php

declare(strict_types=1);

namespace Modules\Newsletter\Mail;

use App\Models\SiteSetting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Modules\Newsletter\Channels\OutboundMessage;
use Modules\Newsletter\Models\NewsletterCampaign;
use Modules\Newsletter\Models\NewsletterDelivery;

class CampaignMail extends Mailable
{
    public ?string $sentMessageId = null;

    public function __construct(
        public OutboundMessage $message,
        public ?NewsletterCampaign $campaign = null,
        public ?NewsletterDelivery $delivery = null,
    ) {}

    public function envelope(): Envelope
    {
        $site = SiteSetting::current();
        $from = $this->campaign?->from_address ?: $site->newsletter_from_address ?: config('mail.from.address');
        $name = $this->campaign?->from_name ?: $site->newsletter_from_name ?: config('mail.from.name');
        $reply = $this->campaign?->reply_to ?: $site->newsletter_reply_to;

        return new Envelope(
            from: new Address((string) $from, (string) $name),
            replyTo: $reply ? [new Address($reply)] : [],
            subject: $this->message->subject,
        );
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->message->html, text: 'newsletter::mail.text', with: ['body' => $this->message->text]);
    }

    public function headers(): Headers
    {
        return new Headers(text: $this->message->headers);
    }

    public function build()
    {
        return $this->withSymfonyMessage(function (\Symfony\Component\Mime\Email $email) {
            $this->sentMessageId = $email->generateMessageId();
            $email->getHeaders()->addIdHeader('Message-ID', $this->sentMessageId);
        });
    }
}
