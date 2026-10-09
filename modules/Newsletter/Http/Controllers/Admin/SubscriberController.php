<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Newsletter\Jobs\SendDoubleOptIn;
use Modules\Newsletter\Jobs\SyncSubscriberToCrm;
use Modules\Newsletter\Models\NewsletterConsent;
use Modules\Newsletter\Models\NewsletterList;
use Modules\Newsletter\Models\NewsletterSegment;
use Modules\Newsletter\Models\NewsletterSuppression;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Panel: subskrybenci — lista z filtrami, karta, edycja, import/eksport, anonimizacja, akcje zbiorcze.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class SubscriberController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->only(['q', 'status', 'topic', 'channel', 'list', 'segment', 'since']);
        $q = $this->filtered($f)->withCount('lists')->latest();

        return view('newsletter::admin.subscribers.index', [
            'subscribers' => $q->paginate(50)->withQueryString(),
            'filters'     => $f,
            'topics'      => Subscriber::$availableTopics,
            'statuses'    => Subscriber::STATUSES,
            'channels'    => Subscriber::CHANNELS,
            'lists'       => NewsletterList::orderBy('name')->get(),
            'segments'    => NewsletterSegment::orderBy('name')->get(),
            'counts'      => Subscriber::selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function show(Subscriber $subscriber)
    {
        $subscriber->load(['lists', 'consents', 'deliveries.campaign', 'events']);

        return view('newsletter::admin.subscribers.show', ['subscriber' => $subscriber, 'lists' => NewsletterList::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('newsletter::admin.subscribers.form', ['subscriber' => new Subscriber(['status' => Subscriber::STATUS_PENDING, 'channels' => ['email']]), 'lists' => NewsletterList::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $subscriber = Subscriber::findByEmail($data['email']) ?? new Subscriber(['email' => $data['email'], 'site_id' => SiteSetting::current()->id, 'source' => 'admin']);
        $subscriber->fill($data)->save();
        $subscriber->lists()->sync(array_fill_keys($request->input('lists', []), ['added_at' => now(), 'added_by' => auth()->id()]));

        if ($data['status'] === Subscriber::STATUS_CONFIRMED) {
            $this->adminConsent($subscriber, (string) $request->input('consent_note'));
            $subscriber->forceFill(['confirmed_at' => $subscriber->confirmed_at ?? now()])->save();
        } elseif ($data['status'] === Subscriber::STATUS_PENDING) {
            SendDoubleOptIn::dispatch($subscriber->id);
        }
        $subscriber->logEvent('status_changed', ['status' => $data['status'], 'via' => 'admin_create']);

        return redirect()->route('admin.newsletter.subskrybenci.show', $subscriber)->with('status', 'Subskrybent zapisany.');
    }

    public function edit(Subscriber $subscriber)
    {
        return view('newsletter::admin.subscribers.form', ['subscriber' => $subscriber, 'lists' => NewsletterList::orderBy('name')->get()]);
    }

    public function update(Request $request, Subscriber $subscriber)
    {
        $data = $this->validated($request, $subscriber);
        $old = $subscriber->status;
        $subscriber->fill($data)->save();
        $subscriber->lists()->sync(array_fill_keys($request->input('lists', []), ['added_at' => now(), 'added_by' => auth()->id()]));

        if ($old !== $data['status']) {
            if ($data['status'] === Subscriber::STATUS_CONFIRMED) {
                $this->adminConsent($subscriber, (string) $request->input('consent_note'));
                $subscriber->forceFill(['confirmed_at' => $subscriber->confirmed_at ?? now()])->save();
            }
            if ($data['status'] === Subscriber::STATUS_SUPPRESSED) {
                NewsletterSuppression::updateOrCreate(['email_hash' => $subscriber->email_hash], ['reason' => 'admin', 'expires_at' => null]);
            } elseif ($old === Subscriber::STATUS_SUPPRESSED) {
                NewsletterSuppression::where('email_hash', $subscriber->email_hash)->delete();
            }
            $subscriber->logEvent('status_changed', ['from' => $old, 'to' => $data['status'], 'via' => 'admin']);
        }

        return redirect()->route('admin.newsletter.subskrybenci.show', $subscriber)->with('status', 'Zmiany zapisane.');
    }

    public function destroy(Subscriber $subscriber)
    {
        $subscriber->delete();

        return redirect()->route('admin.newsletter.subskrybenci.index')->with('status', 'Subskrybent usunięty.');
    }

    public function anonymize(Subscriber $subscriber)
    {
        SyncSubscriberToCrm::dispatchSync($subscriber->id, 'anonymized');
        $subscriber->anonymize(auth()->id());

        return redirect()->route('admin.newsletter.subskrybenci.show', $subscriber)->with('status', 'Dane osobowe zostały zanonimizowane.');
    }

    public function resendConfirmation(Subscriber $subscriber)
    {
        if (! in_array($subscriber->status, [Subscriber::STATUS_PENDING, Subscriber::STATUS_EXPIRED], true)) {
            return back()->with('error', 'Ponowne potwierdzenie można wysłać tylko do oczekujących lub wygasłych.');
        }
        $subscriber->forceFill(['status' => Subscriber::STATUS_PENDING, 'token' => Subscriber::generateToken()])->save();
        SendDoubleOptIn::dispatch($subscriber->id);

        return back()->with('status', 'Wiadomość potwierdzająca została zakolejkowana.');
    }

    public function sync(Subscriber $subscriber)
    {
        SyncSubscriberToCrm::dispatchSync($subscriber->id, 'confirmed');

        return back()->with('status', 'Synchronizacja z SZO/CRM wykonana — sprawdź status na karcie.');
    }

    public function personalData(Subscriber $subscriber)
    {
        $subscriber->logEvent('exported', ['scope' => 'personal_data']);

        return response()->json($subscriber->exportPersonalData(), 200, ['Content-Disposition' => 'attachment; filename="subskrybent-' . $subscriber->id . '.json"'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['add_list', 'remove_list', 'add_tag', 'unsubscribe', 'anonymize', 'delete', 'resend'])],
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer'],
            'list_id'=> ['nullable', 'integer'],
            'tag'    => ['nullable', 'string', 'max:40'],
        ]);

        $subs = Subscriber::whereIn('id', $data['ids'])->get();
        $n = $subs->count();

        foreach ($subs as $s) {
            match ($data['action']) {
                'add_list'    => $data['list_id'] ? $s->lists()->syncWithoutDetaching([$data['list_id'] => ['added_at' => now(), 'added_by' => auth()->id()]]) : null,
                'remove_list' => $data['list_id'] ? $s->lists()->detach($data['list_id']) : null,
                'add_tag'     => $s->forceFill(['tags' => array_values(array_unique(array_merge($s->tags ?? [], [trim((string) $data['tag'])])))])->save(),
                'unsubscribe' => $s->unsubscribe('admin'),
                'anonymize'   => $s->anonymize(auth()->id()),
                'delete'      => $s->delete(),
                'resend'      => $s->isPending() ? SendDoubleOptIn::dispatch($s->id) : null,
            };
        }

        return back()->with('status', "Wykonano akcję dla {$n} subskrybentów.");
    }

    // ── Import ───────────────────────────────────────────────────────────────

    public function importForm()
    {
        return view('newsletter::admin.subscribers.import', ['lists' => NewsletterList::orderBy('name')->get(), 'topics' => Subscriber::$availableTopics]);
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'file'          => ['required', 'file', 'mimes:csv,txt,json', 'max:10240'],
            'status'        => ['required', Rule::in([Subscriber::STATUS_PENDING, Subscriber::STATUS_CONFIRMED])],
            'has_consent'   => ['required_if:status,confirmed'],
            'consent_source'=> ['required_if:status,confirmed', 'nullable', 'string', 'max:120'],
            'consent_date'  => ['nullable', 'date'],
            'list_id'       => ['nullable', 'integer'],
            'topics'        => ['nullable', 'array'],
            'tag'           => ['nullable', 'string', 'max:40'],
        ]);

        $rows = $this->parseImport($request->file('file'));
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'invalid' => []];
        $topics = $data['topics'] ?? ['news'];

        foreach ($rows as $i => $row) {
            $email = trim((string) ($row['email'] ?? ''));
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $stats['invalid'][] = ($i + 1) . ': ' . $email;
                continue;
            }
            $s = Subscriber::findByEmail($email);
            if ($s && in_array($s->status, [Subscriber::STATUS_ANONYMIZED, Subscriber::STATUS_COMPLAINED, Subscriber::STATUS_SUPPRESSED], true)) {
                $stats['skipped']++;
                continue;
            }
            $isNew = ! $s;
            $s ??= new Subscriber(['email' => $email, 'site_id' => SiteSetting::current()->id, 'source' => 'import']);
            $s->name = $row['name'] ?? ($row['imie'] ?? $s->name);
            $s->phone = ! empty($row['phone'] ?? $row['telefon'] ?? null) ? preg_replace('/[^0-9+]/', '', (string) ($row['phone'] ?? $row['telefon'])) : $s->phone;
            $rowTopics = ! empty($row['topics']) ? array_values(array_intersect(array_map('trim', explode(',', (string) $row['topics'])), array_keys(Subscriber::$availableTopics))) : [];
            $s->topics = array_values(array_unique(array_merge($s->topics ?? [], $rowTopics ?: $topics)));
            if (! empty($data['tag'])) {
                $s->tags = array_values(array_unique(array_merge($s->tags ?? [], [$data['tag']])));
            }
            if (! $s->isConfirmed()) {
                $s->status = $data['status'];
                if ($data['status'] === Subscriber::STATUS_CONFIRMED) {
                    $s->confirmed_at = ! empty($data['consent_date']) ? $data['consent_date'] : now();
                }
            }
            $s->save();
            if (! empty($data['list_id'])) {
                $s->lists()->syncWithoutDetaching([$data['list_id'] => ['added_at' => now(), 'added_by' => auth()->id()]]);
            }
            if ($data['status'] === Subscriber::STATUS_CONFIRMED) {
                NewsletterConsent::create([
                    'subscriber_id' => $s->id, 'channel' => 'email', 'clause_text' => 'Zgoda udokumentowana poza systemem: ' . $data['consent_source'],
                    'source' => 'import', 'method' => 'admin_import', 'granted_at' => ! empty($data['consent_date']) ? $data['consent_date'] : now(), 'confirmed_at' => now(),
                ]);
            } elseif ($isNew || $s->isPending()) {
                SendDoubleOptIn::dispatch($s->id);
            }
            $s->logEvent('imported', ['status' => $data['status'], 'file' => $request->file('file')->getClientOriginalName()]);
            $isNew ? $stats['created']++ : $stats['updated']++;
        }

        activity()->causedBy(auth()->user())->withProperties($stats + ['file' => $request->file('file')->getClientOriginalName()])->log('newsletter.import');

        return redirect()->route('admin.newsletter.subskrybenci.index')->with('status', "Import: nowych {$stats['created']}, zaktualizowanych {$stats['updated']}, pominiętych {$stats['skipped']}, błędnych " . count($stats['invalid']) . '.')
            ->with('import_invalid', $stats['invalid']);
    }

    public function export(Request $request): StreamedResponse
    {
        $f = $request->only(['q', 'status', 'topic', 'channel', 'list', 'segment', 'since']);
        $format = $request->query('format', 'csv');
        $query = $this->filtered($f)->orderBy('id');
        $filename = 'subskrybenci-' . now()->format('Y-m-d') . '.' . $format;

        activity()->causedBy(auth()->user())->withProperties(['filters' => $f, 'count' => (clone $query)->count(), 'format' => $format])->log('newsletter.export');

        return response()->streamDownload(function () use ($query, $format) {
            $out = fopen('php://output', 'w');
            if ($format === 'json') {
                fwrite($out, "[\n");
                $first = true;
                $query->chunk(500, function ($rows) use ($out, &$first) {
                    foreach ($rows as $s) {
                        fwrite($out, ($first ? '' : ",\n") . json_encode(['email' => $s->email, 'name' => $s->name, 'status' => $s->status, 'topics' => $s->topics, 'channels' => $s->channels, 'tags' => $s->tags, 'source' => $s->source, 'confirmed_at' => $s->confirmed_at?->toIso8601String(), 'created_at' => $s->created_at?->toIso8601String()], JSON_UNESCAPED_UNICODE));
                        $first = false;
                    }
                });
                fwrite($out, "\n]");
            } else {
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, ['email', 'name', 'status', 'topics', 'channels', 'tags', 'source', 'confirmed_at', 'created_at'], ';');
                $query->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $s) {
                        fputcsv($out, [$s->email, $s->name, $s->status, implode(',', $s->topics ?? []), implode(',', $s->channels ?? []), implode(',', $s->tags ?? []), $s->source, $s->confirmed_at?->format('Y-m-d H:i'), $s->created_at?->format('Y-m-d H:i')], ';');
                    }
                });
            }
            fclose($out);
        }, $filename, ['Content-Type' => $format === 'json' ? 'application/json' : 'text/csv; charset=UTF-8']);
    }

    // ── Pomocnicze ───────────────────────────────────────────────────────────

    private function filtered(array $f)
    {
        return Subscriber::query()
            ->when(! empty($f['q']), function ($q) use ($f) {
                $term = trim((string) $f['q']);
                $q->where(fn ($w) => $w->where('email_hash', Subscriber::hashEmail($term))->orWhere('email', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%"));
            })
            ->when(! empty($f['status']), fn ($q) => $q->where('status', $f['status']))
            ->when(! empty($f['topic']), fn ($q) => $q->whereJsonContains('topics', $f['topic']))
            ->when(! empty($f['channel']), fn ($q) => $q->whereJsonContains('channels', $f['channel']))
            ->when(! empty($f['list']), fn ($q) => $q->whereHas('lists', fn ($l) => $l->where('newsletter_lists.id', (int) $f['list'])))
            ->when(! empty($f['segment']), function ($q) use ($f) {
                $segment = NewsletterSegment::find((int) $f['segment']);
                if ($segment) {
                    $q->whereIn('id', $segment->subscribersQuery()->select('id'));
                }
            })
            ->when(! empty($f['since']), fn ($q) => $q->where('created_at', '>=', $f['since']));
    }

    private function validated(Request $request, ?Subscriber $s = null): array
    {
        $data = $request->validate([
            'email'      => ['required', 'email', 'max:255'],
            'name'       => ['nullable', 'string', 'max:100'],
            'phone'      => ['nullable', 'string', 'max:30'],
            'status'     => ['required', Rule::in(array_keys(Subscriber::STATUSES))],
            'topics'     => ['nullable', 'array'],
            'topics.*'   => [Rule::in(array_keys(Subscriber::$availableTopics))],
            'channels'   => ['nullable', 'array'],
            'channels.*' => [Rule::in(array_keys(Subscriber::CHANNELS))],
            'tags'       => ['nullable', 'string', 'max:255'],
            'locale'     => ['nullable', 'string', 'max:5'],
            'timezone'   => ['nullable', 'string', 'max:64'],
        ]);
        $hash = Subscriber::hashEmail($data['email']);
        $dup = Subscriber::where('email_hash', $hash)->when($s, fn ($q) => $q->where('id', '!=', $s->id))->exists();
        if ($dup) {
            throw \Illuminate\Validation\ValidationException::withMessages(['email' => 'Ten adres już istnieje w bazie.']);
        }
        $data['tags'] = array_values(array_filter(array_map('trim', explode(',', (string) ($data['tags'] ?? '')))));
        $data['channels'] = array_values(array_unique(array_merge(['email'], $data['channels'] ?? [])));
        $data['topics'] = $data['topics'] ?? [];
        $data['phone'] = ! empty($data['phone']) ? preg_replace('/[^0-9+]/', '', $data['phone']) : null;

        return $data;
    }

    private function adminConsent(Subscriber $s, string $note): void
    {
        NewsletterConsent::create([
            'subscriber_id' => $s->id, 'channel' => 'email', 'clause_text' => 'Zgoda potwierdzona przez administratora' . ($note !== '' ? ': ' . $note : ''),
            'source' => 'admin', 'method' => 'admin', 'granted_at' => now(), 'confirmed_at' => now(),
        ]);
    }

    /** @return array<int, array<string, string>> */
    private function parseImport(\Illuminate\Http\UploadedFile $file): array
    {
        $content = (string) file_get_contents($file->getRealPath());
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

        if (strtolower($file->getClientOriginalExtension()) === 'json') {
            $json = json_decode($content, true);
            $rows = is_array($json) ? (isset($json[0]) ? $json : ($json['subscribers'] ?? $json['data'] ?? [])) : [];

            return array_map(fn ($r) => is_array($r) ? array_change_key_case($r, CASE_LOWER) : ['email' => (string) $r], $rows);
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($content)) ?: [];
        if ($lines === []) {
            return [];
        }
        $delimiter = substr_count($lines[0], ';') >= substr_count($lines[0], ',') ? ';' : ',';
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), str_getcsv($lines[0], $delimiter));
        $hasHeader = in_array('email', $header, true) || in_array('e-mail', $header, true);
        $rows = [];
        foreach ($lines as $i => $line) {
            if ($line === '' || ($hasHeader && $i === 0)) {
                continue;
            }
            $cells = str_getcsv($line, $delimiter);
            if ($hasHeader) {
                $row = [];
                foreach ($header as $k => $name) {
                    $row[$name === 'e-mail' ? 'email' : $name] = $cells[$k] ?? null;
                }
                $rows[] = $row;
            } else {
                $rows[] = ['email' => $cells[0] ?? '', 'name' => $cells[1] ?? null];
            }
        }

        return $rows;
    }
}
