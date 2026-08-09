<?php

declare(strict_types=1);

namespace App\Console\Commands\Modules;

use App\Modules\ModuleManager;
use Illuminate\Console\Command;

final class ModuleDeactivateCommand extends Command
{
    protected $signature   = 'module:deactivate {identifier}';
    protected $description = 'Dezaktywuj moduł (dane w bazie pozostają nienaruszone)';

    public function handle(ModuleManager $manager): int
    {
        $identifier = $this->argument('identifier');

        if ($manager->get($identifier) === null) {
            $this->error("Moduł [{$identifier}] nie istnieje w katalogu modules/.");
            return self::FAILURE;
        }

        $manager->deactivate($identifier);
        $this->info("Moduł [{$identifier}] dezaktywowany.");

        return self::SUCCESS;
    }
}
