<?php

namespace App\Console\Commands;

use App\Models\Donation;
use App\Services\DonationService;
use App\Services\SzoClient;
use Illuminate\Console\Command;

/**
 * Siatka bezpieczeństwa dla darowizn online, w dwóch krokach:
 *   1. odpytuje Przelewy24 o wpłaty wciąż „pending" (albo „failed" z powodu
 *      chwilowego błędu weryfikacji) i finalizuje te, które P24 potwierdza,
 *   2. dosyła do SZO opłacone darowizny, których SZO jeszcze nie przyjęło.
 *
 * W harmonogramie: co 10 minut (patrz routes/console.php).
 */
class DonationsSync extends Command
{
    protected $signature = 'donations:sync
                            {--after=5 : Minimalny wiek wpłaty w minutach (daje czas webhookowi)}
                            {--within=3 : Nie sprawdzaj w P24 wpłat starszych niż tyle dni}
                            {--limit=100 : Najwięcej darowizn wysłanych do SZO w jednym przebiegu}';

    protected $description = 'Uzgadnia darowizny z Przelewy24 i dosyła opłacone do rejestru darowizn SZO';

    public function handle(DonationService $donations, SzoClient $szo): int
    {
        $open = Donation::whereIn('status', ['pending', 'failed'])
            ->where('created_at', '<=', now()->subMinutes((int) $this->option('after')))
            ->where('created_at', '>=', now()->subDays((int) $this->option('within')))
            ->orderBy('id')
            ->get();

        $confirmed = $open->filter(fn (Donation $d) => $donations->reconcile($d))->count();
        $this->info("P24: sprawdzono {$open->count()}, potwierdzono {$confirmed}.");

        if (! $szo->enabled()) {
            $this->warn('SZO: integracja wyłączona (SZO_ENABLED / SZO_URL / SZO_TOKEN) — pomijam wysyłkę.');

            return self::SUCCESS;
        }

        $pending = Donation::pendingSzo()->orderBy('id')->limit((int) $this->option('limit'))->get();
        $sent = $pending->filter(fn (Donation $d) => $szo->pushDonation($d))->count();
        $this->info("SZO: do wysłania {$pending->count()}, przyjęto {$sent}.");

        return self::SUCCESS;
    }
}
