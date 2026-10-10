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
use Modules\Newsletter\Models\NewsletterTemplate;
use Modules\Newsletter\Services\Personalizer;

/** E-mail potwierdzający zapis (double opt-in). Temat/treść z MailTemplate `newsletter_confirm`, gdy istnieje. */
class DoubleOptInMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Subscriber $subscriber) {}

    public function envelope(): Envelope
    {
        if ($sys = $this->systemTemplate()) {
            return new Envelope(subject: $this->renderTags((string) ($sys->subject ?: NewsletterTemplate::SYSTEM_MAILS['double_opt_in']['subject'])));
        }
        $tpl = MailTemplate::findBySlug('newsletter_confirm');

        return new Envelope(subject: $tpl ? $tpl->renderSubject($this->templateVars()) : 'Potwierdź zapis na newsletter — ' . SiteSetting::current()->site_name);
    }

    public function content(): Content
    {
        // 1. Szablon systemowy z Mosaico (Newsletter → Szablony → Maile systemowe)
        if ($sys = $this->systemTemplate()) {
            return new Content(htmlString: $this->renderTags((string) $sys->html_body));
        }
        // 2. Szablon maili z panelu (Szablony maili, slug newsletter_confirm)
        $tpl = MailTemplate::findBySlug('newsletter_confirm');
        if ($tpl) {
            return new Content(htmlString: $tpl->renderBody($this->templateVars()));
        }
        // 3. Wbudowany widok
        return new Content(view: 'newsletter::mail.double-opt-in', with: $this->vars());
    }

    private function systemTemplate(): ?NewsletterTemplate
    {
        try {
            $t = NewsletterTemplate::system('double_opt_in');

            return $t && filled($t->html_body) && str_contains((string) $t->html_body, 'confirm_url') ? $t : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function renderTags(string $html): string
    {
        $v = $this->templateVars();
        $vars = ['confirm_url' => $v['confirm_url'], 'first_name' => $v['first_name'], 'email' => e($this->subscriber->email), 'topic_list' => e($v['topics']), 'ttl_days' => (string) $v['ttl_days'],
            'site_name' => e((string) $v['site_name']), 'site_url' => (string) $v['site_url'], 'preferences_url' => $v['preferences_url'], 'unsubscribe_url' => $v['preferences_url'], 'webversion_url' => $v['site_url'], 'name' => e((string) $this->subscriber->name), 'date' => now()->format('d.m.Y'), 'year' => now()->format('Y'), 'campaign_title' => '', 'subject' => ''];

        return app(Personalizer::class)->render($html, $vars);
    }

    private function templateVars(): array
    {
        $v = $this->vars();
        $v['topics'] = implode(', ', $v['topics']);
        unset($v['subscriber']);

        return $v;
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
            'topics'      => $this->subscriber->topicLabels(),
            'ttl_days'    => (int) ($site->newsletter_doi_ttl_days ?: 7),
            'logo_url'    => $site->logoUrl(),
            'tagline'     => $site->tagline,
            'preferences_url' => route('newsletter.preferences', ['token' => $this->subscriber->token]),
        ];
    }
}
