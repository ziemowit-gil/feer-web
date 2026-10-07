{{--
    Szablon dedykowany Fundacji FEER (Brand book 2024): rozbudowana strona główna
    w klasycznej oprawie — górny pasek, nagłówek i stopka z domyślnych partiali.
    Wygląd (Montserrat, paleta, płaskie powierzchnie) daje partials/theme-feer.
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
    @if ($siteSettings->isHomepageSectionEnabled('ankieta') && $siteSettings->isModuleEnabled('quick_actions'))
        @include('templates.feer.partials.shortcuts')
    @endif
    @include('templates.feer.partials.bands-slot', ['slot' => 'after_shortcuts'])
    @if ($siteSettings->isHomepageSectionEnabled('news'))
        @include('templates.feer.partials.news')
    @endif
    @include('templates.feer.partials.bands-slot', ['slot' => 'after_news'])
    @if ($siteSettings->isHomepageSectionEnabled('events'))
        @include('templates.feer.partials.trainings')
    @endif
    @include('templates.feer.partials.bands-slot', ['slot' => 'after_trainings'])
    @include('templates.feer.partials.projects')
    @include('templates.feer.partials.bands-slot', ['slot' => 'after_projects'])
    @include('templates.feer.partials.support-cta')
    @include('templates.feer.partials.bands-slot', ['slot' => 'end'])
@endsection
