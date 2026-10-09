<?php

declare(strict_types=1);

namespace Modules\Newsletter\Channels;

use App\Models\SiteSetting;
use App\Models\Subscriber;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Modules\Newsletter\Models\NewsletterCampaign;
use Modules\Newsletter\Models\NewsletterDelivery;
use Modules\Newsletter\Services\CampaignRenderer;
use Throwable;

/** Web Push przez istniejące `push_subscriptions` powiązane z subskrybentem. */
class WebPushChannel implements ChannelDriver
{
    public function __construct(private readonly CampaignRenderer $renderer) {}

    public function key(): string
    {
        return 'webpush';
    }

    public function label(): string
    {
        return 'Powiadomienia push';
    }

    public function enabled(): bool
    {
        return (bool) SiteSetting::current()->newsletter_webpush_enabled
            && filled(config('webpush.vapid.public_key'))
            && filled(config('webpush.vapid.private_key'));
    }

    public function supports(Subscriber $s): bool
    {
        return $s->canReceive('webpush') && $s->pushSubscriptions()->exists();
    }

    public function render(NewsletterCampaign $campaign, Subscriber $subscriber, NewsletterDelivery $delivery): OutboundMessage
    {
        $short = $this->renderer->renderShort($campaign, $subscriber, $delivery);

        return new OutboundMessage('webpush', $short['title'], text: $short['text'], url: $short['url']);
    }

    public function send(OutboundMessage $message, NewsletterDelivery $delivery): DeliveryResult
    {
        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject'    => config('webpush.vapid.subject', config('app.url')),
                    'publicKey'  => config('webpush.vapid.public_key'),
                    'privateKey' => config('webpush.vapid.private_key'),
                ],
            ]);
            $payload = json_encode([
                'title' => $message->subject,
                'body'  => $message->text,
                'url'   => $message->url,
                'icon'  => SiteSetting::current()->getFirstMediaUrl('logo') ?: null,
            ], JSON_UNESCAPED_UNICODE);

            $sent = 0;
            foreach ($delivery->subscriber->pushSubscriptions as $ps) {
                $webPush->queueNotification(Subscription::create([
                    'endpoint' => $ps->endpoint, 'publicKey' => $ps->p256dh_key, 'authToken' => $ps->auth_token,
                ]), $payload);
            }
            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    $sent++;
                } elseif ($report->isSubscriptionExpired()) {
                    $delivery->subscriber->pushSubscriptions()->where('endpoint', $report->getEndpoint())->delete();
                }
            }

            return $sent > 0
                ? DeliveryResult::success('webpush', (string) $sent)
                : DeliveryResult::failure('webpush', 'Żadna subskrypcja push nie przyjęła powiadomienia.', 'expired', true);
        } catch (Throwable $e) {
            return DeliveryResult::failure('webpush', $e->getMessage(), 'exception');
        }
    }

    public function maxPerMinute(): int
    {
        return 600;
    }
}
