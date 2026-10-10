<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Modules\Newsletter\Channels\ChannelRegistry;
use Modules\Newsletter\Channels\OutboundMessage;
use Modules\Newsletter\Jobs\DispatchCampaign;
use Modules\Newsletter\Mail\CampaignMail;
use Modules\Newsletter\Models\NewsletterCampaign;
use Modules\Newsletter\Models\NewsletterClick;
use Modules\Newsletter\Models\NewsletterDelivery;
use Modules\Newsletter\Models\NewsletterList;
use Modules\Newsletter\Models\NewsletterOpen;
use Modules\Newsletter\Models\NewsletterSegment;
use Modules\Newsletter\Models\NewsletterTemplate;
use Modules\Newsletter\Services\AudienceResolver;
use Modules\Newsletter\Services\CampaignRenderer;
use Modules\Newsletter\Services\ContentFeeder;
use Modules\Newsletter\Services\Personalizer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Panel: kampanie newslettera — kreator (podstawy, odbiorcy, treść, test, wysyłka), sterowanie i raport.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $status = (string) $request->query('status', '');
        $campaigns = NewsletterCampaign::query()
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->whereNull('parent_campaign_id')
            ->orWhere(fn ($q) => $q->whereNotNull('parent_campaign_id')->when($status !== '', fn ($w) => $w->where('status', $status)))
            ->orderByDesc('updated_at')->paginate(30)->withQueryString();

        return view('newsletter::admin.campaigns.index', ['campaigns' => $campaigns, 'status' => $status, 'statuses' => NewsletterCampaign::STATUSES]);
    }

    public function create()
    {
        $site = SiteSetting::current();

        return $this->form(new NewsletterCampaign([
            'channels' => ['email'], 'audience' => ['all' => true], 'track_opens' => (bool) $site->newsletter_track_opens, 'track_clicks' => (bool) $site->newsletter_track_clicks,
            'from_name' => $site->newsletter_from_name, 'from_address' => $site->newsletter_from_address, 'reply_to' => $site->newsletter_reply_to,
            'utm' => ['source' => 'newsletter', 'medium' => 'email', 'campaign' => now()->format('Y-m')],
            'template_id' => NewsletterTemplate::where('is_default', true)->value('id'),
        ]));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $campaign = new NewsletterCampaign($data + ['site_id' => SiteSetting::current()->id, 'created_by' => auth()->id(), 'status' => NewsletterCampaign::STATUS_DRAFT]);

        if ($campaign->template_id && ($tpl = NewsletterTemplate::find($campaign->template_id))) {
            $campaign->mosaico_template = $tpl->mosaico_template ?: 'feer-1';
            $campaign->editor_metadata = $tpl->editor_metadata;
            $campaign->editor_content  = $tpl->editor_content;
            $campaign->html_body       = $tpl->html_body;
        }
        $campaign->mosaico_template ??= 'feer-1';
        $campaign->save();

        return redirect()->route('admin.newsletter.kampanie.editor', $campaign)->with('status', 'Kampania utworzona — teraz ułóż treść.');
    }

    public function show(NewsletterCampaign $campaign, AudienceResolver $audience, ChannelRegistry $channels)
    {
        $campaign->loadCount('deliveries');
        $byStatus = NewsletterDelivery::where('campaign_id', $campaign->id)->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');

        return view('newsletter::admin.campaigns.show', [
            'campaign'  => $campaign,
            'audienceCount' => $campaign->isEditable() ? $audience->count($campaign) : $campaign->recipients_count,
            'byStatus'  => $byStatus,
            'checks'    => $this->checks($campaign),
            'channels'  => $channels->all(),
            'lists'     => NewsletterList::whereIn('id', (array) ($campaign->audience['lists'] ?? []))->pluck('name'),
            'segments'  => NewsletterSegment::whereIn('id', (array) ($campaign->audience['segments'] ?? []))->pluck('name'),
            'topics'    => array_intersect_key(Subscriber::availableTopics(), array_flip((array) ($campaign->audience['topics'] ?? []))),
            'batchProgress' => $campaign->batch_id ? Bus::findBatch($campaign->batch_id) : null,
        ]);
    }

    public function edit(NewsletterCampaign $campaign)
    {
        abort_unless($campaign->isEditable(), 403, 'Kampanii w trakcie wysyłki nie można edytować.');

        return $this->form($campaign);
    }

    public function update(Request $request, NewsletterCampaign $campaign)
    {
        abort_unless($campaign->isEditable(), 403);
        $campaign->update($this->validated($request));
        if ($campaign->status === NewsletterCampaign::STATUS_CANCELLED || $campaign->status === NewsletterCampaign::STATUS_FAILED) {
            $campaign->forceFill(['status' => NewsletterCampaign::STATUS_DRAFT])->save();
        }

        return redirect()->route('admin.newsletter.kampanie.show', $campaign)->with('status', 'Kampania zapisana.');
    }

    public function destroy(NewsletterCampaign $campaign)
    {
        abort_if($campaign->isRunning(), 403, 'Najpierw anuluj wysyłkę.');
        $campaign->delete();

        return redirect()->route('admin.newsletter.kampanie.index')->with('status', 'Kampania przeniesiona do kosza.');
    }

    public function duplicate(NewsletterCampaign $campaign)
    {
        $copy = $campaign->replicate(['uuid', 'batch_id', 'status', 'scheduled_at', 'started_at', 'finished_at', 'recipients_count', 'sent_count', 'delivered_count', 'failed_count',
            'opened_unique', 'clicked_unique', 'bounced_count', 'complained_count', 'unsubscribed_count', 'parent_campaign_id', 'content_snapshot', 'last_feed_item_at', 'last_run_at', 'approved_by']);
        $copy->title = $campaign->title . ' (kopia)';
        $copy->status = NewsletterCampaign::STATUS_DRAFT;
        $copy->created_by = auth()->id();
        $copy->save();

        return redirect()->route('admin.newsletter.kampanie.edit', $copy)->with('status', 'Kampania zduplikowana.');
    }

    // ── Edytor / podgląd / test ──────────────────────────────────────────────

    public function editor(NewsletterCampaign $campaign, ContentFeeder $feeder)
    {
        abort_unless($campaign->isEditable(), 403);

        return view('newsletter::admin.editor', [
            'subject'  => $campaign,
            'saveUrl'  => route('admin.newsletter.kampanie.editor.save', $campaign),
            'backUrl'  => route('admin.newsletter.kampanie.show', $campaign),
            'title'    => 'Treść: ' . $campaign->title,
            'mosaicoTemplate' => $campaign->mosaico_template ?: 'feer-1',
            'newsCategories' => $feeder->newsCategories(),
        ]);
    }

    public function saveEditor(Request $request, NewsletterCampaign $campaign)
    {
        abort_unless($campaign->isEditable(), 403);
        $data = $request->validate(['metadata' => ['nullable', 'array'], 'content' => ['nullable', 'array'], 'html' => ['required', 'string'], 'text' => ['nullable', 'string']]);
        $campaign->update(['editor_metadata' => $data['metadata'] ?? $campaign->editor_metadata, 'editor_content' => $data['content'] ?? $campaign->editor_content, 'html_body' => $data['html'], 'text_body' => $data['text'] ?? $campaign->text_body]);

        return response()->json(['ok' => true, 'saved_at' => now()->format('H:i:s'), 'checks' => $this->checks($campaign->fresh())]);
    }

    public function preview(Request $request, NewsletterCampaign $campaign, CampaignRenderer $renderer)
    {
        $subscriber = $request->filled('subscriber') ? Subscriber::find($request->integer('subscriber')) : null;
        $subscriber ??= new Subscriber(['email' => 'przyklad@example.com', 'name' => 'Anna Przykładowa', 'topics' => ['news', 'events'], 'token' => 'podglad']);
        $r = $renderer->render($campaign, $subscriber, null, track: false);

        if ($request->query('format') === 'text') {
            return response($r['text'], 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return response($r['html'])->header('X-Frame-Options', 'SAMEORIGIN');
    }

    public function sendTest(Request $request, NewsletterCampaign $campaign, CampaignRenderer $renderer)
    {
        $data = $request->validate(['emails' => ['required', 'string']]);
        $emails = array_slice(array_values(array_filter(array_map('trim', preg_split('/[,;\s]+/', $data['emails'])), fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))), 0, 5);
        if ($emails === []) {
            return back()->with('error', 'Podaj przynajmniej jeden poprawny adres.');
        }

        $sample = new Subscriber(['email' => $emails[0], 'name' => auth()->user()->name ?? 'Test', 'topics' => ['news'], 'token' => 'test']);
        $r = $renderer->render($campaign, $sample, null, track: false);
        $msg = new OutboundMessage('email', '[TEST] ' . $r['subject'], $r['html'], $r['text']);

        try {
            foreach ($emails as $email) {
                Mail::mailer('newsletter')->to($email)->send(new CampaignMail($msg, $campaign));
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Wysyłka testowa nie powiodła się: ' . $e->getMessage());
        }

        return back()->with('status', 'Test wysłany na: ' . implode(', ', $emails));
    }

    public function audienceCount(Request $request, AudienceResolver $audience)
    {
        $campaign = new NewsletterCampaign(['audience' => $this->audienceFrom($request), 'site_id' => SiteSetting::current()->id]);

        return response()->json(['count' => $audience->count($campaign)]);
    }

    // ── Sterowanie wysyłką ───────────────────────────────────────────────────

    public function send(Request $request, NewsletterCampaign $campaign, AudienceResolver $audience)
    {
        abort_unless($campaign->isEditable(), 403);
        $blocking = array_filter($this->checks($campaign), fn ($c) => $c['level'] === 'error');
        if ($blocking !== []) {
            return back()->with('error', 'Nie można wysłać: ' . implode(' ', array_column($blocking, 'message')));
        }
        $count = $audience->count($campaign);
        if ($count > 1000 && strtoupper((string) $request->input('confirm')) !== 'WYŚLIJ') {
            return back()->with('error', "Wysyłka do {$count} osób wymaga wpisania słowa WYŚLIJ w polu potwierdzenia.");
        }
        if (SiteSetting::current()->newsletter_require_approval && ! $campaign->approved_by) {
            $campaign->forceFill(['approved_by' => auth()->id()])->save();
        }

        $campaign->forceFill(['status' => NewsletterCampaign::STATUS_QUEUED, 'scheduled_at' => now(), 'batch_id' => null])->save();
        DispatchCampaign::dispatch($campaign->id)->onQueue('newsletter');
        activity()->performedOn($campaign)->causedBy(auth()->user())->withProperties(['recipients' => $count])->log('newsletter.send');

        return redirect()->route('admin.newsletter.kampanie.show', $campaign)->with('status', "Kampania trafiła do kolejki ({$count} odbiorców). Upewnij się, że działa worker kolejki.");
    }

    public function schedule(Request $request, NewsletterCampaign $campaign)
    {
        abort_unless($campaign->isEditable(), 403);
        $data = $request->validate(['scheduled_at' => ['required', 'date', 'after:now'], 'send_in_recipient_tz' => ['nullable', 'boolean']]);
        $blocking = array_filter($this->checks($campaign), fn ($c) => $c['level'] === 'error');
        if ($blocking !== []) {
            return back()->with('error', 'Nie można zaplanować: ' . implode(' ', array_column($blocking, 'message')));
        }
        $campaign->forceFill(['status' => NewsletterCampaign::STATUS_SCHEDULED, 'scheduled_at' => $data['scheduled_at'], 'send_in_recipient_tz' => $request->boolean('send_in_recipient_tz')])->save();

        return redirect()->route('admin.newsletter.kampanie.show', $campaign)->with('status', 'Kampania zaplanowana na ' . $campaign->scheduled_at->format('d.m.Y H:i') . '.');
    }

    public function pause(NewsletterCampaign $campaign)
    {
        abort_unless($campaign->status === NewsletterCampaign::STATUS_SENDING, 403);
        $campaign->forceFill(['status' => NewsletterCampaign::STATUS_PAUSED])->save();

        return back()->with('status', 'Wysyłka wstrzymana. Joby w kolejce poczekają na wznowienie.');
    }

    public function resume(NewsletterCampaign $campaign)
    {
        abort_unless($campaign->status === NewsletterCampaign::STATUS_PAUSED, 403);
        $campaign->forceFill(['status' => NewsletterCampaign::STATUS_SENDING])->save();

        return back()->with('status', 'Wysyłka wznowiona.');
    }

    public function cancel(NewsletterCampaign $campaign)
    {
        abort_unless(in_array($campaign->status, [NewsletterCampaign::STATUS_SCHEDULED, NewsletterCampaign::STATUS_QUEUED, NewsletterCampaign::STATUS_SENDING, NewsletterCampaign::STATUS_PAUSED], true), 403);
        if ($campaign->batch_id && ($batch = Bus::findBatch($campaign->batch_id))) {
            $batch->cancel();
        }
        NewsletterDelivery::where('campaign_id', $campaign->id)->where('status', NewsletterDelivery::STATUS_QUEUED)->update(['status' => NewsletterDelivery::STATUS_SKIPPED, 'error_message' => 'Anulowano']);
        $campaign->refreshCounters();
        $campaign->forceFill(['status' => NewsletterCampaign::STATUS_CANCELLED, 'finished_at' => now()])->save();

        return back()->with('status', 'Kampania anulowana.');
    }

    // ── Raport ───────────────────────────────────────────────────────────────

    public function report(Request $request, NewsletterCampaign $campaign, CampaignRenderer $renderer)
    {
        $campaign->refreshCounters();
        $deliveryIds = NewsletterDelivery::where('campaign_id', $campaign->id)->select('id');

        $timeline = [];
        if ($campaign->started_at) {
            $opens = NewsletterOpen::whereIn('delivery_id', $deliveryIds)->where('opened_at', '>=', $campaign->started_at)->get(['opened_at']);
            $clicks = NewsletterClick::whereIn('delivery_id', $deliveryIds)->where('clicked_at', '>=', $campaign->started_at)->get(['clicked_at']);
            for ($h = 0; $h < 72; $h++) {
                $from = $campaign->started_at->copy()->addHours($h);
                $to = $from->copy()->addHour();
                $timeline[] = ['label' => $h . 'h', 'opens' => $opens->whereBetween('opened_at', [$from, $to])->count(), 'clicks' => $clicks->whereBetween('clicked_at', [$from, $to])->count()];
            }
        }

        $clients = NewsletterOpen::whereIn('delivery_id', $deliveryIds)->selectRaw('client_family, COUNT(*) c')->groupBy('client_family')->pluck('c', 'client_family');
        $proxyOpens = NewsletterOpen::whereIn('delivery_id', $deliveryIds)->where('is_proxy', true)->distinct('delivery_id')->count('delivery_id');
        $byChannel = NewsletterDelivery::where('campaign_id', $campaign->id)->selectRaw("channel, COUNT(*) total, SUM(CASE WHEN status IN ('sent','delivered') THEN 1 ELSE 0 END) ok, SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) opened, SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) clicked")->groupBy('channel')->get();

        $recipientFilter = (string) $request->query('r', '');
        $recipients = NewsletterDelivery::with('subscriber:id,email,name')->where('campaign_id', $campaign->id)
            ->when($recipientFilter === 'opened', fn ($q) => $q->whereNotNull('opened_at'))
            ->when($recipientFilter === 'clicked', fn ($q) => $q->whereNotNull('clicked_at'))
            ->when($recipientFilter === 'bounced', fn ($q) => $q->whereIn('status', ['soft_bounced', 'hard_bounced']))
            ->when($recipientFilter === 'failed', fn ($q) => $q->whereIn('status', ['failed', 'skipped']))
            ->orderByDesc('id')->paginate(50, ['*'], 'odbiorcy')->withQueryString();

        $sample = new Subscriber(['email' => 'przyklad@example.com', 'name' => 'Anna', 'topics' => ['news'], 'token' => 'podglad']);
        $previewHtml = $renderer->render($campaign, $sample, null, track: false)['html'];

        return view('newsletter::admin.campaigns.report', compact('campaign', 'timeline', 'clients', 'proxyOpens', 'byChannel', 'recipients', 'recipientFilter', 'previewHtml') + [
            'links' => $campaign->links()->get(),
            'maxClicks' => max(1, (int) $campaign->links()->max('clicks_unique')),
            'unsubscribes' => Subscriber::where('unsubscribe_campaign_id', $campaign->id)->count(),
        ]);
    }

    public function reportCsv(NewsletterCampaign $campaign): StreamedResponse
    {
        return response()->streamDownload(function () use ($campaign) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['email', 'kanal', 'status', 'wyslano', 'otwarto', 'klikniecia', 'blad'], ';');
            NewsletterDelivery::with('subscriber:id,email')->where('campaign_id', $campaign->id)->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $d) {
                    fputcsv($out, [$d->subscriber?->email, $d->channel, $d->status, $d->sent_at?->format('Y-m-d H:i'), $d->opened_at?->format('Y-m-d H:i'), $d->clicks_count, $d->error_message], ';');
                }
            });
            fclose($out);
        }, 'raport-kampanii-' . $campaign->id . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ── Pomocnicze ───────────────────────────────────────────────────────────

    private function form(NewsletterCampaign $campaign)
    {
        return view('newsletter::admin.campaigns.form', [
            'campaign'  => $campaign,
            'templates' => NewsletterTemplate::orderByDesc('is_default')->orderBy('name')->get(),
            'lists'     => NewsletterList::withCount('subscribers')->orderBy('name')->get(),
            'segments'  => NewsletterSegment::orderBy('name')->get(),
            'topics'    => Subscriber::availableTopics(),
            'channels'  => app(ChannelRegistry::class)->all(),
            'tags'      => Personalizer::TAGS,
            'sources'   => ContentFeeder::SOURCES,
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'subject' => ['required', 'string', 'max:255'],
            'subject_b' => ['nullable', 'string', 'max:255'],
            'ab_split_percent' => ['nullable', 'integer', 'min:10', 'max:50'],
            'preheader' => ['nullable', 'string', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:120'],
            'from_address' => ['nullable', 'email', 'max:255'],
            'reply_to' => ['nullable', 'email', 'max:255'],
            'template_id' => ['nullable', 'integer', 'exists:newsletter_templates,id'],
            'short_title' => ['nullable', 'string', 'max:60'],
            'short_text' => ['nullable', 'string', 'max:160'],
            'short_url' => ['nullable', 'url', 'max:500'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => [Rule::in(array_keys(Subscriber::CHANNELS))],
            'utm_source' => ['nullable', 'string', 'max:60'], 'utm_medium' => ['nullable', 'string', 'max:60'], 'utm_campaign' => ['nullable', 'string', 'max:60'],
            'recurrence' => ['nullable', Rule::in(['weekly', 'monthly'])],
            'rec_weekday' => ['nullable', 'integer', 'min:1', 'max:7'], 'rec_day' => ['nullable', 'integer', 'min:1', 'max:28'],
            'rec_hour' => ['nullable', 'integer', 'min:0', 'max:23'], 'rec_min_items' => ['nullable', 'integer', 'min:0', 'max:20'],
        ]);
        $data['channels'] = array_values(array_unique(array_merge(['email'], $data['channels'])));
        $data['audience'] = $this->audienceFrom($request);
        $data['utm'] = array_filter(['source' => $data['utm_source'] ?? null, 'medium' => $data['utm_medium'] ?? null, 'campaign' => $data['utm_campaign'] ?? null]);
        $data['track_opens'] = $request->boolean('track_opens');
        $data['track_clicks'] = $request->boolean('track_clicks');
        $data['recurrence_rule'] = $data['recurrence'] ?? null
            ? ['weekday' => (int) ($data['rec_weekday'] ?? 5), 'day' => (int) ($data['rec_day'] ?? 1), 'hour' => (int) ($data['rec_hour'] ?? 9), 'min_items' => (int) ($data['rec_min_items'] ?? 1)]
            : null;
        unset($data['utm_source'], $data['utm_medium'], $data['utm_campaign'], $data['rec_weekday'], $data['rec_day'], $data['rec_hour'], $data['rec_min_items']);
        if (empty($data['subject_b'])) {
            $data['ab_split_percent'] = null;
        }

        return $data;
    }

    private function audienceFrom(Request $request): array
    {
        return [
            'all'           => $request->input('audience_mode', 'all') === 'all',
            'lists'         => array_map('intval', (array) $request->input('lists', [])),
            'segments'      => array_map('intval', (array) $request->input('segments', [])),
            'topics'        => array_values(array_filter((array) $request->input('topics', []))),
            'exclude_lists' => array_map('intval', (array) $request->input('exclude_lists', [])),
        ];
    }

    /** Checklista przed wysyłką. @return array<int, array{level:string, message:string}> */
    private function checks(NewsletterCampaign $campaign): array
    {
        $html = (string) $campaign->html_body;
        $out = [];
        $out[] = trim($html) === '' ? ['level' => 'error', 'message' => 'Treść jest pusta.'] : ['level' => 'ok', 'message' => 'Treść wiadomości jest ustawiona.'];
        $out[] = Personalizer::hasUnsubscribeTag($html) ? ['level' => 'ok', 'message' => 'Link wypisu jest w treści.'] : ['level' => 'error', 'message' => 'Brak linku wypisu ({{unsubscribe_url}}) — wymagany prawnie.'];
        $out[] = filled($campaign->subject) ? ['level' => 'ok', 'message' => 'Temat ustawiony.'] : ['level' => 'error', 'message' => 'Brak tematu.'];
        $noAlt = preg_match_all('/<img\b(?![^>]*\balt=)[^>]*>/i', $html);
        $out[] = $noAlt ? ['level' => 'warning', 'message' => "Obrazki bez atrybutu alt: {$noAlt}."] : ['level' => 'ok', 'message' => 'Wszystkie obrazki mają tekst alternatywny.'];
        $size = strlen($html);
        $out[] = $size > 102400 ? ['level' => 'warning', 'message' => 'HTML przekracza 100 kB (' . round($size / 1024) . ' kB) — Gmail może przyciąć wiadomość.'] : ['level' => 'ok', 'message' => 'Rozmiar HTML: ' . round($size / 1024) . ' kB.'];
        $emptyLinks = preg_match_all('/<a\b[^>]*\bhref\s*=\s*("|\')(\s*|#)\1/i', $html);
        $out[] = $emptyLinks ? ['level' => 'warning', 'message' => "Puste linki: {$emptyLinks}."] : ['level' => 'ok', 'message' => 'Brak pustych linków.'];
        if (preg_match('/\b(100% za darmo|kliknij tutaj teraz|gwarantowany zysk|free money)\b/iu', $html . ' ' . $campaign->subject)) {
            $out[] = ['level' => 'warning', 'message' => 'Temat lub treść zawiera frazy typowe dla spamu.'];
        }
        if ($campaign->hasChannel('webpush') || $campaign->hasChannel('sms')) {
            $out[] = filled($campaign->short_text ?: $campaign->short_title) ? ['level' => 'ok', 'message' => 'Wersja skrócona (push/SMS) ustawiona.'] : ['level' => 'warning', 'message' => 'Brak wersji skróconej — push/SMS użyją tematu i preheadera.'];
        }

        return $out;
    }
}
