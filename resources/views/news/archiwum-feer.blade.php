@extends('layouts.site')

{{-- Archiwum aktualności w szablonie FEER — ten sam płaski układ (news/archiwum-list), wiersze z mikrointerakcją kart. --}}

@section('title', 'Archiwum aktualności — ' . $siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Aktualności', 'url' => route('news.index')],
        ['label' => 'Archiwum', 'url' => null],
    ]])
@endsection

@section('content')
    @include('news.archiwum-list', ['rowView' => 'news._archive-row-feer'])
@endsection
