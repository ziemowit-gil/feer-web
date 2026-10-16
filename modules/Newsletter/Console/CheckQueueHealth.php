<?php

declare(strict_types=1);

namespace Modules\Newsletter\Console;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Newsletter\Mail\QueueAlertMail;
use Modules\Newsletter\Services\QueueHealth;

/**
 * Co 5 minut sprawdza kolejkę; przy problemie (zadania utknęły, worker milczy,
 * nieudane zadania) wysyła mail do administratorów i osób z prawem wysyłki
 * newslettera — najwyżej raz na godzinę dla tego samego zestawu problemów.
 * Mail idzie synchronicznie (z pominięciem kolejki, bo to ona może nie działać).
 */
class CheckQueueHealth extends Command
{
    protected $signature = 'newsletter:queue-health {--force : Wyślij alert mimo wcześniejszego w ciągu godziny}';
    protected $description = 'Sprawdza stan kolejki i powiadamia administratorów o utkniętych lub nieudanych zadaniach';

    public const THROTTLE_KEY = 'newsletter:queue-alert:';

    public function handle(QueueHealth $health): int
    {
        $state = $health->snapshot();
        if ($state['ok']) {
            $this->info('Kolejka OK.');

            return self::SUCCESS;
        }

        $key = self::THROTTLE_KEY . md5(implode('|', array_map(fn ($p) => preg_replace('/\d+|od .*?\(/u', '', $p), $state['problems'])));
        if (! $this->option('force') && Cache::has($key)) {
            $this->warn('Problem znany, alert wysłany w ciągu ostatniej godziny.');

            return self::SUCCESS;
        }

        $recipients = User::query()->get()
            ->filter(fn (User $u) => $u->isAdmin() || (method_exists($u, 'hasNewsletterPermission') && $u->hasNewsletterPermission('newsletter.send')))
            ->pluck('email')->filter()->unique()->values();
        if ($recipients->isEmpty()) {
            $this->warn('Brak adresatów alertu.');

            return self::SUCCESS;
        }

        $site = SiteSetting::current();
        try {
            foreach ($recipients as $email) {
                Mail::to($email)->send(new QueueAlertMail($state, (string) ($site->site_name ?? config('app.name'))));
            }
            Cache::put($key, now()->getTimestamp(), now()->addHour());
            $this->info('Alert wysłany do: ' . $recipients->implode(', '));
        } catch (\Throwable $e) {
            Log::error('[Newsletter] Nie udało się wysłać alertu o kolejce: ' . $e->getMessage());
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
