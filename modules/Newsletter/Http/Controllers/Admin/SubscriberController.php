<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Newsletter\Jobs\RunSubscriberImport;
use Modules\Newsletter\Jobs\SendDoubleOptIn;
use Modules\Newsletter\Jobs\SyncSubscriberToCrm;
use Modules\Newsletter\Models\NewsletterConsent;
use Modules\Newsletter\Models\NewsletterImport;
use Modules\Newsletter\Models\NewsletterList;
use Modules\Newsletter\Models\NewsletterSegment;
use Modules\Newsletter\Models\NewsletterSuppression;
use Modules\Newsletter\Services\GetResponseClient;
use Modules\Newsletter\Services\MailerLiteClient;
use Modules\Newsletter\Services\SubscriberImporter;
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
            'topics'      => Subscriber::availableTopics(),
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
        $site = SiteSetting::current();
        $mask = fn (?string $t) => $t ? '••••' . substr($t, -4) : null;

        return view('newsletter::admin.subscribers.import', [
            'tokenMask'  => $mask(MailerLiteClient::token($site)),
            'tokenInDb'  => filled($site->newsletter_mailerlite_token),
            'grMask'     => $mask(GetResponseClient::token($site)),
            'grInDb'     => filled($site->newsletter_getresponse_token),
            'imports'    => NewsletterImport::latest()->limit(10)->get(),
        ]);
    }

    /** Krok 1 (plik): zapis pliku, rozpoznanie kolumn i podgląd. */
    public function import(Request $request, SubscriberImporter $importer)
    {
        $data = $request->validate([
            'file'   => ['required', 'file', 'mimes:csv,txt,json', 'max:20480'],
            'source' => ['nullable', Rule::in([NewsletterImport::SOURCE_FILE, NewsletterImport::SOURCE_MAILERLITE_CSV, NewsletterImport::SOURCE_GETRESPONSE_CSV])],
        ]);

        $import = NewsletterImport::create([
            'site_id' => SiteSetting::current()->id, 'user_id' => auth()->id(), 'source' => $data['source'] ?? NewsletterImport::SOURCE_FILE,
            'status'  => NewsletterImport::STATUS_PREVIEW, 'options' => $this->defaultImportOptions(),
        ]);

        try {
            $importer->storeFile($request->file('file'), $import);
            $import->summary = $importer->previewFile($import);
            $import->save();
        } catch (\Throwable $e) {
            $importer->deleteFile($import);
            $import->delete();

            return back()->withErrors(['file' => 'Nie udało się odczytać pliku: ' . $e->getMessage()]);
        }

        return redirect()->route('admin.newsletter.subskrybenci.import.show', $import);
    }

    /** Krok 1 (API MailerLite): zapis tokenu, test połączenia, liczby wg statusu, grupy i pola. */
    public function importMailerLite(Request $request)
    {
        $data = $request->validate(['token' => ['nullable', 'string', 'max:2000'], 'forget_token' => ['nullable', 'boolean']]);
        $site = SiteSetting::current();

        if ($request->boolean('forget_token')) {
            $site->forceFill(['newsletter_mailerlite_token' => null])->save();

            return back()->with('status', 'Token MailerLite usunięty z ustawień.');
        }
        if (filled($data['token'] ?? null)) {
            $site->forceFill(['newsletter_mailerlite_token' => trim($data['token'])])->save();
        }

        $client = MailerLiteClient::fromSettings($site);
        if (! $client) {
            return back()->withErrors(['token' => 'Podaj token API MailerLite (MailerLite → Integracje → API).']);
        }

        try {
            $counts = $client->countsByStatus();
            $groups = $client->groups();
            $fields = $client->fields();
        } catch (\RuntimeException $e) {
            return back()->withErrors(['token' => $e->getMessage()]);
        }

        $byStatus = [];
        foreach ($counts as $ml => $n) {
            $local = app(SubscriberImporter::class)->mapStatus($ml);
            $byStatus[$local] = ($byStatus[$local] ?? 0) + $n;
        }

        $import = NewsletterImport::create([
            'site_id' => $site->id, 'user_id' => auth()->id(), 'source' => NewsletterImport::SOURCE_MAILERLITE_API,
            'status'  => NewsletterImport::STATUS_PREVIEW, 'total' => array_sum($counts),
            'options' => $this->defaultImportOptions() + ['field_map' => array_filter([
                'name'      => isset($fields['name']) ? 'name' : null,
                'last_name' => isset($fields['last_name']) ? 'last_name' : null,
                'phone'     => isset($fields['phone']) ? 'phone' : null,
            ])],
            'summary' => [
                'total'         => array_sum($counts),
                'source_status' => $counts,
                'by_status'     => array_filter($byStatus),
                'groups'        => array_map(fn ($g) => ['key' => $g['id'], 'name' => $g['name'], 'count' => $g['total'], 'active' => $g['active_count']], $groups),
                'fields'        => $fields,
            ],
        ]);

        return redirect()->route('admin.newsletter.subskrybenci.import.show', $import);
    }

    /** Krok 1 (API GetResponse): zapis klucza, test połączenia, liczba kontaktów, listy i czarna lista. */
    public function importGetResponse(Request $request)
    {
        $data = $request->validate(['token' => ['nullable', 'string', 'max:2000'], 'forget_token' => ['nullable', 'boolean']]);
        $site = SiteSetting::current();

        if ($request->boolean('forget_token')) {
            $site->forceFill(['newsletter_getresponse_token' => null])->save();

            return back()->with('status', 'Klucz API GetResponse usunięty z ustawień.');
        }
        if (filled($data['token'] ?? null)) {
            $site->forceFill(['newsletter_getresponse_token' => trim($data['token'])])->save();
        }

        $client = GetResponseClient::fromSettings($site);
        if (! $client) {
            return back()->withErrors(['gr_token' => 'Podaj klucz API GetResponse (Narzędzia → Integracje i API → API).']);
        }

        try {
            $contacts = $client->count();
            $campaigns = $client->campaigns();
            $blocked = count($client->blocklist());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['gr_token' => $e->getMessage()]);
        }

        $import = NewsletterImport::create([
            'site_id' => $site->id, 'user_id' => auth()->id(), 'source' => NewsletterImport::SOURCE_GETRESPONSE_API,
            'status'  => NewsletterImport::STATUS_PREVIEW, 'total' => $contacts + $blocked,
            'options' => $this->defaultImportOptions() + ['import_blocklist' => true],
            'summary' => [
                'total'         => $contacts + $blocked,
                'source_status' => ['active' => $contacts, 'blocklisted' => $blocked],
                'by_status'     => array_filter([Subscriber::STATUS_CONFIRMED => $contacts, Subscriber::STATUS_UNSUBSCRIBED => $blocked]),
                'groups'        => array_map(fn ($c) => ['key' => $c['id'], 'name' => $c['name'], 'count' => $c['total']], $campaigns),
            ],
        ]);

        return redirect()->route('admin.newsletter.subskrybenci.import.show', $import);
    }

    /** Krok 2: podgląd i mapowanie (przed uruchomieniem) albo postęp i raport. */
    public function importShow(NewsletterImport $import)
    {
        return view('newsletter::admin.subscribers.import-show', [
            'import'   => $import,
            'lists'    => NewsletterList::orderBy('name')->get(),
            'topics'   => Subscriber::availableTopics(),
            'statuses' => Subscriber::STATUSES,
            'fields'   => SubscriberImporter::FIELDS,
        ]);
    }

    /** Zapis mapowania: przeliczenie podglądu albo uruchomienie importu. */
    public function importUpdate(Request $request, NewsletterImport $import, SubscriberImporter $importer)
    {
        abort_unless($import->status === NewsletterImport::STATUS_PREVIEW, 409, 'Ten import został już uruchomiony.');

        $groupKeys = collect($import->summary['groups'] ?? [])->pluck('key')->map(fn ($k) => (string) $k)->all();
        $data = $request->validate([
            'action'         => ['required', Rule::in(['preview', 'run'])],
            'mapping'        => ['nullable', 'array'],
            'mapping.*'      => ['nullable', 'integer', 'min:0', 'max:' . max(0, count($import->columns ?? []) - 1)],
            'field_map'      => ['nullable', 'array'],
            'field_map.*'    => ['nullable', 'string', 'max:100'],
            'group_map'      => ['nullable', 'array'],
            'group_map.*'    => ['nullable', 'string', 'max:60'],
            'default_status' => ['nullable', Rule::in([Subscriber::STATUS_PENDING, Subscriber::STATUS_CONFIRMED])],
            'resend_doi'     => ['nullable', 'boolean'],
            'has_consent'    => ['nullable', 'boolean'],
            'consent_source' => ['nullable', 'string', 'max:120'],
            'consent_date'   => ['nullable', 'date', 'before_or_equal:today'],
            'list_id'        => ['nullable', 'integer', Rule::exists('newsletter_lists', 'id')],
            'topics'         => ['nullable', 'array'],
            'topics.*'       => [Rule::in(array_keys(Subscriber::availableTopics(false)))],
            'tag'            => ['nullable', 'string', 'max:40'],
            'import_blocklist' => ['nullable', 'boolean'],
        ]);

        $groupMap = [];
        foreach ((array) ($data['group_map'] ?? []) as $key => $target) {
            if (in_array((string) $key, $groupKeys, true) && preg_match('/^(|new_list|list:\d+|topic:[\w-]+)$/', (string) $target)) {
                $groupMap[(string) $key] = (string) $target;
            }
        }

        $import->options = array_merge($import->options ?? [], [
            'group_map'      => $groupMap,
            'default_status' => $data['default_status'] ?? Subscriber::STATUS_PENDING,
            'resend_doi'     => $request->boolean('resend_doi'),
            'has_consent'    => $request->boolean('has_consent'),
            'consent_source' => $data['consent_source'] ?? null,
            'consent_date'   => $data['consent_date'] ?? null,
            'list_id'        => $data['list_id'] ?? null,
            'topics'         => $data['topics'] ?? [],
            'tag'            => $data['tag'] ?? null,
        ] + ($import->source === NewsletterImport::SOURCE_MAILERLITE_API ? ['field_map' => array_filter((array) ($data['field_map'] ?? []))] : [])
          + ($import->source === NewsletterImport::SOURCE_GETRESPONSE_API ? ['import_blocklist' => $request->boolean('import_blocklist')] : []));

        if (! $import->isApi()) {
            $mapping = array_map('intval', array_filter((array) ($data['mapping'] ?? []), fn ($v) => $v !== null && $v !== ''));
            $import->mapping = array_intersect_key($mapping, SubscriberImporter::FIELDS);
            $import->summary = $importer->previewFile($import);
        }
        $import->save();

        if ($data['action'] === 'preview') {
            return redirect()->route('admin.newsletter.subskrybenci.import.show', $import)->with('status', 'Podgląd przeliczony.');
        }

        if (! isset($import->mapping['email']) && ! $import->isApi()) {
            return back()->withErrors(['mapping.email' => 'Wskaż kolumnę z adresem e-mail.']);
        }
        $activeRows = (int) ($import->summary['by_status'][Subscriber::STATUS_CONFIRMED] ?? 0);
        // Z MailerLite i GetResponse zgody są już udokumentowane (data zapisu i IP) — oświadczenie tylko dla plików z innych systemów.
        if ($activeRows > 0 && ! $import->isExternal() && ! $import->option('resend_doi') && ! $import->option('has_consent')) {
            return back()->withErrors(['has_consent' => "Import obejmuje {$activeRows} aktywnych adresów bez ponownego potwierdzenia — potwierdź, że masz udokumentowane zgody, albo zaznacz ponowne potwierdzenie (double opt-in)."]);
        }
        if ($activeRows > 0 && ! $import->isExternal() && ! $import->option('resend_doi') && blank($import->option('consent_source'))) {
            return back()->withErrors(['consent_source' => 'Podaj źródło zgody — trafi do rejestru zgód.']);
        }

        // Grupy mapowane na „nową listę” — zakładamy listy przed startem, żeby porcje w kolejce ich nie dublowały.
        foreach ($groupMap as $key => $target) {
            if ($target === 'new_list') {
                $name = (string) (collect($import->summary['groups'] ?? [])->firstWhere('key', $key)['name'] ?? $key);
                $groupMap[$key] = 'list:' . $this->listForGroup($name, $import->providerLabel())->id;
            }
        }
        $options = $import->options;
        $options['group_map'] = $groupMap;
        $import->forceFill(['options' => $options, 'status' => NewsletterImport::STATUS_QUEUED, 'position' => 0, 'cursor' => null])->save();

        // Mały plik — od razu, z raportem po przeładowaniu; większe i import przez API — w kolejce.
        $small = ! $import->isApi() && (int) ($import->summary['total'] ?? 0) <= RunSubscriberImport::FILE_CHUNK;
        if (! $small) {
            RunSubscriberImport::dispatch($import->id);
        } else {
            try {
                RunSubscriberImport::dispatchSync($import->id);
            } catch (\Throwable $e) {
                (new RunSubscriberImport($import->id))->failed($e);
            }
        }

        return redirect()->route('admin.newsletter.subskrybenci.import.show', $import)
            ->with('status', $small ? 'Import zakończony.' : 'Import uruchomiony w tle — raport uzupełnia się na bieżąco.');
    }

    public function importProgress(NewsletterImport $import)
    {
        return response()->json($import->progress());
    }

    public function importDestroy(NewsletterImport $import, SubscriberImporter $importer)
    {
        abort_if($import->isInProgress(), 409, 'Nie można usunąć trwającego importu.');
        $importer->deleteFile($import);
        $import->delete();

        return redirect()->route('admin.newsletter.subskrybenci.import')->with('status', 'Import usunięty z historii.');
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
            'topics.*'   => [Rule::in(array_keys(Subscriber::availableTopics()))],
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

    private function defaultImportOptions(): array
    {
        return ['default_status' => Subscriber::STATUS_PENDING, 'topics' => ['news'], 'tag' => 'import-' . now()->format('Y-m-d'), 'resend_doi' => false];
    }

    private function listForGroup(string $name, string $from): NewsletterList
    {
        $name = mb_substr(trim($name) ?: $from, 0, 120);
        $existing = NewsletterList::where('name', $name)->first();
        if ($existing) {
            return $existing;
        }
        $base = \Illuminate\Support\Str::slug($name) ?: 'lista';
        $slug = $base;
        $i = 2;
        while (NewsletterList::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return NewsletterList::create(['site_id' => SiteSetting::current()->id, 'name' => $name, 'slug' => $slug, 'description' => 'Grupa zaimportowana z ' . ($from === 'plik' ? 'pliku' : $from) . '.']);
    }
}
