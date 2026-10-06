<?php

namespace App\Services;

/**
 * Autokonfigurator poczty: na podstawie adresu e-mail rozpoznaje dostawcę
 * skrzynki (po domenie, a dla domen własnych po rekordach MX) i podpowiada
 * ustawienia wysyłki — Microsoft 365 przez Graph, SMTP Google, OVH, Zoho itd.
 *
 * Wynik to wyłącznie podpowiedź do zastosowania w formularzu: nic nie jest
 * zapisywane, a hasła i sekrety zawsze wpisuje administrator.
 */
class MailProviderDetector
{
    /**
     * Dostawcy: klucz => [etykieta, domeny, wzorce hosta MX, ustawienia, uwaga].
     * `settings` to podpowiedź dla trybu „smtp”; `graph` = true oznacza, że
     * zalecany jest transport Microsoft Graph (SMTP AUTH bywa wyłączony w tenancie).
     */
    public const PROVIDERS = [
        'm365' => [
            'label' => 'Microsoft 365 / Exchange Online',
            'domains' => ['outlook.com', 'hotmail.com', 'live.com', 'outlook.pl'],
            'mx' => ['mail.protection.outlook.com', 'olc.protection.outlook.com'],
            'graph' => true,
            'settings' => ['host' => 'smtp.office365.com', 'port' => 587, 'encryption' => 'tls'],
            'note' => 'Zalecany Microsoft Graph (uprawnienie Mail.Send) — nie wymaga SMTP AUTH, który w wielu tenantach jest wyłączony. SMTP: smtp.office365.com, STARTTLS, login = pełny adres skrzynki.',
        ],
        'google' => [
            'label' => 'Google Workspace / Gmail',
            'domains' => ['gmail.com', 'googlemail.com'],
            'mx' => ['aspmx.l.google.com', 'googlemail.com', '.google.com'],
            'graph' => false,
            'settings' => ['host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls'],
            'note' => 'Wymaga włączonej weryfikacji dwuetapowej i hasła aplikacji (zwykłe hasło konta nie zadziała). Login = pełny adres skrzynki.',
        ],
        'ovh' => [
            'label' => 'OVH',
            'domains' => [],
            'mx' => ['mx.ovh.net', '.mail.ovh.net', '.ovh.net'],
            'graph' => false,
            'settings' => ['host' => 'ssl0.ovh.net', 'port' => 465, 'encryption' => 'ssl'],
            'note' => 'Login = pełny adres skrzynki. Dla Exchange w OVH host to ex*.mail.ovh.net — sprawdź w panelu OVH.',
        ],
        'zoho' => [
            'label' => 'Zoho Mail',
            'domains' => ['zoho.com', 'zohomail.eu'],
            'mx' => ['.zoho.eu', '.zoho.com'],
            'graph' => false,
            'settings' => ['host' => 'smtp.zoho.eu', 'port' => 465, 'encryption' => 'ssl'],
            'note' => 'Host zależy od centrum danych konta (smtp.zoho.eu dla Europy, smtp.zoho.com dla USA).',
        ],
        'homepl' => [
            'label' => 'home.pl',
            'domains' => [],
            'mx' => ['.home.pl'],
            'graph' => false,
            'settings' => ['host' => '', 'port' => 587, 'encryption' => 'tls'],
            'note' => 'home.pl używa hosta przypisanego do konta (serwerNNNNNN.home.pl) — znajdziesz go w panelu home.pl w ustawieniach poczty. Login = pełny adres skrzynki.',
        ],
        'nazwapl' => [
            'label' => 'nazwa.pl',
            'domains' => [],
            'mx' => ['.nazwa.pl'],
            'graph' => false,
            'settings' => ['host' => '', 'port' => 587, 'encryption' => 'tls'],
            'note' => 'Host SMTP jest przypisany do konta (np. login.nazwa.pl) — sprawdź w panelu nazwa.pl. Login = pełny adres skrzynki.',
        ],
        'wp' => [
            'label' => 'WP Poczta',
            'domains' => ['wp.pl', 'o2.pl', 'tlen.pl', 'go2.pl'],
            'mx' => ['.wp.pl', '.o2.pl'],
            'graph' => false,
            'settings' => ['host' => 'smtp.wp.pl', 'port' => 465, 'encryption' => 'ssl'],
            'note' => 'Skrzynki prywatne mają dzienne limity wysyłki — do serwisu organizacji lepsza jest poczta firmowa.',
        ],
        'onet' => [
            'label' => 'Onet Poczta',
            'domains' => ['onet.pl', 'onet.eu', 'op.pl', 'vp.pl', 'poczta.onet.pl'],
            'mx' => ['.onet.pl'],
            'graph' => false,
            'settings' => ['host' => 'smtp.poczta.onet.pl', 'port' => 465, 'encryption' => 'ssl'],
            'note' => 'Skrzynki prywatne mają dzienne limity wysyłki — do serwisu organizacji lepsza jest poczta firmowa.',
        ],
    ];

    /** @var callable|null  fn (string $domain): string[]  — lista hostów MX (do podmiany w testach) */
    private $mxResolver;

    public function __construct(?callable $mxResolver = null)
    {
        $this->mxResolver = $mxResolver;
    }

    /**
     * @return array{email: string, domain: string, detected: bool, provider: ?array<string, mixed>, via: string, mx: string[]}
     */
    public function detect(string $email): array
    {
        $email = strtolower(trim($email));
        $domain = substr(strrchr($email, '@') ?: '', 1);

        $base = ['email' => $email, 'domain' => $domain, 'detected' => false, 'provider' => null, 'via' => 'none', 'mx' => []];
        if ($domain === '') {
            return $base;
        }

        foreach (self::PROVIDERS as $key => $provider) {
            if (in_array($domain, $provider['domains'], true)) {
                return array_merge($base, ['detected' => true, 'provider' => $this->payload($key, $email), 'via' => 'domain']);
            }
        }

        $mx = $this->mxHosts($domain);
        $base['mx'] = $mx;

        foreach (self::PROVIDERS as $key => $provider) {
            foreach ($mx as $host) {
                foreach ($provider['mx'] as $pattern) {
                    $match = str_starts_with($pattern, '.') ? str_ends_with($host, $pattern) : ($host === $pattern || str_ends_with($host, '.'.$pattern));
                    if ($match) {
                        return array_merge($base, ['detected' => true, 'provider' => $this->payload($key, $email), 'via' => 'mx']);
                    }
                }
            }
        }

        // Nieznany dostawca: typowa podpowiedź smtp.<domena>:587 (STARTTLS).
        return array_merge($base, ['provider' => [
            'key' => 'custom',
            'label' => 'Własny serwer pocztowy',
            'graph' => false,
            'settings' => ['host' => 'smtp.'.$domain, 'port' => 587, 'encryption' => 'tls', 'username' => $email],
            'note' => 'Nie rozpoznano dostawcy. Podpowiadamy typowe ustawienia — host SMTP i metodę szyfrowania potwierdź u administratora poczty.',
        ]]);
    }

    /** @return array<string, mixed> */
    private function payload(string $key, string $email): array
    {
        $provider = self::PROVIDERS[$key];

        return [
            'key' => $key,
            'label' => $provider['label'],
            'graph' => $provider['graph'],
            'settings' => $provider['settings'] + ['username' => $email],
            'note' => $provider['note'],
        ];
    }

    /** @return string[] znormalizowane (małe litery, bez kropki) hosty MX posortowane wg priorytetu */
    private function mxHosts(string $domain): array
    {
        if ($this->mxResolver) {
            return array_map(fn ($h) => rtrim(strtolower($h), '.'), (array) ($this->mxResolver)($domain));
        }

        $records = @dns_get_record($domain, DNS_MX) ?: [];
        usort($records, fn ($a, $b) => ($a['pri'] ?? 0) <=> ($b['pri'] ?? 0));

        return array_values(array_filter(array_map(fn ($r) => rtrim(strtolower((string) ($r['target'] ?? '')), '.'), $records)));
    }
}
