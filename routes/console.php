<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Worker kolejki (QUEUE_CONNECTION=database): na hostingu bez supervisora uruchamiany
// co minutę z crona schedule:run; kończy pracę po opróżnieniu kolejki lub po ~50 s.
// Obsługuje wysyłkę newslettera (kolejka „newsletter”) oraz maile/zadania z „default”.
Schedule::command('queue:work --queue=newsletter,default --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()->withoutOverlapping(10)->runInBackground();

// Codzienna auto-archiwizacja przeterminowanych wydarzeń i ofert wolontariatu.
Schedule::command('content:auto-archive')->dailyAt('03:00');

// Sprawdzanie klucza licencji w panelu licencji weCMS (unieważnienie/zastąpienie klucza).
Schedule::command('license:check')->dailyAt('05:10')->withoutOverlapping();

// Poranny digest nieprzeczytanych wiadomości kontaktowych — wysyłany tylko gdy są.
Schedule::command('contact:notify-unread')->dailyAt('08:00');

// Poranne przypomnienia o zadaniach z terminem na jutro.
Schedule::command('tasks:remind-due')->dailyAt('08:00');

// Przypomnienia dla zatwierdzających o treściach czekających ponad 12 h.
Schedule::command('approvals:notify-pending')->hourly();

// Dosyłanie do SZO zgłoszeń formularzy, których nie udało się przekazać przy
// zapisie (niedostępne SZO, timeout). Co 15 minut, żeby zaległość nie rosła.
Schedule::command('szo:push-submissions')->everyFifteenMinutes()->withoutOverlapping();
// Strony, aktualności i działania z ustawionym „Zdejmij z publikacji” — po terminie przestają być publiczne.
Schedule::command('content:unpublish-due')->everyFifteenMinutes()->withoutOverlapping();
// Przegląd treści: w poniedziałek rano lista treści nieaktualizowanych od 12 miesięcy (tylko gdy jakieś są).
Schedule::command('content:review-reminder')->weeklyOn(1, '08:30')->withoutOverlapping();
// Komunikaty w Aktualnościach po dacie ważności → archiwum.
Schedule::command('news:archiwizuj-komunikaty')->everyFifteenMinutes()->withoutOverlapping();

// Klauzule RODO z SZO — lokalna kopia dla shortcode [klauzule-rodo] (strona /rodo).
// Raz dziennie wystarcza; zmianę widać od razu po „Importuj teraz” w panelu.
Schedule::command('szo:import-clauses')->dailyAt('04:30')->withoutOverlapping();

// Siatka bezpieczeństwa dla płatności Sklepu — dociąga zamówienia, do których
// nie dotarł webhook Przelewy24 (patrz SklepOrderService::reconcile()).
Schedule::command('sklep:verify-pending')->everyTenMinutes()->withoutOverlapping();

// Darowizny online: uzgadnianie z Przelewy24 i dosyłanie do rejestru darowizn SZO.
Schedule::command('donations:sync')->everyTenMinutes()->withoutOverlapping();

// Przypomnienia dla redakcji: kończące się nabory i terminy zapytań ofertowych,
// brak informacji po naborze / wyniku postępowania (każde wysyłane raz).
Schedule::command('strony:przypomnienia')->dailyAt('08:00')->withoutOverlapping();

// Sygnał życia harmonogramu — co minutę zapis do cache; `system:sprawdz`
// i Health Check alarmują, gdy ostatni zapis jest starszy niż 10 minut.
// Działa także w trybie serwisowym, żeby po deployu sygnał nie znikał.
Schedule::call(fn () => \App\Support\DeployHealth::recordHeartbeat())
    ->everyMinute()
    ->name('system:heartbeat')
    ->evenInMaintenanceMode();

// Kopie zapasowe — harmonogram ustawiany w panelu (Narzędzia → Kopie zapasowe),
// zapisany w pliku obok kopii (BackupSettings), więc czytamy go przy każdym schedule:run.
try {
    $backupSettings = \App\Support\Backup\BackupSettings::load();
    $backupSchedule = $backupSettings->get('schedule');

    if (in_array($backupSchedule, ['daily', 'weekly'], true)) {
        $backupTime = preg_match('/^\d{2}:\d{2}$/', (string) $backupSettings->get('time')) ? $backupSettings->get('time') : '02:30';
        $backupEvent = Schedule::command('backup:run --auto')->withoutOverlapping(180)->name('backup:run');
        $backupSchedule === 'weekly' ? $backupEvent->weeklyOn(1, $backupTime) : $backupEvent->dailyAt($backupTime);
    }
} catch (\Throwable) {
    // Brak katalogu/uszkodzony plik ustawień nie może zatrzymać reszty harmonogramu.
}
