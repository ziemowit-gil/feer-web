@extends('admin.layout')
@section('title', 'Newsletter — pulpit')
@section('content')
    @include('newsletter::admin.partials.flash')
    @include('newsletter::admin.partials.queue-alert')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Kampanie e-mail, push i SMS · subskrybenci z double opt-in</p>
        <div class="flex gap-2">
            @can('newsletter.campaigns')<a href="{{ route('admin.newsletter.kampanie.create') }}" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink"><i class="fa-solid fa-plus" aria-hidden="true"></i> Nowa kampania</a>@endcan
            @can('newsletter.templates')<a href="{{ route('admin.newsletter.mosaico') }}" class="rounded-md px-4 py-2 text-sm font-bold text-gray-700 bg-white hover:bg-brand-light"><i class="fa-solid fa-pen-ruler" aria-hidden="true"></i> Edytor Mosaico</a>@endcan
            <a href="{{ route('newsletter.show') }}" target="_blank" rel="noopener" class="rounded-md px-4 py-2 text-sm font-bold text-gray-700 bg-white hover:bg-brand-light">Strona /newsletter <span class="sr-only">(nowe okno)</span></a>
        </div>
    </div>

    <nav aria-label="Sekcje newslettera" class="mb-6">
        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            @foreach ([
                ['Kampanie', 'admin.newsletter.kampanie.index', 'fa-paper-plane', 'Wysyłki e-mail, push i SMS', 'newsletter.view'],
                ['Subskrybenci', 'admin.newsletter.subskrybenci.index', 'fa-users', 'Lista osób i ich zgody', 'newsletter.subscribers'],
                ['Listy', 'admin.newsletter.listy.index', 'fa-list', 'Grupy odbiorców do wysyłek', 'newsletter.subscribers'],
                ['Segmenty', 'admin.newsletter.segmenty.index', 'fa-filter', 'Filtrowane grupy subskrybentów', 'newsletter.subscribers'],
                ['Tematy', 'admin.newsletter.tematy.index', 'fa-tags', 'Tematy do wyboru przy zapisie', 'newsletter.subscribers'],
                ['Szablony', 'admin.newsletter.szablony.index', 'fa-file-code', 'Gotowe układy wiadomości', 'newsletter.templates'],
                ['Edytor Mosaico', 'admin.newsletter.mosaico', 'fa-pen-ruler', 'Projektowanie wiadomości', 'newsletter.templates'],
                ['Formularze zapisu', 'admin.newsletter.formularze.index', 'fa-clipboard-list', 'Formularze na stronach i ich wygląd', 'newsletter.subscribers'],
                ['Ustawienia', 'admin.newsletter.ustawienia.edit', 'fa-gear', 'Nadawca, zgody i integracje', 'newsletter.settings'],
            ] as [$title, $route, $icon, $desc, $ability])
                @continue(! auth()->user()->can($ability))
                <li>
                    <a href="{{ route($route) }}" class="flex h-full items-start gap-3 rounded-md bg-white p-4 hover:bg-gray-50 focus-visible:outline focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-brand-dark">
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

    @if (! empty($imageSizes))
    <section class="mb-6 rounded-md bg-white p-5" aria-labelledby="nl-img-sizes">
        <h2 id="nl-img-sizes" class="text-base font-bold text-ink"><i class="fa-solid fa-ruler-combined mr-1 text-brand-dark" aria-hidden="true"></i> Wymiary obrazków w blokach Mosaico</h2>
        <p class="mt-1 text-sm text-muted">Wgrywaj obrazy co najmniej w tym rozmiarze — najlepiej dwa razy większe (np. 1068 px zamiast 534 px), żeby były ostre na telefonach. Przy stałej wysokości obraz o innych proporcjach zostanie przycięty. Te same wymiary edytor pokazuje na każdym obrazku (przycisk „Wymiary obrazków”).</p>
        @foreach ($imageSizes as $tpl => $blocks)
            <details class="mt-4" @if ($loop->first) open @endif>
                <summary class="cursor-pointer text-sm font-bold text-ink">Szablon {{ strtoupper($tpl) }}</summary>
                <div class="mt-2 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <caption class="sr-only">Wymagane wymiary obrazków w szablonie {{ strtoupper($tpl) }}</caption>
                        <thead class="bg-gray-100 text-xs font-bold uppercase text-muted"><tr><th scope="col" class="px-3 py-2">Blok</th><th scope="col" class="px-3 py-2">Obraz</th><th scope="col" class="px-3 py-2">Wymiar (szer. × wys.)</th></tr></thead>
                        <tbody>
                        @foreach ($blocks as $block)
                            @foreach ($block['rows'] as $row)
                                @php
                                    $w = implode(' lub ', $row['widths']);
                                    $sizes = collect($row['heights'])->map(fn ($h) => "{$w} × {$h} px (stała wysokość)")->all();
                                    if ($row['freeHeight']) { $sizes[] = "{$w} px szer. · wysokość dowolna"; }
                                @endphp
                                <tr class="border-t border-gray-100">
                                    @if ($loop->first)<th scope="row" rowspan="{{ count($block['rows']) }}" class="px-3 py-2 align-top font-bold">{{ $block['label'] }}</th>@endif
                                    <td class="px-3 py-2 align-top">{{ $row['fields'] ? 'każdy: ' . implode(', ', $row['fields']) : 'obraz' }}</td>
                                    <td class="px-3 py-2 align-top">{!! implode('<br>', array_map('e', $sizes)) !!}
                                        @if ($row['options'])<span class="block text-xs text-muted">Opcja „Rozmiar obrazu” w bloku zmienia szerokość: {{ implode(' / ', $row['options']) }} px.</span>@endif
                                        @if (count($row['widths']) > 1)<span class="block text-xs text-muted">Węższa wersja, gdy blok ma marginesy boczne.</span>@endif
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @endforeach
    </section>
    @endif

    @php $canSubscribers = auth()->user()->can('newsletter.subscribers'); $canReports = auth()->user()->can('newsletter.reports'); @endphp
    <dl class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5" role="group" aria-label="Kluczowe wskaźniki">
        @foreach ([
            ['Aktywni subskrybenci', number_format($active, 0, ',', ' '), 'fa-users', $canSubscribers ? route('admin.newsletter.subskrybenci.index', ['status' => 'confirmed']) : null],
            ['Nowi (30 dni)', '+' . number_format($new30, 0, ',', ' ') . ($lost30 ? ' / −' . $lost30 : ''), 'fa-user-plus', $canSubscribers ? route('admin.newsletter.subskrybenci.index', ['since' => now()->subDays(30)->toDateString()]) : null],
            ['Oczekują na potwierdzenie', number_format($pending, 0, ',', ' '), 'fa-clock', $canSubscribers ? route('admin.newsletter.subskrybenci.index', ['status' => 'pending']) : null],
            ['Śr. otwarcia (90 dni)', $avgOpen !== null ? $avgOpen . ' %' : '—', 'fa-envelope-open', null],
            ['Śr. kliknięcia (90 dni)', $avgClick !== null ? $avgClick . ' %' : '—', 'fa-arrow-pointer', null],
        ] as [$label, $value, $icon, $href])
            <div class="p-4 rounded-md bg-white">
                <dt class="flex items-center gap-2 text-xs font-bold uppercase text-muted"><i class="fa-solid {{ $icon }}" aria-hidden="true"></i> {{ $label }}</dt>
                <dd class="mt-1 text-2xl font-extrabold text-ink">@if($href)<a href="{{ $href }}" class="hover:underline">{{ $value }}</a>@else{{ $value }}@endif</dd>
            </div>
        @endforeach
    </dl>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="lg:col-span-2 rounded-md bg-white" aria-labelledby="recent-h">
            <h2 id="recent-h" class="border-b border-gray-200 px-4 py-3 text-sm font-bold uppercase text-muted">Ostatnie kampanie</h2>
            @if ($recent->isEmpty())
                <p class="px-4 py-6 text-sm text-muted">Jeszcze nic nie wysłano.@can('newsletter.campaigns') <a href="{{ route('admin.newsletter.kampanie.create') }}" class="font-bold text-brand-dark underline">Utwórz pierwszą kampanię</a>.@endcan</p>
            @else
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-100 text-xs font-bold uppercase text-muted"><tr><th class="px-4 py-2">Nazwa</th><th class="px-4 py-2">Wysłano</th><th class="px-4 py-2 text-right">Dost.</th><th class="px-4 py-2 text-right">Otw.</th><th class="px-4 py-2 text-right">Klik.</th><th class="px-4 py-2">Stan</th></tr></thead>
                <tbody>
                @foreach ($recent as $c)
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-2"><a href="{{ $canReports ? route('admin.newsletter.kampanie.report', $c) : route('admin.newsletter.kampanie.show', $c) }}" class="font-bold text-brand-dark hover:underline">{{ $c->title }}</a></td>
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
            <section class="rounded-md bg-white" aria-labelledby="sched-h">
                <h2 id="sched-h" class="border-b border-gray-200 px-4 py-3 text-sm font-bold uppercase text-muted">Zaplanowane</h2>
                @forelse ($scheduled as $c)
                    <p class="border-t border-gray-100 px-4 py-2 text-sm first:border-0"><span class="font-bold text-ink">{{ $c->scheduled_at?->format('d.m H:i') }}</span> · <a href="{{ route('admin.newsletter.kampanie.show', $c) }}" class="text-brand-dark hover:underline">{{ $c->title }}</a></p>
                @empty
                    <p class="px-4 py-4 text-sm text-muted">Brak zaplanowanych kampanii.</p>
                @endforelse
            </section>
            <section class="p-4 rounded-md bg-white" aria-labelledby="queue-h">
                <h2 id="queue-h" class="mb-2 text-sm font-bold uppercase text-muted">Kolejka i dostarczalność</h2>
                <ul class="space-y-1 text-sm text-ink">
                    <li><i class="fa-solid fa-layer-group text-muted" aria-hidden="true"></i> W kolejce: <strong>{{ $queueHealth['pending'] }}</strong>@foreach ($queueHealth['queues'] as $qName => $q) <span class="text-muted">({{ $qName }}: {{ $q['pending'] }})</span>@endforeach</li>
                    <li><i class="fa-solid fa-hourglass-half text-muted" aria-hidden="true"></i> Najstarsze gotowe zadanie: <strong>{{ $queueHealth['oldest']?->diffForHumans() ?? '—' }}</strong></li>
                    <li><i class="fa-solid fa-heart-pulse text-muted" aria-hidden="true"></i> Worker ostatnio pracował: <strong>{{ $queueHealth['worker']?->diffForHumans() ?? 'brak danych' }}</strong></li>
                    <li><i class="fa-solid fa-circle-xmark text-muted" aria-hidden="true"></i> Nieudane zadania: <strong class="{{ $queueHealth['failed'] ? 'text-red-800' : '' }}">{{ $queueHealth['failed'] }}</strong>@if ($queueHealth['failed_recent']) <span class="font-bold text-red-800">(w ostatniej dobie: {{ $queueHealth['failed_recent'] }})</span>@endif</li>
                    <li><i class="fa-solid fa-triangle-exclamation text-muted" aria-hidden="true"></i> Odbicia (90 dni): <strong>{{ $bounce !== null ? $bounce . ' %' : '—' }}</strong> {{ $bounce !== null && $bounce > 2 ? '— powyżej normy (2 %)' : '' }}</li>
                    @if ($deliverability['domain'])
                    <li class="pt-2 text-xs text-muted">Domena nadawcy: <strong class="text-ink">{{ $deliverability['domain'] }}</strong></li>
                    <li>SPF: {!! $deliverability['spf'] ? '<span class="font-bold text-green-800">✔ jest</span>' : '<span class="font-bold text-red-700">✘ brak</span>' !!}</li>
                    <li>DKIM: {!! $deliverability['dkim'] ? '<span class="font-bold text-green-800">✔ selektor ' . e($deliverability['dkim']) . '</span>' : '<span class="font-bold text-amber-700">? nie znaleziono typowych selektorów</span>' !!}</li>
                    <li>DMARC: {!! $deliverability['dmarc'] ? '<span class="font-bold text-green-800">✔ ' . e(\Illuminate\Support\Str::limit($deliverability['dmarc'], 40)) . '</span>' : '<span class="font-bold text-red-700">✘ brak</span>' !!}</li>
                    @endif
                </ul>
                <p class="mt-3 text-xs text-muted">Worker startuje co minutę z harmonogramu (<code>schedule:run</code>) i obsługuje kolejki <code>newsletter</code> i <code>default</code>.</p>
            </section>
        </div>
    </div>

    <section class="mt-6 p-4 rounded-md bg-white" aria-labelledby="growth-h">
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
