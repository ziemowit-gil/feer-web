<?php

namespace App\Console\Commands;

use App\Support\Backup\BackupManager;
use Illuminate\Console\Command;
use Illuminate\Support\Number;

/** Lista kopii zapasowych w katalogu kopii. */
class BackupList extends Command
{
    protected $signature = 'backup:list';

    protected $description = 'Wyświetla listę kopii zapasowych';

    public function handle(BackupManager $manager): int
    {
        $this->table(['Nazwa', 'Rodzaj', 'Rozmiar'], array_map(fn ($b) => [
            $b['name'], $b['kind_label'], Number::fileSize($b['size'], 1),
        ], $manager->list()));

        return self::SUCCESS;
    }
}
