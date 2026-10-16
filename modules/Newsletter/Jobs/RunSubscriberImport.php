<?php

declare(strict_types=1);

namespace Modules\Newsletter\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Newsletter\Models\NewsletterImport;
use Modules\Newsletter\Services\GetResponseClient;
use Modules\Newsletter\Services\MailerLiteClient;
use Modules\Newsletter\Services\SubscriberImporter;
use RuntimeException;
use Throwable;

/**
 * Jedna porcja importu subskrybentów (strona API MailerLite/GetResponse albo fragment pliku).
 * Po porcji zapisuje postęp (kursor/pozycję) i kolejkuje następną — dzięki temu duże
 * bazy mieszczą się w limicie czasu workera (co minutę, --max-time=50).
 */
class RunSubscriberImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const FILE_CHUNK = 1000;

    public const API_PAGE = 500;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $importId)
    {
        $this->onQueue('newsletter');
    }

    public function handle(SubscriberImporter $importer): void
    {
        $import = NewsletterImport::find($this->importId);
        if (! $import || ! $import->isInProgress()) {
            return;
        }
        $import->forceFill(['status' => NewsletterImport::STATUS_RUNNING, 'started_at' => $import->started_at ?? now()])->save();

        if ($import->source === NewsletterImport::SOURCE_MAILERLITE_API) {
            $client = MailerLiteClient::fromSettings() ?? throw new RuntimeException('Brak tokenu API MailerLite.');
            $page = $client->subscribersPage($import->cursor, self::API_PAGE);
            $fieldMap = (array) $import->option('field_map', []);
            $n = $import->position;
            foreach ($page['data'] as $ml) {
                $this->one($importer, $import, ++$n, $importer->fromMailerLite($ml, $fieldMap));
            }
            $import->position = $n;
            $import->cursor = $page['next_cursor'];
            $more = $page['next_cursor'] !== null && $page['data'] !== [];
        } elseif ($import->source === NewsletterImport::SOURCE_GETRESPONSE_API) {
            // Najpierw kontakty strona po stronie („contacts:<strona>”), potem czarna lista konta („blocklist:<przesunięcie>”).
            $client = GetResponseClient::fromSettings() ?? throw new RuntimeException('Brak klucza API GetResponse.');
            [$phase, $at] = explode(':', $import->cursor ?: 'contacts:1') + [1 => '1'];
            $n = $import->position;
            if ($phase === 'contacts') {
                $page = $client->contactsPage((int) $at, self::API_PAGE);
                foreach ($page['data'] as $contact) {
                    $this->one($importer, $import, ++$n, $importer->fromGetResponse($contact));
                }
                $import->cursor = $page['more'] ? 'contacts:'.((int) $at + 1) : ($import->option('import_blocklist', true) ? 'blocklist:0' : null);
                $more = $import->cursor !== null;
            } else {
                $emails = array_slice($client->blocklist(), (int) $at, self::API_PAGE);
                foreach ($emails as $email) {
                    $this->one($importer, $import, ++$n, $importer->fromGetResponseBlocklist($email));
                }
                $import->cursor = 'blocklist:'.((int) $at + count($emails));
                $more = count($emails) === self::API_PAGE;
            }
            $import->position = $n;
        } else {
            $count = 0;
            foreach ($importer->fileRows($import, $import->position, self::FILE_CHUNK) as $n => $row) {
                $this->one($importer, $import, $n, $row);
                $count++;
            }
            $import->position += $count;
            $more = $count === self::FILE_CHUNK;
        }

        if (! $more) {
            $import->status = NewsletterImport::STATUS_COMPLETED;
            $import->finished_at = now();
            $import->total = $import->processed();
            $importer->deleteFile($import);
            activity()->causedBy($import->user_id)->withProperties($import->progress() + ['import_id' => $import->id, 'source' => $import->source])->log('newsletter.import');
        }
        $import->save();

        if ($more) {
            static::dispatch($import->id);
        }
    }

    public function failed(Throwable $e): void
    {
        $import = NewsletterImport::find($this->importId);
        if ($import && ! $import->isFinished()) {
            $import->addError('Import przerwany: '.$e->getMessage());
            $import->forceFill(['status' => NewsletterImport::STATUS_FAILED, 'finished_at' => now()])->save();
        }
    }

    private function one(SubscriberImporter $importer, NewsletterImport $import, int $n, array $row): void
    {
        try {
            $outcome = $importer->importRow($import, $row);
        } catch (Throwable $e) {
            $import->failed_count++;
            $email = (string) ($row['email'] ?? '');
            $import->addError("Rekord {$n}".($email !== '' && ! $e instanceof RuntimeException ? " ({$email})" : '').': '.$e->getMessage());

            return;
        }

        if (str_starts_with($outcome, 'skipped')) {
            $import->skipped_count++;
            $reason = substr($outcome, 8) ?: 'inne';
            $summary = $import->summary ?? [];
            $summary['skip_reasons'][$reason] = ($summary['skip_reasons'][$reason] ?? 0) + 1;
            $import->summary = $summary;
        } else {
            $outcome === 'created' ? $import->created_count++ : $import->updated_count++;
        }
    }
}
