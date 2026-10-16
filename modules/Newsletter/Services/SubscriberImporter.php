<?php

declare(strict_types=1);

namespace Modules\Newsletter\Services;

use App\Models\Subscriber;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Newsletter\Jobs\SendDoubleOptIn;
use Modules\Newsletter\Models\NewsletterConsent;
use Modules\Newsletter\Models\NewsletterImport;
use Modules\Newsletter\Models\NewsletterSuppression;
use RuntimeException;

/**
 * Import subskrybentów: odczyt pliku (CSV/JSON, także eksport MailerLite) z mapowaniem
 * kolumn, normalizacja rekordów z API MailerLite, podgląd i zapis pojedynczego rekordu.
 *
 * Import jest idempotentny po adresie e-mail (ślepy indeks `email_hash`): ponowny import
 * aktualizuje rekord, nie dubluje go, a zgody z importu powstają tylko raz.
 */
class SubscriberImporter
{
    public const DISK = 'local';

    /** Pola, na które mapujemy kolumny pliku: klucz => etykieta. */
    public const FIELDS = [
        'email' => 'E-mail (wymagane)',
        'name' => 'Imię',
        'last_name' => 'Nazwisko',
        'phone' => 'Telefon',
        'status' => 'Status',
        'subscribed_at' => 'Data zapisu',
        'ip' => 'IP zapisu',
        'groups' => 'Grupy',
        'topics' => 'Tematy',
        'unsubscribed_at' => 'Data wypisu',
    ];

    /** Nazwy kolumn rozpoznawane automatycznie (po normalizacji: małe litery, bez spacji, „_” i „-”). */
    private const SYNONYMS = [
        'email' => ['email', 'emailaddress', 'adresemail', 'subscriber', 'mail', 'adres'],
        'name' => ['name', 'firstname', 'imie', 'imię', 'first'],
        'last_name' => ['lastname', 'surname', 'nazwisko', 'last'],
        'phone' => ['phone', 'phonenumber', 'telefon', 'tel'],
        'status' => ['status', 'stan', 'subscriberstatus'],
        'subscribed_at' => ['optedinat', 'optedin', 'subscribedat', 'subscribed', 'datazapisu', 'signupdate', 'dateadded', 'createdat', 'created'],
        'ip' => ['optinip', 'signupip', 'subscribedip', 'ipaddress', 'ip', 'adresip'],
        'groups' => ['groups', 'group', 'grupy', 'grupa', 'listy'],
        'topics' => ['topics', 'tematy'],
        'unsubscribed_at' => ['unsubscribedat', 'unsubscribed', 'datawypisu'],
    ];

    /** Statusy źródłowe (MailerLite, polskie, lokalne) => status subskrybenta. */
    private const STATUS_MAP = [
        'active' => Subscriber::STATUS_CONFIRMED, 'subscribed' => Subscriber::STATUS_CONFIRMED, 'confirmed' => Subscriber::STATUS_CONFIRMED, 'aktywny' => Subscriber::STATUS_CONFIRMED,
        'unconfirmed' => Subscriber::STATUS_PENDING, 'pending' => Subscriber::STATUS_PENDING, 'oczekujacy' => Subscriber::STATUS_PENDING, 'oczekujący' => Subscriber::STATUS_PENDING,
        'unsubscribed' => Subscriber::STATUS_UNSUBSCRIBED, 'wypisany' => Subscriber::STATUS_UNSUBSCRIBED,
        'bounced' => Subscriber::STATUS_BOUNCED, 'odbity' => Subscriber::STATUS_BOUNCED,
        'junk' => Subscriber::STATUS_COMPLAINED, 'spam' => Subscriber::STATUS_COMPLAINED, 'complained' => Subscriber::STATUS_COMPLAINED,
    ];

    /** Statusy, które blokują wysyłkę — trafiają też na listę tłumienia. */
    public const BLOCKING = [Subscriber::STATUS_UNSUBSCRIBED, Subscriber::STATUS_BOUNCED, Subscriber::STATUS_COMPLAINED];

    // ── Plik ────────────────────────────────────────────────────────────────

    /** Zapisuje plik poza katalogiem publicznym i odczytuje nagłówek. */
    public function storeFile(UploadedFile $file, NewsletterImport $import): void
    {
        $ext = strtolower($file->getClientOriginalExtension()) === 'json' ? 'json' : 'csv';
        $path = $file->storeAs('newsletter-imports', $import->id.'-'.bin2hex(random_bytes(8)).'.'.$ext, self::DISK);

        $import->forceFill(['file_path' => $path, 'file_name' => mb_substr($file->getClientOriginalName(), 0, 255)]);
        [$columns, $hasHeader] = $this->readHeader($import);
        $mapping = $this->guessMapping($columns, $hasHeader);

        $import->forceFill([
            'columns' => $columns,
            'mapping' => $mapping,
            'options' => array_merge($import->options ?? [], ['has_header' => $hasHeader]),
        ]);
    }

    public function deleteFile(NewsletterImport $import): void
    {
        if ($import->file_path) {
            Storage::disk(self::DISK)->delete($import->file_path);
        }
    }

    /** @return array{0: array<int, string>, 1: bool} kolumny i czy plik ma nagłówek */
    public function readHeader(NewsletterImport $import): array
    {
        if ($this->isJson($import)) {
            $keys = [];
            foreach (array_slice($this->jsonRows($import), 0, 50) as $row) {
                $keys = array_merge($keys, array_keys($row));
            }

            return [array_values(array_unique($keys)), true];
        }

        $h = $this->openCsv($import);
        $first = fgetcsv($h['handle'], null, $h['delimiter'], '"', '');
        fclose($h['handle']);
        if (! is_array($first)) {
            throw new RuntimeException('Plik jest pusty albo nie jest poprawnym CSV.');
        }
        $first = array_map(fn ($c) => trim((string) $c), $first);
        $hasHeader = ! collect($first)->contains(fn ($c) => filter_var($c, FILTER_VALIDATE_EMAIL));

        return [$hasHeader ? $first : array_map(fn ($i) => 'Kolumna '.($i + 1), array_keys($first)), $hasHeader];
    }

    /** @return array<string, int> pole => indeks kolumny */
    public function guessMapping(array $columns, bool $hasHeader = true): array
    {
        if (! $hasHeader) {
            return array_filter(['email' => 0, 'name' => count($columns) > 1 ? 1 : null], fn ($v) => $v !== null);
        }

        $normalized = array_map(fn ($c) => preg_replace('/[\s_\-.]+/u', '', mb_strtolower((string) $c)), $columns);
        $mapping = [];
        foreach (self::SYNONYMS as $field => $names) {
            foreach ($names as $name) {
                $idx = array_search($name, $normalized, true);
                if ($idx !== false && ! in_array($idx, $mapping, true)) {
                    $mapping[$field] = (int) $idx;
                    break;
                }
            }
        }

        return $mapping;
    }

    /**
     * Wiersze pliku od pozycji `$offset` (bez nagłówka), już po mapowaniu kolumn.
     *
     * @return \Generator<int, array> numer wiersza danych (od 1) => rekord kanoniczny
     */
    public function fileRows(NewsletterImport $import, int $offset = 0, ?int $limit = null): \Generator
    {
        $mapping = $import->mapping ?? [];
        $n = 0;

        if ($this->isJson($import)) {
            $columns = $import->columns ?? [];
            foreach (array_slice($this->jsonRows($import), $offset, $limit) as $i => $row) {
                $cells = array_map(fn ($c) => is_array($c) ? implode(',', $c) : $c, array_map(fn ($col) => $row[$col] ?? null, $columns));
                yield $offset + $i + 1 => $this->canonical($cells, $mapping);
            }

            return;
        }

        $h = $this->openCsv($import);
        $line = 0;
        while (($cells = fgetcsv($h['handle'], null, $h['delimiter'], '"', '')) !== false) {
            if ($line++ === 0 && $import->option('has_header', true)) {
                continue;
            }
            if ($cells === [null] || $cells === []) {
                continue;
            }
            $n++;
            if ($n <= $offset) {
                continue;
            }
            if ($limit !== null && $n > $offset + $limit) {
                break;
            }
            yield $n => $this->canonical($cells, $mapping);
        }
        fclose($h['handle']);
    }

    /** Rekord z API MailerLite → rekord kanoniczny. */
    public function fromMailerLite(array $ml, array $fieldMap = []): array
    {
        $fields = $ml['fields'] ?? [];
        $groups = [];
        foreach ($ml['groups'] ?? [] as $g) {
            $groups[(string) $g['id']] = (string) ($g['name'] ?? $g['id']);
        }

        return [
            'email' => (string) ($ml['email'] ?? ''),
            'name' => $fields[$fieldMap['name'] ?? 'name'] ?? null,
            'last_name' => $fields[$fieldMap['last_name'] ?? 'last_name'] ?? null,
            'phone' => $fields[$fieldMap['phone'] ?? 'phone'] ?? null,
            'status' => $ml['status'] ?? null,
            'subscribed_at' => $ml['opted_in_at'] ?? $ml['subscribed_at'] ?? $ml['created_at'] ?? null,
            'ip' => $ml['optin_ip'] ?? $ml['ip_address'] ?? null,
            'groups' => $groups,
            'topics' => [],
            'unsubscribed_at' => $ml['unsubscribed_at'] ?? null,
        ];
    }

    // ── Podgląd ─────────────────────────────────────────────────────────────

    /** Podgląd pliku: liczby wg statusu docelowego, grupy, błędne adresy, duplikaty, rekordy już w bazie. */
    public function previewFile(NewsletterImport $import): array
    {
        $byStatus = [];
        $groups = [];
        $invalid = 0;
        $seen = [];
        $samples = [];
        $default = (string) $import->option('default_status', Subscriber::STATUS_PENDING);

        foreach ($this->fileRows($import) as $n => $row) {
            if (count($samples) < 5) {
                $samples[] = $row;
            }
            $email = Subscriber::normalizeEmail((string) $row['email']);
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalid++;

                continue;
            }
            $hash = Subscriber::hashEmail($email);
            $seen[$hash] = ($seen[$hash] ?? 0) + 1;
            $status = $this->mapStatus($row['status'], $default);
            $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;
            foreach ($row['groups'] as $key => $label) {
                $groups[$key] ??= ['key' => (string) $key, 'name' => $label, 'count' => 0];
                $groups[$key]['count']++;
            }
        }

        $existing = 0;
        foreach (array_chunk(array_keys($seen), 1000) as $chunk) {
            $existing += Subscriber::whereIn('email_hash', $chunk)->count();
        }

        return [
            'total' => array_sum($byStatus) + $invalid,
            'by_status' => $byStatus,
            'groups' => array_values($groups),
            'invalid' => $invalid,
            'duplicates' => array_sum($seen) - count($seen),
            'existing' => $existing,
            'samples' => $samples,
        ];
    }

    // ── Zapis ───────────────────────────────────────────────────────────────

    public function mapStatus(mixed $raw, string $default = Subscriber::STATUS_PENDING): string
    {
        $key = mb_strtolower(trim((string) $raw));

        return self::STATUS_MAP[$key] ?? $default;
    }

    /**
     * Zapisuje jeden rekord. Zwraca `created`, `updated` albo `skipped:<powód>`.
     *
     * @throws RuntimeException przy błędnym rekordzie (np. adresie)
     */
    public function importRow(NewsletterImport $import, array $row): string
    {
        $email = Subscriber::normalizeEmail((string) ($row['email'] ?? ''));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('niepoprawny adres e-mail „'.mb_substr($email, 0, 80).'”');
        }

        $mailerLite = $import->isMailerLite();
        $target = $this->mapStatus($row['status'] ?? null, (string) $import->option('default_status', Subscriber::STATUS_PENDING));
        $hash = Subscriber::hashEmail($email);
        $s = Subscriber::where('email_hash', $hash)->first();
        $old = $s?->status;
        $subscribedAt = $this->date($row['subscribed_at'] ?? null);
        $unsubscribedAt = $this->date($row['unsubscribed_at'] ?? null);

        if (in_array($old, [Subscriber::STATUS_ANONYMIZED, Subscriber::STATUS_SUPPRESSED], true)) {
            return 'skipped:'.($old === Subscriber::STATUS_ANONYMIZED ? 'zanonimizowany' : 'zablokowany');
        }
        $blocking = in_array($target, self::BLOCKING, true);
        if (! $blocking) {
            if (in_array($old, self::BLOCKING, true)) {
                return 'skipped:wypisany lub zablokowany w newsletterze';
            }
            if (NewsletterSuppression::contains($hash) && ! in_array($old, [Subscriber::STATUS_CONFIRMED, Subscriber::STATUS_PENDING], true)) {
                return 'skipped:na liście tłumienia';
            }
        } elseif ($target === Subscriber::STATUS_UNSUBSCRIBED && $old === Subscriber::STATUS_CONFIRMED && $s->confirmed_at && $unsubscribedAt && $s->confirmed_at->greaterThan($unsubscribedAt)) {
            // Wypisał się z MailerLite, a potem zapisał u nas — nowsza zgoda wygrywa.
            return 'skipped:zapisany ponownie po wypisaniu';
        }

        $isNew = ! $s;
        $s ??= new Subscriber([
            'email' => $email, 'site_id' => $import->site_id, 'source' => $mailerLite ? 'mailerlite' : 'import', 'status' => Subscriber::STATUS_PENDING,
        ]);

        $name = trim(trim((string) ($row['name'] ?? '')).' '.trim((string) ($row['last_name'] ?? '')));
        if ($name !== '') {
            $s->name = mb_substr($name, 0, 100);
        }
        $phone = preg_replace('/[^0-9+]/', '', (string) ($row['phone'] ?? ''));
        if ($phone !== '') {
            $s->phone = mb_substr($phone, 0, 30);
        }

        [$listIds, $groupTopics] = $this->groupTargets($import, (array) ($row['groups'] ?? []));
        $available = array_keys(Subscriber::availableTopics(false));
        $rowTopics = array_values(array_intersect(array_map('trim', is_array($row['topics'] ?? null) ? $row['topics'] : explode(',', (string) ($row['topics'] ?? ''))), $available));
        $topics = array_values(array_unique(array_merge($s->topics ?? [], $rowTopics, $groupTopics)));
        $s->topics = $topics ?: array_values(array_intersect((array) $import->option('topics', ['news']), $available));
        if ($tag = $import->option('tag')) {
            $s->tags = array_values(array_unique(array_merge($s->tags ?? [], [(string) $tag])));
        }

        $sendDoi = false;
        if ($blocking) {
            $s->status = $target;
            match ($target) {
                Subscriber::STATUS_UNSUBSCRIBED => $s->unsubscribed_at = $s->unsubscribed_at ?? $unsubscribedAt ?? now(),
                Subscriber::STATUS_BOUNCED => $s->bounced_at = $s->bounced_at ?? now(),
                default => $s->complained_at = $s->complained_at ?? now(),
            };
        } elseif ($old !== Subscriber::STATUS_CONFIRMED) {
            $resend = (bool) $import->option('resend_doi', false);
            if ($target === Subscriber::STATUS_CONFIRMED && ! $resend) {
                $s->status = Subscriber::STATUS_CONFIRMED;
                $s->confirmed_at = $s->confirmed_at ?? $subscribedAt ?? now();
            } else {
                $s->status = Subscriber::STATUS_PENDING;
                // Plik „inny system” ze statusem oczekującym zawsze idzie przez double opt-in;
                // z MailerLite — tylko gdy zaznaczono ponowne potwierdzenie.
                $sendDoi = ($isNew || $old !== Subscriber::STATUS_PENDING) && ($resend || ! $mailerLite);
            }
        }

        $s->save();

        if ($listIds) {
            $s->lists()->syncWithoutDetaching(array_fill_keys($listIds, ['added_at' => now(), 'added_by' => $import->user_id]));
        }

        if ($blocking) {
            if ($old !== $target) {
                $s->consents()->whereNull('revoked_at')->update(['revoked_at' => $unsubscribedAt ?? now(), 'revoked_via' => $mailerLite ? 'mailerlite' : 'import']);
            }
            NewsletterSuppression::firstOrCreate(['email_hash' => $hash], ['reason' => ($mailerLite ? 'mailerlite_' : 'import_').$target, 'expires_at' => null]);
        } elseif ($target === Subscriber::STATUS_CONFIRMED && ! $import->option('resend_doi', false)) {
            $this->recordConsent($import, $s, $subscribedAt, $row['ip'] ?? null);
        }

        if ($sendDoi) {
            SendDoubleOptIn::dispatch($s->id);
        }

        if ($isNew || $old !== $s->status) {
            $s->logEvent('imported', array_filter(['import_id' => $import->id, 'source' => $import->source, 'from' => $old, 'status' => $s->status]), null, $import->user_id);
        }

        return $isNew ? 'created' : 'updated';
    }

    /** Zgoda z importu — jedna na subskrybenta i źródło (ponowny import jej nie dubluje). */
    private function recordConsent(NewsletterImport $import, Subscriber $s, ?CarbonImmutable $subscribedAt, ?string $ip): void
    {
        $mailerLite = $import->isMailerLite();
        $source = $mailerLite ? 'import MailerLite' : 'import';
        $method = $mailerLite ? 'mailerlite_import' : 'admin_import';

        if ($s->consents()->where('source', $source)->where('method', $method)->whereNull('revoked_at')->exists()) {
            return;
        }

        $note = trim((string) $import->option('consent_source', ''));
        $granted = $subscribedAt ?? ($import->option('consent_date') ? CarbonImmutable::parse($import->option('consent_date')) : now());

        NewsletterConsent::create([
            'subscriber_id' => $s->id,
            'channel' => 'email',
            'clause_text' => $mailerLite
                ? 'Zgoda na newsletter zebrana w MailerLite (data zapisu i IP z MailerLite)'.($note !== '' ? ': '.$note : '').'.'
                : 'Zgoda udokumentowana poza systemem: '.($note !== '' ? $note : 'import pliku'),
            'source' => $source,
            'method' => $method,
            'ip_hash' => filter_var((string) $ip, FILTER_VALIDATE_IP) ? Subscriber::hashIp((string) $ip) : null,
            'granted_at' => $granted,
            'confirmed_at' => $subscribedAt ?? now(),
        ]);
    }

    /**
     * Grupy źródła → listy i tematy wg mapowania z podglądu (`options.group_map`:
     * klucz grupy => `list:<id>` | `topic:<klucz>` | '').
     *
     * @return array{0: array<int, int>, 1: array<int, string>}
     */
    private function groupTargets(NewsletterImport $import, array $groups): array
    {
        $map = (array) $import->option('group_map', []);
        $lists = $import->option('list_id') ? [(int) $import->option('list_id')] : [];
        $topics = [];
        foreach (array_keys($groups) as $key) {
            $target = (string) ($map[(string) $key] ?? '');
            if (str_starts_with($target, 'list:')) {
                $lists[] = (int) substr($target, 5);
            } elseif (str_starts_with($target, 'topic:')) {
                $topics[] = substr($target, 6);
            }
        }

        return [array_values(array_unique(array_filter($lists))), array_values(array_unique($topics))];
    }

    // ── Pomocnicze ──────────────────────────────────────────────────────────

    private function canonical(array $cells, array $mapping): array
    {
        $get = fn (string $field) => isset($mapping[$field]) ? (isset($cells[$mapping[$field]]) ? trim((string) $cells[$mapping[$field]]) : null) : null;
        $groups = [];
        foreach (preg_split('/\s*[,;|]\s*/u', (string) $get('groups'), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $g) {
            $groups[$g] = $g;
        }

        return [
            'email' => (string) $get('email'),
            'name' => $get('name'),
            'last_name' => $get('last_name'),
            'phone' => $get('phone'),
            'status' => $get('status'),
            'subscribed_at' => $get('subscribed_at'),
            'ip' => $get('ip'),
            'groups' => $groups,
            'topics' => (string) $get('topics'),
            'unsubscribed_at' => $get('unsubscribed_at'),
        ];
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if (blank($value)) {
            return null;
        }
        try {
            $date = CarbonImmutable::parse((string) $value);

            return $date->isFuture() ? null : $date;
        } catch (\Throwable) {
            return null;
        }
    }

    private function isJson(NewsletterImport $import): bool
    {
        return str_ends_with((string) $import->file_path, '.json');
    }

    /** @return array<int, array<string, mixed>> */
    private function jsonRows(NewsletterImport $import): array
    {
        $json = json_decode($this->contents($import), true);
        $rows = is_array($json) ? (array_is_list($json) ? $json : ($json['subscribers'] ?? $json['data'] ?? [])) : [];

        return array_values(array_map(fn ($r) => is_array($r) ? array_change_key_case($r, CASE_LOWER) : ['email' => (string) $r], $rows));
    }

    private function contents(NewsletterImport $import): string
    {
        $content = (string) Storage::disk(self::DISK)->get((string) $import->file_path);

        return preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
    }

    /** @return array{handle: resource, delimiter: string} */
    private function openCsv(NewsletterImport $import): array
    {
        $handle = Storage::disk(self::DISK)->readStream((string) $import->file_path);
        if (! $handle) {
            throw new RuntimeException('Nie można odczytać pliku importu.');
        }
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        $pos = ftell($handle);
        $firstLine = (string) fgets($handle);
        fseek($handle, $pos);
        $counts = [';' => substr_count($firstLine, ';'), ',' => substr_count($firstLine, ','), "\t" => substr_count($firstLine, "\t")];
        arsort($counts);

        return ['handle' => $handle, 'delimiter' => (string) array_key_first($counts)];
    }
}
