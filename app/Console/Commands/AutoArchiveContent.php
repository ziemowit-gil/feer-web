<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\VolunteerAd;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('content:auto-archive')]
#[Description('Archiwizuje przeterminowane wydarzenia i ogłoszenia wolontariatu (chowa je z domyślnych list panelu).')]
class AutoArchiveContent extends Command
{
    public function handle(): int
    {
        $now = now();

        // Wydarzenia: zakończone (koniec, a gdy go brak — początek, minął).
        $events = Event::query()
            ->whereNull('archived_at')
            ->whereRaw('COALESCE(ends_at, starts_at) < ?', [$now])
            ->update(['archived_at' => $now]);

        // Ogłoszenia wolontariatu: termin zgłoszeń już minął.
        $ads = VolunteerAd::query()
            ->whereNull('archived_at')
            ->whereNotNull('closes_at')
            ->whereDate('closes_at', '<', $now)
            ->update(['archived_at' => $now]);

        $this->info("Zarchiwizowano: wydarzenia — {$events}, ogłoszenia wolontariatu — {$ads}.");

        return self::SUCCESS;
    }
}
