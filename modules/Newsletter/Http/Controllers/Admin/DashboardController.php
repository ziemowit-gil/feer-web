<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\Subscriber;
use Illuminate\Support\Facades\DB;
use Modules\Newsletter\Models\NewsletterCampaign;
use Modules\Newsletter\Models\NewsletterDelivery;

/**
 * Pulpit newslettera: KPI, ostatnie i zaplanowane kampanie, wzrost bazy, dostarczalność.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class DashboardController extends Controller
{
    public function index()
    {
        $site = SiteSetting::current();

        $active   = Subscriber::confirmed()->count();
        $pending  = Subscriber::where('status', Subscriber::STATUS_PENDING)->count();
        $new30    = Subscriber::confirmed()->where('confirmed_at', '>=', now()->subDays(30))->count();
        $lost30   = Subscriber::where('unsubscribed_at', '>=', now()->subDays(30))->count();

        $sent = NewsletterCampaign::where('status', NewsletterCampaign::STATUS_SENT)->whereNull('parent_campaign_id')
            ->orWhere(fn ($q) => $q->where('status', NewsletterCampaign::STATUS_SENT)->whereNotNull('parent_campaign_id'))
            ->orderByDesc('finished_at')->take(8)->get();

        $recent = NewsletterCampaign::whereIn('status', [NewsletterCampaign::STATUS_SENT, NewsletterCampaign::STATUS_SENDING, NewsletterCampaign::STATUS_PAUSED])
            ->orderByDesc('started_at')->take(8)->get();
        $scheduled = NewsletterCampaign::where('status', NewsletterCampaign::STATUS_SCHEDULED)->orderBy('scheduled_at')->take(5)->get();

        $totals = NewsletterCampaign::where('status', NewsletterCampaign::STATUS_SENT)->where('finished_at', '>=', now()->subDays(90))
            ->selectRaw('SUM(delivered_count) d, SUM(opened_unique) o, SUM(clicked_unique) c, SUM(bounced_count) b, SUM(sent_count) s')->first();
        $avgOpen  = $totals && $totals->d > 0 ? round($totals->o / $totals->d * 100, 1) : null;
        $avgClick = $totals && $totals->d > 0 ? round($totals->c / $totals->d * 100, 1) : null;
        $bounce   = $totals && $totals->s > 0 ? round($totals->b / $totals->s * 100, 2) : null;

        // Wzrost bazy: potwierdzeni per tydzień, ostatnie 12 tygodni.
        $growth = [];
        for ($i = 11; $i >= 0; $i--) {
            $from = now()->subWeeks($i)->startOfWeek();
            $growth[] = ['label' => $from->format('d.m'), 'value' => Subscriber::where('confirmed_at', '<', $from->copy()->endOfWeek())->whereIn('status', [Subscriber::STATUS_CONFIRMED])->count()];
        }

        $queue = DB::table('jobs')->where('queue', 'newsletter')->count();
        $failedJobs = DB::table('failed_jobs')->where('payload', 'like', '%Newsletter%')->count();

        return view('newsletter::admin.dashboard', compact('active', 'pending', 'new30', 'lost30', 'recent', 'scheduled', 'avgOpen', 'avgClick', 'bounce', 'growth', 'queue', 'failedJobs') + [
            'deliverability' => $this->deliverability($site),
            'site' => $site,
        ]);
    }

    /** SPF / DKIM / DMARC dla domeny nadawcy (tylko odczyt DNS). */
    private function deliverability(SiteSetting $site): array
    {
        $from = (string) ($site->newsletter_from_address ?: config('mail.from.address'));
        $domain = strtolower((string) substr(strrchr($from, '@') ?: '', 1));
        if ($domain === '' || in_array($domain, ['example.com', 'localhost'], true)) {
            return ['domain' => $domain, 'spf' => null, 'dmarc' => null, 'dkim' => null];
        }
        $txt = fn (string $host) => collect(@dns_get_record($host, DNS_TXT) ?: [])->pluck('txt')->all();
        $spf   = collect($txt($domain))->first(fn ($t) => str_starts_with((string) $t, 'v=spf1'));
        $dmarc = collect($txt('_dmarc.' . $domain))->first(fn ($t) => str_starts_with((string) $t, 'v=DMARC1'));
        $dkim  = null;
        foreach (['default', 'selector1', 'selector2', 'mailgun', 'k1', 'pm', 'amazonses', 'google'] as $sel) {
            if (@dns_get_record($sel . '._domainkey.' . $domain, DNS_TXT | DNS_CNAME)) {
                $dkim = $sel;
                break;
            }
        }

        return ['domain' => $domain, 'spf' => $spf, 'dmarc' => $dmarc, 'dkim' => $dkim];
    }
}
