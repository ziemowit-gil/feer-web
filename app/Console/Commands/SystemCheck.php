<?php

namespace App\Console\Commands;

use App\Support\DeployHealth;
use Illuminate\Console\Command;

/**
 * Sprawdza kompletność wdrożenia bez wdrażania: manifest Vite vs wejścia
 * z vite.config.js, oczekujące migracje (główna + blog), plik bazy bloga,
 * link public/storage i sygnał harmonogramu. Wywoływana też przez deploy.sh.
 */
class SystemCheck extends Command
{
    protected $signature = 'system:sprawdz
                            {--tylko= : Tylko wybrane grupy, po przecinku: manifest,migracje,blog,storage,harmonogram}
                            {--scisle : Kod wyjścia 1 także przy ostrzeżeniach}';

    protected $description = 'Sprawdza, czy wdrożenie jest kompletne (assety, migracje, baza bloga, storage, harmonogram). Kod 1 przy problemach.';

    public function handle(DeployHealth $health): int
    {
        $only = null;
        if ($this->option('tylko')) {
            $only = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('tylko')))));
            $unknown = array_diff($only, DeployHealth::GROUPS);
            if ($unknown !== []) {
                $this->components->error('Nieznane grupy: '.implode(', ', $unknown).'. Dostępne: '.implode(', ', DeployHealth::GROUPS));

                return self::INVALID;
            }
        }

        $rows = $health->run($only);
        $labels = ['ok' => '<fg=green>OK</>', 'warning' => '<fg=yellow>UWAGA</>', 'error' => '<fg=red>PROBLEM</>'];

        foreach ($rows as $row) {
            $this->components->twoColumnDetail($row['name'].' <fg=gray>'.$row['detail'].'</>', $labels[$row['status']]);
        }

        $errors = count(array_filter($rows, fn ($r) => $r['status'] === 'error'));
        $warnings = count(array_filter($rows, fn ($r) => $r['status'] === 'warning'));

        $this->newLine();
        if ($errors > 0 || ($this->option('scisle') && $warnings > 0)) {
            $this->components->error("Wykryto problemy: {$errors}, ostrzeżenia: {$warnings}.");

            return self::FAILURE;
        }

        $warnings > 0
            ? $this->components->warn("Bez błędów, ostrzeżenia: {$warnings}.")
            : $this->components->info('Wszystko w porządku.');

        return self::SUCCESS;
    }
}
