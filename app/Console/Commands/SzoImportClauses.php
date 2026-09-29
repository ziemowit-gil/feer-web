<?php

namespace App\Console\Commands;

use App\Services\GdprClauseImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Importuje opublikowane klauzule RODO z SZO (/klauzule.json) do lokalnej kopii,
 * z której korzysta shortcode [klauzule-rodo] (np. na stronie /rodo).
 *
 * W harmonogramie: codziennie. Ręcznie: Admin → Klauzule RODO → „Importuj teraz”.
 */
class SzoImportClauses extends Command
{
    protected $signature = 'szo:import-clauses';

    protected $description = 'Importuje klauzule informacyjne RODO z SZO';

    public function handle(GdprClauseImporter $importer): int
    {
        try {
            $s = $importer->import();
        } catch (RuntimeException $e) {
            Log::warning('[SZO] Import klauzul RODO nieudany: ' . $e->getMessage());
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Klauzule: {$s['added']} nowych, {$s['updated']} zaktualizowanych, {$s['unchanged']} bez zmian, {$s['removed']} usuniętych (razem {$s['total']}).");

        return self::SUCCESS;
    }
}
