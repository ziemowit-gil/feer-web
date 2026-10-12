<?php

namespace App\Console\Commands;

use App\Support\Backup\BackupManager;
use Illuminate\Console\Command;

/**
 * Przywraca kopię zapasową z katalogu kopii (nazwa z `backup:list`).
 * Przed przywróceniem automatycznie robi kopię bieżącego stanu.
 */
class BackupRestore extends Command
{
    protected $signature = 'backup:restore {name : Nazwa archiwum, np. backup-2026-10-12-023000-auto.zip}
        {--no-files : Przywróć tylko bazy danych}
        {--force : Bez pytania o potwierdzenie}';

    protected $description = 'Przywraca kopię zapasową (bazy i pliki)';

    public function handle(BackupManager $manager): int
    {
        $name = $this->argument('name');

        try {
            $manifest = $manager->readManifest($manager->pathFor($name));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->line("Kopia z {$manifest['created_at']} ({$manifest['host']}), bazy: "
            .collect($manifest['databases'])->pluck('connection')->implode(', ')
            .($manifest['files'] ? ", pliki: {$manifest['file_count']}" : ', bez plików'));

        if (! $this->option('force') && ! $this->confirm('Bieżące dane zostaną zastąpione. Kontynuować?')) {
            return self::FAILURE;
        }

        $safety = $manager->restore($name, ! $this->option('no-files'));
        $this->info("Przywrócono. Kopia stanu sprzed przywrócenia: {$safety}");

        return self::SUCCESS;
    }
}
