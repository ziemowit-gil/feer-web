@extends('layouts.site')

{{--
    Portal kategorii działań (/portal/{adres}) — informuje o jednym obszarze działalności fundacji:
    komunikat (opcjonalnie), tytuł i wstęp, slider, obok menu działań kategorii z rozwijanymi podstronami i „Na skróty”,
    niżej widżety w kolejności z panelu (aktualności, kontakt, wydarzenia…), na końcu „Pozostańmy w kontakcie”.
    Treści edytowalne w panelu (Działania → Kategorie → Portal kategorii); puste elementy się nie pokazują.
    Płasko i kontrastowo (brandbook FEER): bez ramek, linii i etykiet, kolory marki, tekst ≥ 4,5:1.
--}}

@section('title', $title . ' — ' . $siteSettings->site_name)

@push('structured_data')
    <link rel="alternate" type="application/rss+xml" title="{{ $title }} — RSS" href="{{ route('portal.feed', $category->portal_slug) }}">
@endpush

@section('breadcrumbs')
    {{-- Portal to samodzielna sekcja: Strona główna › portal (bez pośredniego „Działania”). --}}
    @include('partials.breadcrumbs', ['items' => [
        ['label' => $title, 'url' => null],
    ]])
@endsection

@section('content')
    @php
        $h2 = 'text-xl font-bold leading-tight text-ink md:text-2xl';
        $bar = 'mt-2 block h-1 w-12 bg-brand';
        $more = 'inline-flex min-h-11 items-center gap-2 text-sm font-bold text-brand-dark underline underline-offset-4 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
        // Wstęp: akapity oddzielone pustą linią.
        $introParagraphs = array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/', (string) $category->portal_intro))));
        $notice = $category->portalOption('notice');
        $noticeUrl = $category->portalOption('notice_link_url');
        // Bez slidera kafelki (wiersze) działań stają pod wstępem, żeby główna kolumna nie była pusta.
        $projectsInMain = $slides->isEmpty() && $projects->isNotEmpty() && in_array('projects', $widgets, true);
        // Menu jak na stronie działania (projects/partials/sidebar-nav): pozycje na całą szerokość ze strzałką.
        $navItem = 'proj-nav-item flex min-h-11 w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-left text-[0.9375rem] text-ink transition hover:bg-white hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
        $shortcuts = $category->portalShortcuts() ?: array_values(array_filter([
            ['Wszystkie działania w kategorii', route('categories.show', $category), ''],
            Route::has('events.index') ? ['Kalendarz szkoleń i wydarzeń', route('events.index'), 'fa-regular fa-calendar'] : null,
            Route::has('newsletter.show') ? ['Zapisz się do newslettera', route('newsletter.show'), 'fa-regular fa-envelope'] : null,
            Route::has('contact.show') ? ['Kontakt z fundacją', route('contact.show'), 'fa-regular fa-address-card'] : null,
            ['Kanał RSS portalu', route('portal.feed', $category->portal_slug), ''],
        ]));
        if (! $category->portalOption('shortcuts_enabled', true)) {
            $shortcuts = [];
        }
        $shortcutsTitle = $category->portalOption('shortcuts_title', 'Na skróty');
        $navTitle = $category->portalOption('nav.title', $title);
        // Układ z panelu: kolumna boczna po prawej / po lewej / bez niej; przy kolumnie po lewej tytuł zawsze nad nią,
        // żeby na telefonie (jedna kolumna) nagłówek strony był pierwszy.
        $sidebar = $category->portalLayout('sidebar');
        $headerFull = $category->portalLayout('header') === 'full' || $sidebar === 'left';
        $shortcutsInSidebar = $shortcuts && $sidebar !== 'none' && $category->portalLayout('shortcuts') === 'sidebar';
        $shortcutsAsTiles = $shortcuts && ! $shortcutsInSidebar;
        $gridCols = match ($sidebar) {
            'left' => 'lg:grid-cols-[20rem_minmax(0,1fr)]',
            'none' => '',
            default => 'lg:grid-cols-[minmax(0,1fr)_20rem]',
        };
        $stayEnabled = (bool) $category->portalOption('stay_enabled', true);
    @endphp

    <div class="mx-auto max-w-6xl px-4 pb-14 pt-6">
        {{-- ═════ Komunikat (z panelu) ═════ --}}
        @if ($notice)
            <div role="region" aria-label="Komunikat" class="mb-8 flex flex-col gap-3 rounded-md bg-brand-light px-5 py-4 text-ink sm:flex-row sm:items-center sm:justify-between">
                <p class="flex items-start gap-3 font-bold leading-snug">
                    <i class="fa-regular fa-bell mt-1 flex-none" aria-hidden="true"></i>
                    <span>{{ $notice }}</span>
                </p>
                @if ($noticeUrl)
                    <a href="{{ $noticeUrl }}" class="{{ $more }} flex-none">{{ $category->portalOption('notice_link_label', 'Więcej') }} <span aria-hidden="true">→</span></a>
                @endif
            </div>
        @endif

        @if ($headerFull)
            <div class="mb-8">
                @include('portal.partials.header')
                @includeWhen($shortcutsAsTiles, 'portal.partials.quick-actions')
            </div>
        @endif

        <div class="grid items-start gap-10 {{ $gridCols }}">
            @includeWhen($sidebar === 'left', 'portal.partials.sidebar')

            {{-- ═════ Główna kolumna: tytuł i wstęp, szybkie akcje, slider (albo działania) ═════ --}}
            <div class="min-w-0">
                @unless ($headerFull)
                    @include('portal.partials.header')
                    @includeWhen($shortcutsAsTiles, 'portal.partials.quick-actions')
                @endunless

                @if ($slides->isNotEmpty())
                    <div class="{{ $headerFull ? '' : 'mt-8' }}">
                        @include('portal.partials.slider', ['slides' => $slides, 'label' => 'Slider: ' . $title, 'duration' => $sliderDuration])
                    </div>
                @elseif ($projectsInMain)
                    <div class="{{ $headerFull ? '' : 'mt-10' }}">
                        @include('portal.widgets.projects', ['cols' => $sidebar === 'none' ? 'md:grid-cols-2' : ''])
                    </div>
                @endif
            </div>

            @includeWhen($sidebar === 'right', 'portal.partials.sidebar')
        </div>

        {{-- ═════ Widżety w kolejności z panelu; dwa kolejne „na połowę” stają obok siebie, puste się nie pokazują ═════ --}}
        @php
            $blocks = [];
            foreach ($widgets as $widget) {
                if (($widget === 'projects' && $projectsInMain) || ! view()->exists('portal.widgets.' . $widget)) {
                    continue;
                }
                $half = $category->portalWidgetWidth($widget) === 'half';
                $html = trim($__env->make('portal.widgets.' . $widget, ['compact' => $half] + \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path', 'blocks', 'html']))->render());
                if ($html !== '') {
                    $blocks[] = ['half' => $half, 'html' => $html];
                }
            }
            $rows = [];
            for ($k = 0; $k < count($blocks); $k++) {
                if ($blocks[$k]['half'] && ($blocks[$k + 1]['half'] ?? false)) {
                    $rows[] = [$blocks[$k]['html'], $blocks[++$k]['html']];
                } else {
                    $rows[] = [$blocks[$k]['html']];
                }
            }
        @endphp
        @if ($rows)
            <div class="mt-14 space-y-14">
                @foreach ($rows as $row)
                    @if (count($row) === 2)
                        <div class="grid items-start gap-14 lg:grid-cols-2 lg:gap-10">{!! $row[0] !!}{!! $row[1] !!}</div>
                    @else
                        {!! $row[0] !!}
                    @endif
                @endforeach
            </div>
        @endif

        {{-- ═════ Pozostańmy w kontakcie (włączane, nagłówek i opis z panelu) ═════ --}}
        @if ($stayEnabled)
            <section aria-labelledby="portal-stay" class="mt-14 rounded-md bg-brand-light p-6 md:p-8">
                <h2 id="portal-stay" class="{{ $h2 }}">{{ $category->portalOption('stay_title', 'Pozostańmy w kontakcie') }}</h2>
                <span class="{{ $bar }}" aria-hidden="true"></span>
                @if ($stayText = $category->portalOption('stay_text'))
                    <p class="mt-4 max-w-3xl text-lg leading-relaxed text-ink">{{ $stayText }}</p>
                @endif
                <div class="mt-5 grid gap-6 md:grid-cols-3">
                    @if (Route::has('newsletter.show'))
                        <div>
                            <h3 class="font-bold text-ink">Newsletter</h3>
                            <p class="mt-1 text-ink">Nowe szkolenia, materiały i wydarzenia prosto na Twoją skrzynkę.</p>
                            <p class="mt-2"><a href="{{ route('newsletter.show') }}" class="{{ $more }}">Zapisz się <span aria-hidden="true">→</span></a></p>
                        </div>
                    @endif
                    @if (Route::has('contact.show'))
                        <div>
                            <h3 class="font-bold text-ink">Napisz do nas</h3>
                            <p class="mt-1 text-ink">Masz pytanie o działania z tego portalu? Odpowiemy tak szybko, jak to możliwe.</p>
                            <p class="mt-2"><a href="{{ route('contact.show') }}" class="{{ $more }}">Formularz kontaktowy <span aria-hidden="true">→</span></a></p>
                        </div>
                    @endif
                    <div>
                        <h3 class="font-bold text-ink">RSS</h3>
                        <p class="mt-1 text-ink">Śledź aktualności i nowe działania tego portalu w czytniku RSS.</p>
                        <p class="mt-2"><a href="{{ route('portal.feed', $category->portal_slug) }}" class="{{ $more }}"><i class="fa-solid fa-rss" aria-hidden="true"></i> Kanał RSS</a></p>
                    </div>
                </div>
            </section>
        @endif
    </div>
@endsection
