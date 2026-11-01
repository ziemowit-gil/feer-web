{{--
    Wariant motywu FEER „FEER IS” (portal instytucji szkoleniowej). Zostają bez zmian: nagłówek witryny, slider oraz
    katalog szkoleń (najbliższe szkolenia z modułu „Szkolenia i zajęcia”, wyróżnione szkolenia i najbliższe terminy,
    wydarzenia). Reszta strony: szybkie wyszukiwanie szkoleń (temat i forma), ścieżki odbiorców z listą usług, jak się
    zapisać, prowadzący, aktualności, pytania i kontakt, partnerzy, wsparcie. Każdy blok pokazuje się tylko wtedy, gdy ma
    dane. Style: templates/feer/partials/is/styles (prefiks fis-, inline, bo wdrożenie nie buduje CSS).
--}}
@extends('layouts.site')

@section('content')
    <h1 class="sr-only">{{ $siteSettings->site_name }}</h1>

    {{-- Slider jak na stronie głównej FEER; slajdy i misja dziedziczone z witryny głównej, gdy ta witryna nie ma własnych. --}}
    @if ($siteSettings->isHomepageSectionEnabled('hero'))
        @include('templates.feer.partials.hero')
    @endif

    @include('templates.feer.partials.is.styles')

    @include('templates.feer.partials.is.find')
    @include('templates.feer.partials.is.audiences')

    {{-- Katalog szkoleń (bez zmian). --}}
    @include('templates.feer.partials.courses-upcoming')
    @include('templates.feer.partials.training-modules')
    @if ($siteSettings->isHomepageSectionEnabled('events'))
        @include('templates.feer.partials.trainings')
    @endif

    @include('templates.feer.partials.is.steps')
    @include('templates.feer.partials.is.trainers')
    @include('templates.feer.partials.is.news')
    @include('templates.feer.partials.is.faq-contact')
    @include('templates.feer.partials.is.partners')
    @include('templates.feer.partials.support-cta')
@endsection
