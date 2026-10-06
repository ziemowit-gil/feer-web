@extends('layouts.site')

@section('content')
    {{-- Karuzela hero renderuje tytuły slajdów jako h2 (może być ich kilka
         naraz w DOM) — ukryty h1 daje czytnikom ekranu jeden, jednoznaczny
         nagłówek strony (WCAG 2.4.6). --}}
    <h1 class="sr-only">{{ $siteSettings->site_name }}</h1>
    @if ($siteSettings->isHomepageSectionEnabled('hero'))
        @include('templates.ngo.partials.home.hero')
    @endif
    @if ($siteSettings->isHomepageSectionEnabled('news'))
        @include('templates.ngo.partials.home.news')
    @endif
    @include('templates.ngo.partials.home.projects')
    @include('templates.ngo.partials.home.support-cta')
    @if ($siteSettings->isHomepageSectionEnabled('events'))
        @include('templates.ngo.partials.home.events')
    @endif
    @include('templates.ngo.partials.home.carousel')
@endsection
