<?php

declare(strict_types=1);

namespace Modules\Newsletter\Mail;

use App\Models\MailTemplate;
use App\Models\SiteSetting;
use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** E-mail potwierdzający zapis (double opt-in). Temat/treść z MailTemplate `newsletter_confirm`, gdy istnieje. */
class DoubleOptInMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Subscriber $subscriber) {}

    public function envelope(): Envelope
    {
        $tpl = MailTemplate::findBySlug('newsletter_confirm');

        return new Envelope(subject: $tpl ? $tpl->renderSubject($this->vars()) : 'Potwierdź zapis na newsletter — ' . SiteSetting::current()->site_name);
    }

    public function content(): Content
    {
        $tpl = MailTemplate::findBySlug('newsletter_confirm');
        if ($tpl) {
            return new Content(htmlString: $tpl->renderBody($this->vars()));
        }

        return new Content(view: 'newsletter::mail.double-opt-in', with: $this->vars());
    }

    private function vars(): array
    {
        $site = SiteSetting::current();

        return [
            'subscriber'  => $this->subscriber,
            'first_name'  => e($this->subscriber->firstName()),
            'confirm_url' => route('newsletter.confirm', ['token' => $this->subscriber->token]),
            'site_name'   => $site->site_name,
            'site_url'    => config('app.url'),
            'topics'      => implode(', ', $this->subscriber->topicLabels()),
            'ttl_days'    => (int) ($site->newsletter_doi_ttl_days ?: 7),
        ];
    }
}
