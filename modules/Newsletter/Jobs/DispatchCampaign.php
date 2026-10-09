<?php

declare(strict_types=1);

namespace Modules\Newsletter\Jobs;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Modules\Newsletter\Channels\ChannelRegistry;
use Modules\Newsletter\Models\NewsletterCampaign;
use Modules\Newsletter\Models\NewsletterDelivery;
use Modules\Newsletter\Services\AudienceResolver;
use Modules\Newsletter\Services\TrackingRewriter;

/**
 * Buduje dostawy (odbiorca × kanał) i partię jobów SendDelivery.
 * Idempotentny: ponowne uruchomienie nie dubluje dostaw (unikalny klucz).
 */
class DispatchCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 1800;

    public function __construct(public int $campaignId) {}

    public function handle(AudienceResolver $audience, ChannelRegistry $channels, TrackingRewriter $tracking): void
    {
        $campaign = NewsletterCampaign::find($this->campaignId);
        if (! $campaign || ! in_array($campaign->status, [NewsletterCampaign::STATUS_QUEUED, NewsletterCampaign::STATUS_SCHEDULED], true)) {
            return;
        }

        $campaign->forceFill(['status' => NewsletterCampaign::STATUS_SENDING, 'started_at' => now()])->save();
        $tracking->collectLinks($campaign, (string) $campaign->html_body);

        $wanted  = array_values(array_filter((array) $campaign->channels, fn ($c) => $channels->get($c)?->enabled()));
        $jobs    = [];
        $created = 0;
        $abSplit = (int) ($campaign->ab_split_percent ?? 0);
        $i       = 0;

        $audience->query($campaign)->select(['id', 'email', 'email_hash', 'name', 'status', 'channels', 'anonymized_at', 'phone'])
            ->orderBy('id')->chunkById(500, function ($subscribers) use (&$jobs, &$created, &$i, $campaign, $channels, $wanted, $abSplit) {
                foreach ($subscribers as $s) {
                    $variant = null;
                    if ($abSplit > 0 && filled($campaign->subject_b)) {
                        $bucket  = $i % 100;
                        $variant = $bucket < $abSplit / 2 ? 'A' : ($bucket < $abSplit ? 'B' : 'A');
                    }
                    $i++;
                    foreach ($wanted as $channelKey) {
                        if (! $channels->get($channelKey)->supports($s)) {
                            continue;
                        }
                        $delivery = NewsletterDelivery::firstOrCreate(
                            ['campaign_id' => $campaign->id, 'subscriber_id' => $s->id, 'channel' => $channelKey],
                            ['status' => NewsletterDelivery::STATUS_QUEUED, 'queued_at' => now(), 'variant' => $variant],
                        );
                        if ($delivery->wasRecentlyCreated || $delivery->status === NewsletterDelivery::STATUS_QUEUED) {
                            $jobs[] = new SendDelivery($delivery->id);
                            $created++;
                        }
                    }
                }
            });

        $campaign->forceFill(['recipients_count' => $created])->save();

        if ($jobs === []) {
            $campaign->forceFill(['status' => NewsletterCampaign::STATUS_SENT, 'finished_at' => now()])->save();

            return;
        }

        $batch = Bus::batch($jobs)
            ->name('newsletter:' . $campaign->id)
            ->allowFailures()
            ->onQueue('newsletter')
            ->finally(function (Batch $batch) use ($campaign) {
                $fresh = NewsletterCampaign::find($campaign->id);
                if ($fresh && $fresh->status === NewsletterCampaign::STATUS_SENDING) {
                    $fresh->refreshCounters();
                    $fresh->forceFill(['status' => NewsletterCampaign::STATUS_SENT, 'finished_at' => now()])->save();
                }
            })
            ->dispatch();

        $campaign->forceFill(['batch_id' => $batch->id])->save();
        Log::info("[Newsletter] Kampania {$campaign->id}: zakolejkowano {$created} dostaw (batch {$batch->id}).");
    }
}
