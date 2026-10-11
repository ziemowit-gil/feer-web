<?php

namespace App\Console\Commands;

use App\Support\BlogDatabaseMover;
use App\Support\BlogDatabasePath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Przenosi bazę bloga z database/blog.sqlite (śledzone w gicie) do
 * storage/app/blog/blog.sqlite. Stary plik zostaje nietknięty.
 * Patrz docs/blog-db-migration.md.
 */
class BlogMoveDatabase extends Command
{
    protected $signature = 'blog:przenies-baze
                            {--zrodlo= : Plik źródłowy (domyślnie database/blog.sqlite)}
                            {--cel= : Plik docelowy (domyślnie storage/app/blog/blog.sqlite)}
                            {--kopie= : Katalog kopii zapasowych (domyślnie storage/app/blog/backups)}
                            {--force : Nie pytaj, gdy aplikacja nie jest w trybie serwisowym}';

    protected $description = 'Kopiuje bazę bloga Wiem FEER do storage/app/blog/ z kopią zapasową i weryfikacją liczby wierszy.';

    public function handle(BlogDatabaseMover $mover): int
    {
        $explicit = $this->option('zrodlo') || $this->option('cel');
        $source = $this->option('zrodlo') ?: BlogDatabasePath::legacyPath();
        $target = $this->option('cel') ?: BlogDatabasePath::newPath();
        $backups = $this->option('kopie') ?: dirname($target).'/backups';

        // Własna ścieżka z BLOG_DB_DATABASE (czytamy z configu, bo przy
        // config:cache env() nie widzi .env) — wtedy nie ma czego przenosić.
        $active = (string) config('database.connections.blog.database');
        if (! $explicit && ! in_array($active, [BlogDatabasePath::legacyPath(), BlogDatabasePath::newPath()], true)) {
            $this->components->info("Baza bloga ma własną ścieżkę (BLOG_DB_DATABASE = {$active}) — nic nie przenoszę.");

            return self::SUCCESS;
        }

        if (file_exists($target)) {
            $this->components->info("Baza bloga jest już w nowej lokalizacji: {$target}");

            return self::SUCCESS;
        }

        if (! is_file($source)) {
            $this->components->warn("Brak starej bazy ({$source}) — nie ma czego przenosić. Migracje bloga utworzą nową bazę w starej lokalizacji, chyba że najpierw utworzysz pusty plik {$target}.");

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->laravel->isDownForMaintenance()) {
            $this->components->warn('Aplikacja nie jest w trybie serwisowym — zapisy w blogu między kopią a przełączeniem mogłyby przepaść.');
            if (! $this->input->isInteractive() || ! $this->confirm('Kontynuować mimo to?', false)) {
                $this->components->error('Przerwano. Uruchom w trybie serwisowym (artisan down) albo z --force.');

                return self::FAILURE;
            }
        }

        try {
            $result = $mover->move($source, $target, $backups);
        } catch (\Throwable $e) {
            $this->components->error('Przeniesienie bazy bloga nie powiodło się: '.$e->getMessage());
            $this->line('  Aplikacja nadal używa starej lokalizacji — dane są bezpieczne.');

            return self::FAILURE;
        }

        $this->components->info("Skopiowano bazę bloga ({$result['method']}): {$source} → {$target}");
        $this->components->twoColumnDetail('Kopia zapasowa', $result['backup']);
        foreach ($result['tables'] as $table => $count) {
            $this->components->twoColumnDetail($table, $count.' wierszy (zgodne)');
        }

        if (! $explicit) {
            if ($this->laravel->configurationIsCached()) {
                Artisan::call('config:cache');
                $this->components->info('Odświeżono cache konfiguracji (config:cache) — aplikacja używa nowej ścieżki.');
            }
            config(['database.connections.blog.database' => $target]);
            DB::purge('blog');
        }

        return self::SUCCESS;
    }
}
