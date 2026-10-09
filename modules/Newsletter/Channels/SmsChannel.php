<?php

declare(strict_types=1);

namespace Modules\Newsletter\Channels;

use App\Models\SiteSetting;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Http;
use Modules\Newsletter\Models\NewsletterCampaign;
use Modules\Newsletter\Models\NewsletterDelivery;
use Modules\Newsletter\Services\CampaignRenderer;
use Throwable;

/**
 * SMS (opcjonalny). Obsługiwani dostawcy: smsapi (SMSAPI.pl, OAuth token) oraz
 * generyczny webhook (POST JSON {to, from, text}). Wymaga osobnej zgody SMS.
 */
class SmsChannel implements ChannelDriver
{
    public function __construct(private readonly CampaignRenderer $renderer) {}

    public function key(): string
    {
        return 'sms';
    }

    public function label(): string
    {
        return 'SMS';
    }

    public function enabled(): bool
    {
        $site = SiteSetting::current();

        return filled($site->newsletter_sms_provider) && filled($site->newsletter_sms_token);
    }

    public function supports(Subscriber $s): bool
    {
        return $s->canReceive('sms') && filled($s->phone)
            && $s->consents()->where('channel', 'sms')->whereNull('revoked_at')->whereNotNull('confirmed_at')->exists();
    }

    public function render(NewsletterCampaign $campaign, Subscriber $subscriber, NewsletterDelivery $delivery): OutboundMessage
    {
        $short = $this->renderer->renderShort($campaign, $subscriber, $delivery);
        $text  = trim($short['text'] !== '' ? $short['text'] : $short['title']);
        $text  = mb_substr($text, 0, 160 - mb_strlen($short['url']) - 1) . ' ' . $short['url'];

        return new OutboundMessage('sms', $short['title'], text: $text, url: $short['url']);
    }

    public function send(OutboundMessage $message, NewsletterDelivery $delivery): DeliveryResult
    {
        $site  = SiteSetting::current();
        $phone = (string) $delivery->subscriber->phone;

        try {
            if ($site->newsletter_sms_provider === 'smsapi') {
                $res = Http::withToken((string) $site->newsletter_sms_token)->timeout(10)->asForm()
                    ->post('https://api.smsapi.pl/sms.do', [
                        'to' => $phone, 'from' => $site->newsletter_sms_sender ?: 'Info', 'message' => $message->text,
                        'format' => 'json', 'encoding' => 'utf-8',
                    ]);
                $json = $res->json();
                if ($res->successful() && empty($json['error'])) {
                    return DeliveryResult::success('smsapi', $json['list'][0]['id'] ?? null);
                }

                return DeliveryResult::failure('smsapi', (string) ($json['message'] ?? $res->body()), (string) ($json['error'] ?? $res->status()));
            }

            // generyczny webhook
            $res = Http::withToken((string) $site->newsletter_sms_token)->timeout(10)->acceptJson()
                ->post((string) $site->newsletter_sms_provider, ['to' => $phone, 'from' => $site->newsletter_sms_sender, 'text' => $message->text]);

            return $res->successful()
                ? DeliveryResult::success('sms-webhook', (string) ($res->json('id') ?? ''))
                : DeliveryResult::failure('sms-webhook', $res->body(), (string) $res->status());
        } catch (Throwable $e) {
            return DeliveryResult::failure((string) $site->newsletter_sms_provider, $e->getMessage(), 'exception');
        }
    }

    public function maxPerMinute(): int
    {
        return 100;
    }
}
