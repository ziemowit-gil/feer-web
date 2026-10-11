@extends('layouts.site')

{{--
    Aktualności w szablonie FEER — ten sam płaski układ portalu co news/index (news/index-list):
    wyróżniony najnowszy wpis, siatka kart, kolumna boczna z kategoriami, wyszukiwarką i newsletterem.
    Wariant FEER dodaje mikrointerakcje kart (.feer-card) i mocniejszy tytuł wyróżnionego wpisu.
--}}

@section('title', 'Aktualności — ' . $siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Aktualności', 'url' => null],
    ]])
@endsection

@section('content')
    @include('news.index-list', ['isFeer' => true])
@endsection
