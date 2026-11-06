@extends('layouts.site')

@section('title', $siteSettings->site_name . ' — Strona główna')

@section('content')
    @include('templates.podcasts.partials.home.hero')
    @include('templates.podcasts.partials.home.episodes')
    @include('templates.podcasts.partials.home.partners')
@endsection
