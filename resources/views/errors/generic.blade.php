{{--
    Pozostałe błędy HTTP na froncie (400, 401, 405, 419, 429, 502, 504…) w oprawie bieżącej witryny (głównej albo
    podrzędnej). Laravel używa tego widoku przez 4xx.blade.php i 5xx.blade.php, gdy nie ma osobnego widoku dla kodu.
--}}
@extends('layouts.site')

@php
    $status = isset($exception) && method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500;
    $genericErrors = [
        400 => ['Nieprawidłowe żądanie', 'Serwer nie mógł zrozumieć tego żądania. Sprawdź adres albo dane w formularzu.'],
        401 => ['Wymagane logowanie', 'Ta strona wymaga zalogowania. Zaloguj się i spróbuj ponownie.'],
        405 => ['Niedozwolona metoda', 'Ta strona nie obsługuje takiego żądania. Wróć i spróbuj ponownie z odpowiedniego linku.'],
        419 => ['Sesja wygasła', 'Twoja sesja wygasła. Odśwież stronę i spróbuj jeszcze raz.'],
        422 => ['Nieprawidłowe dane', 'Przesłane dane są niepoprawne. Wróć do formularza i popraw zaznaczone pola.'],
        429 => ['Zbyt wiele żądań', 'Wysłano zbyt wiele żądań w krótkim czasie. Odczekaj chwilę i spróbuj ponownie.'],
        502 => ['Błąd bramy', 'Serwer pośredniczący otrzymał nieprawidłową odpowiedź. Spróbuj ponownie za chwilę.'],
        504 => ['Przekroczono czas oczekiwania', 'Serwer nie odpowiedział w oczekiwanym czasie. Spróbuj ponownie za chwilę.'],
    ];
    [$genericTitle, $genericDescription] = $genericErrors[$status] ?? [
        $status >= 500 ? 'Coś poszło nie tak' : 'Wystąpił błąd',
        $status >= 500 ? 'Wystąpił nieoczekiwany błąd po stronie serwera. Spróbuj ponownie za chwilę.' : 'Nie udało się wyświetlić tej strony. Wróć do strony głównej i spróbuj ponownie.',
    ];
@endphp

@section('title', $genericTitle)
@section('meta_description', $genericDescription)

@section('content')
    @include('errors._layout', ['code' => (string) $status, 'title' => $genericTitle, 'description' => $genericDescription])
@endsection
