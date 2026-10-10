<?php

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Model;

/**
 * Dedykowany, edytowalny w panelu formularz zapisu (treści, pola, tematy,
 * klauzula zgody, podpięcie do list i do formularza SZO).
 */
class NewsletterForm extends Model
{
    protected $table = 'newsletter_forms';

    protected $fillable = [
        'site_id', 'name', 'slug', 'eyebrow', 'heading', 'lead', 'ask_name', 'ask_phone', 'show_topics', 'topics',
        'default_topics', 'offer_webpush', 'offer_sms', 'consent_text', 'clause_id', 'privacy_url', 'button_label',
        'success_message', 'style', 'accent_color', 'theme', 'list_ids', 'szo_form_slug', 'is_default', 'is_active', 'submissions_count',
    ];

    protected $casts = [
        'ask_name' => 'boolean', 'ask_phone' => 'boolean', 'show_topics' => 'boolean', 'offer_webpush' => 'boolean',
        'offer_sms' => 'boolean', 'is_default' => 'boolean', 'is_active' => 'boolean',
        'topics' => 'array', 'default_topics' => 'array', 'list_ids' => 'array', 'theme' => 'array',
    ];

    public const STYLES = ['band' => 'Pasek (szeroki, dwie kolumny)', 'card' => 'Karta', 'inline' => 'Jedna linia (stopka)'];

    /**
     * Kolory elementów formularza: klucz => [etykieta, domyślny kolor, grupa].
     * Każdy kolor jest sprawdzany względem tła zgodnie z WCAG (patrz THEME_CONTRAST).
     */
    public const THEME_COLORS = [
        'bg'       => ['Tło formularza', '#FFFFFF', 'Tło'],
        'text'     => ['Tekst i etykiety', '#1D1D1A', 'Tekst'],
        'muted'    => ['Tekst pomocniczy', '#4A4A47', 'Tekst'],
        'link'     => ['Linki i nadtytuł', '#1752BF', 'Tekst'],
        'error'    => ['Komunikaty błędów', '#B3261E', 'Tekst'],
        'border'   => ['Obramowanie pól, przycisków wyboru', '#8E8E8A', 'Kontrolki'],
        'focus'    => ['Obramowanie aktywnego elementu (fokus)', '#1E6DFF', 'Kontrolki'],
        'chip_on'  => ['Tło zaznaczonego tematu', '#EAF1FF', 'Kontrolki'],
        'btn_bg'   => ['Tło przycisku', '#1752BF', 'Przycisk'],
        'btn_hover' => ['Tło przycisku po najechaniu', '#123F94', 'Przycisk'],
        'btn_text' => ['Tekst przycisku', '#FFFFFF', 'Przycisk'],
        'line'     => ['Ramka karty i zgody (dekoracja)', '#D6DEEA', 'Ramki'],
    ];

    /** Przełączniki ramek i cienia: klucz => [etykieta, domyślnie]. */
    public const THEME_FLAGS = [
        'card_border'  => ['Ramka wokół formularza', false],
        'card_shadow'  => ['Cień formularza', false],
        'consent_box'  => ['Ramka wokół zgody', false],
    ];

    /**
     * Pary kolorów do sprawdzenia: [kolor tekstu/elementu, kolor tła, minimalny kontrast, opis].
     * 4,5:1 — tekst (WCAG 1.4.3); 3:1 — obramowanie i fokus kontrolek (WCAG 1.4.11).
     */
    private const THEME_CONTRAST = [
        ['text', 'bg', 4.5, 'Tekst na tle'],
        ['muted', 'bg', 4.5, 'Tekst pomocniczy na tle'],
        ['link', 'bg', 4.5, 'Linki na tle'],
        ['link', 'chip_on', 4.5, 'Tekst zaznaczonego tematu'],
        ['error', 'bg', 4.5, 'Komunikaty błędów na tle'],
        ['btn_text', 'btn_bg', 4.5, 'Tekst przycisku'],
        ['btn_text', 'btn_hover', 4.5, 'Tekst przycisku po najechaniu'],
        ['border', 'bg', 3.0, 'Obramowanie pól na tle'],
        ['focus', 'bg', 3.0, 'Fokus na tle'],
    ];

    public static function defaultFor(?int $siteId = null): ?static
    {
        if (! static::query()->exists()) {
            // Pierwsze uruchomienie: domyślny formularz, żeby /newsletter działało od razu.
            $form = static::makeDefault();
            $form->save();

            return $form;
        }

        return static::query()
            ->where('is_active', true)
            ->when($siteId, fn ($q) => $q->where(fn ($w) => $w->where('site_id', $siteId)->orWhereNull('site_id')))
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    /** Tematy pokazywane w formularzu: wybrane albo wszystkie dostępne. */
    public function topicOptions(): array
    {
        $all = Subscriber::availableTopics();
        $keys = $this->topics ?: array_keys($all);

        return array_intersect_key($all, array_flip($keys));
    }

    /** Wartości wyglądu z uzupełnionymi domyślnymi (formularz bez zapisanego wyglądu = domyślny). */
    public function themeValues(): array
    {
        $theme = $this->theme ?? [];
        $values = [];
        foreach (self::THEME_COLORS as $key => [$label, $default]) {
            $values[$key] = $theme[$key] ?? $default;
        }
        foreach (self::THEME_FLAGS as $key => [$label, $default]) {
            $values[$key] = (bool) ($theme[$key] ?? $default);
        }

        return $values;
    }

    /**
     * Zamienia wejście z formularza w panelu na wygląd i listę błędów.
     * Kolor niepoprawny albo za słabo kontrastujący z tłem nie zostanie zapisany.
     *
     * @return array{0: array, 1: array<string, string>}
     */
    public static function validateTheme(array $input): array
    {
        $theme = [];
        $errors = [];
        foreach (self::THEME_COLORS as $key => [$label, $default]) {
            $value = strtoupper(trim((string) ($input[$key] ?? '')));
            if ($value === '') {
                $value = $default;
            }
            if (! preg_match('/^#[0-9A-F]{6}$/', $value)) {
                $errors[$key] = "{$label}: podaj kolor w formacie #RRGGBB.";
                continue;
            }
            $theme[$key] = $value;
        }
        foreach (self::THEME_FLAGS as $key => [$label, $default]) {
            $theme[$key] = filter_var($input[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
        }
        if ($errors) {
            return [$theme, $errors];
        }

        foreach (self::THEME_CONTRAST as [$fg, $bg, $min, $what]) {
            $ratio = self::contrastRatio($theme[$fg], $theme[$bg]);
            if ($ratio < $min) {
                $errors[$fg] = sprintf(
                    'Zbyt mały kontrast (%s: %.2f:1, wymagane %.1f:1 wg WCAG). Przyciemnij lub rozjaśnij kolor.',
                    $what,
                    $ratio,
                    $min,
                );
            }
        }

        return [$theme, $errors];
    }

    /** Współczynnik kontrastu WCAG 2.x między dwoma kolorami #RRGGBB (1–21). */
    public static function contrastRatio(string $a, string $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    private static function luminance(string $hex): float
    {
        $channels = array_map(function (string $part): float {
            $c = hexdec($part) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split(ltrim($hex, '#'), 2));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /**
     * Zmienne CSS dla widgetu. Brak wpisu = wartości z szablonu widgetu (te same co dotąd).
     */
    public function themeCss(): string
    {
        $v = $this->themeValues();
        $vars = [
            '--nlw-card' => $v['bg'],
            '--nlw-text' => $v['text'],
            '--nlw-muted' => $v['muted'],
            '--nlw-brand-dark' => $v['link'],
            '--nlw-error' => $v['error'],
            '--nlw-border' => $v['border'],
            '--nlw-brand' => $v['focus'],
            '--nlw-chip-on' => $v['chip_on'],
            '--nlw-btn-bg' => $v['btn_bg'],
            '--nlw-btn-hover' => $v['btn_hover'],
            '--nlw-btn-text' => $v['btn_text'],
            '--nlw-line' => $v['line'],
            '--nlw-card-border' => $v['card_border'] ? '1px solid ' . $v['line'] : '0 none',
            '--nlw-card-shadow' => $v['card_shadow'] ? '0 10px 30px rgba(29,29,26,.07)' : 'none',
            '--nlw-consent-border' => $v['consent_box'] ? '1px solid ' . $v['line'] : '0 none',
            '--nlw-consent-pad' => $v['consent_box'] ? '16px 18px 16px 20px' : '0',
        ];

        return collect($vars)->map(fn ($value, $name) => "{$name}:{$value}")->implode(';') . ';';
    }

    public static function makeDefault(): static
    {
        return new static([
            'name'            => 'Formularz główny',
            'slug'            => 'glowny',
            'eyebrow'         => 'Newsletter FEER',
            'heading'         => 'Bądź na bieżąco z tym, co robimy',
            'lead'            => 'Raz w miesiącu: aktualności, szkolenia, materiały edukacyjne. Bez spamu, wypis jednym kliknięciem.',
            'ask_name'        => true,
            'ask_phone'       => false,
            'show_topics'     => true,
            'topics'          => ['news', 'events', 'materials', 'etr'],
            'default_topics'  => ['news'],
            'consent_text'    => 'Chcę otrzymywać newsletter na podany adres e-mail. Wiem, że mogę się wypisać w każdej chwili.',
            'privacy_url'     => '/polityka-prywatnosci',
            'button_label'    => 'Zapisz się',
            'success_message' => 'Dziękujemy! Sprawdź skrzynkę i kliknij link potwierdzający. Jeśli nie widzisz wiadomości, zajrzyj do folderu spam.',
            'style'           => 'band',
            'is_default'      => true,
            'is_active'       => true,
        ]);
    }
}
