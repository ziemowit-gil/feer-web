<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FormDefinition extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'fields',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'fields'    => 'array',
        'settings'  => 'array',
        'is_active' => 'boolean',
    ];

    const FIELD_TYPES = [
        'text'     => 'Tekst (jednoliniowy)',
        'textarea' => 'Tekst (wieloliniowy)',
        'email'    => 'Adres e-mail',
        'tel'      => 'Numer telefonu',
        'number'   => 'Liczba',
        'select'   => 'Lista wyboru',
        'radio'    => 'Wybór jednokrotny (radio)',
        'checkbox' => 'Zgoda / checkbox',
        'date'     => 'Data',
        'checkboxes' => 'Wybór wielokrotny',
        'rating'   => 'Ocena w skali 1–5',
        'url'      => 'Adres strony (www)',
        'time'     => 'Godzina',
        'file'     => 'Załącznik (plik)',
        'consent'  => 'Zgoda RODO (z linkiem do polityki prywatności)',
        'heading'  => 'Nagłówek / opis sekcji',
        'step'     => 'Nowy krok (podział na etapy)',
    ];

    /** Elementy układu — bez wartości (nie walidowane, nie zapisywane, nie w eksporcie). */
    const LAYOUT_TYPES = ['heading', 'step'];

    /** Domyślne typy plików i rozmiar (MB) dla pola „Załącznik”. */
    const FILE_DEFAULT_ACCEPT = 'pdf,doc,docx,odt,jpg,jpeg,png';
    const FILE_MAX_MB = 20;

    public static function isInput(string $type): bool
    {
        return ! in_array($type, self::LAYOUT_TYPES, true);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function unreadSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class)->whereNull('read_at');
    }

    /** Normalizuje pola: generuje klucze ze słownikową de-duplikacją. */
    public function normalizedFields(): array
    {
        $seen   = [];
        $result = [];

        foreach ($this->fields ?? [] as $i => $field) {
            // Elementy układu dostają własny klucz poza puli — nie przesuwają kluczy pól z danymi.
            if (! self::isInput($field['type'] ?? 'text')) {
                $result[] = array_merge($field, ['key' => '_layout_'.$i]);

                continue;
            }

            $key = Str::slug($field['label'] ?? '', '_') ?: 'pole';

            if (isset($seen[$key])) {
                $seen[$key]++;
                $key .= '_' . $seen[$key];
            } else {
                $seen[$key] = 1;
            }

            $result[] = array_merge($field, ['key' => $key]);
        }

        return $result;
    }

    /** Pola z wartościami (bez nagłówków i innych elementów układu). */
    public function inputFields(): array
    {
        return array_values(array_filter($this->normalizedFields(), fn ($f) => self::isInput($f['type'] ?? 'text')));
    }

    /** Opcje pola wyboru (lista, radio, wielokrotny wybór) — rozdzielone przecinkami. */
    public static function fieldOptions(array $field): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) ($field['options'] ?? ''))), 'filled'));
    }

    /** Dozwolone rozszerzenia pola „Załącznik”. */
    public static function fileAccept(array $field): array
    {
        $list = array_filter(array_map(fn ($e) => strtolower(trim($e, " .\t")), explode(',', (string) ($field['accept'] ?? ''))));

        return $list ?: explode(',', self::FILE_DEFAULT_ACCEPT);
    }

    /** Maksymalny rozmiar pliku (MB) pola „Załącznik”. */
    public static function fileMaxMb(array $field): int
    {
        return max(1, min(self::FILE_MAX_MB, (int) ($field['max_mb'] ?? 0) ?: 10));
    }

    /**
     * Czy pole jest widoczne przy danych odpowiedziach — warunek „Pokaż tylko, gdy…”.
     * Pole jest ukryte, gdy warunek nie jest spełniony albo ukryte jest pole, od którego zależy.
     * Puste „równa się” = gdy pole źródłowe jest wypełnione; przy wielokrotnym wyborze — „zawiera”.
     *
     * @param  array<string, mixed>  $data  odpowiedzi (klucze jak `key` pól)
     * @return array<string, bool>  [key => widoczne]
     */
    public function visibility(array $data): array
    {
        $fields = $this->normalizedFields();
        $byUid = [];
        foreach ($fields as $f) {
            if (filled($f['uid'] ?? null)) {
                $byUid[$f['uid']] = $f;
            }
        }

        $visible = [];
        foreach ($fields as $f) {
            $target = $byUid[$f['show_if_uid'] ?? ''] ?? null;
            if (! $target || ($target['key'] ?? null) === $f['key']) {
                $visible[$f['key']] = true;

                continue;
            }
            // Pole zależy tylko od wcześniejszych pól (już ocenionych); ukryte źródło = ukryte pole.
            if (! ($visible[$target['key']] ?? false)) {
                $visible[$f['key']] = false;

                continue;
            }
            $value = $data[$target['key']] ?? null;
            $want = trim((string) ($f['show_if_value'] ?? ''));
            $visible[$f['key']] = match (true) {
                $want === '' => ! ($value === null || $value === '' || $value === []),
                is_array($value) => in_array($want, array_map('strval', $value), true),
                default => (string) $value === $want,
            };
        }

        return $visible;
    }

    /** Dynamiczne reguły walidacji dla zgłoszenia (pola ukryte warunkiem są pomijane). */
    public function validationRules(?array $data = null): array
    {
        $rules = [];
        $visible = $data === null ? null : $this->visibility($data);

        foreach ($this->inputFields() as $field) {
            if ($visible !== null && ! ($visible[$field['key']] ?? true)) {
                continue;
            }

            $required = ($field['required'] ?? false) ? 'required' : 'nullable';
            $type     = $field['type'] ?? 'text';
            $options  = self::fieldOptions($field);

            $fieldRules = match ($type) {
                'email'    => [$required, 'email', 'max:255'],
                'tel'      => [$required, 'string', 'max:30'],
                'number'   => [$required, 'numeric'],
                'date'     => [$required, 'date'],
                'select',
                'radio'    => array_merge([$required, 'string', 'max:255'], $options ? [Rule::in($options)] : []),
                'checkbox',
                'consent'  => [$required, 'accepted'],
                'checkboxes' => [$required, 'array'],
                'rating'   => [$required, 'integer', 'between:1,5'],
                'url'      => [$required, 'url', 'max:500'],
                'time'     => [$required, 'date_format:H:i'],
                'file'     => [$required, 'file', 'max:'.(self::fileMaxMb($field) * 1024), 'extensions:'.implode(',', self::fileAccept($field)), 'mimes:'.implode(',', self::fileAccept($field))],
                default    => [$required, 'string', 'max:5000'],
            };

            $rules['data.' . $field['key']] = $fieldRules;

            if ($type === 'checkboxes') {
                $rules['data.' . $field['key'] . '.*'] = array_merge(['string'], $options ? [Rule::in($options)] : []);
            }
        }

        return $rules;
    }

    /**
     * Wartość pola jako tekst (e-mail, eksport, panel): listy po przecinku, zgody „Tak”,
     * ocena „4/5”, plik — nazwa i rozmiar.
     */
    public static function displayValue(array $field, mixed $value): string
    {
        $type = $field['type'] ?? 'text';

        if ($value === null || $value === '' || $value === []) {
            return '';
        }

        return match (true) {
            $type === 'file' && is_array($value) => trim(($value['name'] ?? 'plik').(isset($value['size']) ? ' ('.self::humanSize((int) $value['size']).')' : '')),
            in_array($type, ['checkbox', 'consent'], true) => 'Tak',
            $type === 'rating' => $value.'/5',
            is_array($value) => implode(', ', array_map('strval', $value)),
            default => (string) $value,
        };
    }

    /**
     * Czy formularz przyjmuje zgłoszenia: termin od–do i limit zgłoszeń (Ustawienia → Terminy i limity).
     *
     * @return array{open: bool, reason: ?string, message: string, remaining: ?int}
     */
    public function availability(): array
    {
        $s = $this->settings ?? [];
        $tz = config('app.timezone');
        $from = filled($s['open_from'] ?? null) ? \Illuminate\Support\Carbon::parse($s['open_from'], $tz) : null;
        $until = filled($s['open_until'] ?? null) ? \Illuminate\Support\Carbon::parse($s['open_until'], $tz) : null;
        $max = (int) ($s['max_submissions'] ?? 0);
        $remaining = $max > 0 ? max(0, $max - $this->submissions()->count()) : null;
        $custom = trim((string) ($s['closed_message'] ?? ''));
        $fmt = fn ($d) => $d->locale('pl')->translatedFormat('j F Y, H:i');

        [$reason, $default] = match (true) {
            $from && now()->lt($from) => ['not_yet', 'Zgłoszenia ruszają '.$fmt($from).'. Zapraszamy!'],
            $until && now()->gt($until) => ['ended', 'Zbieranie zgłoszeń zakończyło się '.$fmt($until).'.'],
            $remaining === 0 => ['full', 'Limit zgłoszeń został wyczerpany. Dziękujemy za zainteresowanie!'],
            default => [null, ''],
        };

        return [
            'open' => $reason === null,
            'reason' => $reason,
            // Własny komunikat dotyczy zakończenia i wyczerpania limitu; „jeszcze nie ruszyło” zawsze podaje datę.
            'message' => $reason && $reason !== 'not_yet' && $custom !== '' ? $custom : $default,
            'remaining' => $remaining,
            'until' => $until,
        ];
    }

    public static function humanSize(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 1).' MB' : max(1, (int) round($bytes / 1024)).' kB';
    }

    /** Komunikaty walidacji zrozumiałe dla osoby wypełniającej (zamiast technicznych formatów). */
    public function validationMessages(): array
    {
        $messages = [];

        foreach ($this->inputFields() as $field) {
            $k = 'data.' . $field['key'];
            match ($field['type'] ?? 'text') {
                'time' => $messages[$k . '.date_format'] = 'Podaj godzinę w formacie GG:MM, np. 09:30.',
                'url' => $messages[$k . '.url'] = 'Podaj pełny adres strony, np. https://przyklad.pl.',
                'checkboxes' => $messages[$k . '.*.in'] = 'Wybierz odpowiedzi z listy w polu „' . ($field['label'] ?? '') . '”.',
                'rating' => $messages[$k . '.between'] = 'Wybierz ocenę od 1 do 5.',
                default => null,
            };
        }

        return $messages;
    }

    public function validationAttributes(): array
    {
        $attrs = [];

        foreach ($this->inputFields() as $field) {
            $attrs['data.' . $field['key']] = $field['label'] ?? $field['key'];
            $attrs['data.' . $field['key'] . '.*'] = $field['label'] ?? $field['key'];
        }

        return $attrs;
    }
}
