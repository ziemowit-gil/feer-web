<?php

namespace App\Console\Commands;

use App\Models\SiteSetting;
use App\Support\KreatywniMenuSync;
use Illuminate\Console\Command;

/** php artisan kreatywni:menu-w-panelu [witryna] — to samo co przycisk „Odwzoruj w panelu 1:1” w Menu nawigacji. */
class KreatywniMenuCommand extends Command
{
    protected $signature = 'kreatywni:menu-w-panelu
                            {witryna? : Id albo adres skrócony witryny (domyślnie witryna główna)}
                            {--dry-run : Tylko pokaż, co zostanie dodane}';

    protected $description = 'Zapisuje dobudowywane podmenu szablonu „kreatywni” jako podpozycje w Menu nawigacji (panel = strona 1:1)';

    public function handle(): int
    {
        $arg = $this->argument('witryna');
        $site = $arg === null ? SiteSetting::main()
            : SiteSetting::query()->where(fn ($q) => $q->whereKey(is_numeric($arg) ? (int) $arg : 0)->orWhere('slug', $arg))->first();

        if (! $site) {
            $this->error('Nie ma takiej witryny: '.$arg);

            return self::FAILURE;
        }
        if ($site->site_template !== 'kreatywni') {
            $this->error('Witryna „'.$site->site_name.'” nie używa szablonu Kreatywni.');

            return self::FAILURE;
        }

        app()->instance(SiteSetting::CURRENT_SITE_BINDING, $site);

        $plan = KreatywniMenuSync::plan($site);
        if ($plan->isEmpty()) {
            $this->info('Menu w panelu już odpowiada menu na stronie — nie ma czego dodać.');

            return self::SUCCESS;
        }

        foreach ($plan as ['nav' => $nav, 'groups' => $groups]) {
            $this->line('<options=bold>'.$nav->label.'</>');
            foreach ($groups as $group) {
                if ($group['heading']) {
                    $this->line('  [nagłówek kolumny] '.$group['heading']['label']);
                }
                foreach ($group['links'] as $link) {
                    $this->line('    '.$link['label'].'  '.KreatywniMenuSync::relative($link['url'], $site));
                }
            }
        }

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $created = KreatywniMenuSync::materialize($site);
        $this->info('Dodano podpozycji: '.$created.'.');

        return self::SUCCESS;
    }
}
