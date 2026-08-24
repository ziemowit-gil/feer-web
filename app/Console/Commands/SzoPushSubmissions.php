<?php

namespace App\Console\Commands;

use App\Models\FormSubmission;
use App\Services\SzoClient;
use Illuminate\Console\Command;

/**
 * Dosyła do SZO zgłoszenia, których nie udało się przekazać przy zapisie.
 *
 * Wysyłka przy zapisie jest krótka i nieblokująca, więc każda przerwa w
 * dostępności SZO zostawia zaległość. Bez tego polecenia zgłoszenie
 * przepadłoby dla CRM-a mimo że leży w bazie CMS-a — a to najgorszy wariant:
 * dane są, tylko nikt ich nie widzi tam, gdzie pracuje.
 *
 * W harmonogramie: co 15 minut.
 */
class SzoPushSubmissions extends Command
{
    protected $signature = 'szo:push-submissions
                            {--limit=50 : Ile zgłoszeń wysłać w jednym przebiegu}
                            {--days=30 : Jak stare zgłoszenia jeszcze ponawiać}';

    protected $description = 'Dosyła do SZO zgłoszenia formularzy, które nie zostały przekazane';

    public function handle(SzoClient $szo): int
    {
        if (! $szo->enabled()) {
            $this->warn('Integracja z SZO wyłączona (SZO_ENABLED / SZO_URL / SZO_TOKEN).');

            return self::SUCCESS;
        }

        $pending = FormSubmission::query()
            ->pendingSzo()
            ->where('created_at', '>=', now()->subDays((int) $this->option('days')))
            ->with('form')
            ->orderBy('id')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($pending->isEmpty()) {
            $this->info('Brak zaległych zgłoszeń.');

            return self::SUCCESS;
        }

        $ok = 0;
        $failed = 0;

        foreach ($pending as $submission) {
            if ($szo->pushSubmission($submission)) {
                $ok++;
                $this->line("  ✓ #{$submission->id} → kontakt {$submission->szo_contact_id}");
            } else {
                $failed++;
                $this->line("  ✗ #{$submission->id}: {$submission->szo_error}");
            }
        }

        $this->info("Przekazano: {$ok}, nieudanych: {$failed}.");

        return self::SUCCESS;
    }
}
