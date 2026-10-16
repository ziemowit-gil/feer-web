<?php

declare(strict_types=1);

namespace Modules\Newsletter\Jobs;

use App\Models\Subscriber;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Newsletter\Channels\ChannelRegistry;
use Modules\Newsletter\Models\NewsletterCampaign;
use Modules\Newsletter\Models\NewsletterDelivery;
use Modules\Newsletter\Models\NewsletterSuppression;
use Modules\Newsletter\Services\SendWindow;
use App\Models\SiteSetting;

/** Wysyła jedną dostawę. Sprawdza stan subskrybenta i kampanii tuż przed wysłaniem. */
class SendDelivery implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public int $deliveryId) {}

    public function backoff(): array
    {
        return [60, 300, 1800];
    }

    /**
     * Odłożenia (limit na minutę, okno wysyłki, limit dzienny) nie mogą zjadać prób —
     * o porażce decyduje licznik prób samej dostawy ($delivery->attempts), nie licznik joba.
     */
    public function retryUntil(): \DateTimeInterface
    {
        return now()->addDays(10);
    }

    public function middleware(): array
    {
        $delivery = NewsletterDelivery::find($this->deliveryId);
        $channel  = $delivery?->channel ?? 'email';
        $limit    = app(ChannelRegistry::class)->get($channel)?->maxPerMinute() ?? 60;

        RateLimiter::for('newsletter-' . $channel, fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute($limit));

        return [(new RateLimited('newsletter-' . $channel))->releaseAfter(30)];
    }

    public function handle(ChannelRegistry $channels): void
    {
        $delivery = NewsletterDelivery::with(['campaign', 'subscriber'])->find($this->deliveryId);
        if (! $delivery || $delivery->status !== NewsletterDelivery::STATUS_QUEUED) {
            return;
        }

        $campaign   = $delivery->campaign;
        $subscriber = $delivery->subscriber;

        if (! $campaign || $campaign->status === NewsletterCampaign::STATUS_CANCELLED) {
            $delivery->forceFill(['status' => NewsletterDelivery::STATUS_SKIPPED, 'error_message' => 'Kampania anulowana'])->save();

            return;
        }
        if ($campaign->status === NewsletterCampaign::STATUS_PAUSED) {
            $this->release(120);

            return;
        }

        // Okno wysyłki (np. dni robocze 8–18) — poza nim czekamy do najbliższego otwarcia.
        $window = SendWindow::fromSettings();
        if ($next = $window->nextOpening()) {
            $this->release(max(60, (int) now()->diffInSeconds($next, true) + random_int(0, 300)));

            return;
        }

        // Limit dzienny (dzielenie dużej kampanii na części) — po jego wyczerpaniu reszta wychodzi następnego dnia.
        $perDay = (int) (SiteSetting::current()->newsletter_rate_per_day ?? 0);
        if ($perDay > 0 && $delivery->channel === 'email'
            && NewsletterDelivery::where('channel', 'email')->where('sent_at', '>=', now()->startOfDay())->count() >= $perDay) {
            $tomorrow = now()->addDay()->startOfDay();
            $open = $window->nextOpening($tomorrow) ?? $tomorrow;
            $this->release(max(60, (int) now()->diffInSeconds($open, true) + random_int(0, 600)));

            return;
        }

        $driver = $channels->get($delivery->channel);
        if (! $driver || ! $subscriber || ! $driver->supports($subscriber) || NewsletterSuppression::contains($subscriber->email_hash)) {
            $delivery->forceFill(['status' => NewsletterDelivery::STATUS_SKIPPED, 'error_message' => 'Odbiorca nie spełnia warunków (status, zgoda, lista tłumienia)'])->save();

            return;
        }

        $delivery->forceFill(['attempts' => $delivery->attempts + 1])->save();
        $message = $driver->render($campaign, $subscriber, $delivery);
        $result  = $driver->send($message, $delivery);

        if ($result->ok) {
            $delivery->forceFill([
                'status' => NewsletterDelivery::STATUS_SENT, 'sent_at' => now(), 'provider' => $result->provider,
                'provider_message_id' => $result->providerMessageId, 'error_code' => null, 'error_message' => null,
            ])->save();
            $subscriber->forceFill(['last_sent_at' => now()])->saveQuietly();
            $campaign->increment('sent_count');
            $campaign->increment('delivered_count');

            return;
        }

        if ($result->permanent || $delivery->attempts >= $this->tries) {
            $status = $result->permanent && $delivery->channel === 'email' ? NewsletterDelivery::STATUS_HARD_BOUNCED : NewsletterDelivery::STATUS_FAILED;
            $delivery->forceFill([
                'status' => $status, 'provider' => $result->provider, 'error_code' => $result->errorCode, 'error_message' => $result->errorMessage,
            ])->save();
            if ($status === NewsletterDelivery::STATUS_HARD_BOUNCED) {
                $subscriber->forceFill(['status' => Subscriber::STATUS_BOUNCED, 'bounced_at' => now()])->saveQuietly();
                $subscriber->logEvent('bounced', ['reason' => $result->errorMessage], $campaign->id);
                $campaign->increment('bounced_count');
            } else {
                $campaign->increment('failed_count');
            }

            return;
        }

        $delivery->forceFill(['error_code' => $result->errorCode, 'error_message' => $result->errorMessage])->save();
        $this->release($this->backoff()[min(max($delivery->attempts, 1) - 1, 2)]);
    }
}
