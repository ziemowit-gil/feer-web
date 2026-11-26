<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Modules\Newsletter\Channels\ChannelRegistry;
use Modules\Newsletter\Services\Deliverability;
use Modules\Newsletter\Services\SendWindow;
use Modules\Newsletter\Channels\OutboundMessage;
use Modules\Newsletter\Mail\CampaignMail;

/** Panel: ustawienia newslettera (dostawcy, limity, DOI, retencja, SZO/CRM, kod osadzenia). */
class SettingsController extends Controller
{
    public const MAILERS = ['' => 'Domyślny mailer aplikacji (MAIL_MAILER)', 'msgraph' => 'Microsoft Graph', 'ses' => 'Amazon SES', 'mailgun' => 'Mailgun', 'postmark' => 'Postmark', 'smtp' => 'SMTP', 'dpoczta' => 'dPoczta (skrzynka w dhosting.pl)'];

    /** Serwer dPoczty (jak w SZO: userSmtp, DhostingConfig): STARTTLS, login = pełny adres skrzynki. */
    public const DPOCZTA_HOST = 'smtp.dpoczta.pl';

    public const DPOCZTA_PORT = 587;

    public function edit(Request $request, ChannelRegistry $channels, Deliverability $deliverability)
    {
        $site = SiteSetting::current();

        return view('newsletter::admin.settings', [
            'settings' => $site,
            'mailers'  => self::MAILERS,
            'channels' => $channels->all(),
            'szoConfigured' => (bool) config('szo.enabled') && filled(config('szo.url')) && filled(config('szo.token')),
            'vapidConfigured' => filled(config('webpush.vapid.public_key')),
            'sendWindow' => SendWindow::fromSettings($site),
            'dns' => $deliverability->check($site, fresh: $request->boolean('odswiez-dns')),
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
            'newsletter_mautic_url' => ['nullable', 'url', 'max:255'],
            'newsletter_mautic_username' => ['nullable', 'string', 'max:255'],
            'newsletter_mautic_password' => ['nullable', 'string', 'max:255'],
            'newsletter_mautic_segment' => ['nullable', 'integer', 'min:1'],
            'newsletter_code' => ['nullable', 'string'],
            'send_window' => ['nullable', 'array'],
            'send_window.days' => ['nullable', 'array'],
            'send_window.days.*' => ['integer', 'between:1,7'],
            'send_window.from' => ['nullable', 'date_format:H:i'],
            'send_window.to' => ['nullable', 'date_format:H:i'],
        ]);
        // dPoczta: login to adres skrzynki, a hasło musi być zapisane (puste pole = zostaw poprzednie).
        if (($data['newsletter_mailer'] ?? '') === 'dpoczta') {
            $box = trim((string) ($data['newsletter_smtp_username'] ?? ''));
            $errors = [];
            if (! filter_var($box, FILTER_VALIDATE_EMAIL)) {
                $errors['newsletter_smtp_username'] = 'Podaj pełny adres skrzynki w dPoczcie, np. newsletter@feer.org.pl.';
            }
            if (blank($data['newsletter_smtp_password'] ?? null) && blank($site->newsletter_smtp_password)) {
                $errors['newsletter_smtp_password'] = 'Podaj hasło skrzynki w dPoczcie.';
            }
            if ($errors) {
                return back()->withErrors($errors)->withInput();
            }
            $data['newsletter_smtp_username'] = $box;
        }

        // Okno wysyłki: JSON bez rzutowania w modelu (forceFill — kolumna spoza $fillable SiteSetting).
        $window = SendWindow::normalize(['enabled' => $request->boolean('send_window.enabled')] + (array) ($data['send_window'] ?? []));
        unset($data['send_window']);
        $site->forceFill(['newsletter_send_window' => json_encode($window)]);

        foreach (['newsletter_track_opens', 'newsletter_track_clicks', 'newsletter_webpush_enabled', 'newsletter_szo_sync', 'newsletter_crm_sync', 'newsletter_require_approval', 'newsletter_mautic_sync'] as $b) {
            $data[$b] = $request->boolean($b);
        }

        // Puste pole sekretu = nie nadpisuj zapisanej wartości.
        foreach (['newsletter_ses_secret', 'newsletter_mailgun_secret', 'newsletter_postmark_token', 'newsletter_smtp_password', 'newsletter_webhook_secret', 'newsletter_sms_token', 'newsletter_turnstile_secret', 'newsletter_crm_webhook_secret', 'newsletter_ses_key', 'newsletter_mautic_password'] as $secret) {
            if (blank($data[$secret] ?? null)) {
                unset($data[$secret]);
            } elseif ($data[$secret] === '__clear__') {
                $data[$secret] = null;
            }
        }

        // Kolumny Mautic dochodzą migracją 2026_11_27_010000 — przed nią zapis ustawień nie może paść.
        if (! Schema::hasColumn('site_settings', 'newsletter_mautic_sync')) {
            unset($data['newsletter_mautic_sync'], $data['newsletter_mautic_url'], $data['newsletter_mautic_username'], $data['newsletter_mautic_password'], $data['newsletter_mautic_segment']);
        }

        $site->update($data);

        return redirect()->route('admin.newsletter.ustawienia.edit')->with('status', 'Ustawienia newslettera zapisane.');
    }

    /**
     * „Testuj połączenie”: łączy się z serwerem SMTP (także dPoczta i domyślny mailer, jeśli to SMTP) na danych
     * z formularza — przed zapisem — włącza szyfrowanie, loguje się i rozłącza, nic nie wysyłając.
     * Puste hasło = zapisane. Wynik wraca jako flash `connection_test` przy przycisku.
     */
    public function testConnection(Request $request)
    {
        $site = SiteSetting::current();
        $mailer = (string) $request->input('newsletter_mailer', (string) $site->newsletter_mailer);
        $input = fn (string $key) => trim((string) ($request->input($key) ?? ''));
        $password = $input('newsletter_smtp_password') !== '' ? (string) $request->input('newsletter_smtp_password') : (string) $site->newsletter_smtp_password;

        $config = match ($mailer) {
            'dpoczta' => ['transport' => 'smtp', 'host' => self::DPOCZTA_HOST, 'port' => self::DPOCZTA_PORT, 'scheme' => 'smtp',
                'username' => $input('newsletter_smtp_username') ?: (string) $site->newsletter_smtp_username, 'password' => $password],
            'smtp' => ['transport' => 'smtp', 'host' => $input('newsletter_smtp_host') ?: (string) $site->newsletter_smtp_host,
                'port' => (int) ($input('newsletter_smtp_port') ?: ($site->newsletter_smtp_port ?: 587)),
                'scheme' => ($input('newsletter_smtp_encryption') ?: $site->newsletter_smtp_encryption) === 'ssl' ? 'smtps' : 'smtp',
                'username' => $input('newsletter_smtp_username') ?: (string) $site->newsletter_smtp_username, 'password' => $password],
            '' => config('mail.mailers.' . config('mail.default'), []),
            default => null,
        };
        $back = fn (bool $ok, string $message) => back()
            ->withInput($request->except(['newsletter_smtp_password', 'newsletter_ses_secret', 'newsletter_mailgun_secret', 'newsletter_postmark_token', 'newsletter_ses_key', 'newsletter_webhook_secret', 'newsletter_sms_token', 'newsletter_turnstile_secret', 'newsletter_crm_webhook_secret', '_token', '_method']))
            ->with('connection_test', ['ok' => $ok, 'message' => $message]);

        if (! is_array($config) || ($config['transport'] ?? null) !== 'smtp') {
            $label = self::MAILERS[$mailer] ?? $mailer;

            return $back(false, "Dla dostawcy „{$label}” nie ma testu połączenia — wyślij wiadomość testową (formularz poniżej, po zapisaniu ustawień).");
        }
        if (blank($config['host'] ?? null)) {
            return $back(false, 'Podaj adres serwera SMTP.');
        }
        if ($mailer === 'dpoczta' && ! filter_var($config['username'], FILTER_VALIDATE_EMAIL)) {
            return $back(false, 'Podaj pełny adres skrzynki w dPoczcie, np. newsletter@feer.org.pl.');
        }

        $where = $config['host'] . ':' . ($config['port'] ?? 25);
        try {
            /** @var \Symfony\Component\Mailer\Transport\Smtp\SmtpTransport $transport */
            $transport = Mail::createSymfonyTransport($config + ['timeout' => 15]);
            $transport->start();
            $tls = $transport->getStream() instanceof \Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream
                && ($transport->getStream()->isTLS() || ($config['scheme'] ?? '') === 'smtps');
            $transport->stop();
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            $why = match (true) {
                (bool) preg_match('/\b(535|534|530)\b|authenticat|credentials/i', $msg) => 'serwer odrzucił login lub hasło',
                (bool) preg_match('/Connection (could not be established|refused|timed out)|getaddrinfo|timed out/i', $msg) => 'nie udało się połączyć z serwerem (adres, port albo zapora)',
                (bool) preg_match('/TLS|SSL|certificate|crypto/i', $msg) => 'błąd szyfrowania TLS/SSL',
                default => 'błąd serwera',
            };

            return $back(false, "Połączenie z {$where} nie działa: {$why}. Szczegóły: " . mb_strimwidth($msg, 0, 300, '…'));
        }

        $login = filled($config['username'] ?? null) ? ', zalogowano jako ' . $config['username'] : ', bez logowania';

        return $back(true, "Połączenie działa: {$where}" . ($tls ? ', szyfrowane' : ', BEZ szyfrowania') . $login . '. Nic nie zostało wysłane.' . ($request->has('newsletter_mailer') && $mailer !== (string) $site->newsletter_mailer ? ' Pamiętaj, żeby zapisać ustawienia.' : ''));
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
