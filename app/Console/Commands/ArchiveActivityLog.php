<?php

namespace App\Console\Commands;

use App\Support\ActivityArchive;
use Illuminate\Console\Command;

/** Przenosi wpisy Dziennika zdarzeń starsze niż 30 dni do plików TXT (bez rejestru BIP). Codziennie z harmonogramu. */
class ArchiveActivityLog extends Command
{
    protected $signature = 'dziennik:archiwizuj {--dni=30 : Wpisy starsze niż tyle dni}';

    protected $description = 'Archiwizuje wpisy Dziennika zdarzeń starsze niż 30 dni do plików tekstowych (storage/app/activity-archive)';

    public function handle(): int
    {
        $r = ActivityArchive::run(max(1, (int) $this->option('dni')));
        $this->info($r['archived'] ? "Zarchiwizowano {$r['archived']} wpisów: ".implode(', ', $r['files']) : 'Brak wpisów do archiwizacji.');

        return self::SUCCESS;
    }
}
