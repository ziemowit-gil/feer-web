<?php

declare(strict_types=1);

namespace App\Console\Commands\Modules;

use App\Modules\ModuleManager;
use Illuminate\Console\Command;

final class ModuleActivateCommand extends Command
{
    protected $signature   = 'module:activate {identifier}';
    protected $description = 'Aktywuj zainstalowany moduł';

    public function handle(ModuleManager $manager): int
    {
        $identifier = $this->argument('identifier');

        if ($manager->get($identifier) === null) {
            $this->error("Moduł [{$identifier}] nie istnieje w katalogu modules/.");
            return self::FAILURE;
        }

        if ($manager->isActive($identifier)) {
            $this->warn("Moduł [{$identifier}] jest już aktywny.");
            return self::SUCCESS;
        }

        $manager->activate($identifier);
        $this->info("Moduł [{$identifier}] aktywowany. Zmiany obowiązują od następnego żądania HTTP.");

        return self::SUCCESS;
    }
}
