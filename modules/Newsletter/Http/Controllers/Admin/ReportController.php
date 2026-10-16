<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\Subscriber;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Newsletter\Models\NewsletterCampaign;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Porównanie kampanii w czasie (otwarcia, kliknięcia, wypisy, odbicia) i eksport
 * zestawienia do CSV/PDF — same wskaźniki zbiorcze, bez danych osobowych
 * (np. do sprawozdań dla grantodawców).
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class ReportController extends Controller
{
    public const PERIODS = ['3' => 'Ostatnie 3 miesiące', '6' => 'Ostatnie 6 miesięcy', '12' => 'Ostatnie 12 miesięcy', 'year' => 'Bieżący rok', 'prev_year' => 'Poprzedni rok', 'all' => 'Cały okres'];

    public function index(Request $request)
    {
        return view('newsletter::admin.reports.index', $this->data($request) + ['periods' => self::PERIODS]);
    }

    public function csv(Request $request): StreamedResponse
    {
        $data = $this->data($request);

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['kampania', 'data_wysylki', 'odbiorcy', 'dostarczone', 'otwarcia_unik', 'otwarcia_proc', 'klikniecia_unik', 'klikniecia_proc', 'ctor_proc', 'wypisy', 'wypisy_proc', 'odbicia', 'odbicia_proc'], ';');
            foreach ($data['rows'] as $r) {
                fputcsv($out, [$r['title'], $r['date']?->format('Y-m-d'), $r['recipients'], $r['delivered'], $r['opened'], $this->num($r['open_rate']), $r['clicked'], $this->num($r['click_rate']), $this->num($r['ctor']), $r['unsubscribed'], $this->num($r['unsub_rate']), $r['bounced'], $this->num($r['bounce_rate'])], ';');
            }
            $t = $data['totals'];
            fputcsv($out, ['RAZEM / ŚREDNIO', '', $t['recipients'], $t['delivered'], $t['opened'], $this->num($t['open_rate']), $t['clicked'], $this->num($t['click_rate']), $this->num($t['ctor']), $t['unsubscribed'], $this->num($t['unsub_rate']), $t['bounced'], $this->num($t['bounce_rate'])], ';');
            fclose($out);
        }, 'newsletter-zestawienie-' . $data['slug'] . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(Request $request)
    {
        $data = $this->data($request);

        return Pdf::loadView('newsletter::admin.reports.pdf', $data + ['site' => SiteSetting::current(), 'printedAt' => now()->format('d.m.Y')])
            ->setPaper('a4', 'landscape')
            ->download('newsletter-zestawienie-' . $data['slug'] . '.pdf');
    }

    /** PDF podsumowania jednej kampanii (wskaźniki, oś czasu dzienna, linki) — bez adresów odbiorców. */
    public function campaignPdf(NewsletterCampaign $campaign)
    {
        $campaign->refreshCounters();
        $row = $this->row($campaign);

        return Pdf::loadView('newsletter::admin.reports.campaign-pdf', [
            'campaign' => $campaign, 'row' => $row, 'links' => $campaign->links()->orderByDesc('clicks_unique')->limit(15)->get(),
            'site' => SiteSetting::current(), 'printedAt' => now()->format('d.m.Y'),
        ])->setPaper('a4')->download('raport-kampanii-' . $campaign->id . '.pdf');
    }

    /** @return array{rows: Collection, totals: array, monthly: Collection, period: string, from: ?Carbon, to: Carbon, label: string, slug: string, growth: array} */
    private function data(Request $request): array
    {
        $period = (string) $request->query('okres', '12');
        $period = array_key_exists($period, self::PERIODS) ? $period : '12';
        [$from, $to] = match ($period) {
            'year'      => [now()->startOfYear(), now()],
            'prev_year' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            'all'       => [null, now()],
            default     => [now()->subMonths((int) $period)->startOfDay(), now()],
        };

        $campaigns = NewsletterCampaign::where('status', NewsletterCampaign::STATUS_SENT)
            ->when($from, fn ($q) => $q->where('finished_at', '>=', $from))->where('finished_at', '<=', $to)
            ->orderBy('finished_at')->get();
        $rows = $campaigns->map(fn (NewsletterCampaign $c) => $this->row($c));

        $sum = fn (string $k) => (int) $rows->sum($k);
        $totals = [
            'campaigns' => $rows->count(), 'recipients' => $sum('recipients'), 'delivered' => $sum('delivered'), 'opened' => $sum('opened'),
            'clicked' => $sum('clicked'), 'unsubscribed' => $sum('unsubscribed'), 'bounced' => $sum('bounced'), 'sent' => $sum('sent'),
        ];
        $totals += $this->rates($totals);

        $monthly = $rows->groupBy(fn ($r) => $r['date']?->format('Y-m'))->map(function (Collection $g, $ym) {
            $t = ['campaigns' => $g->count(), 'delivered' => (int) $g->sum('delivered'), 'opened' => (int) $g->sum('opened'), 'clicked' => (int) $g->sum('clicked'), 'unsubscribed' => (int) $g->sum('unsubscribed'), 'bounced' => (int) $g->sum('bounced'), 'sent' => (int) $g->sum('sent')];

            return ['month' => Carbon::createFromFormat('Y-m', (string) $ym)->startOfMonth()] + $t + $this->rates($t);
        })->values();

        $growthFrom = $from ?? ($rows->first()['date'] ?? now()->subYear());
        $growth = [
            'new'          => Subscriber::whereNotNull('confirmed_at')->whereBetween('confirmed_at', [$growthFrom, $to])->count(),
            'unsubscribed' => Subscriber::whereNotNull('unsubscribed_at')->whereBetween('unsubscribed_at', [$growthFrom, $to])->count(),
            'active_now'   => Subscriber::confirmed()->count(),
        ];

        $label = self::PERIODS[$period] . ($from ? ' (' . $from->format('d.m.Y') . '–' . $to->format('d.m.Y') . ')' : '');

        return ['rows' => $rows, 'totals' => $totals, 'monthly' => $monthly, 'period' => $period, 'from' => $from, 'to' => $to, 'label' => $label, 'slug' => $period . '-' . now()->format('Y-m-d'), 'growth' => $growth];
    }

    private function row(NewsletterCampaign $c): array
    {
        $r = [
            'id' => $c->id, 'title' => (string) $c->title, 'subject' => (string) $c->subject, 'date' => $c->finished_at ?? $c->started_at,
            'recipients' => (int) $c->recipients_count, 'sent' => (int) $c->sent_count, 'delivered' => (int) $c->delivered_count,
            'opened' => (int) $c->opened_unique, 'clicked' => (int) $c->clicked_unique, 'unsubscribed' => (int) $c->unsubscribed_count, 'bounced' => (int) $c->bounced_count,
        ];

        return $r + $this->rates($r);
    }

    private function rates(array $t): array
    {
        $pct = fn (int $a, int $b, int $dec = 1) => $b > 0 ? round($a / $b * 100, $dec) : null;

        return [
            'open_rate' => $pct($t['opened'], $t['delivered']), 'click_rate' => $pct($t['clicked'], $t['delivered']),
            'ctor' => $pct($t['clicked'], $t['opened']), 'unsub_rate' => $pct($t['unsubscribed'], $t['delivered'], 2),
            'bounce_rate' => $pct($t['bounced'], $t['sent'], 2),
        ];
    }

    private function num(?float $v): string
    {
        return $v === null ? '' : str_replace('.', ',', (string) $v);
    }
}
