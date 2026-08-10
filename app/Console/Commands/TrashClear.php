<?php

namespace App\Console\Commands;

use App\Models\BipDocument;
use App\Models\Campaign;
use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Console\Command;

class TrashClear extends Command
{
    protected $signature = 'trash:clear
                            {--days=30 : Usuń rekordy usunięte co najmniej tyle dni temu (0 = wszystkie)}
                            {--force : Pomiń potwierdzenie}';

    protected $description = 'Trwale usuwa z bazy rekordy w koszu (soft-deleted: Strony, Aktualności, Projekty, Kampanie, Dokumenty BIP).';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $models = [
            'Strony'         => Page::class,
            'Aktualnosci'    => News::class,
            'Projekty'       => Project::class,
            'Kampanie'       => Campaign::class,
            'Dokumenty BIP'  => BipDocument::class,
        ];

        $counts = [];
        foreach ($models as $label => $model) {
            $q = $model::onlyTrashed();
            if ($days > 0) {
                $q->where('deleted_at', '<=', now()->subDays($days));
            }
            $counts[$label] = $q->count();
        }

        $total = array_sum($counts);

        if ($total === 0) {
            $this->info('Kosz jest pusty — nic do usuniecia.');
            return self::SUCCESS;
        }

        $this->table(['Model', 'Rekordow do usuniecia'], array_map(
            fn ($label, $count) => [$label, $count],
            array_keys($counts), $counts
        ));

        $suffix = $days > 0 ? " (usunietych ponad {$days} dni temu)" : ' (wszystkich)';
        if (! $this->option('force') && ! $this->confirm("Trwale usunac {$total} rekordow{$suffix}?")) {
            $this->line('Anulowano.');
            return self::SUCCESS;
        }

        foreach ($models as $label => $model) {
            $q = $model::onlyTrashed();
            if ($days > 0) {
                $q->where('deleted_at', '<=', now()->subDays($days));
            }
            $deleted = $q->forceDelete();
            if ($deleted) {
                $this->line("  {$label}: usunieto {$deleted}");
            }
        }

        $this->info("Gotowe. Usunieto lacznie {$total} rekordow.");
        return self::SUCCESS;
    }
}
