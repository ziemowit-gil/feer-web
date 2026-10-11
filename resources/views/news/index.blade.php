@extends('layouts.site')

{{-- Lista aktualności w układzie portalu (wspólny widok: news/index-list). --}}

@section('title', 'Aktualności — ' . $siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Aktualności', 'url' => null],
    ]])
@endsection

@section('content')
    @include('news.index-list', ['isFeer' => false])
@endsection
