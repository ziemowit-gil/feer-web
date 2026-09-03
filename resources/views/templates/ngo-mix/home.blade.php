{{--
    Szablon mieszany: rozbudowane sekcje strony głównej z szablonu NGO, ale
    w klasycznej oprawie — górny pasek, nagłówek i stopka biorą się z domyślnych
    partiali (layouts/site.blade.php i partials/footer.blade.php wpadają w gałąź
    domyślną dla site_template = ngo_mix).
--}}
@extends('layouts.site')

@section('content')
    {{-- Karuzela hero renderuje tytuły slajdów jako h2 (może być ich kilka
         naraz w DOM) — ukryty h1 daje czytnikom ekranu jeden, jednoznaczny
         nagłówek strony (WCAG 2.4.6). --}}
    <h1 class="sr-only">{{ $siteSettings->site_name }}</h1>
    @include('templates.ngo.partials.home.hero')
    @include('templates.ngo.partials.home.news')
    @include('templates.ngo-mix.partials.trainings')
    @include('templates.ngo.partials.home.projects')
    @include('templates.ngo.partials.home.support-cta')

    {{-- Bez karuzeli partnerów: klasyczna stopka pokazuje ten sam zestaw
         logotypów w bloku „Współpracujemy", i to na każdej podstronie. --}}
@endsection
