<?php

declare(strict_types=1);

namespace Modules\Newsletter\Console;

use App\Models\SiteSetting;
use App\Models\Subscriber;
use Illuminate\Console\Command;
use Modules\Newsletter\Jobs\SyncSubscriberToCrm;

/** Dosyła do SZO/CRM potwierdzonych subskrybentów, których nie udało się zsynchronizować. */
class SyncSubscribersToCrm extends Command
{
    protected $signature = 'newsletter:sync-crm {--all : także już zsynchronizowanych}';
    protected $description = 'Ponawia synchronizację subskrybentów z SZO i CRM';

    public function handle(): int
    {
        $site = SiteSetting::current();
        if (! $site->newsletter_szo_sync && ! $site->newsletter_crm_sync) {
            $this->line('Synchronizacja SZO/CRM jest wyłączona w ustawieniach newslettera.');

            return self::SUCCESS;
        }

        $q = Subscriber::confirmed()->whereNull('anonymized_at');
        if (! $this->option('all')) {
            $q->where(function ($w) use ($site) {
                if ($site->newsletter_szo_sync) {
                    $w->orWhereNull('szo_synced_at');
                }
                if ($site->newsletter_crm_sync) {
                    $w->orWhereNull('crm_synced_at');
                }
            });
        }

        $n = 0;
        $q->orderBy('id')->chunkById(200, function ($subs) use (&$n) {
            foreach ($subs as $s) {
                SyncSubscriberToCrm::dispatch($s->id, 'confirmed');
                $n++;
            }
        });

        $this->info("Zakolejkowano {$n} synchronizacji.");

        return self::SUCCESS;
    }
}
