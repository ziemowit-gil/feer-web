<?php

namespace App\Console\Commands;

use App\Models\SiteSetting;
use App\Support\MainSite;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

/**
 * Pokazuje albo zmienia witrynę główną instalacji (SiteSetting::main(), App\Support\MainSite) — to samo co
 * „Ustaw jako główną” w panelu Witryny.
 */
class MainSiteCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'witryny:glowna
                            {witryna? : Numer witryny, adres skrócony albo domena; bez niego pokazuje bieżącą główną}
                            {--force : Nie pytaj w środowisku produkcyjnym}';

    protected $description = 'Pokazuje albo zmienia witrynę główną (ta otwiera się pod adresem serwera)';

    public function handle(): int
    {
        $wanted = trim((string) $this->argument('witryna'));

        if ($wanted === '') {
            $this->listSites();

            return self::SUCCESS;
        }

        $site = $this->find($wanted);
        if (! $site) {
            $this->error('Nie ma witryny „'.$wanted.'” (podaj numer, adres skrócony albo domenę).');
            $this->listSites();

            return self::FAILURE;
        }

        if ($reason = MainSite::refusal($site)) {
            $this->error($reason);

            return self::FAILURE;
        }

        if (! $this->confirmToProceed('Zmiana witryny głównej na „'.$site->site_name.'”.')) {
            return self::FAILURE;
        }

        $previous = MainSite::switchTo($site);

        $this->info('Witryną główną jest teraz „'.$site->site_name.'” (#'.$site->id.').');
        $this->line('„'.$previous->site_name.'” (#'.$previous->id.') jest jej subwitryną'
            .(($previous->domain || $previous->slug) ? '.' : ' bez publicznego adresu — nadaj go w panelu: Witryny → Zmień nazwę i adres.'));
        $this->line('Ustawienia całej instalacji (logowanie, SSO, poczta) są czytane z witryny głównej — sprawdź je w panelu.');

        return self::SUCCESS;
    }

    private function find(string $wanted): ?SiteSetting
    {
        $query = SiteSetting::query()->notTrashed();

        if (ctype_digit($wanted)) {
            return $query->find((int) $wanted);
        }

        $wanted = mb_strtolower($wanted);

        return (clone $query)->whereRaw('LOWER(slug) = ?', [$wanted])->first()
            ?? $query->whereRaw('LOWER(domain) = ?', [$wanted])->first();
    }

    private function listSites(): void
    {
        $main = SiteSetting::main();
        $this->info('Witryna główna: „'.$main->site_name.'” (#'.$main->id.')');

        $rows = SiteSetting::query()->notTrashed()->orderBy('id')->get()->map(fn (SiteSetting $s) => [
            $s->id,
            $s->site_name,
            $s->slug ?: '—',
            $s->domain ?: '—',
            $s->is($main) ? 'główna' : ($s->isBipSite() ? 'BIP' : 'subwitryna'),
        ])->all();

        $this->table(['#', 'Nazwa', 'Adres skrócony', 'Domena', 'Rola'], $rows);
    }
}
