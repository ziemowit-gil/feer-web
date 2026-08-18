@extends('layouts.site')

@section('content')
    @include('templates.ngo.partials.home.hero')
    @include('templates.ngo.partials.home.news')
    @include('templates.ngo.partials.home.projects')
    @include('templates.ngo.partials.home.support-cta')
    @include('templates.ngo.partials.home.events')
    @include('templates.ngo.partials.home.carousel')
@endsection
