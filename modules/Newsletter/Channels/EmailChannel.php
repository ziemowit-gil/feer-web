<?php

declare(strict_types=1);

namespace Modules\Newsletter\Channels;

use App\Models\SiteSetting;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Mail;
use Modules\Newsletter\Mail\CampaignMail;
use Modules\Newsletter\Models\NewsletterCampaign;
use Modules\Newsletter\Models\NewsletterDelivery;
use Modules\Newsletter\Services\CampaignRenderer;
use Symfony\Component\Mailer\Exception\TransportException;
use Throwable;

class EmailChannel implements ChannelDriver
{
    public function __construct(private readonly CampaignRenderer $renderer) {}

    public function key(): string
    {
        return 'email';
    }

    public function label(): string
    {
        return 'E-mail';
    }

    public function enabled(): bool
    {
        return true;
    }

    public function supports(Subscriber $s): bool
    {
        return $s->canReceive('email') && filter_var($s->email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function render(NewsletterCampaign $campaign, Subscriber $subscriber, NewsletterDelivery $delivery): OutboundMessage
    {
        $r = $this->renderer->render($campaign, $subscriber, $delivery);

        return new OutboundMessage('email', $r['subject'], $r['html'], $r['text'], headers: [
            'List-Unsubscribe'      => '<' . route('newsletter.unsubscribe', ['token' => $subscriber->token]) . '>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            'X-Newsletter-Delivery' => $delivery->uuid,
            'Precedence'            => 'bulk',
        ]);
    }

    public function send(OutboundMessage $message, NewsletterDelivery $delivery): DeliveryResult
    {
        $site     = SiteSetting::current();
        $campaign = $delivery->campaign;
        $provider = (string) ($site->newsletter_mailer ?: config('mail.default'));

        try {
            $mailable = new CampaignMail($message, $campaign, $delivery);
            Mail::mailer('newsletter')->to($delivery->subscriber->email, $delivery->subscriber->name ?: null)->send($mailable);

            return DeliveryResult::success($provider, $mailable->sentMessageId);
        } catch (TransportException $e) {
            $permanent = (bool) preg_match('/\b5\d\d\b/', $e->getMessage()) && ! preg_match('/\b(421|450|451|452)\b/', $e->getMessage());

            return DeliveryResult::failure($provider, $e->getMessage(), 'transport', $permanent);
        } catch (Throwable $e) {
            return DeliveryResult::failure($provider, $e->getMessage(), 'exception');
        }
    }

    public function maxPerMinute(): int
    {
        return max(1, (int) (SiteSetting::current()->newsletter_rate_per_minute ?: 60));
    }
}
