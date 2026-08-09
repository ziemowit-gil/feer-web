<?php

declare(strict_types=1);

namespace App\Console\Commands\Modules;

use App\Modules\ModuleManifest;
use App\Modules\ModuleManager;
use Illuminate\Console\Command;

final class ModuleListCommand extends Command
{
    protected $signature   = 'module:list';
    protected $description = 'Wylistuj wszystkie odkryte moduły i ich status';

    public function handle(ModuleManager $manager): int
    {
        $rows = $manager->all()->map(fn (ModuleManifest $m) => [
            $m->identifier,
            $m->name,
            $m->version,
            $manager->isActive($m->identifier)
                ? '<info>active</info>'
                : '<comment>inactive</comment>',
        ]);

        if ($rows->isEmpty()) {
            $this->warn('Brak modułów w katalogu modules/.');
            return self::SUCCESS;
        }

        $this->table(
            ['Identifier', 'Nazwa', 'Wersja', 'Status'],
            $rows->values()->toArray()
        );

        return self::SUCCESS;
    }
}
