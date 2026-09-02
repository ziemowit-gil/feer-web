<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Codzienna auto-archiwizacja przeterminowanych wydarzeń i ogłoszeń wolontariatu.
Schedule::command('content:auto-archive')->dailyAt('03:00');

// Poranny digest nieprzeczytanych wiadomości kontaktowych — wysyłany tylko gdy są.
Schedule::command('contact:notify-unread')->dailyAt('08:00');

// Poranne przypomnienia o zadaniach z terminem na jutro.
Schedule::command('tasks:remind-due')->dailyAt('08:00');

// Przypomnienia dla zatwierdzających o treściach czekających ponad 12 h.
Schedule::command('approvals:notify-pending')->hourly();

// Dosyłanie do SZO zgłoszeń formularzy, których nie udało się przekazać przy
// zapisie (niedostępne SZO, timeout). Co 15 minut, żeby zaległość nie rosła.
Schedule::command('szo:push-submissions')->everyFifteenMinutes()->withoutOverlapping();

// Kontrola licencji tej instalacji w Helpdesku Centralnym (aktywacja/heartbeat
// per instance_id) — co 5 minut, jak dla produktu "ShowMe" (patrz README Helpdesku).
// Ta sama komenda dociąga też odpowiedzi na zgłoszenia z panelu "Pomoc".
Schedule::command('helpdesk:sync')->everyFiveMinutes()->withoutOverlapping();
