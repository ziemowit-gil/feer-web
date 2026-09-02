{{--
    Szablon federacji: sekcje strony głównej z szablonu NGO (górny pasek,
    nagłówek i stopka biorą się z tych samych partiali — layouts/site.blade.php
    i partials/footer.blade.php traktują "federacja" tak samo jak "ngo"), plus
    własna sekcja „Nasza sieć" prezentująca sub-witryny (Ośrodki) tej federacji.
--}}
@extends('layouts.site')

@section('content')
    @include('templates.ngo.partials.home.hero')
    @include('templates.ngo.partials.home.news')
    @include('templates.federacja.partials.home.network')
    @include('templates.ngo.partials.home.projects')
    @include('templates.ngo.partials.home.support-cta')
    @include('templates.ngo.partials.home.events')
    @include('templates.ngo.partials.home.carousel')
@endsection
