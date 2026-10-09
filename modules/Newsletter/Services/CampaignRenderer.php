<?php

declare(strict_types=1);

namespace Modules\Newsletter\Services;

use App\Models\Subscriber;
use Modules\Newsletter\Models\NewsletterCampaign;
use Modules\Newsletter\Models\NewsletterDelivery;

/**
 * Składa gotową wiadomość dla odbiorcy: treść dynamiczna → personalizacja →
 * śledzenie. Ten sam kod obsługuje podgląd, wersję www i wysyłkę.
 */
class CampaignRenderer
{
    public function __construct(
        private readonly ContentFeeder $feeder,
        private readonly Personalizer $personalizer,
        private readonly TrackingRewriter $tracking,
    ) {}

    /** @return array{subject:string, html:string, text:string, feed:array} */
    public function render(NewsletterCampaign $campaign, Subscriber $subscriber, ?NewsletterDelivery $delivery = null, bool $track = true): array
    {
        $html = (string) $campaign->html_body;
        $feed = $this->feeder->inject($html, $campaign, $subscriber);
        $html = $feed['html'];

        $vars    = $this->personalizer->variables($subscriber, $campaign, $delivery);
        $subject = $this->subjectFor($campaign, $delivery);
        $subject = $this->personalizer->render($subject, $vars);
        $html    = $this->personalizer->render($html, $vars);
        $html    = $this->injectPreheader($html, $this->personalizer->render((string) $campaign->preheader, $vars));

        if ($track && $delivery) {
            $html = $this->tracking->rewrite($html, $campaign, $delivery);
        }

        $text = $campaign->text_body
            ? $this->personalizer->render($campaign->text_body, $vars)
            : HtmlToText::convert($html);

        return ['subject' => $subject, 'html' => $html, 'text' => $text, 'feed' => $feed];
    }

    /** Wariant skrócony (push / SMS) z personalizacją. */
    public function renderShort(NewsletterCampaign $campaign, Subscriber $subscriber, ?NewsletterDelivery $delivery = null): array
    {
        $vars = $this->personalizer->variables($subscriber, $campaign, $delivery);
        $url  = $campaign->short_url ?: $vars['webversion_url'];
        if ($delivery && $campaign->track_clicks && preg_match('#^https?://#', $url)) {
            $campaign->links()->firstOrCreate(
                ['hash' => \Modules\Newsletter\Models\NewsletterCampaignLink::hashFor($url)],
                ['url' => $url, 'label' => 'Link ' . $delivery->channel, 'position' => 999],
            );
            $url = route('newsletter.click', ['uuid' => $delivery->uuid, 'hash' => \Modules\Newsletter\Models\NewsletterCampaignLink::hashFor($url)]);
        }

        return [
            'title' => html_entity_decode($this->personalizer->render((string) ($campaign->short_title ?: $campaign->subject), $vars)),
            'text'  => html_entity_decode($this->personalizer->render((string) ($campaign->short_text ?: $campaign->preheader), $vars)),
            'url'   => $url,
        ];
    }

    private function subjectFor(NewsletterCampaign $campaign, ?NewsletterDelivery $delivery): string
    {
        if ($delivery?->variant === 'B' && filled($campaign->subject_b)) {
            return (string) $campaign->subject_b;
        }

        return (string) $campaign->subject;
    }

    private function injectPreheader(string $html, string $preheader): string
    {
        $preheader = trim(strip_tags($preheader));
        if ($preheader === '' || str_contains($html, 'data-nl-preheader')) {
            return $html;
        }
        $span = '<div data-nl-preheader style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all">' . e($preheader) . '</div>';

        return preg_match('/<body[^>]*>/i', $html, $m) ? str_replace($m[0], $m[0] . $span, $html) : $span . $html;
    }
}
