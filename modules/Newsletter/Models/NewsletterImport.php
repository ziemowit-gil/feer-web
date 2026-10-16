<?php

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Przebieg importu subskrybentów: podgląd (mapowanie kolumn i grup, liczby wg statusu),
 * postęp w kolejce (kursor API albo pozycja w pliku) i raport końcowy.
 */
class NewsletterImport extends Model
{
    public const SOURCE_FILE = 'file';

    public const SOURCE_MAILERLITE_CSV = 'mailerlite_csv';

    public const SOURCE_MAILERLITE_API = 'mailerlite_api';

    public const SOURCE_GETRESPONSE_CSV = 'getresponse_csv';

    public const SOURCE_GETRESPONSE_API = 'getresponse_api';

    /** Dostawcy zewnętrzni: klucz => nazwa (do zgód, źródła subskrybenta i powodu tłumienia). */
    public const PROVIDERS = ['mailerlite' => 'MailerLite', 'getresponse' => 'GetResponse'];

    public const SOURCES = [
        self::SOURCE_FILE => 'Plik CSV/JSON',
        self::SOURCE_MAILERLITE_CSV => 'Plik CSV z MailerLite',
        self::SOURCE_MAILERLITE_API => 'MailerLite (API)',
        self::SOURCE_GETRESPONSE_CSV => 'Plik CSV z GetResponse',
        self::SOURCE_GETRESPONSE_API => 'GetResponse (API)',
    ];

    public const STATUS_PREVIEW = 'preview';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUSES = [
        self::STATUS_PREVIEW => 'Podgląd — czeka na uruchomienie',
        self::STATUS_QUEUED => 'W kolejce',
        self::STATUS_RUNNING => 'Trwa',
        self::STATUS_COMPLETED => 'Zakończony',
        self::STATUS_FAILED => 'Przerwany z błędem',
    ];

    /** Ile komunikatów o błędach trzymamy w raporcie. */
    public const MAX_ERRORS = 200;

    protected $table = 'newsletter_imports';

    protected $fillable = [
        'site_id', 'user_id', 'source', 'status', 'file_name', 'file_path', 'columns', 'mapping', 'options', 'summary',
        'cursor', 'position', 'total', 'created_count', 'updated_count', 'skipped_count', 'failed_count', 'errors',
        'started_at', 'finished_at',
    ];

    protected $casts = [
        'columns' => 'array',
        'mapping' => 'array',
        'options' => 'array',
        'summary' => 'array',
        'errors' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /** Import z MailerLite lub GetResponse (API albo eksport) — zgody udokumentowane u dostawcy. */
    public function isExternal(): bool
    {
        return $this->provider() !== null;
    }

    /** @return 'mailerlite'|'getresponse'|null */
    public function provider(): ?string
    {
        $provider = strstr((string) $this->source, '_', true);

        return isset(self::PROVIDERS[$provider]) ? $provider : null;
    }

    public function providerLabel(): string
    {
        return self::PROVIDERS[$this->provider()] ?? 'plik';
    }

    public function isApi(): bool
    {
        return str_ends_with((string) $this->source, '_api');
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true);
    }

    public function isInProgress(): bool
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_RUNNING], true);
    }

    public function processed(): int
    {
        return $this->created_count + $this->updated_count + $this->skipped_count + $this->failed_count;
    }

    public function option(string $key, mixed $default = null): mixed
    {
        return data_get($this->options ?? [], $key, $default);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function sourceLabel(): string
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }

    /** Dopisuje komunikat o błędzie (z limitem) — bez zapisu. */
    public function addError(string $message): void
    {
        $errors = $this->errors ?? [];
        if (count($errors) < self::MAX_ERRORS) {
            $errors[] = $message;
            $this->errors = $errors;
        }
    }

    /** Stan dla paska postępu (JSON). */
    public function progress(): array
    {
        return [
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'finished' => $this->isFinished(),
            'total' => $this->total,
            'processed' => $this->processed(),
            'created' => $this->created_count,
            'updated' => $this->updated_count,
            'skipped' => $this->skipped_count,
            'failed' => $this->failed_count,
        ];
    }
}
