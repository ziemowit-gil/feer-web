@extends('admin.layout')
@section('title', 'Kampania: ' . $campaign->title)
@section('content')
    @include('newsletter::admin.partials.flash')
    @php $canEdit = auth()->user()->can('newsletter.campaigns'); $canSend = auth()->user()->can('newsletter.send'); @endphp
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div><p class="text-sm text-muted"><a href="{{ route('admin.newsletter.kampanie.index') }}" class="hover:underline">← Kampanie</a></p>
            <p class="mt-1 flex flex-wrap items-center gap-2">@include('newsletter::admin.partials.status-badge', ['value' => $campaign->status, 'label' => $campaign->statusLabel()]) <span class="text-sm text-muted">temat: <strong class="text-ink">{{ $campaign->subject }}</strong></span></p></div>
        <div class="flex flex-wrap gap-2">
            @if ($campaign->isEditable() && $canEdit)
                <a href="{{ route('admin.newsletter.kampanie.edit', $campaign) }}" class="rounded-md px-4 py-2 text-sm font-bold text-gray-700 bg-white hover:bg-brand-light">Ustawienia</a>
                <a href="{{ route('admin.newsletter.kampanie.editor', $campaign) }}" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink"><i class="fa-solid fa-pen-ruler" aria-hidden="true"></i> Treść</a>
            @endif
            <a href="{{ route('admin.newsletter.kampanie.preview', $campaign) }}" target="_blank" rel="noopener" class="rounded-md px-4 py-2 text-sm font-bold text-gray-700 bg-white hover:bg-brand-light">Podgląd <span class="sr-only">(nowe okno)</span></a>
            @if (($campaign->status === 'sent' || $campaign->isRunning()) && auth()->user()->can('newsletter.reports'))<a href="{{ route('admin.newsletter.kampanie.report', $campaign) }}" class="rounded-md px-4 py-2 text-sm font-bold text-gray-700 bg-white hover:bg-brand-light"><i class="fa-solid fa-chart-column" aria-hidden="true"></i> Raport</a>@endif
            @if ($canEdit)<form method="POST" action="{{ route('admin.newsletter.kampanie.duplicate', $campaign) }}">@csrf<button class="rounded-md px-4 py-2 text-sm font-bold text-gray-700 bg-white hover:bg-brand-light">Duplikuj</button></form>@endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="p-6 rounded-md bg-white" aria-labelledby="chk-h"><h2 id="chk-h" class="mb-3 text-sm font-bold uppercase text-muted">Sprawdzenie przed wysyłką</h2>
                <ul class="space-y-1 text-sm">@foreach ($checks as $c)<li class="flex items-start gap-2">{!! ['ok' => '<i class="fa-solid fa-circle-check mt-0.5 text-green-700" aria-label="OK"></i>', 'warning' => '<i class="fa-solid fa-triangle-exclamation mt-0.5 text-amber-600" aria-label="Ostrzeżenie"></i>', 'error' => '<i class="fa-solid fa-circle-xmark mt-0.5 text-red-700" aria-label="Błąd"></i>'][$c['level']] !!} <span>{{ $c['message'] }}</span></li>@endforeach</ul>
            </section>

            @if ($campaign->isEditable() && $canEdit)
            <section class="p-6 rounded-md bg-white" aria-labelledby="test-h"><h2 id="test-h" class="mb-3 text-sm font-bold uppercase text-muted">Wysyłka testowa</h2>
                <form method="POST" action="{{ route('admin.newsletter.kampanie.test', $campaign) }}" class="flex flex-wrap items-end gap-3">@csrf<div class="grow"><label for="emails" class="mb-1 block text-sm font-bold">Adresy (max 5, po przecinku)</label><input id="emails" name="emails" value="{{ auth()->user()->email }}" class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark"></div><button class="rounded-md px-4 py-2 text-sm font-bold text-gray-700 bg-gray-100 hover:bg-brand-light">Wyślij test</button></form>
            </section>
            @endif
            @if ($campaign->isEditable() && ! $canSend)
            <p class="p-4 text-sm rounded-md bg-brand-light text-ink"><i class="fa-solid fa-circle-info mr-1" aria-hidden="true"></i> Wysłać lub zaplanować kampanię może osoba z uprawnieniem „Kampanie: wysyłka”.</p>
            @endif
            @if ($campaign->isEditable() && $canSend)
            <section class="p-6 rounded-md bg-white" aria-labelledby="send-h"><h2 id="send-h" class="mb-3 text-sm font-bold uppercase text-muted">Wysyłka</h2>
                <p class="mb-3 text-sm">Odbiorców (e-mail): <strong>{{ number_format($audienceCount, 0, ',', ' ') }}</strong> · kanały: {{ implode(', ', array_map(fn ($c) => $channels[$c]?->label() ?? $c, $campaign->channels ?? [])) }} · szacowany czas: ~{{ max(1, (int) ceil($audienceCount / max(1, (int) (\App\Models\SiteSetting::current()->newsletter_rate_per_minute ?: 60)))) }} min</p>
                <div class="grid gap-4 md:grid-cols-2">
                    <form method="POST" action="{{ route('admin.newsletter.kampanie.send', $campaign) }}" class="space-y-2 p-4 rounded-md bg-gray-50" onsubmit="return confirm('Wysłać teraz do {{ $audienceCount }} odbiorców?')">@csrf
                        <p class="font-bold">Wyślij teraz</p>
                        @if ($audienceCount > 1000)<div><label for="confirm" class="mb-1 block text-sm">Wpisz <strong>WYŚLIJ</strong>, aby potwierdzić</label><input id="confirm" name="confirm" class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark" autocomplete="off"></div>@endif
                        <button class="rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Wyślij</button>
                    </form>
                    <form method="POST" action="{{ route('admin.newsletter.kampanie.schedule', $campaign) }}" class="space-y-2 p-4 rounded-md bg-gray-50">@csrf
                        <p class="font-bold">Zaplanuj</p>
                        <div><label for="scheduled_at" class="mb-1 block text-sm">Data i godzina</label><input id="scheduled_at" name="scheduled_at" type="datetime-local" required value="{{ old('scheduled_at', $campaign->scheduled_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark"></div>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="send_in_recipient_tz" value="1" class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark" @checked($campaign->send_in_recipient_tz)> o tej godzinie czasu lokalnego odbiorcy</label>
                        <button class="rounded border border-brand-dark px-5 py-2 text-sm font-bold text-brand-dark hover:bg-brand-dark hover:text-white"><i class="fa-solid fa-calendar" aria-hidden="true"></i> Zaplanuj</button>
                    </form>
                </div>
            </section>
            @endif

            @if ($campaign->isRunning() || $campaign->status === 'scheduled')
            <section class="p-6 rounded-md bg-white" aria-labelledby="prog-h"><h2 id="prog-h" class="mb-3 text-sm font-bold uppercase text-muted">Postęp</h2>
                @php $done = (int) ($byStatus->sum()) - (int) ($byStatus['queued'] ?? 0); $total = max(1, $campaign->recipients_count ?: $byStatus->sum()); $pct = min(100, (int) round($done / $total * 100)); @endphp
                <div class="mb-2 h-3 w-full overflow-hidden rounded bg-gray-200" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Postęp wysyłki"><div class="h-3 bg-brand-dark" style="width: {{ $pct }}%"></div></div>
                <p class="text-sm" aria-live="polite">{{ $done }} / {{ $total }} ({{ $pct }} %) · w kolejce: {{ $byStatus['queued'] ?? 0 }} · wysłane: {{ ($byStatus['sent'] ?? 0) + ($byStatus['delivered'] ?? 0) }} · błędy: {{ ($byStatus['failed'] ?? 0) + ($byStatus['hard_bounced'] ?? 0) }} · pominięte: {{ $byStatus['skipped'] ?? 0 }}{{ $campaign->scheduled_at && $campaign->status === 'scheduled' ? ' · start: ' . $campaign->scheduled_at->format('d.m.Y H:i') : '' }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @if ($canSend)
                    @if ($campaign->status === 'sending')<form method="POST" action="{{ route('admin.newsletter.kampanie.pause', $campaign) }}">@csrf<button class="rounded-md px-4 py-2 text-sm font-bold text-gray-700 bg-gray-100 hover:bg-brand-light">Wstrzymaj</button></form>@endif
                    @if ($campaign->status === 'paused')<form method="POST" action="{{ route('admin.newsletter.kampanie.resume', $campaign) }}">@csrf<button class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink">Wznów</button></form>@endif
                    <form method="POST" action="{{ route('admin.newsletter.kampanie.cancel', $campaign) }}" onsubmit="return confirm('Anulować kampanię? Niewysłane dostawy zostaną pominięte.')">@csrf<button class="rounded-md px-4 py-2 text-sm font-bold text-red-800 bg-red-50 hover:bg-red-100">Anuluj wysyłkę</button></form>
                    @endif
                    <meta http-equiv="refresh" content="20">
                </div>
            </section>
            @endif
        </div>
        <aside class="space-y-6">
            <section class="p-6 text-sm rounded-md bg-white" aria-labelledby="sum-h"><h2 id="sum-h" class="mb-3 text-sm font-bold uppercase text-muted">Podsumowanie</h2>
                <dl class="space-y-2">
                    <div><dt class="font-bold">Odbiorcy</dt><dd>{{ ($campaign->audience['all'] ?? false) ? 'wszyscy aktywni' : implode('; ', array_filter(['listy: ' . $lists->implode(', '), 'segmenty: ' . $segments->implode(', '), 'tematy: ' . implode(', ', $topics)], fn ($s) => ! str_ends_with($s, ': '))) }}</dd></div>
                    <div><dt class="font-bold">Nadawca</dt><dd>{{ $campaign->from_name }} &lt;{{ $campaign->from_address ?: \App\Models\SiteSetting::current()->newsletter_from_address ?: config('mail.from.address') }}&gt;</dd></div>
                    <div><dt class="font-bold">Preheader</dt><dd>{{ $campaign->preheader ?: '—' }}</dd></div>
                    <div><dt class="font-bold">Śledzenie</dt><dd>{{ $campaign->track_opens ? 'otwarcia' : '' }}{{ $campaign->track_opens && $campaign->track_clicks ? ', ' : '' }}{{ $campaign->track_clicks ? 'kliknięcia' : '' }}{{ ! $campaign->track_opens && ! $campaign->track_clicks ? 'wyłączone' : '' }}</dd></div>
                    @if ($campaign->subject_b)<div><dt class="font-bold">Test A/B</dt><dd>B: „{{ $campaign->subject_b }}”, próbka {{ $campaign->ab_split_percent }} %</dd></div>@endif
                    @if ($campaign->isRecurring())<div><dt class="font-bold">Cykl</dt><dd>{{ $campaign->recurrence === 'weekly' ? 'co tydzień' : 'co miesiąc' }}, godz. {{ $campaign->recurrence_rule['hour'] ?? 9 }}:00 · ostatnio: {{ $campaign->last_run_at?->format('d.m.Y') ?? 'nigdy' }}</dd></div>@endif
                    <div><dt class="font-bold">Utworzono</dt><dd>{{ $campaign->created_at?->format('d.m.Y H:i') }}</dd></div>
                    @if ($campaign->finished_at)<div><dt class="font-bold">Zakończono</dt><dd>{{ $campaign->finished_at->format('d.m.Y H:i') }}</dd></div>@endif
                </dl>
            </section>
            @if ($campaign->children()->exists())
            <section class="p-6 text-sm rounded-md bg-white" aria-labelledby="ch-h"><h2 id="ch-h" class="mb-3 text-sm font-bold uppercase text-muted">Wysyłki z tego cyklu</h2><ul class="space-y-1">@foreach ($campaign->children()->latest()->take(10)->get() as $ch)<li><a href="{{ auth()->user()->can('newsletter.reports') ? route('admin.newsletter.kampanie.report', $ch) : route('admin.newsletter.kampanie.show', $ch) }}" class="text-brand-dark hover:underline">{{ $ch->title }}</a> · {{ $ch->statusLabel() }}</li>@endforeach</ul></section>
            @endif
            @if ($campaign->isEditable() && $canEdit)
            <form method="POST" action="{{ route('admin.newsletter.kampanie.destroy', $campaign) }}" onsubmit="return confirm('Przenieść kampanię do kosza?')">@csrf @method('DELETE')<button class="w-full rounded-md px-4 py-2 text-sm font-bold text-red-800 bg-red-50 hover:bg-red-100">Usuń kampanię</button></form>
            @endif
        </aside>
    </div>
@endsection
