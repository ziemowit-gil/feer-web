@extends('admin.layout')
@section('title', 'Subskrybent: ' . $subscriber->email)
@section('content')
    @include('newsletter::admin.partials.flash')
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm text-muted"><a href="{{ route('admin.newsletter.subskrybenci.index') }}" class="hover:underline">← Subskrybenci</a></p>
            <p class="mt-1">@include('newsletter::admin.partials.status-badge', ['value' => $subscriber->status, 'label' => $subscriber->statusLabel()]) <span class="ml-2 text-sm text-muted">zapisany {{ $subscriber->created_at?->format('d.m.Y H:i') }} · źródło: {{ $subscriber->source ?? '—' }}</span></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.newsletter.subskrybenci.edit', $subscriber) }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">Edytuj</a>
            @if (in_array($subscriber->status, ['pending', 'expired']))
            <form method="POST" action="{{ route('admin.newsletter.subskrybenci.resend', $subscriber) }}">@csrf<button class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">Wyślij potwierdzenie ponownie</button></form>
            @endif
            @if ($subscriber->isConfirmed())
            <form method="POST" action="{{ route('admin.newsletter.subskrybenci.sync', $subscriber) }}">@csrf<button class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">Synchronizuj z SZO/CRM</button></form>
            @endif
            <a href="{{ route('admin.newsletter.subskrybenci.data', $subscriber) }}" class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">Eksport danych osoby (JSON)</a>
            @if ($subscriber->status !== 'anonymized')
            <form method="POST" action="{{ route('admin.newsletter.subskrybenci.anonymize', $subscriber) }}" onsubmit="return confirm('Zanonimizować dane osobowe? Adres, imię i telefon zostaną usunięte bezpowrotnie.')">@csrf<button class="rounded border border-red-300 bg-white px-4 py-2 text-sm font-bold text-red-800 hover:bg-red-50">Anonimizuj (RODO)</button></form>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-lg border border-gray-200 bg-white p-4" aria-labelledby="prof-h">
            <h2 id="prof-h" class="mb-3 text-sm font-bold uppercase text-muted">Profil</h2>
            <dl class="space-y-2 text-sm">
                <div><dt class="font-bold">Imię</dt><dd>{{ $subscriber->name ?: '—' }}</dd></div>
                <div><dt class="font-bold">Telefon</dt><dd>{{ $subscriber->phone ?: '—' }}</dd></div>
                <div><dt class="font-bold">Tematy</dt><dd>{{ implode(', ', $subscriber->topicLabels()) ?: '—' }}</dd></div>
                <div><dt class="font-bold">Kanały</dt><dd>{{ implode(', ', array_map(fn ($c) => \App\Models\Subscriber::CHANNELS[$c] ?? $c, $subscriber->channels ?? [])) }}</dd></div>
                <div><dt class="font-bold">Tagi</dt><dd>{{ implode(', ', $subscriber->tags ?? []) ?: '—' }}</dd></div>
                <div><dt class="font-bold">Listy</dt><dd>{{ $subscriber->lists->pluck('name')->implode(', ') ?: '—' }}</dd></div>
                <div><dt class="font-bold">Potwierdzony</dt><dd>{{ $subscriber->confirmed_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
                <div><dt class="font-bold">Ostatnia wysyłka / otwarcie / kliknięcie</dt><dd>{{ $subscriber->last_sent_at?->format('d.m.Y') ?? '—' }} / {{ $subscriber->last_open_at?->format('d.m.Y') ?? '—' }} / {{ $subscriber->last_click_at?->format('d.m.Y') ?? '—' }}</dd></div>
                <div><dt class="font-bold">Zaangażowanie</dt><dd>{{ $subscriber->engagement_score }}/100</dd></div>
                <div><dt class="font-bold">SZO</dt><dd>{{ $subscriber->szo_synced_at ? 'zsynchronizowany ' . $subscriber->szo_synced_at->format('d.m.Y H:i') . ' (kontakt ' . $subscriber->szo_contact_id . ')' : ($subscriber->szo_error ?: '—') }}</dd></div>
                <div><dt class="font-bold">CRM</dt><dd>{{ $subscriber->crm_synced_at ? 'zsynchronizowany ' . $subscriber->crm_synced_at->format('d.m.Y H:i') : ($subscriber->crm_error ?: '—') }}</dd></div>
                <div><dt class="font-bold">Link preferencji</dt><dd class="break-all text-xs"><a href="{{ route('newsletter.preferences', ['token' => $subscriber->token]) }}" target="_blank" rel="noopener" class="text-brand-dark underline">{{ route('newsletter.preferences', ['token' => $subscriber->token]) }}</a></dd></div>
            </dl>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-4" aria-labelledby="cons-h">
            <h2 id="cons-h" class="mb-3 text-sm font-bold uppercase text-muted">Rejestr zgód</h2>
            @forelse ($subscriber->consents->sortByDesc('granted_at') as $c)
                <div class="mb-3 border-b border-gray-100 pb-3 text-sm last:border-0">
                    <p class="font-bold">{{ \App\Models\Subscriber::CHANNELS[$c->channel] ?? $c->channel }} {!! $c->revoked_at ? '<span class="text-red-700">— wycofana ' . $c->revoked_at->format('d.m.Y') . ' (' . e($c->revoked_via) . ')</span>' : ($c->confirmed_at ? '<span class="text-green-800">— potwierdzona</span>' : '<span class="text-amber-700">— niepotwierdzona</span>') !!}</p>
                    <p class="text-xs text-muted">{{ $c->granted_at?->format('d.m.Y H:i') }} · {{ $c->source }} · {{ $c->method }} · IP {{ $c->ip_hash ?? '—' }}</p>
                    <p class="mt-1 text-xs italic">„{{ $c->clause_text }}”</p>
                </div>
            @empty
                <p class="text-sm text-muted">Brak zapisanych zgód.</p>
            @endforelse
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-4" aria-labelledby="hist-h">
            <h2 id="hist-h" class="mb-3 text-sm font-bold uppercase text-muted">Historia aktywności</h2>
            <ol class="space-y-2 text-sm">
                @forelse ($subscriber->events->take(60) as $e)
                    <li class="flex gap-2"><span class="w-28 shrink-0 text-xs text-muted">{{ $e->created_at?->format('d.m.Y H:i') }}</span><span><strong>{{ $e->label() }}</strong>@if($e->meta) <span class="text-xs text-muted">{{ \Illuminate\Support\Str::limit(json_encode($e->meta, JSON_UNESCAPED_UNICODE), 80) }}</span>@endif</span></li>
                @empty
                    <li class="text-muted">Brak zdarzeń.</li>
                @endforelse
            </ol>
        </section>
    </div>

    <section class="mt-6 rounded-lg border border-gray-200 bg-white" aria-labelledby="del-h">
        <h2 id="del-h" class="border-b border-gray-200 px-4 py-3 text-sm font-bold uppercase text-muted">Dostawy</h2>
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted"><tr><th class="px-4 py-2">Kampania</th><th class="px-4 py-2">Kanał</th><th class="px-4 py-2">Status</th><th class="px-4 py-2">Wysłano</th><th class="px-4 py-2">Otwarto</th><th class="px-4 py-2">Klik.</th><th class="px-4 py-2">Błąd</th></tr></thead>
            <tbody>
            @forelse ($subscriber->deliveries->sortByDesc('id') as $d)
                <tr class="border-t border-gray-100"><td class="px-4 py-2">{{ $d->campaign?->title ?? '—' }}</td><td class="px-4 py-2">{{ $d->channel }}</td><td class="px-4 py-2">@include('newsletter::admin.partials.status-badge', ['value' => $d->status, 'label' => $d->statusLabel()])</td><td class="px-4 py-2 text-muted">{{ $d->sent_at?->format('d.m.Y H:i') ?? '—' }}</td><td class="px-4 py-2 text-muted">{{ $d->opened_at?->format('d.m.Y H:i') ?? '—' }}</td><td class="px-4 py-2">{{ $d->clicks_count }}</td><td class="px-4 py-2 text-xs text-red-800">{{ $d->error_message }}</td></tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-muted">Brak dostaw.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
@endsection
