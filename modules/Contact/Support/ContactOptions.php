<?php

declare(strict_types=1);

namespace Modules\Contact\Support;

use App\Models\SiteSetting;

/**
 * Opcje modułu Kontakt zapisane w jednym polu JSON (`site_settings.contact_options`, osobno dla każdej witryny):
 *
 *  - formularz na stronie kontaktu (pokazywać czy nie, zdanie o czasie odpowiedzi),
 *  - które automatyczne podstrony /kontakt/… są w menu mimo danych,
 *  - „Gdzie działamy”: miejsca z opisem, co tam robimy,
 *  - dojazd i dostępność biura, link do mapy, komunikat o zmianie godzin (z datami), „Zapisz kontakt w telefonie”.
 *
 * Gdy moduł jest wyłączony albo kolumny jeszcze nie ma (kod wdrożony przed migracją), `for()` zwraca ustawienia
 * domyślne, czyli stronę kontaktu bez żadnych dodatków.
 */
final class ContactOptions
{
    public const RESPONSE_TIME_DEFAULT = 'Odpowiadamy zwykle w ciągu jednego dnia roboczego.';

    public const PLACES_TITLE_DEFAULT = 'Gdzie działamy';

    public const MAX_PLACES = 30;

    /** @param array<string, mixed> $o */
    private function __construct(private array $o) {}

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'form_enabled' => true,
            'response_time' => self::RESPONSE_TIME_DEFAULT,
            'hidden_sections' => [],
            'places_title' => self::PLACES_TITLE_DEFAULT,
            'places_intro' => '',
            'places' => [],
            'directions' => '',
            'accessibility' => '',
            'map_link' => false,
            'closure_text' => '',
            'closure_from' => null,
            'closure_until' => null,
            'vcard' => false,
        ];
    }

    public static function for(?SiteSetting $settings = null): self
    {
        if (! ContactSections::moduleActive()) {
            return new self(self::defaults());
        }

        $raw = ($settings ?? SiteSetting::current())->getAttribute('contact_options');

        return new self(self::normalize(is_array($raw) ? $raw : []));
    }

    /**
     * Zamienia dowolne dane (z formularza panelu albo z bazy) na pełny, bezpieczny zestaw opcji.
     *
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public static function normalize(array $in): array
    {
        $d = self::defaults();
        $str = fn (string $k, int $max) => mb_substr(trim((string) ($in[$k] ?? $d[$k])), 0, $max);
        $bool = fn (string $k) => array_key_exists($k, $in) ? filter_var($in[$k], FILTER_VALIDATE_BOOLEAN) : $d[$k];
        $date = function (string $k) use ($in) {
            $v = $in[$k] ?? null;

            return is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}/', $v) === 1 ? substr($v, 0, 10) : null;
        };

        $places = [];
        foreach (array_slice(array_values(array_filter((array) ($in['places'] ?? []), 'is_array')), 0, self::MAX_PLACES * 2) as $p) {
            $name = mb_substr(trim((string) ($p['name'] ?? '')), 0, 160);
            if ($name === '') {
                continue;
            }
            $url = trim((string) ($p['url'] ?? ''));
            $places[] = [
                'name' => $name,
                'area' => mb_substr(trim((string) ($p['area'] ?? '')), 0, 160),
                'description' => mb_substr(trim((string) ($p['description'] ?? '')), 0, 1500),
                'address' => mb_substr(trim((string) ($p['address'] ?? '')), 0, 255),
                'url' => preg_match('~^(https?://\S+|/(?!/)\S*)$~i', $url) === 1 ? mb_substr($url, 0, 500) : '',
                'url_label' => mb_substr(trim((string) ($p['url_label'] ?? '')), 0, 80),
            ];
            if (count($places) >= self::MAX_PLACES) {
                break;
            }
        }

        $hidden = array_values(array_intersect(ContactSections::SLUGS, array_map('strval', (array) ($in['hidden_sections'] ?? []))));

        return [
            'form_enabled' => $bool('form_enabled'),
            // Puste pole = bez zdania; brak klucza (nigdy nie zapisane) = zdanie domyślne.
            'response_time' => array_key_exists('response_time', $in) ? $str('response_time', 200) : $d['response_time'],
            'hidden_sections' => $hidden,
            'places_title' => $str('places_title', 80) ?: $d['places_title'],
            'places_intro' => $str('places_intro', 1000),
            'places' => $places,
            'directions' => $str('directions', 2000),
            'accessibility' => $str('accessibility', 2000),
            'map_link' => $bool('map_link'),
            'closure_text' => $str('closure_text', 500),
            'closure_from' => $date('closure_from'),
            'closure_until' => $date('closure_until'),
            'vcard' => $bool('vcard'),
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->o;
    }

    public function formEnabled(): bool
    {
        return (bool) $this->o['form_enabled'];
    }

    /** Zdanie pod nagłówkiem formularza; pusty tekst = nie pokazujemy nic. */
    public function responseTime(): string
    {
        return (string) $this->o['response_time'];
    }

    /** Czy automatyczna podstrona (slug) jest ukryta w ustawieniach modułu. */
    public function sectionHidden(string $slug): bool
    {
        return in_array($slug, $this->o['hidden_sections'], true);
    }

    public function placesTitle(): string
    {
        return (string) $this->o['places_title'];
    }

    public function placesIntro(): string
    {
        return (string) $this->o['places_intro'];
    }

    /** @return array<int, array{name: string, area: string, description: string, address: string, url: string, url_label: string}> */
    public function places(): array
    {
        return $this->o['places'];
    }

    public function directions(): string
    {
        return (string) $this->o['directions'];
    }

    public function accessibility(): string
    {
        return (string) $this->o['accessibility'];
    }

    public function mapLink(): bool
    {
        return (bool) $this->o['map_link'];
    }

    public function vcard(): bool
    {
        return (bool) $this->o['vcard'];
    }

    /** Czy blok „Dojazd i dostępność” ma co pokazać (dojazd, dostępność, link do mapy albo vCard). */
    public function hasVisitInfo(): bool
    {
        return $this->directions() !== '' || $this->accessibility() !== '' || $this->mapLink() || $this->vcard();
    }

    /**
     * Komunikat o zmianie godzin lub nieobecności, gdy dziś mieści się w podanych datach (bez dat: zawsze).
     *
     * @return array{text: string, from: ?string, until: ?string}|null
     */
    public function closure(?\DateTimeInterface $today = null): ?array
    {
        $text = (string) $this->o['closure_text'];
        if ($text === '') {
            return null;
        }
        $day = ($today ?? now())->format('Y-m-d');
        $from = $this->o['closure_from'];
        $until = $this->o['closure_until'];
        if (($from !== null && $day < $from) || ($until !== null && $day > $until)) {
            return null;
        }

        return ['text' => $text, 'from' => $from, 'until' => $until];
    }

    // ── Adresy i vCard ───────────────────────────────────────────────────────

    /** Adres, pod który przychodzą odwiedzający: biuro, a bez osobnego adresu biura — adres rejestrowy. */
    public static function visitAddress(SiteSetting $s): string
    {
        return $s->hasOfficeAddress() ? $s->officeAddressLine() : $s->registeredAddressLine();
    }

    /** Link do mapy OpenStreetMap (bez osadzania i śledzenia): wyszukiwanie po adresie. */
    public static function mapUrl(string $address): ?string
    {
        $address = trim($address);

        return $address === '' ? null : 'https://www.openstreetmap.org/search?query='.rawurlencode($address);
    }

    /** Wizytówka organizacji (vCard 3.0, CRLF) do zapisania w telefonie. */
    public static function vcard3(SiteSetting $s): string
    {
        $esc = fn (string $v): string => str_replace(["\\", "\n", "\r", ',', ';'], ['\\\\', '\\n', '', '\\,', '\\;'], trim($v));

        $street = $esc((string) ($s->hasOfficeAddress() ? $s->contact_office_address : $s->contact_address));
        $cityLine = trim((string) ($s->hasOfficeAddress() ? $s->contact_office_city : $s->contact_city));
        $postal = '';
        $city = $cityLine;
        if (preg_match('/^(\d{2}-\d{3})\s+(.+)$/u', $cityLine, $m) === 1) {
            [$postal, $city] = [$m[1], $m[2]];
        }

        $lines = ['BEGIN:VCARD', 'VERSION:3.0', 'FN:'.$esc((string) $s->site_name), 'ORG:'.$esc((string) $s->site_name)];
        if (filled($s->contact_phone)) {
            $lines[] = 'TEL;TYPE=WORK,VOICE:'.$esc((string) $s->contact_phone);
        }
        if (filled($s->contact_email)) {
            $lines[] = 'EMAIL;TYPE=INTERNET,WORK:'.$esc((string) $s->contact_email);
        }
        if ($street !== '' || $cityLine !== '') {
            $lines[] = 'ADR;TYPE=WORK:;;'.$street.';'.$esc($city).';;'.$esc($postal).';Polska';
        }
        $lines[] = 'URL:'.url('/');
        $lines[] = 'END:VCARD';

        return implode("\r\n", $lines)."\r\n";
    }
}
