@extends('admin.layout')
@section('title', 'Raport: ' . $campaign->title)
@section('content')
    @include('newsletter::admin.partials.flash')
    <p class="mb-3 text-sm text-muted"><a href="{{ route('admin.newsletter.kampanie.show', $campaign) }}" class="hover:underline">← Kampania</a> · wysłano {{ $campaign->started_at?->format('d.m.Y H:i') }} · <a href="{{ route('admin.newsletter.kampanie.report.csv', $campaign) }}" class="font-bold text-brand-dark underline">Eksport CSV</a></p>
    @php $d = max(1, $campaign->delivered_count); @endphp
    <dl class="mb-6 grid gap-3 sm:grid-cols-3 lg:grid-cols-6" role="group" aria-label="Wskaźniki kampanii">
        @foreach ([
            ['Dostarczone', number_format($campaign->delivered_count, 0, ',', ' '), 'z ' . number_format($campaign->recipients_count, 0, ',', ' ')],
            ['Otwarcia (OR)', ($campaign->rate('opened_unique') ?? 0) . ' %', $campaign->opened_unique . ' unik.' . ($proxyOpens ? ' · bez proxy: ' . round(max(0, $campaign->opened_unique - $proxyOpens) / $d * 100, 1) . ' %' : '')],
            ['Kliknięcia (CTR)', ($campaign->rate('clicked_unique') ?? 0) . ' %', $campaign->clicked_unique . ' unik.'],
            ['CTOR', $campaign->opened_unique ? round($campaign->clicked_unique / $campaign->opened_unique * 100, 1) . ' %' : '—', 'klikający / otwierający'],
            ['Odbicia', ($campaign->sent_count ? round($campaign->bounced_count / $campaign->sent_count * 100, 2) : 0) . ' %', $campaign->bounced_count . ' · skargi: ' . $campaign->complained_count],
            ['Wypisy', round($unsubscribes / $d * 100, 2) . ' %', $unsubscribes . ' osób'],
        ] as [$l, $v, $sub])
            <div class="rounded-lg border border-gray-200 bg-white p-3"><dt class="text-xs font-bold uppercase text-muted">{{ $l }}</dt><dd class="text-2xl font-extrabold text-ink">{{ $v }}</dd><dd class="text-xs text-muted">{{ $sub }}</dd></div>
        @endforeach
    </dl>

    <div x-data="{ tab: 'timeline' }">
        <nav class="mb-4 flex flex-wrap gap-1 border-b border-gray-200" aria-label="Sekcje raportu">
            @foreach (['timeline' => 'Oś czasu (72 h)', 'links' => 'Linki i mapa cieplna', 'recipients' => 'Odbiorcy', 'channels' => 'Kanały i klienci'] as $k => $l)
                <button type="button" @click="tab = '{{ $k }}'" :class="tab === '{{ $k }}' ? 'border-brand text-brand-dark' : 'border-transparent text-gray-700'" class="-mb-px border-b-2 px-4 py-2 text-sm font-bold">{{ $l }}</button>
            @endforeach
        </nav>

        <section x-show="tab === 'timeline'" class="rounded-lg border border-gray-200 bg-white p-4">
            @php $max = max(1, max(array_map(fn ($t) => max($t['opens'], $t['clicks']), $timeline ?: [['opens' => 0, 'clicks' => 0]]))); @endphp
            @if ($timeline)
            <svg viewBox="0 0 740 180" class="w-full" role="img" aria-label="Otwarcia i kliknięcia na godzinę przez 72 godziny od wysyłki">
                @foreach ($timeline as $i => $t)
                    @php $x = 10 + $i * 10; @endphp
                    <rect x="{{ $x }}" y="{{ 150 - round($t['opens'] / $max * 140) }}" width="4" height="{{ round($t['opens'] / $max * 140) }}" fill="#1752BF"><title>{{ $t['label'] }}: otwarcia {{ $t['opens'] }}</title></rect>
                    <rect x="{{ $x + 4 }}" y="{{ 150 - round($t['clicks'] / $max * 140) }}" width="4" height="{{ round($t['clicks'] / $max * 140) }}" fill="#EA8F00"><title>{{ $t['label'] }}: kliknięcia {{ $t['clicks'] }}</title></rect>
                    @if ($i % 12 === 0)<text x="{{ $x }}" y="168" font-size="10" fill="#4A4A47">{{ $t['label'] }}</text>@endif
                @endforeach
            </svg>
            <p class="text-xs text-muted"><span class="inline-block h-3 w-3 bg-[#1752BF] align-middle"></span> otwarcia · <span class="inline-block h-3 w-3 bg-[#EA8F00] align-middle"></span> kliknięcia</p>
            <details class="mt-2 text-sm"><summary class="cursor-pointer font-bold text-brand-dark">Dane (tabela)</summary><table class="mt-2 text-left text-xs"><thead><tr><th class="pr-3">Godzina</th><th class="pr-3">Otwarcia</th><th>Kliknięcia</th></tr></thead><tbody>@foreach ($timeline as $t)@if($t['opens'] || $t['clicks'])<tr><td class="pr-3">{{ $t['label'] }}</td><td class="pr-3">{{ $t['opens'] }}</td><td>{{ $t['clicks'] }}</td></tr>@endif @endforeach</tbody></table></details>
            @else<p class="text-sm text-muted">Brak danych — kampania nie została jeszcze wysłana.</p>@endif
        </section>

        <section x-show="tab === 'links'" x-cloak class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-white">
                <table class="w-full text-left text-sm"><thead class="bg-gray-50 text-xs font-bold uppercase text-muted"><tr><th class="px-3 py-2">#</th><th class="px-3 py-2">Link</th><th class="px-3 py-2 text-right">Unik.</th><th class="px-3 py-2 text-right">Razem</th><th class="px-3 py-2 text-right">% klik.</th></tr></thead>
                <tbody>@forelse ($links as $i => $l)<tr class="border-t border-gray-100"><td class="px-3 py-2 text-muted">{{ $i + 1 }}</td><td class="px-3 py-2"><span class="font-bold">{{ $l->label ?: '(obraz)' }}</span><br><a href="{{ $l->url }}" target="_blank" rel="noopener" class="break-all text-xs text-brand-dark underline">{{ \Illuminate\Support\Str::limit($l->url, 70) }}</a></td><td class="px-3 py-2 text-right">{{ $l->clicks_unique }}</td><td class="px-3 py-2 text-right">{{ $l->clicks_total }}</td><td class="px-3 py-2 text-right">{{ $campaign->clicked_unique ? round($l->clicks_unique / $campaign->clicked_unique * 100) : 0 }} %</td></tr>@empty<tr><td colspan="5" class="px-3 py-6 text-center text-muted">Brak linków.</td></tr>@endforelse</tbody></table>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-2">
                <p class="mb-2 px-2 text-xs text-muted">Mapa cieplna: etykiety przy linkach pokazują % unikalnych klikających (ciemniejszy = częściej).</p>
                <iframe title="Podgląd z mapą cieplną kliknięć" class="h-[640px] w-full rounded border border-gray-200 bg-white" sandbox="allow-same-origin" srcdoc="{{ e($previewHtml) }}" data-heatmap-frame></iframe>
                <script>
                (function(){
                    var links = @json($links->map(fn ($l) => ['url' => $l->url, 'unique' => $l->clicks_unique]));
                    var max = {{ $maxClicks }}, total = {{ max(1, $campaign->clicked_unique) }};
                    var f = document.querySelector('[data-heatmap-frame]');
                    f.addEventListener('load', function(){
                        var doc = f.contentDocument; if (!doc) return;
                        doc.querySelectorAll('a[href]').forEach(function(a){
                            var href = a.getAttribute('href'); var hit = links.find(function(l){ return l.url === href; });
                            if (!hit) return;
                            var pct = Math.round(hit.unique / total * 100); var t = hit.unique / max;
                            var tag = doc.createElement('span'); tag.textContent = pct + '%';
                            tag.setAttribute('style', 'display:inline-block;margin:0 4px;padding:2px 6px;border-radius:10px;font:bold 11px Arial;color:' + (t > .5 ? '#fff' : '#1D1D1A') + ';background:' + (t > .66 ? '#1752BF' : t > .33 ? '#8FB4FF' : '#DBE7FF') + ';vertical-align:middle;');
                            a.insertAdjacentElement('afterend', tag);
                            a.style.outline = '2px solid ' + (t > .66 ? '#1752BF' : t > .33 ? '#8FB4FF' : '#DBE7FF');
                        });
                    });
                })();
                </script>
            </div>
        </section>

        <section x-show="tab === 'recipients'" x-cloak class="rounded-lg border border-gray-200 bg-white">
            <div class="flex flex-wrap gap-1 border-b border-gray-200 p-3 text-xs">@foreach (['' => 'Wszyscy', 'opened' => 'Otworzyli', 'clicked' => 'Kliknęli', 'bounced' => 'Odbici', 'failed' => 'Błędy/pominięci'] as $k => $l)<a href="{{ route('admin.newsletter.kampanie.report', [$campaign, 'r' => $k]) }}#odbiorcy" class="rounded-full border px-3 py-1 font-bold {{ $recipientFilter === $k ? 'border-brand-dark bg-brand-dark text-white' : 'border-gray-300 bg-white text-gray-700' }}">{{ $l }}</a>@endforeach</div>
            <table class="w-full text-left text-sm"><thead class="bg-gray-50 text-xs font-bold uppercase text-muted"><tr><th class="px-3 py-2">E-mail</th><th class="px-3 py-2">Kanał</th><th class="px-3 py-2">Status</th><th class="px-3 py-2">Wysłano</th><th class="px-3 py-2">Otwarto</th><th class="px-3 py-2 text-right">Klik.</th><th class="px-3 py-2">Błąd</th></tr></thead>
            <tbody>@forelse ($recipients as $r)<tr class="border-t border-gray-100"><td class="px-3 py-2">@if($r->subscriber)<a href="{{ route('admin.newsletter.subskrybenci.show', $r->subscriber) }}" class="text-brand-dark hover:underline">{{ $r->subscriber->email }}</a>@else —@endif</td><td class="px-3 py-2">{{ $r->channel }}{{ $r->variant ? ' (' . $r->variant . ')' : '' }}</td><td class="px-3 py-2">@include('newsletter::admin.partials.status-badge', ['value' => $r->status, 'label' => $r->statusLabel()])</td><td class="px-3 py-2 text-muted">{{ $r->sent_at?->format('d.m H:i') ?? '—' }}</td><td class="px-3 py-2 text-muted">{{ $r->opened_at?->format('d.m H:i') ?? '—' }}</td><td class="px-3 py-2 text-right">{{ $r->clicks_count }}</td><td class="px-3 py-2 text-xs text-red-800">{{ $r->error_message }}</td></tr>@empty<tr><td colspan="7" class="px-3 py-6 text-center text-muted">Brak.</td></tr>@endforelse</tbody></table>
            <div class="p-3">{{ $recipients->links() }}</div>
        </section>

        <section x-show="tab === 'channels'" x-cloak class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-white"><table class="w-full text-left text-sm"><thead class="bg-gray-50 text-xs font-bold uppercase text-muted"><tr><th class="px-3 py-2">Kanał</th><th class="px-3 py-2 text-right">Dostawy</th><th class="px-3 py-2 text-right">OK</th><th class="px-3 py-2 text-right">Otwarte</th><th class="px-3 py-2 text-right">Kliknięte</th></tr></thead><tbody>@foreach ($byChannel as $c)<tr class="border-t border-gray-100"><td class="px-3 py-2 font-bold">{{ \App\Models\Subscriber::CHANNELS[$c->channel] ?? $c->channel }}</td><td class="px-3 py-2 text-right">{{ $c->total }}</td><td class="px-3 py-2 text-right">{{ $c->ok }}</td><td class="px-3 py-2 text-right">{{ $c->opened }}</td><td class="px-3 py-2 text-right">{{ $c->clicked }}</td></tr>@endforeach</tbody></table></div>
            <div class="rounded-lg border border-gray-200 bg-white p-4"><h3 class="mb-2 text-sm font-bold">Klienci poczty (otwarcia)</h3><ul class="space-y-1 text-sm">@forelse ($clients as $k => $v)<li class="flex justify-between"><span>{{ ['gmail' => 'Gmail', 'apple_mail' => 'Apple Mail / iOS', 'outlook' => 'Outlook', 'thunderbird' => 'Thunderbird', 'yahoo' => 'Yahoo', 'other' => 'Inne'][$k] ?? $k }}</span><strong>{{ $v }}</strong></li>@empty<li class="text-muted">Brak danych.</li>@endforelse</ul><p class="mt-3 text-xs text-muted">Otwarcia przez proxy (Apple Mail Privacy Protection, Gmail) są liczone, ale oznaczone — wskaźnik „bez proxy” u góry pokazuje ostrożniejszy OR.</p></div>
        </section>
    </div>
@endsection
