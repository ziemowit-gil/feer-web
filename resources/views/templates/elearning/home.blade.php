@extends('layouts.site')

@section('title', $siteSettings->site_name . ' — Strona główna')

@section('content')
    @include('templates.elearning.partials.home.hero')
    @include('templates.elearning.partials.home.courses')
    @include('templates.elearning.partials.home.stats')
    @include('templates.elearning.partials.home.audience')
    @include('templates.elearning.partials.home.experts')
    @include('templates.elearning.partials.home.partners')
    @include('templates.elearning.partials.home.faq')
@endsection
