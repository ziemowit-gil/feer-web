{{-- Pasma z modułu „FEER Paski" przypisane do miejsca $slot na stronie głównej (kolejność wg pola „Kolejność"). --}}
@foreach (($feerBands[$slot] ?? collect()) as $band)
    @include('partials.feer-band', ['band' => $band])
@endforeach
