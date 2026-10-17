<?php

namespace App\Console\Commands;

use App\Models\SiteSetting;
use App\Support\SiteRemoval;
use Illuminate\Console\Command;

/**
 * Trwale usuwa witryny sieci, które leżą w koszu dłużej niż SiteSetting::TRASH_DAYS dni (z podwitrynami
 * i całą treścią). Uruchamiane codziennie z harmonogramu (routes/console.php).
 */
class PurgeSiteTrash extends Command
{
    protected $signature = 'witryny:oproznij-kosz';

    protected $description = 'Trwale usuwa witryny sieci leżące w koszu dłużej niż '.SiteSetting::TRASH_DAYS.' dni';

    public function handle(): int
    {
        if (! SiteSetting::hasTrashColumn()) {
            $this->warn('Brak kolumny site_settings.trashed_at — uruchom migracje.');

            return self::SUCCESS;
        }

        $removed = SiteRemoval::purgeExpired();
        foreach ($removed as $name) {
            if (function_exists('activity')) {
                activity('system')->event('deleted')->withProperties(['site' => $name])->log('Kosz witryn: trwale usunięto witrynę „'.$name.'” po '.SiteSetting::TRASH_DAYS.' dniach');
            }
        }
        $this->info($removed ? 'Usunięto trwale: '.implode(', ', $removed) : 'Brak witryn do usunięcia.');

        return self::SUCCESS;
    }
}
