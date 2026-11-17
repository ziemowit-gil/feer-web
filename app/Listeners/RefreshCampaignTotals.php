<?php

namespace App\Listeners;

use App\Events\DonationPaid;

/** Po zaksięgowaniu wpłaty przelicza kwotę zebraną zbiórki (od zera, z bazy). */
class RefreshCampaignTotals
{
    public function handle(DonationPaid $event): void
    {
        $event->donation->campaign?->refreshCollected();
    }
}
