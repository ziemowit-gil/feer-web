@extends('admin.layout')
@section('title', 'Import subskrybentów — ' . ($import->isFinished() || $import->isInProgress() ? 'raport' : 'podgląd'))
@section('content')
    @include('newsletter::admin.partials.flash')
    @php
        $summary = $import->summary ?? [];
        $isApi = $import->source === \Modules\Newsletter\Models\NewsletterImport::SOURCE_MAILERLITE_API;
        $mlStatuses = ['active' => 'Aktywni', 'unconfirmed' => 'Niepotwierdzeni', 'unsubscribed' => 'Wypisani', 'bounced' => 'Odbici', 'junk' => 'Spam (junk)'];
        $opt = fn (string $k, $d = null) => old($k, $import->option($k, $d));
    @endphp

    <p class="mb-4 text-sm text-ink">
        <a href="{{ route('admin.newsletter.subskrybenci.import') }}" class="font-bold text-brand-dark hover:underline">← Import</a>
        · {{ $import->sourceLabel() }}@if ($import->file_name) · <strong>{{ $import->file_name }}</strong>@endif
        · utworzony {{ $import->created_at->format('Y-m-d H:i') }}
    </p>

    @if ($import->status === \Modules\Newsletter\Models\NewsletterImport::STATUS_PREVIEW)
        {{-- ── Podgląd ─────────────────────────────────────────────────────── --}}
        <section aria-labelledby="preview-heading" class="mb-6 max-w-5xl rounded-md bg-white p-6">
            <h2 id="preview-heading" class="mb-3 text-lg font-bold">Podgląd: {{ number_format((int) ($summary['total'] ?? 0), 0, ',', ' ') }} rekordów</h2>
            @if ($isApi)
                <h3 class="mb-2 text-sm font-bold">Statusy w MailerLite</h3>
                <ul class="mb-4 flex flex-wrap gap-2 text-sm">
                    @foreach ($mlStatuses as $k => $label)
                        <li class="rounded-md bg-gray-100 px-3 py-1">{{ $label }}: <strong>{{ number_format((int) ($summary['source_status'][$k] ?? 0), 0, ',', ' ') }}</strong></li>
                    @endforeach
                </ul>
            @endif
            <h3 class="mb-2 text-sm font-bold">Status po imporcie</h3>
            <ul class="flex flex-wrap gap-2 text-sm">
                @forelse (($summary['by_status'] ?? []) as $k => $n)
                    <li class="rounded-md bg-brand-light px-3 py-1 text-ink">{{ $statuses[$k] ?? $k }}: <strong>{{ number_format((int) $n, 0, ',', ' ') }}</strong></li>
                @empty
                    <li class="text-muted">Brak rekordów z poprawnym adresem — sprawdź mapowanie kolumny e-mail.</li>
                @endforelse
                @unless ($isApi)
                    <li class="rounded-md bg-gray-100 px-3 py-1">Błędne adresy: <strong>{{ $summary['invalid'] ?? 0 }}</strong></li>
                    <li class="rounded-md bg-gray-100 px-3 py-1">Powtórzone w pliku: <strong>{{ $summary['duplicates'] ?? 0 }}</strong></li>
                    <li class="rounded-md bg-gray-100 px-3 py-1">Już w bazie (zostaną zaktualizowane): <strong>{{ $summary['existing'] ?? 0 }}</strong></li>
                @endunless
            </ul>
            <p class="mt-3 text-xs text-muted">Wypisani, odbici i oznaczeni jako spam trafią na listę tłumienia. Adresów wypisanych lub zablokowanych w newsletterze nie przywracamy do aktywnych.</p>
        </section>

        <form method="POST" action="{{ route('admin.newsletter.subskrybenci.import.update', $import) }}" class="max-w-5xl space-y-6">
            @csrf
            @method('PUT')

            @unless ($isApi)
                <section aria-labelledby="mapping-heading" class="rounded-md bg-white p-6">
                    <h2 id="mapping-heading" class="mb-1 text-lg font-bold">Mapowanie kolumn</h2>
                    <p class="mb-4 text-sm text-muted">Dopasowaliśmy kolumny po nazwach — popraw, jeśli trzeba, i kliknij „Przelicz podgląd”.</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($fields as $field => $label)
                            <div>
                                <label for="mapping-{{ $field }}" class="mb-1 block text-sm font-bold">{{ $label }}</label>
                                <select id="mapping-{{ $field }}" name="mapping[{{ $field }}]" @if($field === 'email') required @endif class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                                    <option value="">— brak —</option>
                                    @foreach (($import->columns ?? []) as $i => $col)
                                        <option value="{{ $i }}" @selected(isset($import->mapping[$field]) && (int) $import->mapping[$field] === $i)>{{ $col }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                    @if (! empty($summary['samples']))
                        <h3 class="mb-2 mt-6 text-sm font-bold">Pierwsze rekordy po mapowaniu</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <caption class="sr-only">Pierwsze rekordy pliku po mapowaniu kolumn</caption>
                                <thead class="bg-gray-100 text-xs font-bold uppercase text-muted"><tr><th scope="col" class="px-3 py-2">E-mail</th><th scope="col" class="px-3 py-2">Imię i nazwisko</th><th scope="col" class="px-3 py-2">Status w pliku</th><th scope="col" class="px-3 py-2">Data zapisu</th><th scope="col" class="px-3 py-2">Grupy</th></tr></thead>
                                <tbody>
                                @foreach ($summary['samples'] as $row)
                                    <tr class="border-t border-gray-100">
                                        <td class="px-3 py-2">{{ $row['email'] }}</td>
                                        <td class="px-3 py-2">{{ trim(($row['name'] ?? '') . ' ' . ($row['last_name'] ?? '')) }}</td>
                                        <td class="px-3 py-2">{{ $row['status'] ?? '—' }}</td>
                                        <td class="px-3 py-2">{{ $row['subscribed_at'] ?? '—' }}</td>
                                        <td class="px-3 py-2">{{ implode(', ', $row['groups'] ?? []) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            @else
                <section aria-labelledby="fields-heading" class="rounded-md bg-white p-6">
                    <h2 id="fields-heading" class="mb-1 text-lg font-bold">Pola MailerLite</h2>
                    <p class="mb-4 text-sm text-muted">Które pola MailerLite przenieść do danych subskrybenta.</p>
                    <div class="grid gap-4 sm:grid-cols-3">
                        @foreach (['name' => 'Imię', 'last_name' => 'Nazwisko', 'phone' => 'Telefon'] as $field => $label)
                            <div>
                                <label for="field-{{ $field }}" class="mb-1 block text-sm font-bold">{{ $label }}</label>
                                <select id="field-{{ $field }}" name="field_map[{{ $field }}]" class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                                    <option value="">— nie przenoś —</option>
                                    @foreach (($summary['fields'] ?? []) as $key => $name)
                                        <option value="{{ $key }}" @selected(($import->option('field_map.' . $field)) === $key)>{{ $name }} ({{ $key }})</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endunless

            @if (! empty($summary['groups']))
                <section aria-labelledby="groups-heading" class="rounded-md bg-white p-6">
                    <h2 id="groups-heading" class="mb-1 text-lg font-bold">Grupy → listy i tematy</h2>
                    <p class="mb-4 text-sm text-muted">Każdą grupę możesz przenieść do istniejącej listy, utworzyć z niej nową listę albo przypisać jej członkom temat.</p>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <caption class="sr-only">Mapowanie grup źródłowych</caption>
                            <thead class="bg-gray-100 text-xs font-bold uppercase text-muted"><tr><th scope="col" class="px-3 py-2">Grupa</th><th scope="col" class="px-3 py-2">Rekordów</th><th scope="col" class="px-3 py-2">Przenieś do</th></tr></thead>
                            <tbody>
                            @foreach ($summary['groups'] as $i => $g)
                                @php $current = old('group_map.' . $g['key'], $import->option('group_map', [])[$g['key']] ?? 'new_list'); @endphp
                                <tr class="border-t border-gray-100">
                                    <th scope="row" class="px-3 py-2 font-bold"><label for="group-{{ $i }}">{{ $g['name'] }}</label></th>
                                    <td class="px-3 py-2">{{ number_format((int) $g['count'], 0, ',', ' ') }}@isset($g['active']) <span class="text-muted">(aktywnych {{ $g['active'] }})</span>@endisset</td>
                                    <td class="px-3 py-2">
                                        <select id="group-{{ $i }}" name="group_map[{{ $g['key'] }}]" class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                                            <option value="" @selected($current === '')>— pomiń —</option>
                                            <option value="new_list" @selected($current === 'new_list')>Nowa lista „{{ $g['name'] }}”</option>
                                            <optgroup label="Istniejące listy">
                                                @foreach ($lists as $l)<option value="list:{{ $l->id }}" @selected($current === 'list:' . $l->id)>{{ $l->name }}</option>@endforeach
                                            </optgroup>
                                            <optgroup label="Tematy">
                                                @foreach ($topics as $k => $label)<option value="topic:{{ $k }}" @selected($current === 'topic:' . $k)>{{ $label }}</option>@endforeach
                                            </optgroup>
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            <section aria-labelledby="consent-heading" class="space-y-4 rounded-md bg-white p-6">
                <h2 id="consent-heading" class="text-lg font-bold">Status i zgody</h2>
                @unless ($isApi)
                    <fieldset>
                        <legend class="mb-1 text-sm font-bold">Status, gdy plik go nie podaje</legend>
                        <label class="flex items-start gap-2 text-sm"><input type="radio" name="default_status" value="pending" @checked($opt('default_status', 'pending') === 'pending') class="mt-1 border-gray-500 text-brand-dark focus:ring-brand-dark"> <span><strong>Oczekujący</strong> — bezpieczny wybór dla adresów z nieznanym źródłem zgody.@if ($import->source === 'file') Wyślemy e-mail potwierdzający (double opt-in).@endif</span></label>
                        <label class="mt-2 flex items-start gap-2 text-sm"><input type="radio" name="default_status" value="confirmed" @checked($opt('default_status') === 'confirmed') class="mt-1 border-gray-500 text-brand-dark focus:ring-brand-dark"> <span><strong>Aktywny</strong> — tylko z udokumentowaną zgodą.</span></label>
                    </fieldset>
                @endunless
                <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="resend_doi" value="1" @checked($opt('resend_doi')) class="mt-1 rounded border-gray-500 text-brand-dark focus:ring-brand-dark"> <span><strong>Poproś aktywnych i niepotwierdzonych o ponowne potwierdzenie (double opt-in).</strong> Domyślnie aktywni z {{ $import->isMailerLite() ? 'MailerLite' : 'pliku' }} zostają aktywni bez wysyłki — ich zgoda trafia do rejestru zgód{{ $import->isMailerLite() ? ' ze źródłem „import MailerLite”, datą zapisu i IP' : '' }}.</span></label>
                <div class="space-y-3 rounded-md {{ $import->isMailerLite() ? 'bg-gray-100' : 'bg-amber-50' }} p-4">
                    @unless ($import->isMailerLite())
                        <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="has_consent" value="1" @checked($opt('has_consent')) class="mt-1 rounded border-gray-500 text-brand-dark focus:ring-brand-dark" @error('has_consent') aria-invalid="true" @enderror> <span>Oświadczam, że dla aktywnych adresów organizacja posiada ważną zgodę na otrzymywanie newslettera.</span></label>
                    @endunless
                    <div><label for="consent_source" class="mb-1 block text-sm font-bold">Źródło zgody {{ $import->isMailerLite() ? '(opcjonalny opis)' : '' }}</label><input id="consent_source" name="consent_source" value="{{ $opt('consent_source') }}" maxlength="120" placeholder="{{ $import->isMailerLite() ? 'np. formularz na stronie, konto MailerLite FEER' : 'np. baza SZO, eksport 2026-09' }}" class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark"></div>
                    <div><label for="consent_date" class="mb-1 block text-sm font-bold">Data zgody, gdy brak daty zapisu (opcjonalnie)</label><input id="consent_date" name="consent_date" type="date" value="{{ $opt('consent_date') }}" class="rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark"></div>
                </div>
            </section>

            <section aria-labelledby="extra-heading" class="space-y-4 rounded-md bg-white p-6">
                <h2 id="extra-heading" class="text-lg font-bold">Dodatkowo</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="list_id" class="mb-1 block text-sm font-bold">Dodaj wszystkich do listy</label><select id="list_id" name="list_id" class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark"><option value="">— nie dodawaj —</option>@foreach ($lists as $l)<option value="{{ $l->id }}" @selected((int) $opt('list_id') === $l->id)>{{ $l->name }}</option>@endforeach</select></div>
                    <div><label for="tag" class="mb-1 block text-sm font-bold">Tag importu</label><input id="tag" name="tag" value="{{ $opt('tag') }}" maxlength="40" class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark"></div>
                </div>
                <fieldset><legend class="mb-1 text-sm font-bold">Tematy dla osób bez tematu</legend><div class="flex flex-wrap gap-3">@foreach ($topics as $k => $l)<label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="topics[]" value="{{ $k }}" class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark" @checked(in_array($k, (array) $opt('topics', []), true))> {{ $l }}</label>@endforeach</div></fieldset>
            </section>

            <div class="flex flex-wrap gap-2">
                <button type="submit" name="action" value="run" class="rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink">Importuj {{ number_format((int) ($summary['total'] ?? 0), 0, ',', ' ') }} rekordów</button>
                <button type="submit" name="action" value="preview" class="rounded-md bg-gray-100 px-5 py-2 text-sm font-bold text-gray-700 hover:bg-brand-light">Przelicz podgląd</button>
                <button type="submit" form="import-delete" class="rounded-md bg-gray-100 px-5 py-2 text-sm font-bold text-gray-700 hover:bg-brand-light">Anuluj import</button>
            </div>
        </form>
        <form id="import-delete" method="POST" action="{{ route('admin.newsletter.subskrybenci.import.destroy', $import) }}">@csrf @method('DELETE')</form>
    @else
        {{-- ── Postęp i raport ─────────────────────────────────────────────── --}}
        @php
            $p = $import->progress();
            $pct = $p['total'] ? min(100, (int) floor($p['processed'] * 100 / max(1, $p['total']))) : ($import->isFinished() ? 100 : 0);
        @endphp
        <section aria-labelledby="report-heading" class="max-w-5xl space-y-4 rounded-md bg-white p-6" data-import-progress="{{ $import->isInProgress() ? route('admin.newsletter.subskrybenci.import.progress', $import) : '' }}">
            <h2 id="report-heading" class="text-lg font-bold">Raport importu</h2>
            <p class="text-sm" role="status" aria-live="polite"><strong>Status:</strong> <span data-k="status_label">{{ $p['status_label'] }}</span>@if ($import->isInProgress()) — strona aktualizuje liczby co kilka sekund; możesz ją zamknąć, import trwa w tle.@endif</p>
            <div>
                <div class="h-3 w-full overflow-hidden rounded bg-gray-100" role="progressbar" aria-label="Postęp importu" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}" data-bar><div class="h-3 bg-brand-dark" style="width: {{ $pct }}%" data-fill></div></div>
                <p class="mt-1 text-xs text-muted">Przetworzono <span data-k="processed">{{ $p['processed'] }}</span>@if ($p['total']) z <span data-k="total">{{ $p['total'] }}</span>@endif</p>
            </div>
            <dl class="grid gap-3 text-sm sm:grid-cols-4">
                <div class="rounded-md bg-brand-light p-3"><dt>Dodani</dt><dd class="text-2xl font-bold text-ink" data-k="created">{{ $p['created'] }}</dd></div>
                <div class="rounded-md bg-brand-light p-3"><dt>Zaktualizowani</dt><dd class="text-2xl font-bold text-ink" data-k="updated">{{ $p['updated'] }}</dd></div>
                <div class="rounded-md bg-gray-100 p-3"><dt>Pominięci</dt><dd class="text-2xl font-bold text-ink" data-k="skipped">{{ $p['skipped'] }}</dd></div>
                <div class="rounded-md bg-gray-100 p-3"><dt>Błędy</dt><dd class="text-2xl font-bold text-ink" data-k="failed">{{ $p['failed'] }}</dd></div>
            </dl>
            @if (! empty($summary['skip_reasons']))
                <div>
                    <h3 class="mb-1 text-sm font-bold">Powody pominięcia</h3>
                    <ul class="list-disc pl-5 text-sm">@foreach ($summary['skip_reasons'] as $reason => $n)<li>{{ $reason }}: {{ $n }}</li>@endforeach</ul>
                </div>
            @endif
            @if (! empty($import->errors))
                <details class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-900" @if($import->status === 'failed') open @endif>
                    <summary class="cursor-pointer font-bold">Błędy ({{ count($import->errors) }}{{ count($import->errors) >= \Modules\Newsletter\Models\NewsletterImport::MAX_ERRORS ? '+' : '' }})</summary>
                    <ul class="mt-2 list-disc pl-5">@foreach ($import->errors as $err)<li>{{ $err }}</li>@endforeach</ul>
                </details>
            @endif
            <p class="flex flex-wrap gap-2">
                <a href="{{ route('admin.newsletter.subskrybenci.index') }}" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink">Subskrybenci</a>
                @if ($import->isInProgress())
                    <a href="{{ route('admin.newsletter.subskrybenci.import.show', $import) }}" class="rounded-md bg-gray-100 px-4 py-2 text-sm font-bold text-gray-700 hover:bg-brand-light" data-refresh>Odśwież raport</a>
                @endif
            </p>
        </section>

        @if ($import->isInProgress())
            <script>
                (() => {
                    const box = document.querySelector('[data-import-progress]');
                    const url = box?.dataset.importProgress;
                    if (!url) return;
                    const tick = async () => {
                        try {
                            const r = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                            if (!r.ok) return setTimeout(tick, 10000);
                            const p = await r.json();
                            box.querySelectorAll('[data-k]').forEach(el => { if (p[el.dataset.k] !== undefined && p[el.dataset.k] !== null) el.textContent = p[el.dataset.k]; });
                            const pct = p.total ? Math.min(100, Math.floor(p.processed * 100 / Math.max(1, p.total))) : (p.finished ? 100 : 0);
                            box.querySelector('[data-bar]').setAttribute('aria-valuenow', pct);
                            box.querySelector('[data-fill]').style.width = pct + '%';
                            // Bez automatycznego przeładowania (WCAG 3.2) — po zakończeniu link prowadzi do pełnego raportu.
                            if (p.finished) { const a = box.querySelector('[data-refresh]'); if (a) a.textContent = 'Pokaż pełny raport (powody pominięć, błędy)'; return; }
                        } catch (e) { /* sieć — spróbuj ponownie */ }
                        setTimeout(tick, 5000);
                    };
                    setTimeout(tick, 5000);
                })();
            </script>
        @endif
    @endif
@endsection
