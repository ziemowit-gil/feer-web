<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;

/** Komunikaty (Aktualności, typ „Komunikat”) po dacie ważności trafiają do archiwum. */
class ArchiveExpiredAnnouncements extends Command
{
    protected $signature = 'news:archiwizuj-komunikaty';

    protected $description = 'Przenosi do archiwum komunikaty, którym minęła data ważności';

    public function handle(): int
    {
        $count = 0;
        News::query()
            ->where('post_type', 'announcement')
            ->where('is_archived', false)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->each(function (News $news) use (&$count) {
                $news->forceFill(['is_archived' => true])->save();
                $count++;
            });

        $this->info("Zarchiwizowane komunikaty: {$count}.");

        return self::SUCCESS;
    }
}
