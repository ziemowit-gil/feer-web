@extends('layouts.site')

@section('title', 'Materiały edukacyjne — ' . $siteSettings->site_name)
@section('meta_description', 'Materiały edukacyjne ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Materiały edukacyjne', 'url' => null],
    ]])
@endsection

{{--
    Materiały edukacyjne w układzie FEER: jasny nagłówek, po lewej nawigacja po grupach docelowych, po prawej materiały
    jako równe karty (partials/material-card-feer) — cała karta to jeden link. Nad listą wyszukiwarka i filtr typu (ulepszenie
    progresywne, wynik ogłaszany w role="status"), komunikat z panelu jako jedna linia. Kontrast: ink/muted na bieli, linki brand-dark.
--}}
@section('content')
    @php
        $groups = $materials->groupBy(fn ($m) => $m->target_group ?: '');
        $hasNamedGroups = $groups->keys()->filter(fn ($k) => $k !== '')->isNotEmpty();
        $groupLabel = fn ($key) => $key === ''
            ? ($hasNamedGroups ? 'Pozostałe materiały' : 'Wszystkie materiały')
            : (\App\Models\EducationalMaterial::TARGET_GROUPS[$key] ?? $key);
        $plural = fn (int $n) => $n === 1 ? 'materiał'
            : (in_array($n % 10, [2, 3, 4], true) && ! in_array($n % 100, [12, 13, 14], true) ? 'materiały' : 'materiałów');
        $presentTypes = $materials->pluck('type')->unique()->values();
        $typeFilters = [
            'video' => 'Nagrania',
            'pdf' => 'PDF',
            'scenariusz' => 'Scenariusze',
        ];
        $newsletterCta = $siteSettings->isModuleEnabled('newsletter') && \Illuminate\Support\Facades\Route::has('newsletter.show');
        $actionPrimary = 'inline-flex min-h-11 items-center gap-2 rounded-md bg-brand px-4 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2';
        $actionGhost = 'inline-flex min-h-11 items-center gap-2 rounded-md bg-gray-100 px-4 text-sm font-bold text-ink transition hover:bg-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2';
    @endphp

    <section class="bg-gray-50">
        <div class="mx-auto max-w-6xl px-4 py-12 md:py-16">
            <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-5xl">Materiały edukacyjne</h1>
            @if ($siteSettings->materials_intro)
                <div class="prose mt-4 max-w-2xl text-lg text-ink">{!! $siteSettings->materials_intro !!}</div>
            @endif
            @if ($siteSettings->materials_notice)
                {{-- Komunikat z panelu jako jedna spokojna linia, bez szarego bloku. --}}
                <div class="mat-notice">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    <div>{!! $siteSettings->materials_notice !!}</div>
                </div>
            @endif
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-12">
        @if ($materials->isEmpty())
            <p class="text-muted">Brak materiałów do wyświetlenia.</p>
        @else
            <div class="grid gap-10 lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-14">
                @if ($groups->count() > 1)
                    <nav aria-label="Grupy materiałów" class="lg:sticky lg:top-6 lg:self-start">
                        <p class="mb-3 text-xs font-bold uppercase tracking-widest text-muted">Dla kogo</p>
                        <ul class="feer-pills-row flex gap-2 overflow-x-auto pb-2 lg:flex-col lg:gap-1 lg:overflow-visible lg:pb-0" role="list">
                            @foreach ($groups as $gKey => $items)
                                <li class="shrink-0">
                                    <a href="#grupa-{{ $loop->index }}"
                                       class="flex min-h-11 items-center justify-between gap-3 rounded-md bg-gray-50 px-4 py-2 text-sm font-bold text-ink transition hover:bg-gray-100 hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand lg:bg-transparent lg:px-3">
                                        <span>{{ $groupLabel($gKey) }}</span>
                                        <span class="text-muted">{{ $items->count() }}</span>
                                    </a>
                                </li>
                            @endforeach
                            <li class="shrink-0"><a href="#zapis" class="flex min-h-11 items-center rounded-md px-4 py-2 text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand lg:px-3">{{ $newsletterCta ? 'Newsletter' : 'Powiadomienia' }} →</a></li>
                        </ul>
                    </nav>
                @endif

                <div class="min-w-0 {{ $groups->count() > 1 ? '' : 'lg:col-span-2' }}">
                    {{-- Wyszukiwarka i filtr typu — ulepszenie progresywne: bez JavaScriptu widać wszystkie materiały. --}}
                    <div class="mat-tools" data-material-tools hidden>
                        <form role="search" class="mat-search" onsubmit="return false">
                            <label for="material-search" class="mat-label">Szukaj materiału</label>
                            <input type="search" id="material-search" data-material-search autocomplete="off" class="mat-input">
                        </form>
                        @if ($presentTypes->count() > 1)
                            <div class="mat-filter" role="group" aria-label="Typ materiału">
                                <button type="button" class="mat-chip" data-material-filter="" aria-pressed="true">Wszystkie</button>
                                @foreach ($typeFilters as $tKey => $tLabel)
                                    @if ($presentTypes->contains($tKey))
                                        <button type="button" class="mat-chip" data-material-filter="{{ $tKey }}" aria-pressed="false">{{ $tLabel }}</button>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        <p class="mat-count" role="status" data-material-count></p>
                    </div>

                    <div class="mat-groups">
                    @foreach ($groups as $gKey => $items)
                        <section id="grupa-{{ $loop->index }}" class="scroll-mt-24" aria-labelledby="grupa-h-{{ $loop->index }}" data-material-group>
                            <h2 id="grupa-h-{{ $loop->index }}" class="mat-group-h">
                                {{ $groupLabel($gKey) }}
                                <span class="mat-group-count">{{ $items->count() }} {{ $plural($items->count()) }}</span>
                            </h2>

                            <ul class="mat-grid" role="list">
                                @foreach ($items as $material)
                                    <li>@include('partials.material-card-feer', ['material' => $material])</li>
                                @endforeach
                            </ul>
                        </section>
                    @endforeach
                    </div>
                    <p class="mat-empty" data-material-empty hidden>Nie znaleźliśmy materiałów pasujących do wyszukiwania. Zmień słowa lub wybierz „Wszystkie”.</p>
                </div>
            </div>
        @endif

        @if ($newsletterCta)
            {{-- Zapis przez FEERletter — ten sam blok co pod „Naszymi działaniami” (brand-light, koperta w tle, przycisk do strony zapisu).
                 Kontrast: ink na brand-light, biały na brand-dark, po najechaniu tło ink (≥ 4,5:1). --}}
            <section id="zapis" class="relative isolate mt-16 flex scroll-mt-24 flex-col gap-6 overflow-hidden rounded-md bg-brand-light p-6 text-ink sm:flex-row sm:items-center sm:justify-between md:p-8" aria-labelledby="zapis-h">
                <span class="pointer-events-none absolute -bottom-8 right-56 -z-10 hidden select-none text-[9rem] leading-none text-white md:block" aria-hidden="true"><i class="fa-regular fa-envelope"></i></span>
                <div class="max-w-2xl">
                    <span class="text-xs font-bold uppercase tracking-widest text-brand-dark">Newsletter</span>
                    <h2 id="zapis-h" class="mt-1 text-xl font-bold leading-snug">Nowe materiały prosto do Twojej skrzynki</h2>
                    <p class="mt-3 text-sm leading-relaxed">Zapisz się na FEERletter, jako pierwsza osoba dowiesz się o nowych bezpłatnych materiałach, terminach szkoleń i webinarów online. FEERletter wychodzi raz na dwa tygodnie.</p>
                </div>
                <a href="{{ route('newsletter.show') }}"
                   class="inline-flex min-h-12 shrink-0 items-center justify-center gap-2 rounded-md bg-brand-dark px-6 text-sm font-bold text-white transition-colors hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2 motion-reduce:transition-none">
                    Zapisuję się na newsletter <span aria-hidden="true">→</span>
                </a>
            </section>
        @else
            {{-- Zapis na powiadomienia o nowych materiałach --}}
            <section id="zapis" class="mt-16 scroll-mt-24 rounded-md bg-gray-50 p-6 sm:p-8" aria-labelledby="zapis-h">
                <div class="max-w-xl">
                    <h2 id="zapis-h" class="text-2xl font-bold text-ink">Powiadomienia o nowych materiałach</h2>
                    <p class="mt-1 text-sm text-ink">Zostaw swój adres e-mail, a damy Ci znać, gdy pojawią się nowe materiały.</p>

                    @if (session('materials_subscribed'))
                        <p class="mt-4 text-sm font-bold text-green-800" role="status">Dziękujemy! Adres został zapisany.</p>
                    @else
                        <form method="POST" action="{{ route('materials.subscribe') }}" class="mt-4">
                            @csrf
                            @include('partials.spam-protection', ['spamScope' => 'materialy', 'flat' => true])
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <label for="subscribe-email" class="sr-only">Adres e-mail</label>
                                <input type="email" id="subscribe-email" name="email" value="{{ old('email') }}" required placeholder="twoj@email.pl" autocomplete="email"
                                       class="min-h-11 w-full rounded-md border-gray-500 focus:border-brand focus:ring-brand">
                                <button type="submit" class="{{ $actionPrimary }} justify-center">Zapisz się</button>
                            </div>
                            @error('email') <p class="mt-2 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
                            <p class="mt-2 text-xs text-muted">Wykorzystamy Twój adres wyłącznie do informowania o nowych materiałach.</p>
                        </form>
                    @endif
                </div>
            </section>
        @endif
    </div>

    @include('educational-materials.partials.styles')

    <script>
        (function () {
            const tools = document.querySelector('[data-material-tools]');
            if (!tools) return;
            tools.hidden = false;

            const input = tools.querySelector('[data-material-search]');
            const chips = tools.querySelectorAll('[data-material-filter]');
            const count = tools.querySelector('[data-material-count]');
            const empty = document.querySelector('[data-material-empty]');
            const cards = Array.from(document.querySelectorAll('[data-material-card]'));
            const groups = document.querySelectorAll('[data-material-group]');
            const norm = (t) => (t || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/ł/g, 'l');
            const plural = (n) => n === 1 ? 'materiał' : ([2, 3, 4].includes(n % 10) && ![12, 13, 14].includes(n % 100) ? 'materiały' : 'materiałów');
            cards.forEach((c) => { c.dataset.searchNorm = norm(c.dataset.search); });
            let type = '';
            let timer = null;

            function apply(announce) {
                const q = norm(input.value.trim());
                let visible = 0;
                cards.forEach((c) => {
                    const show = (!type || c.dataset.type === type) && (!q || c.dataset.searchNorm.includes(q));
                    c.closest('li').hidden = !show;
                    if (show) visible++;
                });
                groups.forEach((g) => { g.hidden = !g.querySelector('li:not([hidden])'); });
                empty.hidden = visible !== 0;
                if (announce) count.textContent = visible === cards.length ? '' : 'Widocznych: ' + visible + ' ' + plural(visible) + ' z ' + cards.length + '.';
            }

            input.addEventListener('input', function () {
                clearTimeout(timer);
                apply(false);
                timer = setTimeout(() => apply(true), 400);
            });
            chips.forEach((chip) => chip.addEventListener('click', function () {
                type = chip.dataset.materialFilter;
                chips.forEach((c) => c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'));
                apply(true);
            }));
        })();
    </script>
    @include('partials.material-preview-dialog')
@endsection
