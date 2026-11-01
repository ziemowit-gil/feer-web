@extends('layouts.site')

@section('title', $siteSettings->site_name . ' — Strona główna')

@section('content')
    <h1 class="sr-only">{{ $siteSettings->site_name }}</h1>
    @include('templates.test2.partials.home.hero')
    @include('templates.test2.partials.home.news')
    @include('templates.test2.partials.home.shortcuts')
@endsection
