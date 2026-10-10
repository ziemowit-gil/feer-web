<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Newsletter\Jobs\SendDoubleOptIn;
use Modules\Newsletter\Models\NewsletterList;
use Modules\Newsletter\Models\NewsletterSegment;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Panel: listy mailingowe — CRUD, członkowie (dodawanie po adresie, z segmentu, z filtru;
 * usuwanie, przenoszenie/kopiowanie między listami), eksport listy.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class ListController extends Controller
{
    public function index()
    {
        $lists = NewsletterList::withCount(['subscribers', 'subscribers as active_count' => fn ($q) => $q->where('status', Subscriber::STATUS_CONFIRMED)])
            ->orderBy('name')->get();

        return view('newsletter::admin.lists.index', ['lists' => $lists]);
    }

    public function create()
    {
        return view('newsletter::admin.lists.form', ['list' => new NewsletterList()]);
    }

    public function store(Request $request)
    {
        $list = NewsletterList::create($this->validated($request) + ['site_id' => SiteSetting::current()->id]);

        return redirect()->route('admin.newsletter.listy.show', $list)->with('status', "Lista „{$list->name}” utworzona. Dodaj do niej subskrybentów.");
    }

    /** Karta listy: członkowie z filtrami, dodawanie, statystyki. */
    public function show(Request $request, NewsletterList $list)
    {
        $q = (string) $request->query('q', '');
        $status = (string) $request->query('status', '');

        $members = $list->subscribers()
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('email', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%")))
            ->when($status !== '', fn ($w) => $w->where('status', $status))
            ->orderByDesc('newsletter_list_subscriber.added_at')
            ->paginate(50)->withQueryString();

        $byStatus = $list->subscribers()->selectRaw('subscribers.status, COUNT(*) c')->groupBy('subscribers.status')->pluck('c', 'status');

        return view('newsletter::admin.lists.show', [
            'list'       => $list,
            'members'    => $members,
            'byStatus'   => $byStatus,
            'statuses'   => Subscriber::STATUSES,
            'q'          => $q,
            'status'     => $status,
            'otherLists' => NewsletterList::where('id', '!=', $list->id)->orderBy('name')->get(),
            'segments'   => NewsletterSegment::orderBy('name')->get(),
            'topics'     => Subscriber::$availableTopics,
        ]);
    }

    public function edit(NewsletterList $list)
    {
        return view('newsletter::admin.lists.form', ['list' => $list]);
    }

    public function update(Request $request, NewsletterList $list)
    {
        $list->update($this->validated($request, $list));

        return redirect()->route('admin.newsletter.listy.show', $list)->with('status', 'Lista zapisana.');
    }

    public function destroy(NewsletterList $list)
    {
        $list->delete();

        return redirect()->route('admin.newsletter.listy.index')->with('status', 'Lista usunięta (subskrybenci pozostają w bazie).');
    }

    // ── Członkowie ───────────────────────────────────────────────────────────

    /** Dodaje subskrybentów: po adresach (istniejący lub nowy z DOI), z segmentu, albo po temacie. */
    public function addMembers(Request $request, NewsletterList $list)
    {
        $data = $request->validate([
            'mode'       => ['required', 'in:emails,segment,topic,status'],
            'emails'     => ['required_if:mode,emails', 'nullable', 'string', 'max:20000'],
            'segment_id' => ['required_if:mode,segment', 'nullable', 'integer'],
            'topic'      => ['required_if:mode,topic', 'nullable', 'string'],
            'status'     => ['nullable', 'string'],
        ]);

        $added = 0;
        $created = 0;
        $invalid = [];
        $actor = auth()->id();

        if ($data['mode'] === 'emails') {
            foreach (preg_split('/[\s,;]+/', (string) $data['emails']) ?: [] as $raw) {
                $email = trim($raw);
                if ($email === '') {
                    continue;
                }
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $invalid[] = $email;
                    continue;
                }
                $s = Subscriber::findByEmail($email);
                if (! $s) {
                    $s = Subscriber::create(['email' => $email, 'site_id' => SiteSetting::current()->id, 'source' => 'admin_list:' . $list->slug, 'topics' => ['news'], 'status' => Subscriber::STATUS_PENDING]);
                    $s->logEvent('subscribed', ['via' => 'admin_list', 'list' => $list->name]);
                    SendDoubleOptIn::dispatch($s->id);
                    $created++;
                }
                $list->subscribers()->syncWithoutDetaching([$s->id => ['added_at' => now(), 'added_by' => $actor]]);
                $s->logEvent('list_added', ['list' => $list->name]);
                $added++;
            }
        } else {
            $query = match ($data['mode']) {
                'segment' => NewsletterSegment::findOrFail((int) $data['segment_id'])->subscribersQuery(),
                'topic'   => Subscriber::query()->whereJsonContains('topics', $data['topic']),
                default   => Subscriber::query()->where('status', $data['status'] ?: Subscriber::STATUS_CONFIRMED),
            };
            $query->orderBy('id')->chunkById(500, function ($subs) use ($list, $actor, &$added) {
                $list->subscribers()->syncWithoutDetaching(array_fill_keys($subs->pluck('id')->all(), ['added_at' => now(), 'added_by' => $actor]));
                $added += $subs->count();
            });
        }

        $msg = "Dodano do listy: {$added}" . ($created ? " (nowych adresów z wysłanym potwierdzeniem: {$created})" : '') . ($invalid ? '. Pominięte błędne: ' . implode(', ', array_slice($invalid, 0, 10)) : '') . '.';

        return redirect()->route('admin.newsletter.listy.show', $list)->with('status', $msg);
    }

    /** Akcje na zaznaczonych członkach: usuń z listy, skopiuj/przenieś do innej listy. */
    public function members(Request $request, NewsletterList $list)
    {
        $data = $request->validate([
            'action'  => ['required', 'in:remove,copy,move,remove_all'],
            'ids'     => ['required_unless:action,remove_all', 'nullable', 'array'],
            'ids.*'   => ['integer'],
            'target'  => ['required_if:action,copy,move', 'nullable', 'integer'],
        ]);

        if ($data['action'] === 'remove_all') {
            $n = $list->subscribers()->count();
            $list->subscribers()->detach();

            return back()->with('status', "Usunięto z listy wszystkich ({$n}).");
        }

        $ids = array_map('intval', $data['ids'] ?? []);
        $target = isset($data['target']) ? NewsletterList::find((int) $data['target']) : null;

        if (in_array($data['action'], ['copy', 'move'], true) && $target) {
            $target->subscribers()->syncWithoutDetaching(array_fill_keys($ids, ['added_at' => now(), 'added_by' => auth()->id()]));
        }
        if (in_array($data['action'], ['remove', 'move'], true)) {
            $list->subscribers()->detach($ids);
            foreach (Subscriber::whereIn('id', $ids)->get() as $s) {
                $s->logEvent('list_removed', ['list' => $list->name]);
            }
        }

        return back()->with('status', 'Wykonano akcję dla ' . count($ids) . ' subskrybentów.');
    }

    public function export(NewsletterList $list): StreamedResponse
    {
        activity()->causedBy(auth()->user())->performedOn($list)->withProperties(['count' => $list->subscribers()->count()])->log('newsletter.list_export');

        return response()->streamDownload(function () use ($list) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['email', 'name', 'status', 'topics', 'added_at'], ';');
            $list->subscribers()->orderBy('subscribers.id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $s) {
                    fputcsv($out, [$s->email, $s->name, $s->status, implode(',', $s->topics ?? []), $s->pivot->added_at], ';');
                }
            });
            fclose($out);
        }, 'lista-' . $list->slug . '-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validated(Request $request, ?NewsletterList $list = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_public' => ['nullable', 'boolean'],
        ]);
        $data['slug'] = Str::slug($data['slug'] ?: $data['name']) ?: 'lista';
        $data['is_public'] = $request->boolean('is_public');
        $base = $data['slug'];
        $i = 2;
        while (NewsletterList::where('slug', $data['slug'])->when($list, fn ($q) => $q->where('id', '!=', $list->id))->exists()) {
            $data['slug'] = $base . '-' . $i++;
        }

        return $data;
    }
}
