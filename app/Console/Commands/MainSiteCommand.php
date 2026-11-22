<?php

namespace App\Console\Commands;

use App\Models\SiteSetting;
use App\Support\Backup\BackupManager;
use App\Support\MainSite;
use App\Support\SiteRemoval;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

/**
 * Pokazuje albo zmienia witrynę główną instalacji (SiteSetting::main(), App\Support\MainSite) — to samo co
 * „Ustaw jako główną” w panelu Witryny. Z --usun-pozostale usuwa wszystkie witryny poza główną (jak „Usuń pozostałe
 * witryny” w panelu): do kosza albo z --trwale trwale, z kopią bazy (bez --bez-kopii).
 */
class MainSiteCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'witryny:glowna
                            {witryna? : Numer witryny, adres skrócony albo domena; bez niego pokazuje bieżącą główną}
                            {--usun-pozostale : Potem usuń wszystkie witryny poza główną (domyślnie do kosza na 30 dni)}
                            {--trwale : Z --usun-pozostale: usuń trwale razem z treścią, także witryny z kosza}
                            {--bez-kopii : Z --trwale: bez kopii zapasowej bazy przed usunięciem}
                            {--force : Nie pytaj o potwierdzenie}';

    protected $description = 'Pokazuje albo zmienia witrynę główną (ta otwiera się pod adresem serwera); opcjonalnie usuwa pozostałe witryny';

    public function handle(): int
    {
        $wanted = trim((string) $this->argument('witryna'));
        $removeOthers = (bool) $this->option('usun-pozostale');

        if ($wanted === '' && ! $removeOthers) {
            $this->listSites();

            return self::SUCCESS;
        }

        if ($wanted !== '') {
            $site = $this->find($wanted);
            if (! $site) {
                $this->error('Nie ma witryny „'.$wanted.'” (podaj numer, adres skrócony albo domenę).');
                $this->listSites();

                return self::FAILURE;
            }

            // Wskazana witryna już jest główną — przy --usun-pozostale to nie błąd, przechodzimy do usuwania.
            $alreadyMain = SiteSetting::main()->is($site);
            if (! ($alreadyMain && $removeOthers)) {
                if ($reason = MainSite::refusal($site)) {
                    $this->error($reason);

                    return self::FAILURE;
                }

                if (! $this->confirmToProceed('Zmiana witryny głównej na „'.$site->site_name.'”.')) {
                    return self::FAILURE;
                }

                $previous = MainSite::switchTo($site);

                $this->info('Witryną główną jest teraz „'.$site->site_name.'” (#'.$site->id.').');
                if (! $removeOthers) {
                    $this->line('„'.$previous->site_name.'” (#'.$previous->id.') jest jej subwitryną'
                        .(($previous->domain || $previous->slug) ? '.' : ' bez publicznego adresu — nadaj go w panelu: Witryny → Zmień nazwę i adres.'));
                }
                $this->line('Ustawienia całej instalacji (logowanie, SSO, poczta) są czytane z witryny głównej — sprawdź je w panelu.');
            }
        }

        return $removeOthers ? $this->removeOthers() : self::SUCCESS;
    }

    /** --usun-pozostale: wszystkie witryny poza główną do kosza albo (--trwale) trwale, po potwierdzeniu. */
    private function removeOthers(): int
    {
        $main = SiteSetting::main();
        $permanent = (bool) $this->option('trwale');
        $tops = MainSite::others($permanent);

        if ($tops->isEmpty()) {
            $this->info('Poza witryną główną „'.$main->site_name.'” nie ma '.($permanent ? 'innych witryn.' : 'innych witryn poza koszem.'));

            return self::SUCCESS;
        }

        $this->table(['#', 'Witryna', 'Podwitryny', 'Rekordy'], $tops->map(fn (SiteSetting $s) => [
            $s->id, $s->site_name, count(SiteRemoval::withDescendants($s)) - 1, array_sum(SiteRemoval::contentCountsDeep($s)),
        ])->all());

        $question = ($permanent ? 'Usunąć TRWALE' : 'Przenieść do kosza').' powyższe witryny (z podwitrynami)? Zostanie tylko „'.$main->site_name.'”.';
        if (! $this->option('force') && ! $this->confirm($question, false)) {
            $this->line('Niczego nie usunięto.');

            return self::FAILURE;
        }

        if ($permanent && ! $this->option('bez-kopii')) {
            try {
                $backup = app(BackupManager::class)->create('pre-delete', false);
                $this->line('Kopia zapasowa: '.basename($backup));
            } catch (\Throwable $e) {
                $this->error('Nie udało się zrobić kopii zapasowej, więc niczego nie usunięto: '.$e->getMessage());

                return self::FAILURE;
            }
        }

        $result = MainSite::removeOthers($permanent);

        if (function_exists('activity')) {
            activity('system')->event('deleted')
                ->withProperties(['main' => $main->site_name, 'sites' => $result['sites'], 'removed' => $result['removed'], 'backup' => isset($backup) ? basename($backup) : null, 'source' => 'witryny:glowna'])
                ->log(($permanent ? 'Trwale usunięto' : 'Przeniesiono do kosza').' wszystkie witryny poza główną „'.$main->site_name.'”');
        }

        $this->info($permanent
            ? 'Trwale usunięto witryny: '.$result['sites'].', rekordy: '.array_sum($result['removed']).'.'
            : 'Do kosza trafiło witryn: '.$result['sites'].'. Przywrócisz je w panelu (Witryny → Kosz) przez '.SiteSetting::TRASH_DAYS.' dni.');

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
