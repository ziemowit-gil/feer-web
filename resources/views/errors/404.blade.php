@extends('layouts.site')

@section('title', 'Nie znaleziono strony')
@section('meta_description', 'Strona, której szukasz, nie istnieje lub została przeniesiona.')

@section('content')
    @include('errors._layout', [
        'code' => '404',
        'icon' => 'fa-map-signs',
        'title' => 'Nie znaleziono strony',
        'description' => 'Strona, której szukasz, mogła zostać usunięta, przeniesiona albo nigdy nie istniała pod tym adresem.',
        'showSearch' => true,
    ])
@endsection
