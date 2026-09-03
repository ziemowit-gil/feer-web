@extends('layouts.site')

@section('title', $siteSettings->site_name)
@section('meta_description', $siteSettings->meta_description)

@section('content')
    @include('templates.wrzos.partials.home.hero')
    @include('templates.wrzos.partials.home.news')
    @include('templates.wrzos.partials.home.intro')
    @include('templates.wrzos.partials.home.values')
    @include('templates.wrzos.partials.home.members')
@endsection
