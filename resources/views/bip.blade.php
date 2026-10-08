@extends('layouts.site')

@section('title', 'Biuletyn Informacji Publicznej — ' . $siteSettings->site_name)
@section('meta_description', 'Biuletyn Informacji Publicznej ' . $siteSettings->site_name . ' — dokumenty publiczne, informacje o organizacji i rejestr zmian.')

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

    {{-- ── Nagłówek strony BIP ── --}}
    @if ($feer)
        <section>
            <div class="mx-auto max-w-5xl px-4 pb-4 pt-8">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <img src="{{ $bipLogo }}" alt="Logo Biuletynu Informacji Publicznej" class="h-12 w-auto flex-none object-contain">
                        <div>
                            <h1 class="text-2xl font-bold leading-tight text-ink md:text-3xl">Biuletyn Informacji Publicznej</h1>
                            <p class="mt-0.5 text-sm text-muted">{{ $siteSettings->site_name }}</p>
                        </div>
                    </div>
                    <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center gap-1.5 text-sm font-bold text-brand-dark underline underline-offset-4 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                        <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>Strona główna organizacji
                    </a>
                </div>
                <span class="mt-4 block h-1 w-14 bg-brand" aria-hidden="true"></span>
            </div>
        </section>
    @else
    <div class="border-b border-gray-200 bg-white">
        <div class="mx-auto max-w-5xl px-4 py-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <img src="{{ $bipLogo }}" alt="Logo Biuletynu Informacji Publicznej" class="h-14 w-auto flex-none object-contain">
                    <div>
                        <h1 class="text-xl font-extrabold leading-tight text-ink sm:text-2xl">Biuletyn Informacji Publicznej</h1>
                        <p class="mt-0.5 text-sm text-muted">{{ $siteSettings->site_name }}</p>
                    </div>
                </div>

                <a href="{{ route('home') }}"
                    class="inline-flex items-center gap-1.5 rounded-full border border-gray-300 bg-white px-3 py-1.5 text-xs font-bold text-muted transition hover:border-brand hover:text-brand-dark focus-visible:outline-2 focus-visible:outline-brand">
                    <i class="fa-solid fa-arrow-left text-[0.65rem]" aria-hidden="true"></i>
                    Strona główna organizacji
                </a>
            </div>
        </div>
    </div>

    @endif

    {{-- ── Układ dwukolumnowy: boczne menu + treść ── --}}
    <div class="mx-auto max-w-5xl px-4 py-8">
        <div class="grid gap-8 lg:grid-cols-[220px_1fr]">

            {{-- ── Boczne menu nawigacyjne ── --}}
            <aside class="{{ $feer ? '' : 'lg:border-r lg:border-gray-100 lg:pr-6' }}">
                @include('bip._sidebar')
            </aside>

            {{-- ── Treść główna ── --}}
            <main>
                {{-- Wyszukiwarka BIP (tryb wbudowany) --}}
                @unless ($isExternal)
                    <form method="GET" action="{{ route('bip') }}" role="search" aria-label="Szukaj w BIP" class="mb-8 flex flex-wrap items-end gap-2">
                        <div class="min-w-0 flex-1" style="min-width: 14rem">
                            <label for="bip-q" class="mb-1 block text-sm font-bold text-ink">Szukaj w BIP</label>
                            <input type="search" id="bip-q" name="q" value="{{ $q }}" placeholder="np. statut, sprawozdanie 2025"
                                class="min-h-11 w-full rounded-md border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        </div>
                        <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-md bg-brand px-5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>Szukaj</button>
                        @if ($q !== '')<a href="{{ route('bip') }}" class="inline-flex min-h-11 items-center px-2 text-sm font-bold text-brand-dark underline hover:text-ink">Wyczyść</a>@endif
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
                <div class="prose max-w-none text-ink [&_h2]:text-ink {{ $feer ? '[&_h3]:text-ink' : '[&_h3]:text-brand-dark' }} [&_li::marker]:font-bold [&_li::marker]:text-brand-dark">
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
                @elseif ($documents->isEmpty() && $q !== '')
                    <p class="mt-4 text-ink">Nie znaleziono dokumentów pasujących do wyszukiwanej frazy. Spróbuj innych słów lub <a href="{{ route('bip.instructions') }}" class="font-bold text-brand-dark underline">zajrzyj do instrukcji korzystania z BIP</a>.</p>
                @elseif ($documents->isNotEmpty())
                    {{-- Tryb wbudowany: lista dokumentów --}}
                    <div class="mt-10">
                        <h2 class="mb-1 text-xl font-extrabold text-ink">{{ $q !== '' ? 'Znalezione dokumenty' : 'Dokumenty publiczne' }}</h2>
                        <p class="mb-8 text-sm text-muted">
                            Kliknij tytuł dokumentu, aby zobaczyć pełną treść lub pobrać pliki.
                        </p>

                        @foreach (\App\Models\BipDocument::CATEGORIES as $catKey => $catLabel)
                            @if ($documents->has($catKey))
                                <div id="kategoria-{{ $catKey }}" class="mb-10 scroll-mt-6">
                                    @if ($feer)
                                        <h3 class="mb-2 text-xs font-bold uppercase tracking-widest text-muted">{{ $catLabel }}</h3>
                                    @else
                                    <h3 class="mb-3 flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-brand-dark">
                                        <span class="h-px flex-1 bg-brand/20" aria-hidden="true"></span>
                                        {{ $catLabel }}
                                        <span class="h-px flex-1 bg-brand/20" aria-hidden="true"></span>
                                    </h3>
                                    @endif

                                    <ul class="space-y-2" role="list">
                                        @foreach ($documents[$catKey] as $doc)
                                            <li class="group transition {{ $feer ? 'rounded-md py-3 pl-4 pr-3 hover:bg-gray-50' : 'rounded-lg border border-gray-200 bg-white px-4 py-3 hover:border-brand/30 hover:shadow-sm' }}" @if ($feer) style="border-left: 4px solid var(--color-brand)" @endif>
                                                <div class="flex flex-wrap items-start justify-between gap-2">
                                                    <div class="min-w-0 flex-1">
                                                        <a href="{{ route('bip.document', $doc->slug) }}"
                                                            class="font-semibold text-ink group-hover:text-brand-dark hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                                            {{ $doc->title }}
                                                        </a>
                                                        @if ($doc->summary)
                                                            <p class="mt-0.5 text-sm text-muted leading-snug">{{ $doc->summary }}</p>
                                                        @endif
                                                    </div>
                                                    @php $files = $doc->getMedia('files'); @endphp
                                                    @if ($files->isNotEmpty())
                                                        <span class="flex-none px-2.5 py-0.5 text-xs font-bold {{ $feer ? 'rounded bg-gray-100 text-ink' : 'rounded-full bg-brand/10 text-brand-dark' }}">
                                                            <i class="fa-solid fa-paperclip mr-1" aria-hidden="true"></i>
                                                            {{ $files->count() }} {{ trans_choice('plik|pliki|plików', $files->count()) }}
                                                        </span>
                                                    @endif
                                                </div>

                                                {{-- Metadane — wymóg ustawowy --}}
                                                <dl class="mt-1.5 flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-muted">
                                                    <div class="flex items-center gap-1">
                                                        <dt class="font-semibold">Dodano:</dt>
                                                        <dd>
                                                            <time datetime="{{ $doc->created_at->toIso8601String() }}">
                                                                {{ $doc->created_at->locale('pl')->isoFormat('D MMM YYYY') }}
                                                            </time>
                                                        </dd>
                                                    </div>
                                                    @if ($doc->creator)
                                                        <div class="flex items-center gap-1">
                                                            <dt class="font-semibold">Przez:</dt>
                                                            <dd>{{ $doc->creator->name }}</dd>
                                                        </div>
                                                    @endif
                                                    @if ($doc->updated_at->ne($doc->created_at))
                                                        <div class="flex items-center gap-1">
                                                            <dt class="font-semibold">Zmieniono:</dt>
                                                            <dd>
                                                                <time datetime="{{ $doc->updated_at->toIso8601String() }}">
                                                                    {{ $doc->updated_at->locale('pl')->isoFormat('D MMM YYYY') }}
                                                                </time>
                                                            </dd>
                                                        </div>
                                                    @endif
                                                </dl>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                {{-- ── Ostatnie zmiany w BIP (tryb wbudowany) ── --}}
                @if ($recentChanges->isNotEmpty())
                    <section class="mt-12" aria-labelledby="recent-changes-heading">
                        <h2 id="recent-changes-heading" class="mb-4 flex items-center gap-2 text-base font-bold text-ink">
                            <i class="fa-solid fa-clock-rotate-left text-brand-dark text-sm" aria-hidden="true"></i>
                            Ostatnie zmiany w BIP
                        </h2>
                        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                            <table class="w-full text-left text-sm">
                                <caption class="sr-only">Ostatnie zmiany dokumentów BIP</caption>
                                <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
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
                                                        'created' => ['bg-green-100 text-green-700', 'fa-plus', 'Dodanie'],
                                                        'updated' => ['bg-blue-100 text-blue-700', 'fa-pen', 'Edycja'],
                                                        'deleted' => ['bg-red-100 text-red-700', 'fa-trash', 'Usunięcie'],
                                                        default   => ['bg-gray-100 text-gray-600', 'fa-circle', $entry->eventLabel()],
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold {{ $badge[0] }}">
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
    </div>
@endsection
