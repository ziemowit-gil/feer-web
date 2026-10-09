<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Modules\Newsletter\Channels\ChannelRegistry;
use Modules\Newsletter\Channels\OutboundMessage;
use Modules\Newsletter\Mail\CampaignMail;

/** Panel: ustawienia newslettera (dostawcy, limity, DOI, retencja, SZO/CRM, kod osadzenia). */
class SettingsController extends Controller
{
    public const MAILERS = ['' => 'Domyślny mailer aplikacji (MAIL_MAILER)', 'msgraph' => 'Microsoft Graph', 'ses' => 'Amazon SES', 'mailgun' => 'Mailgun', 'postmark' => 'Postmark', 'smtp' => 'SMTP'];

    public function edit(ChannelRegistry $channels)
    {
        $site = SiteSetting::current();

        return view('newsletter::admin.settings', [
            'settings' => $site,
            'mailers'  => self::MAILERS,
            'channels' => $channels->all(),
            'szoConfigured' => (bool) config('szo.enabled') && filled(config('szo.url')) && filled(config('szo.token')),
            'vapidConfigured' => filled(config('webpush.vapid.public_key')),
            'webhookUrls' => collect(['ses', 'mailgun', 'postmark', 'generic'])->mapWithKeys(fn ($p) => [$p => route('newsletter.webhook', ['provider' => $p])]),
        ]);
    }

    public function update(Request $request)
    {
        $site = SiteSetting::current();

        $data = $request->validate([
            'newsletter_mailer' => ['nullable', Rule::in(array_keys(self::MAILERS))],
            'newsletter_from_address' => ['nullable', 'email', 'max:255'],
            'newsletter_from_name' => ['nullable', 'string', 'max:120'],
            'newsletter_reply_to' => ['nullable', 'email', 'max:255'],
            'newsletter_ses_key' => ['nullable', 'string', 'max:255'],
            'newsletter_ses_secret' => ['nullable', 'string', 'max:255'],
            'newsletter_ses_region' => ['nullable', 'string', 'max:32'],
            'newsletter_mailgun_domain' => ['nullable', 'string', 'max:255'],
            'newsletter_mailgun_secret' => ['nullable', 'string', 'max:255'],
            'newsletter_mailgun_endpoint' => ['nullable', 'string', 'max:255'],
            'newsletter_postmark_token' => ['nullable', 'string', 'max:255'],
            'newsletter_smtp_host' => ['nullable', 'string', 'max:255'],
            'newsletter_smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'newsletter_smtp_username' => ['nullable', 'string', 'max:255'],
            'newsletter_smtp_password' => ['nullable', 'string', 'max:255'],
            'newsletter_smtp_encryption' => ['nullable', Rule::in(['tls', 'ssl', 'none'])],
            'newsletter_rate_per_minute' => ['required', 'integer', 'min:1', 'max:10000'],
            'newsletter_rate_per_day' => ['nullable', 'integer', 'min:1'],
            'newsletter_webhook_secret' => ['nullable', 'string', 'max:255'],
            'newsletter_sms_provider' => ['nullable', 'string', 'max:500'],
            'newsletter_sms_token' => ['nullable', 'string', 'max:255'],
            'newsletter_sms_sender' => ['nullable', 'string', 'max:20'],
            'newsletter_turnstile_site_key' => ['nullable', 'string', 'max:255'],
            'newsletter_turnstile_secret' => ['nullable', 'string', 'max:255'],
            'newsletter_doi_ttl_days' => ['required', 'integer', 'min:1', 'max:60'],
            'newsletter_retention_days' => ['required', 'integer', 'min:30', 'max:3650'],
            'newsletter_szo_form' => ['nullable', 'string', 'max:120'],
            'newsletter_crm_webhook_url' => ['nullable', 'url', 'max:500'],
            'newsletter_crm_webhook_secret' => ['nullable', 'string', 'max:255'],
            'newsletter_code' => ['nullable', 'string'],
        ]);

        foreach (['newsletter_track_opens', 'newsletter_track_clicks', 'newsletter_webpush_enabled', 'newsletter_szo_sync', 'newsletter_crm_sync', 'newsletter_require_approval'] as $b) {
            $data[$b] = $request->boolean($b);
        }

        // Puste pole sekretu = nie nadpisuj zapisanej wartości.
        foreach (['newsletter_ses_secret', 'newsletter_mailgun_secret', 'newsletter_postmark_token', 'newsletter_smtp_password', 'newsletter_webhook_secret', 'newsletter_sms_token', 'newsletter_turnstile_secret', 'newsletter_crm_webhook_secret', 'newsletter_ses_key'] as $secret) {
            if (blank($data[$secret] ?? null)) {
                unset($data[$secret]);
            } elseif ($data[$secret] === '__clear__') {
                $data[$secret] = null;
            }
        }

        $site->update($data);

        return redirect()->route('admin.newsletter.ustawienia.edit')->with('status', 'Ustawienia newslettera zapisane.');
    }

    /** Wysyła wiadomość testową wybranym mailerem. */
    public function testMail(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        try {
            $msg = new OutboundMessage('email', 'Test newslettera — ' . SiteSetting::current()->site_name, '<p style="font-family:Montserrat,Arial,sans-serif">To jest wiadomość testowa z modułu Newsletter. Jeśli ją widzisz, konfiguracja dostawcy działa.</p>', 'To jest wiadomość testowa z modułu Newsletter.');
            Mail::mailer('newsletter')->to($data['email'])->send(new CampaignMail($msg));

            return back()->with('status', "Wiadomość testowa wysłana na {$data['email']}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Wysyłka testowa nie powiodła się: ' . $e->getMessage());
        }
    }
}
