@extends('layouts.site')

@section('title', 'O nas — ' . $siteSettings->site_name)
@section('meta_description', $siteSettings->meta_description)

@section('content')
    @include('templates.federation.partials.home.hero')
    @include('templates.federation.partials.home.projects')
    @include('templates.federation.partials.home.news')
    @include('templates.federation.partials.home.cta-banner')
    @include('templates.federation.partials.home.partners')
@endsection
