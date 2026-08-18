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
