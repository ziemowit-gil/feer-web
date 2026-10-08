@extends('layouts.site')

@section('title', 'Biuletyn Informacji Publicznej — ' . $siteSettings->site_name)
@section('meta_description', 'Biuletyn Informacji Publicznej ' . $siteSettings->siteNameGenitive() . ' — dokumenty publiczne, informacje o organizacji i rejestr zmian.')

@section('minimal_header', '1')

@section('content')
    @php
        $feer = ($siteSettings->site_template ?? 'default') === 'feer';
        $bipLogo = $siteSettings->bipLogoUrl() ?: asset('img/bip-logo.svg');

        $bipDefault = <<<'HTML'
<h2>Co znajdziesz w Biuletynie Informacji Publicznej Fundacji FEER?</h2>
<p>Fundacja FEER stawia na pełną transparentność, jawność działania oraz budowanie zaufania. Choć przepisy prawa nie nakładają na organizacje pozarządowe sztywnego obowiązku prowadzenia Biuletynu Informacji Publicznej, wierzymy, że otwartość wobec naszych darczyńców, partnerów, uczestników projektów oraz instytucji publicznych to fundament nowoczesnego i odpowiedzialnego trzeciego sektora.</p>
<p>W tym miejscu udostępniamy kluczowe dokumenty, informacje o podejmowanych działaniach, strukturze organizacyjnej oraz gospodarowaniu środkami.</p>
<h3>Co publikujemy w naszym BIP?</h3>
<ul>
<li><strong>Aktualne dokumenty rejestrowe i prawne:</strong> Statut fundacji, wypisy z KRS oraz regulaminy wewnętrzne.</li>
<li><strong>Sprawozdawczość:</strong> Roczne sprawozdania merytoryczne i finansowe z działalności naszej organizacji.</li>
<li><strong>Informacje o realizowanych projektach:</strong> Transparentne podsumowania zadań publicznych, grantów oraz inicjatyw edukacyjnych i społecznych.</li>
<li><strong>Oświadczenia i komunikaty:</strong> Oficjalne stanowiska zarządu oraz ogłoszenia dotyczące bieżącej działalności fundacji.</li>
</ul>
<p>Masz pytanie dotyczące naszej działalności lub poszukujesz konkretnej informacji publicznej? <a href="/kontakt">Skontaktuj się z nami bezpośrednio</a> – chętnie udzielimy wszelkich wyjaśnień.</p>
HTML;
    @endphp

    @include('bip._head')

    {{-- ── Układ dwukolumnowy: boczne menu + treść ── --}}
    <div class="bip-wrap">

            {{-- ── Boczne menu nawigacyjne ── --}}
            <aside class="{{ $feer ? '' : 'lg:border-r lg:border-gray-100 lg:pr-6' }}">
                @include('bip._sidebar')
            </aside>

            {{-- ── Treść główna ── --}}
            <main>
                {{-- Wyszukiwarka BIP (tryb wbudowany) --}}
                @unless ($isExternal)
                    <style>
                        .bip-search { margin-bottom: 1.5rem; padding: 1.5rem; border-radius: .5rem; background: var(--color-brand-light); }
                        .bip-search-label { display: block; margin-bottom: .6rem; font-size: 1.25rem; font-weight: 800; color: #1d1d1a; }
                        .bip-search-row { display: flex; flex-wrap: wrap; gap: .75rem; }
                        .bip-search-input { flex: 1 1 16rem; min-width: 0; min-height: 3.5rem; padding: .5rem 1rem; border: 2px solid #1d1d1a; border-radius: .5rem; background: #fff; font-size: 1.125rem; color: #1d1d1a; }
                        .bip-search-input:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 2px; }
                        .bip-search-btn { display: inline-flex; min-height: 3.5rem; align-items: center; justify-content: center; gap: .6rem; padding: .5rem 2rem; border: 2px solid var(--color-brand-dark); border-radius: .5rem; background: var(--color-brand-dark); color: #fff; font-size: 1.125rem; font-weight: 800; cursor: pointer; }
                        .bip-search-btn:hover { background: #1d1d1a; border-color: #1d1d1a; }
                        .bip-search-btn:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 2px; }
                        .bip-search-hint { margin: .6rem 0 0; font-size: .9rem; color: #374151; }
                    </style>
                    <form method="GET" action="{{ route('bip') }}" role="search" aria-label="Szukaj w BIP" class="bip-search">
                        <label for="bip-q" class="bip-search-label">Szukaj w BIP</label>
                        <div class="bip-search-row">
                            <input type="search" id="bip-q" name="q" value="{{ $q }}" placeholder="np. statut, sprawozdanie 2025, KRS" class="bip-search-input" autocomplete="off">
                            <button type="submit" class="bip-search-btn"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>Szukaj</button>
                        </div>
                        <p class="bip-search-hint">Wyszukiwarka sprawdza tytuły, streszczenia i treść dokumentów.@if ($q !== '') <a href="{{ route('bip') }}" class="font-bold text-brand-dark underline hover:text-ink">Wyczyść wyszukiwanie</a>@endif</p>
                    </form>
                    @if ($q !== '')
                        <p class="mb-6 text-sm text-ink" role="status">
                            @php $found = $documents->flatten()->count(); @endphp
                            Wyniki wyszukiwania „{{ $q }}”: {{ $found }} {{ trans_choice('dokument|dokumenty|dokumentów', $found) }}.
                        </p>
                    @endif
                    @if ($lastUpdate && $q === '')
                        <p class="-mt-4 mb-6 text-xs text-muted">Ostatnia aktualizacja BIP: <time datetime="{{ $lastUpdate->toIso8601String() }}">{{ $lastUpdate->locale('pl')->isoFormat('D MMMM YYYY') }}</time></p>
                    @endif
                @endunless

                @if ($q === '')
                <div class="bip-intro prose text-ink [&_h2]:text-ink {{ $feer ? '[&_h3]:text-ink' : '[&_h3]:text-brand-dark' }} [&_li::marker]:font-bold [&_li::marker]:text-brand-dark">
                    {!! $siteSettings->bip_intro ?: $bipDefault !!}
                </div>
                @endif

                @if ($isExternal)
                    {{-- Tryb zewnętrzny: przycisk do zewnętrznego BIP --}}
                    <div class="mt-8">
                        @if ($siteSettings->bip_url)
                            <a href="{{ $siteSettings->bip_url }}" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 rounded-full bg-brand px-7 py-3 text-base font-bold text-white transition hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                Przejdź do pełnego BIP <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                            </a>
                        @else
                            @auth
                                <p class="text-sm text-muted">Adres zewnętrznego BIP nie został jeszcze ustawiony (Ustawienia → Media i BIP).</p>
                            @endauth
                        @endif
                    </div>
                @elseif ($documents->isEmpty() && $reports->isEmpty() && $q !== '')
                    <p class="mt-4 text-ink">Nie znaleziono dokumentów pasujących do wyszukiwanej frazy. Spróbuj innych słów lub <a href="{{ route('bip.instructions') }}" class="font-bold text-brand-dark underline">zajrzyj do instrukcji korzystania z BIP</a>.</p>
                @elseif ($documents->isNotEmpty())
                    {{-- Tryb wbudowany: lista dokumentów --}}
                    <div class="mt-10">
                        <h2 class="bip-h2">{{ $q !== '' ? 'Znalezione dokumenty' : 'Dokumenty publiczne' }}</h2>
                        <p class="mb-2 text-sm text-muted">Kliknij tytuł dokumentu, aby zobaczyć pełną treść lub pobrać pliki.</p>

                        @foreach (\App\Models\BipDocument::CATEGORIES as $catKey => $catLabel)
                            @if ($documents->has($catKey))
                                <section id="kategoria-{{ $catKey }}" class="scroll-mt-6" aria-labelledby="bip-cat-{{ $catKey }}">
                                    <h3 id="bip-cat-{{ $catKey }}" class="bip-cat">{{ $catLabel }}</h3>
                                    <ul class="bip-docs" role="list">
                                        @foreach ($documents[$catKey] as $doc)
                                            @php $files = $doc->getMedia('files'); @endphp
                                            <li class="bip-doc">
                                                <a href="{{ route('bip.document', $doc->slug) }}" class="bip-doc-t">{{ $doc->title }}</a>
                                                @if ($files->isNotEmpty())
                                                    <span class="bip-doc-files"><i class="fa-solid fa-paperclip" aria-hidden="true"></i>{{ $files->count() }} {{ trans_choice('plik|pliki|plików', $files->count()) }}</span>
                                                @endif
                                                @if ($doc->summary)<p class="bip-doc-s">{{ $doc->summary }}</p>@endif
                                                <dl class="bip-doc-meta">
                                                    <div><dt>Udostępniono:</dt><dd><time datetime="{{ ($doc->published_at ?? $doc->created_at)->toIso8601String() }}">{{ ($doc->published_at ?? $doc->created_at)->locale('pl')->isoFormat('D MMM YYYY') }}</time></dd></div>
                                                    @if ($doc->creator)<div><dt>Wytworzył/-a:</dt><dd>{{ $doc->creator->name }}</dd></div>@endif
                                                    @if ($doc->updated_at->ne($doc->created_at))<div><dt>Zmieniono:</dt><dd><time datetime="{{ $doc->updated_at->toIso8601String() }}">{{ $doc->updated_at->locale('pl')->isoFormat('D MMM YYYY') }}</time></dd></div>@endif
                                                </dl>
                                            </li>
                                        @endforeach
                                    </ul>
                                </section>
                            @endif
                        @endforeach
                    </div>
                @endif

                {{-- ── Sprawozdania roczne z modułu „Sprawozdania" (opcja w ustawieniach BIP) ── --}}
                @if (! $isExternal && $reports->isNotEmpty())
                    <section id="sprawozdania" class="mt-12 scroll-mt-6" aria-labelledby="bip-reports-heading">
                        <h2 id="bip-reports-heading" class="bip-h2">Sprawozdania roczne</h2>
                        <p class="mb-4 text-sm text-muted">Sprawozdania merytoryczne i finansowe Fundacji. <a href="{{ route('reports.index') }}" class="font-bold text-brand-dark underline hover:text-ink">Zobacz pełną stronę sprawozdań →</a></p>
                        <div class="overflow-x-auto">
                            <table class="bip-tbl">
                                <caption class="sr-only">Sprawozdania roczne według lat</caption>
                                <thead>
                                    <tr><th scope="col" class="px-4 py-2.5">Rok</th><th scope="col" class="px-4 py-2.5">Sprawozdanie merytoryczne</th><th scope="col" class="px-4 py-2.5">Sprawozdanie finansowe</th></tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($reports as $report)
                                        <tr>
                                            <th scope="row" class="px-4 py-3 font-bold text-ink">{{ $report->year }}</th>
                                            @foreach (\App\Models\AnnualReport::TYPES as $typeKey => $typeLabel)
                                                <td class="px-4 py-3">
                                                    @if ($report->fileUrlFor($typeKey))
                                                        <a href="{{ $report->fileUrlFor($typeKey) }}" download class="inline-flex min-h-9 items-center gap-1.5 font-bold text-brand-dark underline underline-offset-2 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                                            <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>Pobierz<span class="sr-only"> {{ mb_strtolower($typeLabel) }} {{ $report->year }} (PDF)</span>
                                                        </a>
                                                    @else
                                                        <span class="text-muted">{{ $report->messageFor($typeKey) ?? '—' }}</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                {{-- ── Ostatnie zmiany w BIP (tryb wbudowany) ── --}}
                @if ($recentChanges->isNotEmpty())
                    <section class="mt-12" aria-labelledby="recent-changes-heading">
                        <h2 id="recent-changes-heading" class="bip-h2">Ostatnie zmiany w BIP</h2>
                        <div class="overflow-x-auto">
                            <table class="bip-tbl">
                                <caption class="sr-only">Ostatnie zmiany dokumentów BIP</caption>
                                <thead>
                                    <tr>
                                        <th scope="col" class="px-4 py-2.5">Data</th>
                                        <th scope="col" class="px-4 py-2.5">Operacja</th>
                                        <th scope="col" class="px-4 py-2.5">Dokument</th>
                                        <th scope="col" class="px-4 py-2.5">Autor</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @php
                                        $docSlugs = \App\Models\BipDocument::withTrashed()
                                            ->whereIn('id', $recentChanges->pluck('subject_id')->unique())
                                            ->pluck('slug', 'id');
                                    @endphp
                                    @foreach ($recentChanges as $entry)
                                        <tr class="hover:bg-gray-50">
                                            <td class="whitespace-nowrap px-4 py-2.5 text-muted">
                                                <time datetime="{{ $entry->created_at->toIso8601String() }}">
                                                    {{ $entry->created_at->locale('pl')->isoFormat('D MMM YYYY') }}
                                                </time>
                                            </td>
                                            <td class="px-4 py-2.5">
                                                @php
                                                    $badge = match($entry->event) {
                                                        'created' => ['is-created', 'fa-plus', 'Dodanie'],
                                                        'updated' => ['is-updated', 'fa-pen', 'Edycja'],
                                                        'deleted' => ['is-deleted', 'fa-trash', 'Usunięcie'],
                                                        default   => ['is-other', 'fa-circle', $entry->eventLabel()],
                                                    };
                                                @endphp
                                                <span class="bip-op {{ $badge[0] }}">
                                                    <i class="fa-solid {{ $badge[1] }} text-[0.6rem]" aria-hidden="true"></i>
                                                    {{ $badge[2] }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2.5 font-medium">
                                                @if ($docSlugs[$entry->subject_id] ?? null)
                                                    <a href="{{ route('bip.document', $docSlugs[$entry->subject_id]) }}"
                                                        class="text-brand-dark hover:text-brand-dark hover:underline focus-visible:outline-2 focus-visible:outline-brand">
                                                        {{ $entry->subject_label }}
                                                    </a>
                                                @else
                                                    <span class="text-muted">{{ $entry->subject_label }}
                                                        @if ($entry->event === 'deleted')
                                                            <span class="text-xs">(usunięty)</span>
                                                        @endif
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2.5 text-muted">
                                                {{ $entry->user_name ?: '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 text-right">
                            <a href="{{ route('bip.changelog') }}"
                                class="text-xs font-bold text-brand-dark hover:text-brand-dark hover:underline focus-visible:outline-2 focus-visible:outline-brand">
                                Pełny rejestr zmian →
                            </a>
                        </div>
                    </section>
                @endif
            </main>

    </div>
@endsection
