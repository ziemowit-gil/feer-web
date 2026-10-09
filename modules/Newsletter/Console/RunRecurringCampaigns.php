<?php

declare(strict_types=1);

namespace Modules\Newsletter\Console;

use Illuminate\Console\Command;
use Modules\Newsletter\Jobs\DispatchCampaign;
use Modules\Newsletter\Models\NewsletterCampaign;
use Modules\Newsletter\Services\ContentFeeder;

/**
 * Kampanie cykliczne (digest): co tydzień/miesiąc tworzy kopię kampanii-wzorca
 * i wysyła ją, o ile od ostatniego razu pojawiła się wymagana liczba nowych pozycji.
 * recurrence_rule: {"weekday":5,"day":1,"hour":9,"min_items":1}
 */
class RunRecurringCampaigns extends Command
{
    protected $signature = 'newsletter:recurring';
    protected $description = 'Generuje i wysyła kampanie cykliczne newslettera (digest), gdy przypada ich termin';

    public function handle(ContentFeeder $feeder): int
    {
        $now = now();

        $parents = NewsletterCampaign::whereNotNull('recurrence')->whereNull('parent_campaign_id')
            ->whereIn('status', [NewsletterCampaign::STATUS_SCHEDULED, NewsletterCampaign::STATUS_SENT, NewsletterCampaign::STATUS_DRAFT])
            ->get();

        foreach ($parents as $parent) {
            $rule = $parent->recurrence_rule ?? [];
            $hour = (int) ($rule['hour'] ?? 9);
            if ((int) $now->format('G') !== $hour) {
                continue;
            }
            if ($parent->recurrence === 'weekly' && (int) $now->isoWeekday() !== (int) ($rule['weekday'] ?? 5)) {
                continue;
            }
            if ($parent->recurrence === 'monthly' && (int) $now->day !== (int) ($rule['day'] ?? 1)) {
                continue;
            }
            if ($parent->last_run_at && $parent->last_run_at->gt($now->copy()->subHours(23))) {
                continue;
            }

            $feed = $feeder->inject((string) $parent->html_body, $parent);
            $count = array_sum(array_map('count', $feed['used']));
            $min = (int) ($rule['min_items'] ?? 1);

            if ($count < $min) {
                $parent->forceFill(['last_run_at' => $now])->save();
                $this->line("Kampania #{$parent->id}: brak nowych pozycji ({$count} < {$min}) — pominięta.");
                continue;
            }

            $child = $parent->replicate(['uuid', 'batch_id', 'status', 'started_at', 'finished_at', 'recipients_count', 'sent_count', 'delivered_count',
                'failed_count', 'opened_unique', 'clicked_unique', 'bounced_count', 'complained_count', 'unsubscribed_count', 'recurrence', 'recurrence_rule', 'last_run_at']);
            $child->title = $parent->title . ' — ' . $now->format('d.m.Y');
            $child->parent_campaign_id = $parent->id;
            $child->status = NewsletterCampaign::STATUS_QUEUED;
            $child->scheduled_at = $now;
            $child->content_snapshot = $feed['used'];
            $child->save();

            $parent->forceFill(['last_run_at' => $now, 'last_feed_item_at' => $feed['newest_at'] ?? $now])->save();
            DispatchCampaign::dispatch($child->id)->onQueue('newsletter');
            $this->info("Kampania cykliczna #{$parent->id} → wysyłka #{$child->id} ({$count} pozycji).");
        }

        return self::SUCCESS;
    }
}
