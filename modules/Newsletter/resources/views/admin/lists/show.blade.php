@extends('admin.layout')
@section('title', 'Lista: ' . $list->name)
@section('content')
    @include('newsletter::admin.partials.flash')
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm text-muted"><a href="{{ route('admin.newsletter.listy.index') }}" class="hover:underline">← Listy mailingowe</a> · <code>{{ $list->slug }}</code>{{ $list->is_public ? ' · publiczna (w preferencjach subskrybenta)' : '' }}</p>
            @if ($list->description)<p class="mt-1 text-sm text-ink">{{ $list->description }}</p>@endif
            <p class="mt-2 flex flex-wrap gap-2 text-xs">
                <a href="{{ route('admin.newsletter.listy.show', $list) }}" class="rounded-full border px-3 py-1 font-bold {{ $status === '' ? 'border-brand-dark bg-brand-dark text-white' : 'border-gray-300 bg-white text-gray-700' }}">Wszyscy: {{ $byStatus->sum() }}</a>
                @foreach ($statuses as $k => $l)@if (($byStatus[$k] ?? 0) > 0)<a href="{{ route('admin.newsletter.listy.show', [$list, 'status' => $k]) }}" class="rounded-full border px-3 py-1 font-bold {{ $status === $k ? 'border-brand-dark bg-brand-dark text-white' : 'border-gray-300 bg-white text-gray-700' }}">{{ $l }}: {{ $byStatus[$k] }}</a>@endif @endforeach
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.newsletter.kampanie.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Kampania do tej listy</a>
            <a href="{{ route('admin.newsletter.listy.export', $list) }}" class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50"><i class="fa-solid fa-download" aria-hidden="true"></i> Eksport CSV</a>
            <a href="{{ route('admin.newsletter.listy.edit', $list) }}" class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">Ustawienia listy</a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <form method="GET" action="{{ route('admin.newsletter.listy.show', $list) }}" class="mb-3 flex flex-wrap items-end gap-2">
                <div><label for="q" class="mb-1 block text-xs font-bold">Szukaj w liście</label><input id="q" name="q" value="{{ $q }}" placeholder="e-mail lub imię" class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
                @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
                <button class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">Szukaj</button>
                @if ($q)<a href="{{ route('admin.newsletter.listy.show', $list) }}" class="rounded border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700">Wyczyść</a>@endif
            </form>

            <form method="POST" action="{{ route('admin.newsletter.listy.members', $list) }}" x-data="{ selected: [], action: '' }">
                @csrf
                <div class="mb-3 flex flex-wrap items-center gap-2 rounded-lg border border-gray-200 bg-white p-3 text-sm" x-show="selected.length" x-cloak>
                    <span class="font-bold"><span x-text="selected.length"></span> zaznaczonych:</span>
                    <select name="action" x-model="action" class="rounded border-gray-300 text-sm" aria-label="Akcja"><option value="">— wybierz —</option><option value="remove">Usuń z listy</option><option value="copy">Skopiuj do innej listy</option><option value="move">Przenieś do innej listy</option></select>
                    <select name="target" x-show="action === 'copy' || action === 'move'" class="rounded border-gray-300 text-sm" aria-label="Lista docelowa">@foreach ($otherLists as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach</select>
                    <button type="submit" class="rounded bg-brand px-3 py-1.5 font-bold text-white hover:bg-brand-dark" :disabled="!action">Wykonaj</button>
                </div>
                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs font-bold uppercase text-muted"><tr>
                            <th class="px-3 py-3"><input type="checkbox" aria-label="Zaznacz wszystkich na stronie" @change="selected = $event.target.checked ? [...document.querySelectorAll('[data-m-id]')].map(e => e.value) : []"></th>
                            <th class="px-3 py-3">E-mail</th><th class="px-3 py-3">Imię</th><th class="px-3 py-3">Status</th><th class="px-3 py-3">Tematy</th><th class="px-3 py-3">Dodano</th><th class="px-3 py-3"><span class="sr-only">Akcje</span></th></tr></thead>
                        <tbody>
                        @forelse ($members as $m)
                            <tr class="border-t border-gray-100 hover:bg-gray-50">
                                <td class="px-3 py-2"><input type="checkbox" name="ids[]" value="{{ $m->id }}" data-m-id x-model="selected" aria-label="Zaznacz {{ $m->email }}"></td>
                                <td class="px-3 py-2"><a href="{{ route('admin.newsletter.subskrybenci.show', $m) }}" class="font-bold text-brand-dark hover:underline">{{ $m->email }}</a></td>
                                <td class="px-3 py-2">{{ $m->name }}</td>
                                <td class="px-3 py-2">@include('newsletter::admin.partials.status-badge', ['value' => $m->status, 'label' => $m->statusLabel()])</td>
                                <td class="px-3 py-2 text-xs text-muted">{{ implode(', ', $m->topicLabels()) }}</td>
                                <td class="px-3 py-2 text-muted">{{ \Illuminate\Support\Carbon::parse($m->pivot->added_at)->format('d.m.Y') }}</td>
                                <td class="px-3 py-2 text-right"><button type="submit" name="single_remove" class="text-muted hover:text-red-700" aria-label="Usuń {{ $m->email }} z listy" @click.prevent="selected = ['{{ $m->id }}']; action = 'remove'; $nextTick(() => $el.form.submit())"><i class="fa-solid fa-user-minus" aria-hidden="true"></i></button></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-muted">Lista jest pusta — dodaj członków w panelu obok.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
            <div class="mt-3">{{ $members->links() }}</div>
        </div>

        <aside class="space-y-5">
            <section class="rounded-lg border border-gray-200 bg-white p-5" aria-labelledby="add-h" x-data="{ mode: 'emails' }">
                <h2 id="add-h" class="mb-3 text-sm font-bold uppercase text-muted">Dodaj do listy</h2>
                <form method="POST" action="{{ route('admin.newsletter.listy.add', $list) }}" class="space-y-3">
                    @csrf
                    <div><label for="mode" class="mb-1 block text-sm font-bold">Źródło</label>
                        <select id="mode" name="mode" x-model="mode" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"><option value="emails">Adresy e-mail (wklej)</option><option value="segment">Segment dynamiczny</option><option value="topic">Subskrybenci tematu</option><option value="status">Wszyscy o statusie</option></select></div>
                    <div x-show="mode === 'emails'"><label for="emails" class="mb-1 block text-sm font-bold">Adresy (po przecinku lub w liniach)</label><textarea id="emails" name="emails" rows="5" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand" placeholder="anna@example.com&#10;jan@example.com"></textarea><p class="mt-1 text-xs text-muted">Nieznane adresy zostaną utworzone jako oczekujące i dostaną e-mail potwierdzający.</p></div>
                    <div x-show="mode === 'segment'" x-cloak><label for="segment_id" class="mb-1 block text-sm font-bold">Segment</label><select id="segment_id" name="segment_id" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">@foreach ($segments as $s)<option value="{{ $s->id }}">{{ $s->name }} ({{ $s->cached_count ?? '?' }})</option>@endforeach</select></div>
                    <div x-show="mode === 'topic'" x-cloak><label for="topic" class="mb-1 block text-sm font-bold">Temat</label><select id="topic" name="topic" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">@foreach ($topics as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                    <div x-show="mode === 'status'" x-cloak><label for="status_add" class="mb-1 block text-sm font-bold">Status</label><select id="status_add" name="status" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">@foreach ($statuses as $k => $l)<option value="{{ $k }}" @selected($k === 'confirmed')>{{ $l }}</option>@endforeach</select></div>
                    <button type="submit" class="w-full rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> Dodaj</button>
                </form>
            </section>
            <section class="rounded-lg border border-gray-200 bg-white p-5 text-sm" aria-labelledby="info-h">
                <h2 id="info-h" class="mb-2 text-sm font-bold uppercase text-muted">Jak używać</h2>
                <ul class="list-disc space-y-1 pl-5 text-muted">
                    <li>Wybierz tę listę jako odbiorców w kroku „Odbiorcy” kampanii.</li>
                    <li>Formularz zapisu może dodawać do listy automatycznie (Formularze zapisu → „Dodaj zapisanych do list”).</li>
                    <li>Publiczna lista pojawia się subskrybentowi w preferencjach — sam może się zapisać lub wypisać.</li>
                    <li>Kampania wysyła tylko do członków o statusie „Aktywny” z ważną zgodą.</li>
                </ul>
                <form method="POST" action="{{ route('admin.newsletter.listy.members', $list) }}" class="mt-4" onsubmit="return confirm('Usunąć wszystkich członków z tej listy? Subskrybenci pozostają w bazie.')">@csrf<input type="hidden" name="action" value="remove_all"><button class="rounded border border-red-300 bg-white px-3 py-1.5 text-xs font-bold text-red-800 hover:bg-red-50">Opróżnij listę</button></form>
            </section>
        </aside>
    </div>
@endsection
