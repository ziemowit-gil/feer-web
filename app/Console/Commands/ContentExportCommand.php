<?php

namespace App\Console\Commands;

use App\Support\ContentPortability;
use Illuminate\Console\Command;

class ContentExportCommand extends Command
{
    protected $signature = 'content:export {path? : Docelowy plik ZIP}';

    protected $description = 'Eksportuje treść (baza + media) do paczki ZIP — do przeniesienia na inny hosting.';

    public function handle(ContentPortability $portability): int
    {
        $path = $this->argument('path') ?: storage_path('app/content-export-'.date('Ymd-His').'.zip');

        $this->info('Eksportuję treść…');
        $portability->export($path);
        $this->info("Gotowe: {$path}");

        return self::SUCCESS;
    }
}
