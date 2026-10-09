@extends('admin.layout')
@section('title', $campaign->exists ? 'Kampania: ' . $campaign->title : 'Nowa kampania')
@section('content')
    @include('newsletter::admin.partials.flash')
    @php $a = old('audience_mode', ($campaign->audience['all'] ?? true) ? 'all' : 'pick'); $inp = 'w-full rounded border-gray-300 focus:border-brand focus:ring-brand'; @endphp
    <form method="POST" action="{{ $campaign->exists ? route('admin.newsletter.kampanie.update', $campaign) : route('admin.newsletter.kampanie.store') }}" class="grid gap-6 lg:grid-cols-3"
          x-data="{ mode: '{{ $a }}', ab: {{ old('subject_b', $campaign->subject_b) ? 'true' : 'false' }}, channels: @js(old('channels', $campaign->channels ?? ['email'])), recurrence: '{{ old('recurrence', $campaign->recurrence ?? '') }}', count: null,
                    recount() { const fd = new FormData(this.$root); fetch('{{ route('admin.newsletter.kampanie.audience', $campaign->exists ? $campaign : 0) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }, body: fd }).then(r => r.json()).then(d => this.count = d.count).catch(() => this.count = '?'); } }" x-init="recount()">
        @csrf @if ($campaign->exists) @method('PUT') @endif
        <div class="space-y-5 lg:col-span-2">
            <section class="space-y-4 rounded-lg border border-gray-200 bg-white p-6" aria-labelledby="c-basics"><h2 id="c-basics" class="text-sm font-bold uppercase text-muted">1. Podstawy</h2>
                <div><label for="title" class="mb-1 block text-sm font-bold">Nazwa robocza (widoczna tylko w panelu)</label><input id="title" name="title" required value="{{ old('title', $campaign->title) }}" class="{{ $inp }}"></div>
                <div><label for="subject" class="mb-1 block text-sm font-bold">Temat</label><input id="subject" name="subject" required maxlength="255" value="{{ old('subject', $campaign->subject) }}" class="{{ $inp }}" x-ref="subject" @input="$refs.subjectLen.textContent = $event.target.value.length"><p class="mt-1 text-xs text-muted"><span x-ref="subjectLen">{{ mb_strlen((string) old('subject', $campaign->subject)) }}</span>/78 znaków widocznych w większości skrzynek · można użyć tagów, np. <code>@{{first_name|default:Cześć}}</code></p></div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" x-model="ab" class="rounded border-gray-300 text-brand focus:ring-brand"> Test A/B tematu</label>
                <div x-show="ab" x-cloak class="grid gap-4 sm:grid-cols-3"><div class="sm:col-span-2"><label for="subject_b" class="mb-1 block text-sm font-bold">Temat B</label><input id="subject_b" name="subject_b" value="{{ old('subject_b', $campaign->subject_b) }}" class="{{ $inp }}"></div><div><label for="ab_split_percent" class="mb-1 block text-sm font-bold">Próbka (% odbiorców)</label><input id="ab_split_percent" name="ab_split_percent" type="number" min="10" max="50" value="{{ old('ab_split_percent', $campaign->ab_split_percent ?: 20) }}" class="{{ $inp }}"></div></div>
                <div><label for="preheader" class="mb-1 block text-sm font-bold">Preheader (podgląd pod tematem)</label><input id="preheader" name="preheader" maxlength="255" value="{{ old('preheader', $campaign->preheader) }}" class="{{ $inp }}"></div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div><label for="from_name" class="mb-1 block text-sm font-bold">Nadawca</label><input id="from_name" name="from_name" value="{{ old('from_name', $campaign->from_name) }}" class="{{ $inp }}"></div>
                    <div><label for="from_address" class="mb-1 block text-sm font-bold">Adres nadawcy</label><input id="from_address" name="from_address" type="email" value="{{ old('from_address', $campaign->from_address) }}" class="{{ $inp }}"></div>
                    <div><label for="reply_to" class="mb-1 block text-sm font-bold">Odpowiedzi do</label><input id="reply_to" name="reply_to" type="email" value="{{ old('reply_to', $campaign->reply_to) }}" class="{{ $inp }}"></div>
                </div>
                <div class="rounded border border-gray-200 bg-gray-50 p-3 text-sm" aria-label="Podgląd w skrzynce"><p class="text-xs font-bold uppercase text-muted">Tak zobaczy to odbiorca</p><p class="mt-1"><strong x-text="document.getElementById('from_name').value || '{{ e($campaign->from_name ?: config('mail.from.name')) }}'"></strong></p><p class="font-bold text-ink" x-text="$refs.subject?.value || '(temat)'"></p><p class="text-muted" x-text="document.getElementById('preheader').value || ''"></p></div>
            </section>

            <section class="space-y-4 rounded-lg border border-gray-200 bg-white p-6" aria-labelledby="c-aud"><h2 id="c-aud" class="text-sm font-bold uppercase text-muted">2. Odbiorcy i kanały</h2>
                <div class="flex flex-wrap gap-4 text-sm">
                    <label class="inline-flex items-center gap-2"><input type="radio" name="audience_mode" value="all" x-model="mode" @change="recount()" class="border-gray-300 text-brand focus:ring-brand"> Wszyscy aktywni subskrybenci</label>
                    <label class="inline-flex items-center gap-2"><input type="radio" name="audience_mode" value="pick" x-model="mode" @change="recount()" class="border-gray-300 text-brand focus:ring-brand"> Wybrane listy / segmenty / tematy</label>
                </div>
                <div x-show="mode === 'pick'" x-cloak class="grid gap-4 sm:grid-cols-3">
                    <fieldset><legend class="mb-1 text-sm font-bold">Listy</legend><div class="space-y-1 text-sm">@forelse ($lists as $l)<label class="flex items-center gap-2"><input type="checkbox" name="lists[]" value="{{ $l->id }}" @change="recount()" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(in_array($l->id, old('lists', $campaign->audience['lists'] ?? []), true))> {{ $l->name }} <span class="text-xs text-muted">({{ $l->subscribers_count }})</span></label>@empty<span class="text-muted">brak</span>@endforelse</div></fieldset>
                    <fieldset><legend class="mb-1 text-sm font-bold">Segmenty</legend><div class="space-y-1 text-sm">@forelse ($segments as $s)<label class="flex items-center gap-2"><input type="checkbox" name="segments[]" value="{{ $s->id }}" @change="recount()" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(in_array($s->id, old('segments', $campaign->audience['segments'] ?? []), true))> {{ $s->name }} <span class="text-xs text-muted">({{ $s->cached_count ?? '?' }})</span></label>@empty<span class="text-muted">brak</span>@endforelse</div></fieldset>
                    <fieldset><legend class="mb-1 text-sm font-bold">Tematy</legend><div class="space-y-1 text-sm">@foreach ($topics as $k => $l)<label class="flex items-center gap-2"><input type="checkbox" name="topics[]" value="{{ $k }}" @change="recount()" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(in_array($k, old('topics', $campaign->audience['topics'] ?? []), true))> {{ $l }}</label>@endforeach</div></fieldset>
                </div>
                <fieldset><legend class="mb-1 text-sm font-bold">Wyklucz listy</legend><div class="flex flex-wrap gap-3 text-sm">@foreach ($lists as $l)<label class="inline-flex items-center gap-2"><input type="checkbox" name="exclude_lists[]" value="{{ $l->id }}" @change="recount()" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(in_array($l->id, old('exclude_lists', $campaign->audience['exclude_lists'] ?? []), true))> {{ $l->name }}</label>@endforeach</div></fieldset>
                <p class="rounded bg-brand/10 px-4 py-2 text-sm font-bold text-brand-dark" aria-live="polite">Odbiorców (e-mail): <span x-text="count === null ? '…' : count"></span></p>
                <fieldset><legend class="mb-1 text-sm font-bold">Kanały</legend><div class="flex flex-wrap gap-4 text-sm">
                    @foreach ($channels as $key => $driver)
                        <label class="inline-flex items-center gap-2 {{ $driver->enabled() ? '' : 'text-muted' }}"><input type="checkbox" name="channels[]" value="{{ $key }}" x-model="channels" class="rounded border-gray-300 text-brand focus:ring-brand" @checked($key === 'email') @disabled($key === 'email' || ! $driver->enabled())> {{ $driver->label() }}{{ $driver->enabled() ? '' : ' (wyłączony w ustawieniach)' }}</label>
                    @endforeach
                    <input type="hidden" name="channels[]" value="email">
                </div></fieldset>
            </section>

            <section class="space-y-4 rounded-lg border border-gray-200 bg-white p-6" aria-labelledby="c-short" x-show="channels.includes('webpush') || channels.includes('sms')" x-cloak><h2 id="c-short" class="text-sm font-bold uppercase text-muted">Wersja skrócona (push / SMS)</h2>
                <div class="grid gap-4 sm:grid-cols-2"><div><label for="short_title" class="mb-1 block text-sm font-bold">Tytuł (≤ 60)</label><input id="short_title" name="short_title" maxlength="60" value="{{ old('short_title', $campaign->short_title) }}" class="{{ $inp }}"></div><div><label for="short_url" class="mb-1 block text-sm font-bold">Link docelowy</label><input id="short_url" name="short_url" type="url" value="{{ old('short_url', $campaign->short_url) }}" placeholder="puste = wersja www wiadomości" class="{{ $inp }}"></div></div>
                <div><label for="short_text" class="mb-1 block text-sm font-bold">Tekst (≤ 160)</label><textarea id="short_text" name="short_text" maxlength="160" rows="2" class="{{ $inp }}">{{ old('short_text', $campaign->short_text) }}</textarea></div>
            </section>
        </div>

        <div class="space-y-5">
            <section class="space-y-4 rounded-lg border border-gray-200 bg-white p-6" aria-labelledby="c-tpl"><h2 id="c-tpl" class="text-sm font-bold uppercase text-muted">Treść</h2>
                @if (! $campaign->exists)
                    <div><label for="template_id" class="mb-1 block text-sm font-bold">Szablon startowy</label><select id="template_id" name="template_id" class="{{ $inp }}"><option value="">Pusty szablon FEER (Mosaico)</option>@foreach ($templates as $t)<option value="{{ $t->id }}" @selected((int) old('template_id', $campaign->template_id) === $t->id)>{{ $t->name }}{{ $t->is_default ? ' (domyślny)' : '' }}</option>@endforeach</select></div>
                    <p class="text-xs text-muted">Po zapisaniu otworzy się edytor Mosaico. Blok „Najnowsze aktualności” pobiera treść z CMS (sortowanie wg daty lub kategorii).</p>
                @else
                    <a href="{{ route('admin.newsletter.kampanie.editor', $campaign) }}" class="inline-block rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark"><i class="fa-solid fa-pen-ruler" aria-hidden="true"></i> Otwórz edytor treści</a>
                    <p class="text-xs text-muted">HTML: {{ $campaign->html_body ? round(strlen($campaign->html_body) / 1024) . ' kB' : 'brak' }}</p>
                @endif
                <details class="text-xs"><summary class="cursor-pointer font-bold text-brand-dark">Tagi personalizacji</summary><ul class="mt-2 space-y-0.5">@foreach ($tags as $k => $l)<li><code>@{{{{ $k }}}}</code> — {{ $l }}</li>@endforeach</ul></details>
            </section>
            <section class="space-y-3 rounded-lg border border-gray-200 bg-white p-6" aria-labelledby="c-track"><h2 id="c-track" class="text-sm font-bold uppercase text-muted">Śledzenie i UTM</h2>
                <label class="flex items-center gap-2 text-sm"><input type="hidden" name="track_opens" value="0"><input type="checkbox" name="track_opens" value="1" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(old('track_opens', $campaign->track_opens ?? true))> Otwarcia</label>
                <label class="flex items-center gap-2 text-sm"><input type="hidden" name="track_clicks" value="0"><input type="checkbox" name="track_clicks" value="1" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(old('track_clicks', $campaign->track_clicks ?? true))> Kliknięcia</label>
                <div class="grid gap-2"><input name="utm_source" value="{{ old('utm_source', $campaign->utm['source'] ?? 'newsletter') }}" aria-label="utm_source" placeholder="utm_source" class="{{ $inp }} text-sm"><input name="utm_medium" value="{{ old('utm_medium', $campaign->utm['medium'] ?? 'email') }}" aria-label="utm_medium" placeholder="utm_medium" class="{{ $inp }} text-sm"><input name="utm_campaign" value="{{ old('utm_campaign', $campaign->utm['campaign'] ?? '') }}" aria-label="utm_campaign" placeholder="utm_campaign" class="{{ $inp }} text-sm"></div>
            </section>
            <section class="space-y-3 rounded-lg border border-gray-200 bg-white p-6" aria-labelledby="c-rec"><h2 id="c-rec" class="text-sm font-bold uppercase text-muted">Cykliczność (digest)</h2>
                <div><label for="recurrence" class="mb-1 block text-sm font-bold">Powtarzaj</label><select id="recurrence" name="recurrence" x-model="recurrence" class="{{ $inp }}"><option value="">nie — jednorazowa</option><option value="weekly">co tydzień</option><option value="monthly">co miesiąc</option></select></div>
                <div x-show="recurrence" x-cloak class="grid gap-2 sm:grid-cols-2">
                    <div x-show="recurrence === 'weekly'"><label for="rec_weekday" class="mb-1 block text-xs font-bold">Dzień tygodnia</label><select id="rec_weekday" name="rec_weekday" class="{{ $inp }} text-sm">@foreach ([1 => 'pon', 2 => 'wt', 3 => 'śr', 4 => 'czw', 5 => 'pt', 6 => 'sob', 7 => 'nd'] as $d => $l)<option value="{{ $d }}" @selected((int) old('rec_weekday', $campaign->recurrence_rule['weekday'] ?? 5) === $d)>{{ $l }}</option>@endforeach</select></div>
                    <div x-show="recurrence === 'monthly'"><label for="rec_day" class="mb-1 block text-xs font-bold">Dzień miesiąca</label><input id="rec_day" name="rec_day" type="number" min="1" max="28" value="{{ old('rec_day', $campaign->recurrence_rule['day'] ?? 1) }}" class="{{ $inp }} text-sm"></div>
                    <div><label for="rec_hour" class="mb-1 block text-xs font-bold">Godzina</label><input id="rec_hour" name="rec_hour" type="number" min="0" max="23" value="{{ old('rec_hour', $campaign->recurrence_rule['hour'] ?? 9) }}" class="{{ $inp }} text-sm"></div>
                    <div><label for="rec_min_items" class="mb-1 block text-xs font-bold">Min. nowych pozycji</label><input id="rec_min_items" name="rec_min_items" type="number" min="0" max="20" value="{{ old('rec_min_items', $campaign->recurrence_rule['min_items'] ?? 1) }}" class="{{ $inp }} text-sm"></div>
                </div>
                <p class="text-xs text-muted">Kampania-wzorzec z blokiem „Najnowsze aktualności” wysyła kopię w terminie, jeśli od ostatniej wysyłki pojawiły się nowe pozycje.</p>
            </section>
            <div class="flex gap-2"><button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">{{ $campaign->exists ? 'Zapisz' : 'Zapisz i przejdź do treści' }}</button><a href="{{ $campaign->exists ? route('admin.newsletter.kampanie.show', $campaign) : route('admin.newsletter.kampanie.index') }}" class="rounded border border-gray-300 bg-white px-5 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">Anuluj</a></div>
        </div>
    </form>
@endsection
