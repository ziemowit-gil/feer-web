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
                            <li class="shrink-0"><a href="#zapis" class="flex min-h-11 items-center rounded-md px-4 py-2 text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand lg:px-3">Powiadomienia →</a></li>
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
    </div>

    <style>
        /* Materiały FEER — style wbudowane (push na produkcję nie buduje Tailwinda). Płasko: bez ramek, cieni i etykiet. */
        .mat-notice { display: flex; gap: .5rem; align-items: baseline; max-width: 42rem; margin-top: 1rem; font-size: .875rem; line-height: 1.6; color: var(--color-muted, #4b5563); }
        .mat-notice > i { color: var(--color-brand-dark, #1752bf); }
        .mat-notice p { margin: 0; }
        .mat-notice a { color: var(--color-brand-dark, #1752bf); text-decoration: underline; text-underline-offset: 3px; }

        .mat-tools { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem 1.5rem; margin-bottom: 2.5rem; }
        .mat-tools[hidden] { display: none; }
        .mat-search { flex: 1 1 16rem; max-width: 26rem; }
        .mat-label { display: block; margin-bottom: .375rem; font-size: .875rem; font-weight: 700; color: var(--color-ink, #1a1a1a); }
        .mat-input { width: 100%; min-height: 2.75rem; padding: 0 .875rem; border: 1px solid #6b7280; border-radius: .375rem; background: #fff; color: var(--color-ink, #1a1a1a); font-size: 1rem; }
        .mat-input:focus { outline: 3px solid var(--color-brand, #1e6dff); outline-offset: 1px; border-color: var(--color-brand-dark, #1752bf); }
        .mat-filter { display: flex; flex-wrap: wrap; gap: .5rem; }
        .mat-chip { min-height: 2.75rem; padding: 0 1rem; border-radius: .375rem; background: #f3f4f6; color: var(--color-ink, #1a1a1a); font-size: .875rem; font-weight: 700; cursor: pointer; }
        .mat-chip:hover { background: #e5e7eb; }
        .mat-chip[aria-pressed="true"] { background: var(--color-ink, #1a1a1a); color: #fff; }
        .mat-chip:focus-visible { outline: 3px solid var(--color-brand, #1e6dff); outline-offset: 2px; }
        .mat-count { flex-basis: 100%; margin: 0; font-size: .875rem; color: var(--color-muted, #4b5563); }

        .mat-groups > * + * { margin-top: 4rem; }
        .mat-group-h { display: flex; flex-wrap: wrap; align-items: baseline; gap: .25rem .75rem; margin-bottom: 1.25rem; font-size: 1.5rem; font-weight: 800; line-height: 1.25; color: var(--color-ink, #1a1a1a); }
        .mat-group-count { font-family: var(--font-sans); font-size: .9375rem; font-weight: 400; color: var(--color-muted, #4b5563); }
        .mat-grid { display: grid; gap: 1.5rem; }
        @media (min-width: 640px) { .mat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 1280px) { .mat-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        .mat-grid > li { display: flex; }
        .mat-grid > li[hidden] { display: none; }

        .mat-card { position: relative; display: flex; flex-direction: column; width: 100%; overflow: hidden; border-radius: .5rem; background: #f9fafb; transition: background-color .15s; }
        .mat-card:hover { background: #f3f4f6; }
        .mat-card:has(.mat-link:focus-visible) { outline: 3px solid var(--color-brand, #1e6dff); outline-offset: 3px; }
        .mat-thumb { position: relative; aspect-ratio: 16 / 9; overflow: hidden; }
        .mat-thumb--video { background: var(--color-ink, #1a1a1a); }
        .mat-thumb--doc { background: var(--color-brand-light, #e8f0ff); }
        .mat-thumb-img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s; }
        .mat-card:hover .mat-thumb-img { transform: scale(1.03); }
        .mat-play { position: absolute; left: 1rem; bottom: 1rem; display: flex; width: 2.75rem; height: 2.75rem; align-items: center; justify-content: center; border-radius: .375rem; background: #fff; color: var(--color-ink, #1a1a1a); transition: background-color .15s, color .15s; }
        .mat-card:hover .mat-play { background: var(--color-brand-dark, #1752bf); color: #fff; }
        .mat-doc-icon { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: var(--color-brand-dark, #1752bf); }
        .mat-thumb-canvas { position: relative; z-index: 1; display: block; width: 100%; height: 100%; object-fit: contain; }

        .mat-body { display: flex; flex: 1; flex-direction: column; padding: 1.25rem; }
        .mat-eyebrow { margin: 0 0 .25rem; font-size: .8125rem; font-weight: 700; color: var(--color-muted, #4b5563); }
        .mat-title { margin: 0; font-size: 1.125rem; font-weight: 700; line-height: 1.35; color: var(--color-ink, #1a1a1a); display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .mat-link { color: inherit; text-decoration: none; }
        .mat-link:focus-visible { outline: none; }
        .mat-link::after { content: ''; position: absolute; inset: 0; z-index: 2; }
        .mat-meta { display: flex; flex-wrap: wrap; gap: .25rem 1rem; margin: .5rem 0 0; font-size: .875rem; color: var(--color-muted, #4b5563); }
        .mat-meta-item { display: inline-flex; align-items: center; gap: .375rem; }
        .mat-desc { margin: .75rem 0 0; font-size: .875rem; line-height: 1.6; color: var(--color-muted, #4b5563); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .mat-cta { display: inline-flex; align-items: center; gap: .5rem; min-height: 2.75rem; margin-top: auto; padding-top: 1rem; font-size: .9375rem; font-weight: 700; color: var(--color-brand-dark, #1752bf); }
        .mat-card:hover .mat-cta, .mat-card:has(.mat-link:focus-visible) .mat-cta { text-decoration: underline; text-underline-offset: 4px; }
        .mat-empty { margin-top: 1rem; color: var(--color-ink, #1a1a1a); }
        .mat-empty[hidden] { display: none; }
        @media (prefers-reduced-motion: reduce) { .mat-card, .mat-thumb-img, .mat-play { transition: none; } .mat-card:hover .mat-thumb-img { transform: none; } }
    </style>

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
