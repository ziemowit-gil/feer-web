@extends('layouts.site')

@section('title', $siteSettings->site_name . ' — Strona główna')

@section('content')
    @include('templates.municipality.partials.home.split-hero')
    @include('templates.municipality.partials.home.news-grid')
    @include('templates.municipality.partials.home.shortcuts')
    @include('templates.municipality.partials.home.carousel')
@endsection
