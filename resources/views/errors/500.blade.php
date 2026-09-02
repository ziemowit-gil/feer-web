@extends('layouts.site')

@section('title', 'Błąd serwera')
@section('meta_description', 'Wystąpił nieoczekiwany błąd. Pracujemy nad jego usunięciem.')

@section('content')
    @include('errors._layout', [
        'code' => '500',
        'description' => 'Wystąpił nieoczekiwany błąd po stronie serwera. Zostaliśmy o tym powiadomieni — spróbuj ponownie za chwilę.',
    ])
@endsection
