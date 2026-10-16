<?php

declare(strict_types=1);

namespace Modules\Newsletter\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Sprawdzenie DNS domeny nadawcy: SPF, DKIM (typowe selektory), DMARC — tylko odczyt.
 * Wynik w cache na 10 minut (odświeżenie: ?odswiez-dns=1 w ustawieniach).
 */
class Deliverability
{
    public const DKIM_SELECTORS = ['default', 'selector1', 'selector2', 'google', 'mail', 'dkim', 's1', 's2', 'k1', 'mailgun', 'mg', 'pm', 'amazonses', 'mailjet', 'smtp', 'x'];

    public function domainFor(?SiteSetting $site = null): string
    {
        $site ??= SiteSetting::current();
        $from = (string) ($site->newsletter_from_address ?: config('mail.from.address'));

        return strtolower((string) substr(strrchr($from, '@') ?: '', 1));
    }

    /**
     * @return array{domain: string, spf: ?string, dkim: ?string, dmarc: ?string, spf_ok: bool, dmarc_ok: bool, dmarc_policy: ?string, advice: list<string>}
     */
    public function check(?SiteSetting $site = null, bool $fresh = false): array
    {
        $domain = $this->domainFor($site);
        if ($domain === '' || in_array($domain, ['example.com', 'localhost'], true)) {
            return ['domain' => $domain, 'spf' => null, 'dkim' => null, 'dmarc' => null, 'spf_ok' => false, 'dmarc_ok' => false, 'dmarc_policy' => null, 'advice' => ['Ustaw adres nadawcy newslettera w swojej domenie.']];
        }
        $key = 'newsletter:dns:' . $domain;
        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, now()->addMinutes(10), fn () => $this->lookup($domain));
    }

    private function lookup(string $domain): array
    {
        $txt = fn (string $host) => collect(@dns_get_record($host, DNS_TXT) ?: [])->pluck('txt')->map(fn ($t) => (string) $t)->all();
        $spf   = collect($txt($domain))->first(fn ($t) => str_starts_with(strtolower($t), 'v=spf1'));
        $dmarc = collect($txt('_dmarc.' . $domain))->first(fn ($t) => str_starts_with(strtoupper($t), 'V=DMARC1'));
        $dkim  = null;
        foreach (self::DKIM_SELECTORS as $sel) {
            if (@dns_get_record($sel . '._domainkey.' . $domain, DNS_TXT | DNS_CNAME)) {
                $dkim = $sel;
                break;
            }
        }

        $policy = $dmarc && preg_match('/\bp\s*=\s*(none|quarantine|reject)/i', $dmarc, $m) ? strtolower($m[1]) : null;
        $advice = [];
        if (! $spf) {
            $advice[] = 'Brak rekordu SPF — dodaj w DNS rekord TXT „v=spf1 …” z serwerami, które wysyłają pocztę w imieniu domeny.';
        } elseif (preg_match('/\+all\b/i', $spf)) {
            $advice[] = 'SPF kończy się „+all” — każdy może wysyłać w imieniu domeny. Zmień na „~all” albo „-all”.';
        }
        if (! $dkim) {
            $advice[] = 'Nie znaleziono DKIM pod typowymi selektorami — sprawdź u dostawcy poczty, czy podpis DKIM jest włączony (selektor może mieć inną nazwę).';
        }
        if (! $dmarc) {
            $advice[] = 'Brak DMARC — Gmail i Yahoo wymagają go od nadawców masowych. Minimum: TXT „_dmarc” z „v=DMARC1; p=none; rua=mailto:…”.';
        } elseif ($policy === 'none') {
            $advice[] = 'DMARC ma politykę „p=none” (tylko monitorowanie) — wystarcza do wysyłki, docelowo warto przejść na „quarantine”.';
        }

        return [
            'domain' => $domain, 'spf' => $spf, 'dkim' => $dkim, 'dmarc' => $dmarc,
            'spf_ok' => (bool) $spf && ! preg_match('/\+all\b/i', (string) $spf), 'dmarc_ok' => (bool) $dmarc, 'dmarc_policy' => $policy,
            'advice' => $advice,
        ];
    }
}
