@extends('admin.layout')
@section('title', 'Subskrybenci newslettera')
@section('content')
    @include('newsletter::admin.partials.flash')
    @if (session('import_invalid'))
        <details class="mb-4 rounded border border-amber-300 bg-amber-50 px-4 py-3 text-sm"><summary class="cursor-pointer font-bold">Błędne wiersze importu ({{ count(session('import_invalid')) }})</summary><ul class="mt-2 list-disc pl-5">@foreach (session('import_invalid') as $row)<li>{{ $row }}</li>@endforeach</ul></details>
    @endif

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2 text-xs">
            @foreach ($statuses as $key => $label)
                @if (($counts[$key] ?? 0) > 0)
                    <a href="{{ route('admin.newsletter.subskrybenci.index', ['status' => $key]) }}" class="rounded-full border px-3 py-1 font-bold {{ ($filters['status'] ?? '') === $key ? 'border-brand-dark bg-brand-dark text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}">{{ $label }}: {{ $counts[$key] }}</a>
                @endif
            @endforeach
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.newsletter.subskrybenci.import') }}" class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50"><i class="fa-solid fa-upload" aria-hidden="true"></i> Import</a>
            <a href="{{ route('admin.newsletter.subskrybenci.export', request()->query()) }}" class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50"><i class="fa-solid fa-download" aria-hidden="true"></i> CSV</a>
            <a href="{{ route('admin.newsletter.subskrybenci.export', request()->query() + ['format' => 'json']) }}" class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">JSON</a>
            <a href="{{ route('admin.newsletter.subskrybenci.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj</a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.newsletter.subskrybenci.index') }}" class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-3">
        <div><label for="q" class="mb-1 block text-xs font-bold">Szukaj</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="e-mail lub imię" class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
        <div><label for="status" class="mb-1 block text-xs font-bold">Status</label><select id="status" name="status" class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand"><option value="">Wszystkie</option>@foreach ($statuses as $k => $l)<option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $l }}</option>@endforeach</select></div>
        <div><label for="topic" class="mb-1 block text-xs font-bold">Temat</label><select id="topic" name="topic" class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand"><option value="">Wszystkie</option>@foreach ($topics as $k => $l)<option value="{{ $k }}" @selected(($filters['topic'] ?? '') === $k)>{{ $l }}</option>@endforeach</select></div>
        <div><label for="channel" class="mb-1 block text-xs font-bold">Kanał</label><select id="channel" name="channel" class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand"><option value="">Wszystkie</option>@foreach ($channels as $k => $l)<option value="{{ $k }}" @selected(($filters['channel'] ?? '') === $k)>{{ $l }}</option>@endforeach</select></div>
        <div><label for="list" class="mb-1 block text-xs font-bold">Lista</label><select id="list" name="list" class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand"><option value="">Wszystkie</option>@foreach ($lists as $l)<option value="{{ $l->id }}" @selected((int) ($filters['list'] ?? 0) === $l->id)>{{ $l->name }}</option>@endforeach</select></div>
        <div><label for="segment" class="mb-1 block text-xs font-bold">Segment</label><select id="segment" name="segment" class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand"><option value="">—</option>@foreach ($segments as $s)<option value="{{ $s->id }}" @selected((int) ($filters['segment'] ?? 0) === $s->id)>{{ $s->name }}</option>@endforeach</select></div>
        <div><label for="since" class="mb-1 block text-xs font-bold">Zapisani od</label><input id="since" type="date" name="since" value="{{ $filters['since'] ?? '' }}" class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
        <button type="submit" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">Filtruj</button>
        @if (array_filter($filters))<a href="{{ route('admin.newsletter.subskrybenci.index') }}" class="rounded border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Wyczyść</a>@endif
    </form>

    <form method="POST" action="{{ route('admin.newsletter.subskrybenci.bulk') }}" x-data="{ selected: [], action: '' }">
        @csrf
        <div class="mb-3 flex flex-wrap items-center gap-2 rounded-lg border border-gray-200 bg-white p-3 text-sm" x-show="selected.length" x-cloak>
            <span class="font-bold"><span x-text="selected.length"></span> zaznaczonych:</span>
            <select name="action" x-model="action" class="rounded border-gray-300 text-sm" aria-label="Akcja zbiorcza">
                <option value="">— wybierz akcję —</option><option value="add_list">Dodaj do listy</option><option value="remove_list">Usuń z listy</option><option value="add_tag">Dodaj tag</option>
                <option value="resend">Wyślij ponownie potwierdzenie</option><option value="unsubscribe">Wypisz</option><option value="anonymize">Anonimizuj (RODO)</option><option value="delete">Usuń</option>
            </select>
            <select name="list_id" x-show="action === 'add_list' || action === 'remove_list'" class="rounded border-gray-300 text-sm" aria-label="Lista">@foreach ($lists as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach</select>
            <input name="tag" x-show="action === 'add_tag'" placeholder="tag" class="rounded border-gray-300 text-sm" aria-label="Tag">
            <button type="submit" class="rounded bg-brand px-3 py-1.5 font-bold text-white hover:bg-brand-dark" :disabled="!action" onclick="return (this.form.action.value !== 'delete' && this.form.action.value !== 'anonymize') || confirm('Na pewno? Tej operacji nie można cofnąć.')">Wykonaj</button>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                    <tr><th class="px-3 py-3"><input type="checkbox" aria-label="Zaznacz wszystkich" @change="selected = $event.target.checked ? [...document.querySelectorAll('[data-sub-id]')].map(e => e.value) : []"></th>
                        <th class="px-3 py-3">E-mail</th><th class="px-3 py-3">Imię</th><th class="px-3 py-3">Status</th><th class="px-3 py-3">Kanały</th><th class="px-3 py-3">Tematy</th><th class="px-3 py-3">Zaang.</th><th class="px-3 py-3">Zapisany</th><th class="px-3 py-3">Ost. otwarcie</th><th class="px-3 py-3"><span class="sr-only">Akcje</span></th></tr>
                </thead>
                <tbody>
                @forelse ($subscribers as $s)
                    <tr class="border-t border-gray-100 hover:bg-gray-50">
                        <td class="px-3 py-2"><input type="checkbox" name="ids[]" value="{{ $s->id }}" data-sub-id x-model="selected" aria-label="Zaznacz {{ $s->email }}"></td>
                        <td class="px-3 py-2"><a href="{{ route('admin.newsletter.subskrybenci.show', $s) }}" class="font-bold text-brand-dark hover:underline">{{ $s->email }}</a>@if($s->lists_count) <span class="text-xs text-muted">· list: {{ $s->lists_count }}</span>@endif</td>
                        <td class="px-3 py-2">{{ $s->name }}</td>
                        <td class="px-3 py-2">@include('newsletter::admin.partials.status-badge', ['value' => $s->status, 'label' => $s->statusLabel()])</td>
                        <td class="px-3 py-2 text-muted">@foreach ($s->channels ?? [] as $ch)<i class="fa-solid {{ ['email' => 'fa-envelope', 'webpush' => 'fa-bell', 'sms' => 'fa-comment-sms'][$ch] ?? 'fa-circle' }} mr-1" title="{{ $channels[$ch] ?? $ch }}" aria-label="{{ $channels[$ch] ?? $ch }}"></i>@endforeach</td>
                        <td class="px-3 py-2 text-xs text-muted">{{ implode(', ', $s->topicLabels()) }}</td>
                        <td class="px-3 py-2"><span class="inline-block h-2 w-16 rounded bg-gray-200 align-middle" aria-hidden="true"><span class="block h-2 rounded bg-brand-dark" style="width: {{ $s->engagement_score }}%"></span></span> <span class="text-xs">{{ $s->engagement_score }}</span></td>
                        <td class="px-3 py-2 text-muted">{{ $s->created_at?->format('d.m.Y') }}</td>
                        <td class="px-3 py-2 text-muted">{{ $s->last_open_at?->format('d.m.Y') ?? '—' }}</td>
                        <td class="px-3 py-2 text-right"><a href="{{ route('admin.newsletter.subskrybenci.edit', $s) }}" class="text-muted hover:text-brand-dark" aria-label="Edytuj {{ $s->email }}"><i class="fa-solid fa-pen" aria-hidden="true"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="px-4 py-8 text-center text-muted">Brak subskrybentów spełniających kryteria.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </form>
    <div class="mt-4">{{ $subscribers->links() }}</div>
@endsection
