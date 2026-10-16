<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\Subscriber;
use Modules\Newsletter\Models\NewsletterCampaign;
use Modules\Newsletter\Models\NewsletterDelivery;
use Modules\Newsletter\Services\Deliverability;
use Modules\Newsletter\Services\QueueHealth;
use Modules\Newsletter\Services\MosaicoImageSizes;

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

        $queueHealth = app(QueueHealth::class)->snapshot();

        return view('newsletter::admin.dashboard', compact('active', 'pending', 'new30', 'lost30', 'recent', 'scheduled', 'avgOpen', 'avgClick', 'bounce', 'growth', 'queueHealth') + [
            'deliverability' => app(Deliverability::class)->check($site),
            'site' => $site,
            'imageSizes' => collect(MosaicoImageSizes::templates())->map(fn ($name, $key) => MosaicoImageSizes::forTemplate($key))->filter()->all(),
        ]);
    }
}
