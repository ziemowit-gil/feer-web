@extends('layouts.site')

@section('title', $siteSettings->site_name)
@section('meta_description', $siteSettings->meta_description)

@section('content')
    {{-- Sekcja hero jest czysto dekoracyjna (aria-hidden) — ukryty h1 daje
         czytnikom ekranu jednoznaczny nagłówek strony (WCAG 2.4.6). --}}
    <h1 class="sr-only">{{ $siteSettings->site_name }}</h1>
    @include('templates.wrzos.partials.home.hero')
    @include('templates.wrzos.partials.home.news')
    @include('templates.wrzos.partials.home.intro')
    @include('templates.wrzos.partials.home.values')
    @include('templates.wrzos.partials.home.members')
@endsection
