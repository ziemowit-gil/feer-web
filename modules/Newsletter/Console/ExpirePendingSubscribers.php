<?php

declare(strict_types=1);

namespace Modules\Newsletter\Console;

use App\Models\SiteSetting;
use App\Models\Subscriber;
use Illuminate\Console\Command;

class ExpirePendingSubscribers extends Command
{
    protected $signature = 'newsletter:expire-pending';
    protected $description = 'Oznacza jako wygasłe zapisy niepotwierdzone w terminie i usuwa je po 30 dniach';

    public function handle(): int
    {
        $ttl = (int) (SiteSetting::current()->newsletter_doi_ttl_days ?: 7);

        $expired = Subscriber::where('status', Subscriber::STATUS_PENDING)
            ->where('created_at', '<', now()->subDays($ttl))->update(['status' => Subscriber::STATUS_EXPIRED]);

        $deleted = Subscriber::where('status', Subscriber::STATUS_EXPIRED)
            ->where('updated_at', '<', now()->subDays(30))->delete();

        $this->info("Wygasłych: {$expired}, usuniętych: {$deleted}.");

        return self::SUCCESS;
    }
}
