<?php

declare(strict_types=1);

namespace Modules\Newsletter\Console;

use App\Models\SiteSetting;
use Illuminate\Console\Command;
use Modules\Newsletter\Models\NewsletterBounce;
use Modules\Newsletter\Models\NewsletterClick;
use Modules\Newsletter\Models\NewsletterOpen;
use Modules\Newsletter\Models\NewsletterSuppression;

/** Retencja: usuwa szczegółowe logi starsze niż ustawiony okres (liczniki zostają). */
class PruneNewsletterData extends Command
{
    protected $signature = 'newsletter:prune';
    protected $description = 'Usuwa logi otwarć/kliknięć/odbić starsze niż okres retencji oraz wygasłe wpisy listy tłumienia';

    public function handle(): int
    {
        $days = (int) (SiteSetting::current()->newsletter_retention_days ?: 730);
        $cut  = now()->subDays($days);

        $opens   = NewsletterOpen::where('opened_at', '<', $cut)->delete();
        $clicks  = NewsletterClick::where('clicked_at', '<', $cut)->delete();
        $bounces = NewsletterBounce::where('occurred_at', '<', $cut)->update(['raw_payload' => null]);
        $supp    = NewsletterSuppression::whereNotNull('expires_at')->where('expires_at', '<', now())->delete();

        $this->info("Usunięto: otwarć {$opens}, kliknięć {$clicks}; wyczyszczono payload odbić: {$bounces}; wygasłe tłumienia: {$supp}.");

        return self::SUCCESS;
    }
}
