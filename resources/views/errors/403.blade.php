@extends('layouts.site')

@section('title', 'Brak dostępu')
@section('meta_description', 'Nie masz uprawnień do wyświetlenia tej strony.')

@section('content')
    @include('errors._layout', [
        'code' => '403',
        'icon' => 'fa-lock',
        'title' => 'Brak dostępu',
        'description' => 'Nie masz uprawnień do wyświetlenia tej strony. Jeśli uważasz, że to błąd, skontaktuj się z nami.',
    ])
@endsection
