{{--
    Szablon dedykowany Fundacji FEER (Brand book 2024): rozbudowana strona główna
    w klasycznej oprawie — górny pasek, nagłówek i stopka z domyślnych partiali.
    Wygląd (Montserrat, paleta, płaskie powierzchnie) daje partials/theme-feer.
--}}
@extends('layouts.site')

@section('content')
    <h1 class="sr-only">{{ $siteSettings->site_name }}</h1>
    @include('templates.ngo.partials.home.hero')
    @include('templates.ngo.partials.home.news')
    @include('templates.feer.partials.trainings')
    @include('templates.ngo.partials.home.projects')
    @include('templates.ngo.partials.home.support-cta')
@endsection
