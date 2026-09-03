<?php

namespace App\Console\Commands;

use App\Models\SklepOrder;
use App\Services\SklepOrderService;
use Illuminate\Console\Command;

/**
 * Siatka bezpieczeństwa dla płatności Sklepu: normalnie zamówienie finalizuje
 * webhook P24 (Przelewy24WebhookController) natychmiast po wpłacie, ale gdy
 * webhook z jakiegoś powodu nie dotrze (chwilowa awaria sieci, restart
 * serwera w złym momencie), zamówienie zostałoby na zawsze "pending" mimo
 * opłacenia. To polecenie odpytuje P24 o każde takie zamówienie i finalizuje
 * je tak samo jak webhook, gdy P24 faktycznie potwierdza wpłatę.
 *
 * W harmonogramie: co 10 minut (patrz routes/console.php).
 */
class SklepVerifyPendingPayments extends Command
{
    protected $signature = 'sklep:verify-pending
                            {--after=5 : Minimalny wiek zamówienia w minutach (daje czas webhookowi)}
                            {--within=3 : Nie sprawdzaj zamówień starszych niż tyle dni}';

    protected $description = 'Odpytuje Przelewy24 o zamówienia sklepu wciąż oznaczone jako "pending" (siatka na wypadek niedostarczonego webhooka)';

    public function handle(SklepOrderService $orders): int
    {
        $pending = SklepOrder::where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes((int) $this->option('after')))
            ->where('created_at', '>=', now()->subDays((int) $this->option('within')))
            ->orderBy('id')
            ->get();

        if ($pending->isEmpty()) {
            $this->info('Brak zamówień do sprawdzenia.');

            return self::SUCCESS;
        }

        $confirmed = 0;

        foreach ($pending as $order) {
            if ($orders->reconcile($order)) {
                $confirmed++;
                $this->line("  ✓ #{$order->id} potwierdzone jako opłacone");
            }
        }

        $this->info("Sprawdzono: {$pending->count()}, potwierdzono: {$confirmed}.");

        return self::SUCCESS;
    }
}
