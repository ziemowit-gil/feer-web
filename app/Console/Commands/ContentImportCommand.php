<?php

namespace App\Console\Commands;

use App\Support\ContentPortability;
use Illuminate\Console\Command;

class ContentImportCommand extends Command
{
    protected $signature = 'content:import {path : Plik ZIP z paczką treści}';

    protected $description = 'Importuje treść z paczki ZIP (upsert po ID — nie kasuje istniejących wierszy).';

    public function handle(ContentPortability $portability): int
    {
        $path = $this->argument('path');

        $this->info('Importuję treść…');
        $summary = $portability->import($path);

        foreach ($summary as $table => $count) {
            $this->line("  {$table}: {$count}");
        }

        $this->info('Gotowe.');

        return self::SUCCESS;
    }
}
