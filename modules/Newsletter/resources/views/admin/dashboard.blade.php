@extends('admin.layout')
@section('title', 'Newsletter — pulpit')
@section('content')
    @include('newsletter::admin.partials.flash')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Kampanie e-mail, push i SMS · subskrybenci z double opt-in</p>
        <div class="flex gap-2">
            <a href="{{ route('admin.newsletter.kampanie.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark"><i class="fa-solid fa-plus" aria-hidden="true"></i> Nowa kampania</a>
            <a href="{{ route('admin.newsletter.mosaico') }}" class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50"><i class="fa-solid fa-pen-ruler" aria-hidden="true"></i> Edytor Mosaico</a>
            <a href="{{ route('newsletter.show') }}" target="_blank" rel="noopener" class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">Strona /newsletter <span class="sr-only">(nowe okno)</span></a>
        </div>
    </div>

    <nav aria-label="Sekcje newslettera" class="mb-6">
        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            @foreach ([
                ['Kampanie', 'admin.newsletter.kampanie.index', 'fa-paper-plane', 'Wysyłki e-mail, push i SMS'],
                ['Subskrybenci', 'admin.newsletter.subskrybenci.index', 'fa-users', 'Lista osób i ich zgody'],
                ['Listy', 'admin.newsletter.listy.index', 'fa-list', 'Grupy odbiorców do wysyłek'],
                ['Segmenty', 'admin.newsletter.segmenty.index', 'fa-filter', 'Filtrowane grupy subskrybentów'],
                ['Tematy', 'admin.newsletter.tematy.index', 'fa-tags', 'Tematy do wyboru przy zapisie'],
                ['Szablony', 'admin.newsletter.szablony.index', 'fa-file-code', 'Gotowe układy wiadomości'],
                ['Edytor Mosaico', 'admin.newsletter.mosaico', 'fa-pen-ruler', 'Projektowanie wiadomości'],
                ['Formularze zapisu', 'admin.newsletter.formularze.index', 'fa-clipboard-list', 'Formularze na stronach i ich wygląd'],
                ['Ustawienia', 'admin.newsletter.ustawienia.edit', 'fa-gear', 'Nadawca, zgody i integracje'],
            ] as [$title, $route, $icon, $desc])
                <li>
                    <a href="{{ route($route) }}" class="flex h-full items-start gap-3 rounded-lg border border-gray-200 bg-white p-4 hover:border-brand hover:bg-gray-50 focus-visible:outline focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-brand">
                        <i class="fa-solid {{ $icon }} mt-1 text-brand-dark" aria-hidden="true"></i>
                        <span>
                            <span class="block text-sm font-bold text-ink">{{ $title }}</span>
                            <span class="mt-1 block text-xs text-muted">{{ $desc }}</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <dl class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5" role="group" aria-label="Kluczowe wskaźniki">
        @foreach ([
            ['Aktywni subskrybenci', number_format($active, 0, ',', ' '), 'fa-users', route('admin.newsletter.subskrybenci.index', ['status' => 'confirmed'])],
            ['Nowi (30 dni)', '+' . number_format($new30, 0, ',', ' ') . ($lost30 ? ' / −' . $lost30 : ''), 'fa-user-plus', route('admin.newsletter.subskrybenci.index', ['since' => now()->subDays(30)->toDateString()])],
            ['Oczekują na potwierdzenie', number_format($pending, 0, ',', ' '), 'fa-clock', route('admin.newsletter.subskrybenci.index', ['status' => 'pending'])],
            ['Śr. otwarcia (90 dni)', $avgOpen !== null ? $avgOpen . ' %' : '—', 'fa-envelope-open', null],
            ['Śr. kliknięcia (90 dni)', $avgClick !== null ? $avgClick . ' %' : '—', 'fa-arrow-pointer', null],
        ] as [$label, $value, $icon, $href])
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <dt class="flex items-center gap-2 text-xs font-bold uppercase text-muted"><i class="fa-solid {{ $icon }}" aria-hidden="true"></i> {{ $label }}</dt>
                <dd class="mt-1 text-2xl font-extrabold text-ink">@if($href)<a href="{{ $href }}" class="hover:underline">{{ $value }}</a>@else{{ $value }}@endif</dd>
            </div>
        @endforeach
    </dl>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="lg:col-span-2 rounded-lg border border-gray-200 bg-white" aria-labelledby="recent-h">
            <h2 id="recent-h" class="border-b border-gray-200 px-4 py-3 text-sm font-bold uppercase text-muted">Ostatnie kampanie</h2>
            @if ($recent->isEmpty())
                <p class="px-4 py-6 text-sm text-muted">Jeszcze nic nie wysłano. <a href="{{ route('admin.newsletter.kampanie.create') }}" class="font-bold text-brand-dark underline">Utwórz pierwszą kampanię</a>.</p>
            @else
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs font-bold uppercase text-muted"><tr><th class="px-4 py-2">Nazwa</th><th class="px-4 py-2">Wysłano</th><th class="px-4 py-2 text-right">Dost.</th><th class="px-4 py-2 text-right">Otw.</th><th class="px-4 py-2 text-right">Klik.</th><th class="px-4 py-2">Stan</th></tr></thead>
                <tbody>
                @foreach ($recent as $c)
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-2"><a href="{{ route('admin.newsletter.kampanie.report', $c) }}" class="font-bold text-brand-dark hover:underline">{{ $c->title }}</a></td>
                        <td class="px-4 py-2 text-muted">{{ $c->started_at?->format('d.m.Y H:i') }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($c->delivered_count, 0, ',', ' ') }}</td>
                        <td class="px-4 py-2 text-right">{{ $c->rate('opened_unique') !== null ? $c->rate('opened_unique') . ' %' : '—' }}</td>
                        <td class="px-4 py-2 text-right">{{ $c->rate('clicked_unique') !== null ? $c->rate('clicked_unique') . ' %' : '—' }}</td>
                        <td class="px-4 py-2">@include('newsletter::admin.partials.status-badge', ['value' => $c->status, 'label' => $c->statusLabel()])</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </section>

        <div class="space-y-6">
            <section class="rounded-lg border border-gray-200 bg-white" aria-labelledby="sched-h">
                <h2 id="sched-h" class="border-b border-gray-200 px-4 py-3 text-sm font-bold uppercase text-muted">Zaplanowane</h2>
                @forelse ($scheduled as $c)
                    <p class="border-t border-gray-100 px-4 py-2 text-sm first:border-0"><span class="font-bold text-ink">{{ $c->scheduled_at?->format('d.m H:i') }}</span> · <a href="{{ route('admin.newsletter.kampanie.show', $c) }}" class="text-brand-dark hover:underline">{{ $c->title }}</a></p>
                @empty
                    <p class="px-4 py-4 text-sm text-muted">Brak zaplanowanych kampanii.</p>
                @endforelse
            </section>
            <section class="rounded-lg border border-gray-200 bg-white p-4" aria-labelledby="queue-h">
                <h2 id="queue-h" class="mb-2 text-sm font-bold uppercase text-muted">Kolejka i dostarczalność</h2>
                <ul class="space-y-1 text-sm text-ink">
                    <li><i class="fa-solid fa-layer-group text-muted" aria-hidden="true"></i> Jobów w kolejce <code>newsletter</code>: <strong>{{ $queue }}</strong>@if($failedJobs) · <span class="font-bold text-red-700">nieudanych: {{ $failedJobs }}</span>@endif</li>
                    <li><i class="fa-solid fa-triangle-exclamation text-muted" aria-hidden="true"></i> Odbicia (90 dni): <strong>{{ $bounce !== null ? $bounce . ' %' : '—' }}</strong> {{ $bounce !== null && $bounce > 2 ? '— powyżej normy (2 %)' : '' }}</li>
                    @if ($deliverability['domain'])
                    <li class="pt-2 text-xs text-muted">Domena nadawcy: <strong class="text-ink">{{ $deliverability['domain'] }}</strong></li>
                    <li>SPF: {!! $deliverability['spf'] ? '<span class="font-bold text-green-800">✔ jest</span>' : '<span class="font-bold text-red-700">✘ brak</span>' !!}</li>
                    <li>DKIM: {!! $deliverability['dkim'] ? '<span class="font-bold text-green-800">✔ selektor ' . e($deliverability['dkim']) . '</span>' : '<span class="font-bold text-amber-700">? nie znaleziono typowych selektorów</span>' !!}</li>
                    <li>DMARC: {!! $deliverability['dmarc'] ? '<span class="font-bold text-green-800">✔ ' . e(\Illuminate\Support\Str::limit($deliverability['dmarc'], 40)) . '</span>' : '<span class="font-bold text-red-700">✘ brak</span>' !!}</li>
                    @endif
                </ul>
                <p class="mt-3 text-xs text-muted">Worker: <code>php84 artisan queue:work database --queue=newsletter,default</code></p>
            </section>
        </div>
    </div>

    <section class="mt-6 rounded-lg border border-gray-200 bg-white p-4" aria-labelledby="growth-h">
        <h2 id="growth-h" class="mb-3 text-sm font-bold uppercase text-muted">Wzrost bazy (12 tygodni)</h2>
        @php $max = max(1, max(array_column($growth, 'value'))); @endphp
        <svg viewBox="0 0 600 160" class="w-full" role="img" aria-labelledby="growth-title growth-desc"><title id="growth-title">Liczba aktywnych subskrybentów tygodniowo</title><desc id="growth-desc">Od {{ $growth[0]['value'] }} do {{ end($growth)['value'] }} subskrybentów.</desc>
            @foreach ($growth as $i => $g)
                @php $h = round($g['value'] / $max * 120); $x = 10 + $i * 49; @endphp
                <rect x="{{ $x }}" y="{{ 130 - $h }}" width="36" height="{{ $h }}" fill="#1752BF" rx="3"><title>{{ $g['label'] }}: {{ $g['value'] }}</title></rect>
                <text x="{{ $x + 18 }}" y="148" font-size="10" text-anchor="middle" fill="#4A4A47">{{ $g['label'] }}</text>
            @endforeach
        </svg>
        <details class="mt-2 text-sm"><summary class="cursor-pointer font-bold text-brand-dark">Dane wykresu (tabela)</summary>
            <table class="mt-2 text-left"><thead><tr><th class="pr-4">Tydzień od</th><th>Subskrybenci</th></tr></thead><tbody>@foreach ($growth as $g)<tr><td class="pr-4">{{ $g['label'] }}</td><td>{{ $g['value'] }}</td></tr>@endforeach</tbody></table>
        </details>
    </section>
@endsection
