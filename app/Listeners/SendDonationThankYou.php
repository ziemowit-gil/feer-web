<?php

namespace App\Listeners;

use App\Events\DonationPaid;
use App\Mail\DonationThankYouMail;
use App\Models\Donation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * E-mail z podziękowaniem po zaksięgowaniu wpłaty. Wysyłka „rezerwuje” się
 * warunkowym UPDATE-em na thanked_at, więc nawet przy wyścigu dwóch webhooków
 * darczyńca dostaje jedną wiadomość. Błąd poczty nie cofa wpłaty.
 */
class SendDonationThankYou
{
    public function handle(DonationPaid $event): void
    {
        $donation = $event->donation;

        // Bez migracji modułu zbiórek (kolumna thanked_at) nie ma jak zapobiec podwójnej wysyłce.
        if (! $event->notify || blank($donation->email) || ! Schema::hasColumn('donations', 'thanked_at')) {
            return;
        }

        $claimed = Donation::whereKey($donation->id)->whereNull('thanked_at')->update(['thanked_at' => now()]);
        if ($claimed !== 1) {
            return;
        }

        try {
            Mail::to($donation->email)->send(new DonationThankYouMail($donation->fresh(['campaign'])));
        } catch (Throwable $e) {
            // Zwolnij rezerwację, żeby dało się wysłać ponownie z panelu.
            Donation::whereKey($donation->id)->update(['thanked_at' => null]);
            Log::warning("[Darowizny] Nie wysłano podziękowania [{$donation->session_id}]: ".$e->getMessage());
        }
    }
}
