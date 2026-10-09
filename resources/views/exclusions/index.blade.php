@extends('layouts.site')

@section('title', 'Wyłączenia treści — ' . $siteSettings->site_name)
@section('meta_description', 'Treści tymczasowo niedostępne w serwisie ' . $siteSettings->site_name . ' — co jest wyłączone i dlaczego.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Wyłączenia treści', 'url' => null],
    ]])
@endsection

@section('content')
@include('partials.flat-page-styles')

<div class="fp-head">
    <h1 class="fp-h1">Wyłączenia treści</h1>
    <span class="fp-bar" aria-hidden="true"></span>
    <p class="fp-lead">Niektóre treści w serwisie bywają tymczasowo wyłączone — na przykład na czas aktualizacji, przeglądu albo zmiany przepisów. Poniżej znajdziesz listę takich stron.</p>
</div>

<div class="fp-wrap">
    <h2 class="fp-h2" id="wyl-aktualne"><i class="fa-solid fa-circle-pause" aria-hidden="true"></i>Aktualnie wyłączone</h2>
    @if ($pages->isEmpty() && $inherited->isEmpty())
        <p class="fp-empty">Obecnie żadna treść nie jest wyłączona. Wszystkie strony są dostępne.</p>
    @else
        <ul class="fp-list" role="list" aria-labelledby="wyl-aktualne">
            @foreach ($pages as $page)
                <li class="fp-item">
                    <div style="min-width:0;flex:1 1 16rem">
                        <a href="{{ $page->publicUrl() }}" class="fp-item-t" style="text-decoration:underline;text-underline-offset:3px">{{ $page->title }}</a>
                        <p class="fp-item-d">{{ $page->disabledMessage() }}</p>
                    </div>
                    <span class="fp-tag">Wyłączona</span>
                </li>
            @endforeach
            @foreach ($inherited as $page)
                <li class="fp-item">
                    <div style="min-width:0;flex:1 1 16rem">
                        <span class="fp-item-t">{{ $page->title }}</span>
                        <p class="fp-item-d">Wyłączona razem z działem „{{ $page->disabledAncestor()?->title }}”.</p>
                    </div>
                    <span class="fp-tag">Przez dział</span>
                </li>
            @endforeach
        </ul>
    @endif

    <h2 class="fp-h2"><i class="fa-solid fa-circle-info" aria-hidden="true"></i>Co to oznacza</h2>
    <div class="fp-box">
        <p class="fp-p">Wyłączona strona pokazuje komunikat zamiast treści. Dane i historia zostają zachowane — po ponownym włączeniu treść wraca bez zmian.</p>
        <p class="fp-p" style="margin:0">Pełną informację o dostępności serwisu znajdziesz w <a href="{{ route('accessibility.show') }}" class="fp-link">deklaracji dostępności</a>. Pytania możesz zadać przez <a href="{{ route('contact.show') }}" class="fp-link">formularz kontaktowy</a>.</p>
    </div>
</div>
@endsection
