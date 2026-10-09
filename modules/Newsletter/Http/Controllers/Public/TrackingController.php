<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Modules\Newsletter\Models\NewsletterCampaign;
use Modules\Newsletter\Models\NewsletterCampaignLink;
use Modules\Newsletter\Models\NewsletterDelivery;
use Modules\Newsletter\Services\CampaignRenderer;

/**
 * Piksel otwarcia, przekierowania kliknięć i wersja „zobacz w przeglądarce".
 * Bez sesji i ciasteczek; IP pseudonimizowane.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class TrackingController extends Controller
{
    private const GIF = "GIF89a\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xff\xff\xff\x21\xf9\x04\x01\x00\x00\x00\x00\x2c\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x44\x01\x00\x3b";

    public function open(Request $request, string $uuid)
    {
        $delivery = NewsletterDelivery::where('uuid', $uuid)->first();

        if ($delivery && $delivery->campaign?->track_opens) {
            $ua = (string) $request->userAgent();
            $delivery->opens()->create([
                'opened_at'     => now(),
                'ip_hash'       => Subscriber::hashIp($request->ip()),
                'client_family' => $this->clientFamily($ua),
                'is_proxy'      => (bool) preg_match('/GoogleImageProxy|Apple|YahooMailProxy/i', $ua),
            ]);
            $first = $delivery->opened_at === null;
            $delivery->forceFill(['opened_at' => $delivery->opened_at ?? now(), 'opens_count' => $delivery->opens_count + 1])->save();
            $delivery->subscriber?->forceFill(['last_open_at' => now()])->saveQuietly();
            if ($first) {
                $delivery->campaign->increment('opened_unique');
            }
        }

        return response(self::GIF, 200, [
            'Content-Type'  => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
            'Expires'       => '0',
        ]);
    }

    public function click(Request $request, string $uuid, string $hash)
    {
        $delivery = NewsletterDelivery::where('uuid', $uuid)->firstOrFail();
        $link = NewsletterCampaignLink::where('campaign_id', $delivery->campaign_id)->where('hash', $hash)->firstOrFail();

        if ($delivery->campaign?->track_clicks) {
            $delivery->clicks()->create([
                'delivery_id' => $delivery->id, 'link_id' => $link->id, 'clicked_at' => now(),
                'ip_hash' => Subscriber::hashIp($request->ip()), 'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            ]);
            $firstForDelivery = $delivery->clicked_at === null;
            $firstForLink = ! $delivery->clicks()->where('link_id', $link->id)->where('id', '!=', $delivery->clicks()->latest('id')->value('id'))->exists();
            $delivery->forceFill([
                'clicked_at' => $delivery->clicked_at ?? now(), 'opened_at' => $delivery->opened_at ?? now(), 'clicks_count' => $delivery->clicks_count + 1,
            ])->save();
            $delivery->subscriber?->forceFill(['last_click_at' => now(), 'last_open_at' => $delivery->subscriber->last_open_at ?? now()])->saveQuietly();
            $link->increment('clicks_total');
            if ($firstForLink) {
                $link->increment('clicks_unique');
            }
            if ($firstForDelivery) {
                $delivery->campaign->increment('clicked_unique');
            }
        }

        $url = app(\Modules\Newsletter\Services\TrackingRewriter::class)->withUtm($link->url, $delivery->campaign);

        return redirect()->away($url, 302, ['Cache-Control' => 'no-store']);
    }

    /** Wersja www wysłanej wiadomości (spersonalizowana, bez piksela). */
    public function web(string $uuid, CampaignRenderer $renderer)
    {
        $delivery = NewsletterDelivery::with(['campaign', 'subscriber'])->where('uuid', $uuid)->firstOrFail();
        abort_unless($delivery->campaign && $delivery->subscriber, 404);

        $r = $renderer->render($delivery->campaign, $delivery->subscriber, $delivery, track: false);

        return response($r['html'])->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /** Wersja www kampanii przed wysyłką (link w podglądzie), z danymi przykładowymi. */
    public function webPreview(string $uuid, CampaignRenderer $renderer)
    {
        $campaign = NewsletterCampaign::where('uuid', $uuid)->firstOrFail();
        abort_unless(auth()->check(), 404);

        $sample = new Subscriber(['email' => 'przyklad@example.com', 'name' => 'Anna Przykładowa', 'topics' => ['news'], 'token' => 'podglad']);
        $r = $renderer->render($campaign, $sample, null, track: false);

        return response($r['html'])->header('X-Robots-Tag', 'noindex, nofollow');
    }

    private function clientFamily(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'GoogleImageProxy') => 'gmail',
            (bool) preg_match('/Apple|iPhone|iPad|Macintosh/i', $ua) => 'apple_mail',
            (bool) preg_match('/Outlook|Microsoft Office|MSOffice/i', $ua) => 'outlook',
            (bool) preg_match('/Thunderbird/i', $ua) => 'thunderbird',
            (bool) preg_match('/Yahoo/i', $ua) => 'yahoo',
            default => 'other',
        };
    }
}
