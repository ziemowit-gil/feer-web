<?php

namespace App\Console\Commands;

use App\Support\Edition;
use Illuminate\Console\Command;

/**
 * Pokazuje edycję tej instalacji (darmowa/płatna), skąd się wzięła i które
 * funkcje płatne są dostępne — patrz config/editions.php.
 */
class EditionStatus extends Command
{
    protected $signature = 'edition:status';

    protected $description = 'Pokaż edycję weCMS (darmowa/płatna) i dostępność funkcji płatnych';

    public function handle(): int
    {
        $this->line('Edycja: <info>'.(Edition::isPro() ? 'płatna (pro)' : 'darmowa (free)').'</info>');
        $this->line('Powód:  '.Edition::reason());

        $last = \App\Support\LicenseOnlineCheck::last();
        $this->line('Panel:  '.match (true) {
            ! \App\Support\LicenseOnlineCheck::enabled() => 'sprawdzanie online wyłączone',
            $last === null => 'jeszcze nie sprawdzano (php artisan license:check)',
            default => ($last['valid'] ? 'klucz ważny' : (\App\Support\LicenseOnlineCheck::REASON_LABELS[$last['reason']] ?? $last['reason']))
                .', sprawdzono '.\Illuminate\Support\Carbon::parse($last['checked_at'])->format('d.m.Y H:i'),
        });
        $this->newLine();

        $this->table(['Funkcja', 'Klucz', 'Dostępna'], collect(config('editions.features', []))
            ->map(fn (string $label, string $key) => [$label, $key, Edition::allows($key) ? 'tak' : 'tylko odczyt'])
            ->values()
            ->all());

        return self::SUCCESS;
    }
}
