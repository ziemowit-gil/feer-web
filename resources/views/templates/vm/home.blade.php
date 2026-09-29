@extends('layouts.site')

@section('title', $siteSettings->site_name)
@section('meta_description', $siteSettings->meta_description)

@section('content')
    {{-- Slider renderuje tytuły slajdów jako h2 — ukryty h1 daje czytnikom
         ekranu jeden, jednoznaczny nagłówek strony (WCAG 2.4.6). --}}
    <h1 class="sr-only">{{ $siteSettings->site_name }}</h1>
    @include('templates.vm.partials.home.hero')
    @include('templates.vm.partials.home.intro')
    @include('templates.vm.partials.home.news')
    @include('templates.vm.partials.home.projects')
    @include('templates.vm.partials.home.knowledge')
    @include('templates.vm.partials.home.partners')
    @include('templates.vm.partials.home.newsletter')
@endsection
