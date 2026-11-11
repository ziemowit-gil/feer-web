@extends('layouts.site')

@section('title', $siteSettings->site_name . ' — Strona główna')

@section('content')
    @include('templates.bu2.partials.base-css')
    <div class="bu2">
        <h1 class="bu2-sr">{{ $siteSettings->site_name }}</h1>
        @include('templates.bu2.partials.home.slider')
        @include('templates.bu2.partials.home.news')
        @include('templates.bu2.partials.home.contact')
    </div>
@endsection
