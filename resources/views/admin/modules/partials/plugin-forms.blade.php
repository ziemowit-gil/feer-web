{{-- Puste formularze akcji modułów instalowanych — przyciski w kartach
     (partial plugin-card) wskazują je atrybutem form="…". Muszą stać POZA
     każdym innym formularzem (HTML nie pozwala zagnieżdżać <form>).
     Parametry: $cards, $returnTo (opcjonalnie 'settings' — powrót do zakładki Ustawienia → Moduły). --}}
@foreach ($cards as $card)
    @php
        $id = $card['id'];
        $action = match ($card['status']) {
            'active' => 'admin.moduly.deactivate',
            'inactive' => 'admin.moduly.activate',
            default => 'admin.moduly.install',
        };
    @endphp
    <form id="plugin-module-form-{{ $id }}" method="POST" action="{{ route($action, $id) }}" class="hidden"
        @if ($card['status'] === 'active') data-confirm="Dezaktywować moduł „{{ $card['manifest']->name }}”? Jego funkcje przestaną działać." @endif>
        @csrf
        @if (! empty($returnTo))
            <input type="hidden" name="return_to" value="{{ $returnTo }}">
        @endif
    </form>
@endforeach
