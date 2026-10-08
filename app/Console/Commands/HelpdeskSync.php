<?php

namespace App\Console\Commands;

use App\Models\SupportTicket;
use App\Support\Helpdesk;
use Illuminate\Console\Command;

/**
 * Aktywuje/odświeża (heartbeat) licencję tej instalacji wobec Helpdesku
 * Centralnego, a przy okazji dociąga odpowiedzi obsługi dla zgłoszeń panelu
 * "Pomoc" i ponawia wysyłkę tych, które się wcześniej nie udały. Patrz
 * App\Support\Helpdesk, App\Models\LicenseStatus, App\Models\SupportTicket.
 *
 * W harmonogramie: co 5 minut (jak `helpdesk:sync` dla produktu "ShowMe",
 * patrz README Helpdesku).
 */
class HelpdeskSync extends Command
{
    protected $signature = 'helpdesk:sync';

    protected $description = 'Sprawdza/odświeża licencję tej instalacji i zgłoszenia "Pomoc" w Helpdesku Centralnym';

    public function handle(Helpdesk $helpdesk): int
    {
        $exitCode = self::SUCCESS;

        if (! $helpdesk->enabled()) {
            $this->warn('Integracja z Helpdeskiem wyłączona (HELPDESK_URL / HELPDESK_LICENSE_KEY).');
        } else {
            $status = $helpdesk->sync();

            if ($status->last_error) {
                $this->error("Nie udało się sprawdzić licencji: {$status->last_error}");
                $exitCode = self::FAILURE;
            } else {
                $this->info($status->statusLabel().($status->valid_until ? ' (do '.$status->valid_until->toDateString().')' : ''));
            }
        }

        if ($helpdesk->ticketsEnabled()) {
            $this->syncTickets($helpdesk);
        }

        return $exitCode;
    }

    private function syncTickets(Helpdesk $helpdesk): void
    {
        // Zgłoszenia, które nie doszły przy pierwszej próbie (np. Helpdesk był
        // chwilowo niedostępny) — ponawiamy najstarszą jeszcze niewysłaną wiadomość.
        SupportTicket::whereNull('remote_id')->each(function (SupportTicket $ticket) use ($helpdesk) {
            $firstMessage = $ticket->messages()->where('sender_type', 'me')->oldest()->first();

            if ($firstMessage) {
                $helpdesk->submitTicket($ticket, $firstMessage->body, $firstMessage->remote_message_id);
            }
        });

        SupportTicket::whereNotNull('remote_id')->whereNotIn('status', ['closed', 'resolved'])
            ->each(fn (SupportTicket $ticket) => $helpdesk->pullTicketUpdates($ticket));

        $this->info('Zgłoszenia "Pomoc" zsynchronizowane.');
    }
}
