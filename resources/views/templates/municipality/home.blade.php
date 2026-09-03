@extends('layouts.site')

@section('title', $siteSettings->site_name . ' — Strona główna')

@section('content')
    {{-- Karuzela hero renderuje tytuły slajdów jako h2 (może być ich kilka
         naraz w DOM) — ukryty h1 daje czytnikom ekranu jeden, jednoznaczny
         nagłówek strony (WCAG 2.4.6). --}}
    <h1 class="sr-only">{{ $siteSettings->site_name }}</h1>
    @include('templates.municipality.partials.home.split-hero')
    @include('templates.municipality.partials.home.news-grid')
    @include('templates.municipality.partials.home.shortcuts')
    @include('templates.municipality.partials.home.carousel')
@endsection
