<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Codzienna auto-archiwizacja przeterminowanych wydarzeń i ogłoszeń wolontariatu.
Schedule::command('content:auto-archive')->dailyAt('03:00');
