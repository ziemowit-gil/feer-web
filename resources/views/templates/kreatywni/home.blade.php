{{--
    Strona główna szablonu „kreatywni” — wizytówka fundacji na jednej stronie. Teksty: App\Support\KreatywniTexts
    (Ustawienia → Szablon), profile social media i dane kontaktowe z ustawień, zdjęcia z modułu Galeria.
    Sekcję ukrytą w panelu albo bez treści pomijamy. Kolejność: KreatywniTexts::SECTIONS.
--}}
@extends('layouts.site')

@section('title', $siteSettings->site_name)

@section('content')
    @include('templates.kreatywni.partials.base-css')
    <div class="kc">
        @foreach (array_keys(\App\Support\KreatywniTexts::SECTIONS) as $kcSection)
            @if ($texts->shows($kcSection))
                @include('templates.kreatywni.partials.home.'.$kcSection)
            @endif
        @endforeach
    </div>
@endsection
