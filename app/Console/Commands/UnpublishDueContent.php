<?php

namespace App\Console\Commands;

use App\Support\ContentReview;
use Illuminate\Console\Command;

/** Zdejmuje z publikacji strony, aktualności i działania, którym minął termin „Zdejmij z publikacji”. */
class UnpublishDueContent extends Command
{
    protected $signature = 'content:unpublish-due';

    protected $description = 'Zdejmuje z publikacji treści, którym minął termin publikacji (unpublish_at).';

    public function handle(): int
    {
        $done = array_filter(ContentReview::unpublishDue());
        $this->info($done ? 'Zdjęto z publikacji: '.collect($done)->map(fn ($n, $k) => "{$k}: {$n}")->implode(', ') : 'Nic do zdjęcia.');

        return self::SUCCESS;
    }
}
