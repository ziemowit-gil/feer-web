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
 * newslettera — najwyżej raz na 24 godziny (THROTTLE_HOURS), niezależnie od zestawu problemów.
 * Czas ostatniego alertu zapisujemy w cache i w pliku (cache:clear przy deployu go nie skasuje).
 * Mail idzie synchronicznie (z pominięciem kolejki, bo to ona może nie działać).
 */
class CheckQueueHealth extends Command
{
    protected $signature = 'newsletter:queue-health {--force : Wyślij alert mimo wcześniejszego w ciągu 24 godzin}';
    protected $description = 'Sprawdza stan kolejki i powiadamia administratorów o utkniętych lub nieudanych zadaniach';

    public const THROTTLE_KEY = 'newsletter:queue-alert';

    /** Najwyżej jeden alert o problemach z kolejką na tyle godzin. */
    public const THROTTLE_HOURS = 24;

    public static function throttleFile(): string
    {
        return storage_path('app/newsletter-queue-alert');
    }

    /** Czas ostatniego wysłanego alertu (cache, a gdy go wyczyszczono — plik). */
    public static function lastAlertAt(): ?int
    {
        $ts = Cache::get(self::THROTTLE_KEY);
        if (! is_numeric($ts) && is_file(self::throttleFile())) {
            $ts = trim((string) @file_get_contents(self::throttleFile()));
        }

        return is_numeric($ts) ? (int) $ts : null;
    }

    public function handle(QueueHealth $health): int
    {
        $state = $health->snapshot();
        if ($state['ok']) {
            $this->info('Kolejka OK.');

            return self::SUCCESS;
        }

        $last = self::lastAlertAt();
        if (! $this->option('force') && $last && $last > now()->subHours(self::THROTTLE_HOURS)->getTimestamp()) {
            $this->warn('Alert wysłany ' . \Illuminate\Support\Carbon::createFromTimestamp($last)->diffForHumans() . ' — kolejny najwcześniej po ' . self::THROTTLE_HOURS . ' h.');

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
            Cache::put(self::THROTTLE_KEY, now()->getTimestamp(), now()->addHours(self::THROTTLE_HOURS));
            @file_put_contents(self::throttleFile(), (string) now()->getTimestamp());
            $this->info('Alert wysłany do: ' . $recipients->implode(', '));
        } catch (\Throwable $e) {
            Log::error('[Newsletter] Nie udało się wysłać alertu o kolejce: ' . $e->getMessage());
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
