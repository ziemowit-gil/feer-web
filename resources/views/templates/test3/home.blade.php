@extends('layouts.site')

@section('title', $siteSettings->site_name . ' — Strona główna')

@section('content')
    <h1 class="sr-only">{{ $siteSettings->site_name }}</h1>
    @include('templates.test3.partials.home.hero')
    @include('templates.test3.partials.home.news')
    @include('templates.test3.partials.home.stats')
    @include('templates.test3.partials.home.gallery')
    @include('templates.test3.partials.home.partners')
@endsection
