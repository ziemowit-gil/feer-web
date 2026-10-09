<?php

declare(strict_types=1);

namespace Modules\Newsletter\Console;

use Illuminate\Console\Command;
use Modules\Newsletter\Jobs\DispatchCampaign;
use Modules\Newsletter\Models\NewsletterCampaign;

class DispatchDueCampaigns extends Command
{
    protected $signature = 'newsletter:dispatch-due';
    protected $description = 'Uruchamia zaplanowane kampanie newslettera, których termin minął';

    public function handle(): int
    {
        $due = NewsletterCampaign::where('status', NewsletterCampaign::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')->where('scheduled_at', '<=', now())->get();

        foreach ($due as $campaign) {
            $campaign->forceFill(['status' => NewsletterCampaign::STATUS_QUEUED])->save();
            DispatchCampaign::dispatch($campaign->id)->onQueue('newsletter');
            $this->info("Kampania #{$campaign->id} „{$campaign->title}” trafiła do kolejki.");
        }

        return self::SUCCESS;
    }
}
