{{--
    Szablon dedykowany Fundacji FEER (Brand book 2024): rozbudowana strona główna
    w klasycznej oprawie — górny pasek, nagłówek i stopka z domyślnych partiali.
    Wygląd (Montserrat, paleta, płaskie powierzchnie) daje partials/theme-feer.
--}}
@extends('layouts.site')

@section('content')
    <h1 class="sr-only">{{ $siteSettings->site_name }}</h1>
    @if ($siteSettings->isHomepageSectionEnabled('hero'))
        @include('templates.feer.partials.hero')
    @endif
    @if ($siteSettings->isHomepageSectionEnabled('ankieta') && $siteSettings->isModuleEnabled('quick_actions'))
        @include('templates.feer.partials.shortcuts')
    @endif
    @if ($siteSettings->isHomepageSectionEnabled('news'))
        @include('templates.feer.partials.news')
    @endif
    @if ($siteSettings->isHomepageSectionEnabled('events'))
        @include('templates.feer.partials.trainings')
    @endif
    @include('templates.feer.partials.projects')
    @include('templates.feer.partials.support-cta')
@endsection
