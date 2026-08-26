{{--
    Szablon mieszany: rozbudowane sekcje strony głównej z szablonu NGO, ale
    w klasycznej oprawie — górny pasek, nagłówek i stopka biorą się z domyślnych
    partiali (layouts/site.blade.php i partials/footer.blade.php wpadają w gałąź
    domyślną dla site_template = ngo_mix).
--}}
@extends('layouts.site')

@section('content')
    @include('templates.ngo.partials.home.hero')
    @include('templates.ngo.partials.home.news')
    @include('templates.ngo-mix.partials.trainings')
    @include('templates.ngo.partials.home.projects')
    @include('templates.ngo.partials.home.support-cta')
    @include('templates.ngo.partials.home.carousel')
@endsection
