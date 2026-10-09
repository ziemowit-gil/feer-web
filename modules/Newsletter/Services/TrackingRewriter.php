<?php

declare(strict_types=1);

namespace Modules\Newsletter\Services;

use Modules\Newsletter\Models\NewsletterCampaign;
use Modules\Newsletter\Models\NewsletterCampaignLink;
use Modules\Newsletter\Models\NewsletterDelivery;

/**
 * Przepisuje linki na /n/c/{uuid}/{hash} i dokleja piksel otwarcia.
 * Linki systemowe (wypis, preferencje, mailto, tel, kotwice) zostają bez zmian.
 */
class TrackingRewriter
{
    private const SKIP_PREFIXES = ['mailto:', 'tel:', 'sms:', '#', 'javascript:'];

    /** @var array<int, array<string, bool>> hash-e linków już zapisane w tej instancji */
    private array $known = [];

    /** Buduje słownik linków kampanii (po personalizacji tagów systemowych nie trzeba — hash liczymy z surowego URL). */
    public function collectLinks(NewsletterCampaign $campaign, string $html): void
    {
        $position = 0;
        $seen = [];

        preg_replace_callback('/<a\b[^>]*\bhref\s*=\s*("|\')([^"\']+)\1[^>]*>(.*?)<\/a>/is', function ($m) use ($campaign, &$position, &$seen) {
            $url = html_entity_decode(trim($m[2]));
            if ($this->shouldSkip($url) || isset($seen[$url])) {
                return $m[0];
            }
            $seen[$url] = true;
            $label = trim(strip_tags($m[3]));
            if ($label === '' && preg_match('/alt\s*=\s*("|\')([^"\']*)\1/i', $m[3], $alt)) {
                $label = $alt[2];
            }

            NewsletterCampaignLink::updateOrCreate(
                ['campaign_id' => $campaign->id, 'hash' => NewsletterCampaignLink::hashFor($url)],
                ['url' => $url, 'label' => mb_substr($label, 0, 255), 'position' => $position++],
            );

            return $m[0];
        }, $html);
    }

    public function rewrite(string $html, NewsletterCampaign $campaign, NewsletterDelivery $delivery): string
    {
        if ($campaign->track_clicks) {
            $html = (string) preg_replace_callback('/(<a\b[^>]*\bhref\s*=\s*)("|\')([^"\']+)\2/i', function ($m) use ($campaign, $delivery) {
                $url = html_entity_decode(trim($m[3]));
                if ($this->shouldSkip($url)) {
                    return $m[0];
                }
                $hash = NewsletterCampaignLink::hashFor($url);
                // Linki z treści dynamicznej (aktualności) pojawiają się dopiero przy renderowaniu,
                // więc słownik uzupełniamy także tutaj (unikalny indeks campaign+hash).
                if (! isset($this->known[$campaign->id][$hash])) {
                    NewsletterCampaignLink::firstOrCreate(
                        ['campaign_id' => $campaign->id, 'hash' => $hash],
                        ['url' => $url, 'label' => null, 'position' => 500],
                    );
                    $this->known[$campaign->id][$hash] = true;
                }
                $tracked = route('newsletter.click', ['uuid' => $delivery->uuid, 'hash' => $hash]);

                return $m[1] . $m[2] . e($tracked) . $m[2];
            }, $html);
        }

        if ($campaign->track_opens && $delivery->channel === 'email') {
            $pixel = '<img src="' . e(route('newsletter.open', ['uuid' => $delivery->uuid])) . '" width="1" height="1" alt="" style="display:block;width:1px;height:1px;border:0;overflow:hidden" />';
            $html = str_contains($html, '</body>') ? str_replace('</body>', $pixel . '</body>', $html) : $html . $pixel;
        }

        return $html;
    }

    public function withUtm(string $url, NewsletterCampaign $campaign): string
    {
        $utm = array_filter((array) ($campaign->utm ?? []));
        if ($utm === [] || ! preg_match('#^https?://#i', $url)) {
            return $url;
        }
        $params = [];
        foreach (['source', 'medium', 'campaign', 'content'] as $k) {
            if (! empty($utm[$k])) {
                $params['utm_' . $k] = $utm[$k];
            }
        }
        if ($params === [] || str_contains($url, 'utm_')) {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
    }

    private function shouldSkip(string $url): bool
    {
        $lower = strtolower($url);
        foreach (self::SKIP_PREFIXES as $p) {
            if (str_starts_with($lower, $p)) {
                return true;
            }
        }
        // tagi systemowe (wypis, preferencje, wersja www) nie są śledzone
        if (str_contains($url, '{{') || str_contains($url, '[unsubscribe_link]') || str_contains($url, '[show_link]') || str_contains($url, '[profile_link]')) {
            return true;
        }
        foreach (['/n/u/', '/n/p/', '/n/w/', '/n/c/', '/n/o/'] as $sys) {
            if (str_contains($url, $sys)) {
                return true;
            }
        }

        return $url === '';
    }
}
