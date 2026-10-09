<?php

declare(strict_types=1);

namespace Modules\Newsletter\Jobs;

use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Newsletter\Models\NewsletterBounce;
use Modules\Newsletter\Models\NewsletterDelivery;
use Modules\Newsletter\Models\NewsletterSuppression;

/**
 * Zdarzenie zwrotne od dostawcy (bounce / complaint / delivered) — znormalizowane
 * przez kontroler webhooków: {type, provider, event_id, email, message_id, code, reason, at}.
 */
class ProcessBounceEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $event) {}

    public function handle(): void
    {
        $e = $this->event;
        $delivery = null;
        if (! empty($e['message_id'])) {
            $delivery = NewsletterDelivery::where('provider_message_id', $e['message_id'])->first();
        }
        if (! empty($e['delivery_uuid'])) {
            $delivery ??= NewsletterDelivery::where('uuid', $e['delivery_uuid'])->first();
        }
        $subscriber = $delivery?->subscriber ?? (! empty($e['email']) ? Subscriber::findByEmail($e['email']) : null);

        if ($e['type'] === 'delivered') {
            $delivery?->forceFill(['status' => NewsletterDelivery::STATUS_DELIVERED, 'delivered_at' => now()])->save();

            return;
        }

        if (! empty($e['event_id']) && NewsletterBounce::where('provider', $e['provider'])->where('provider_event_id', $e['event_id'])->exists()) {
            return;
        }

        NewsletterBounce::create([
            'delivery_id'       => $delivery?->id,
            'subscriber_id'     => $subscriber?->id,
            'type'              => $e['type'],
            'provider'          => $e['provider'],
            'provider_event_id' => $e['event_id'] ?? null,
            'smtp_code'         => $e['code'] ?? null,
            'reason'            => isset($e['reason']) ? mb_substr((string) $e['reason'], 0, 500) : null,
            'raw_payload'       => $e['raw'] ?? null,
            'occurred_at'       => $e['at'] ?? now(),
            'processed_at'      => now(),
        ]);

        match ($e['type']) {
            'hard' => $this->hard($delivery, $subscriber, $e),
            'soft' => $delivery?->forceFill(['status' => NewsletterDelivery::STATUS_SOFT_BOUNCED, 'error_message' => $e['reason'] ?? null])->save(),
            'complaint' => $this->complaint($delivery, $subscriber, $e),
            default => null,
        };

        $delivery?->campaign?->refreshCounters();
    }

    private function hard(?NewsletterDelivery $d, ?Subscriber $s, array $e): void
    {
        $d?->forceFill(['status' => NewsletterDelivery::STATUS_HARD_BOUNCED, 'error_message' => $e['reason'] ?? null])->save();
        if ($s && $s->status !== Subscriber::STATUS_ANONYMIZED) {
            $s->forceFill(['status' => Subscriber::STATUS_BOUNCED, 'bounced_at' => now()])->saveQuietly();
            $s->logEvent('bounced', ['reason' => $e['reason'] ?? null, 'provider' => $e['provider']], $d?->campaign_id);
        }
    }

    private function complaint(?NewsletterDelivery $d, ?Subscriber $s, array $e): void
    {
        $d?->forceFill(['status' => NewsletterDelivery::STATUS_COMPLAINED])->save();
        if ($s && $s->status !== Subscriber::STATUS_ANONYMIZED) {
            $s->forceFill(['status' => Subscriber::STATUS_COMPLAINED, 'complained_at' => now()])->saveQuietly();
            $s->consents()->whereNull('revoked_at')->update(['revoked_at' => now(), 'revoked_via' => 'complaint']);
            NewsletterSuppression::updateOrCreate(['email_hash' => $s->email_hash], ['reason' => 'complaint', 'expires_at' => null]);
            $s->logEvent('complained', ['provider' => $e['provider']], $d?->campaign_id);
        }
    }
}
