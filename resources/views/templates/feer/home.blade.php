{{--
    Szablon dedykowany Fundacji FEER (Brand book 2024): rozbudowana strona główna
    w klasycznej oprawie — górny pasek, nagłówek i stopka z domyślnych partiali.
    Wygląd (Montserrat, paleta, płaskie powierzchnie) daje partials/theme-feer.
--}}
@extends('layouts.site')

@section('content')
    <h1 class="sr-only">{{ $siteSettings->site_name }}</h1>
    @if ($siteSettings->isHomepageSectionEnabled('hero'))
        @include('templates.ngo.partials.home.hero')
    @endif
    @if ($siteSettings->isHomepageSectionEnabled('ankieta') && $siteSettings->isModuleEnabled('quick_actions'))
        @include('templates.feer.partials.shortcuts')
    @endif
    @if ($siteSettings->isHomepageSectionEnabled('news'))
        @include('templates.ngo.partials.home.news')
    @endif
    @if ($siteSettings->isHomepageSectionEnabled('events'))
        @include('templates.feer.partials.trainings')
    @endif
    @include('templates.ngo.partials.home.projects')
    @include('templates.ngo.partials.home.support-cta')
@endsection
