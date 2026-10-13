<?php

namespace App\Console\Commands;

use App\Mail\ContentReviewReminderMail;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\ContentReview;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/** Przypomnienie dla administratorów o treściach nieaktualizowanych od wielu miesięcy (wysyłane tylko, gdy są). */
class ContentReviewReminder extends Command
{
    protected $signature = 'content:review-reminder {--months=12 : Po ilu miesiącach bez zmian treść czeka na przegląd}';

    protected $description = 'Wysyła administratorom listę treści do przejrzenia (nieaktualizowanych od N miesięcy).';

    public function handle(): int
    {
        $months = max(1, (int) $this->option('months'));
        $rows = ContentReview::stale($months);
        if ($rows->isEmpty()) {
            $this->info('Wszystkie treści są aktualne — przypomnienie nie wysłane.');

            return self::SUCCESS;
        }

        $site = SiteSetting::current();
        $emails = User::where('role', User::ROLE_ADMIN)->whereNotNull('email')->pluck('email')->unique();
        foreach ($emails as $email) {
            try {
                Mail::to($email)->send(new ContentReviewReminderMail($rows, $site->site_name, $months));
            } catch (\Throwable $e) {
                $this->error("Błąd wysyłki na {$email}: {$e->getMessage()}");
            }
        }
        $this->info("Przypomnienie o {$rows->count()} treściach wysłane na {$emails->count()} adresów.");

        return self::SUCCESS;
    }
}
