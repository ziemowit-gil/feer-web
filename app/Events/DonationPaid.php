<?php

namespace App\Events;

use App\Models\Donation;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Wpłata została zaksięgowana (webhook P24, uzgodnienie albo wpis ręczny w panelu).
 * Wysyłane DOKŁADNIE raz na wpłatę — DonationService::markPaid() ustawia status
 * pod blokadą wiersza i emituje zdarzenie tylko wtedy, gdy to ono zmieniło status.
 */
class DonationPaid
{
    use Dispatchable;

    /** @param bool $notify false = bez e-maila z podziękowaniem (np. wpis ręczny bez zgody darczyńcy). */
    public function __construct(public readonly Donation $donation, public readonly bool $notify = true) {}
}
