@extends('layouts.site')

@section('title', 'Błąd serwera')
@section('meta_description', 'Wystąpił nieoczekiwany błąd. Pracujemy nad jego usunięciem.')

@section('content')
    @include('errors._layout', [
        'code' => '500',
        'icon' => 'fa-triangle-exclamation',
        'title' => 'Coś poszło nie tak',
        'description' => 'Wystąpił nieoczekiwany błąd po stronie serwera. Zostaliśmy o tym powiadomieni i pracujemy nad jego usunięciem. Spróbuj ponownie za chwilę.',
    ])
@endsection
