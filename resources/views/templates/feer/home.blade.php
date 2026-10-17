{{--
    Szablon dedykowany Fundacji FEER (Brand book 2024): rozbudowana strona główna
    w klasycznej oprawie — górny pasek, nagłówek i stopka z domyślnych partiali.
    Wygląd (Montserrat, paleta, płaskie powierzchnie) daje partials/theme-feer.
    Mniej sekcji naraz: każda sekcja pokazuje się tylko wtedy, gdy ma treść (puste i nieaktywne — np. ankieta,
    nabory, galeria z mniej niż 3 zdjęciami — znikają), hero ma jeden przekaz (Ustawienia → Strona główna).
--}}
@extends('layouts.site')

@section('content')
    @php
        // Moduł „FEER Paski": aktywne paski pogrupowane wg miejsca na stronie głównej.
        $feerBands = $siteSettings->isModuleEnabled('feer_bands')
            ? \App\Models\FeerBand::forCurrentSite()->active()->where('placement', '!=', 'shortcode')->orderBy('order')->orderBy('id')->get()->groupBy('placement')
            : collect();
    @endphp
    <h1 class="sr-only">{{ $siteSettings->site_name }}</h1>
    @if ($siteSettings->isHomepageSectionEnabled('hero'))
        @include('templates.feer.partials.hero')
    @endif
    @include('templates.feer.partials.bands-slot', ['slot' => 'after_hero'])
    @if ($siteSettings->home_search_enabled ?? false)
        @include('templates.feer.partials.search')
    @endif
    {{-- Kończące się nabory i zapytania ofertowe (renderuje się tylko, gdy są). --}}
    @include('partials.home.closing-recruitments')
    @if ($siteSettings->isHomepageSectionEnabled('ankieta'))
        @include('templates.feer.partials.shortcuts')
    @endif
    @include('templates.feer.partials.bands-slot', ['slot' => 'after_shortcuts'])
    {{-- Strefa banerów „Strona główna” (moduł Banery) — pusta strefa nic nie renderuje. --}}
    <x-banner-zone name="home" class="mx-auto max-w-6xl px-4 py-8" />
    @if ($siteSettings->isHomepageSectionEnabled('news'))
        @include('templates.feer.partials.news')
    @endif
    @include('templates.feer.partials.bands-slot', ['slot' => 'after_news'])
    {{-- Wpisy z Substacka (blog przeniesiony z „Wiem FEER”) — tylko gdy w Ustawieniach podano adres Substacka. --}}
    @include('partials.home.substack')
    @if ($siteSettings->isHomepageSectionEnabled('events'))
        @include('templates.feer.partials.trainings')
    @endif
    @include('templates.feer.partials.bands-slot', ['slot' => 'after_trainings'])
    @include('templates.feer.partials.projects')
    @include('templates.feer.partials.bands-slot', ['slot' => 'after_projects'])
    @if ($siteSettings->isHomepageSectionEnabled('gallery') && $siteSettings->isModuleEnabled('gallery'))
        @include('templates.feer.partials.gallery')
    @endif
    @include('templates.feer.partials.support-cta')
    @include('templates.feer.partials.bands-slot', ['slot' => 'end'])


    {{-- Administrator: lewitujący przycisk do edycji strony głównej (Ustawienia → Strona główna), jak „Edytuj" na podstronach. --}}
    @if (auth()->check() && auth()->user()->isAdmin())
        <a href="{{ route('admin.ustawienia.edit', ['tab' => 'homepage']) }}"
           class="fixed bottom-24 right-4 z-[9999] inline-flex min-h-12 items-center gap-2 rounded-full bg-ink px-5 text-sm font-bold text-white shadow-[0_6px_20px_rgba(0,0,0,.28)] transition hover:-translate-y-0.5 hover:bg-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 print:hidden">
            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>Edytuj<span class="sr-only"> stronę główną</span>
        </a>
    @endif
@endsection
