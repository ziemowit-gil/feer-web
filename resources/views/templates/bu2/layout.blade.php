{{--
    Układ podstron szablonu „bu2”: menu boczne z drzewa Menu nawigacji (gałąź bieżącej strony rozwinięta) i treść.
    Widoki BU2 (strona, aktualność, kontakt, lista projektów) rozszerzają ten układ i wypełniają sekcję „bu2_main”.
    Okruszki: sekcja „breadcrumbs” widoku (partials.breadcrumbs → wersja BU2). Bez pozycji w menu — sama treść.
--}}
@extends('layouts.site')

@section('content')
    @include('templates.bu2.partials.base-css')
    @php $bu2Tree = \App\Support\Bu2Menu::tree(); @endphp
    <div class="bu2">
        <div @class(['bu2-wrap', 'bu2-page', 'bu2-page--wide' => $bu2Tree === []])>
            @if ($bu2Tree !== [])
                <nav class="bu2-side" aria-label="Menu serwisu">
                    @include('templates.bu2.partials.side-tree', ['nodes' => $bu2Tree, 'depth' => 1, 'prefix' => 's'])
                </nav>
            @endif
            <div class="bu2-main">
                @yield('bu2_main')
            </div>
        </div>
    </div>
@endsection
