{{-- Dostępny przełącznik: prawdziwy <input type="checkbox" role="switch"> (wizualnie
     ukryty, ale fokusowalny) + rysowany tor z gałką i tekst stanu (nie tylko kolor).
     Wymaga w nadrzędnym x-data obiektu `enabled` z kluczem $stateKey.
     Parametry: $id, $name, $value, $checked (bool), $stateKey, $label (nazwa dostępna),
     $onText, $offText, $describedBy (opcjonalnie), $disabled (opcjonalnie). --}}
@php
    $disabled = ! empty($disabled);
    $describedBy = $describedBy ?? null;
    $jsKey = \Illuminate\Support\Js::from($stateKey);
@endphp
<label for="{{ $id }}" class="inline-flex items-center gap-3 self-start {{ $disabled ? 'cursor-not-allowed' : 'cursor-pointer' }}">
    <input type="checkbox" role="switch" id="{{ $id }}"
        name="{{ $name }}" value="{{ $value }}" {{ $checked ? 'checked' : '' }} {{ $disabled ? 'disabled' : '' }}
        aria-label="{{ $label }}"
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @change="enabled[{{ $jsKey }}] = $event.target.checked"
        class="peer sr-only">
    <span aria-hidden="true"
        class="relative inline-flex h-6 w-11 flex-none items-center rounded-full border-2 transition-colors peer-focus-visible:ring-2 peer-focus-visible:ring-brand-dark peer-focus-visible:ring-offset-2 {{ $checked ? 'border-brand-dark bg-brand-dark' : 'border-gray-600 bg-white' }}"
        :class="enabled[{{ $jsKey }}] ? 'border-brand-dark bg-brand-dark' : 'border-gray-600 bg-white'">
        <span class="absolute left-0.5 h-4 w-4 rounded-full transition-transform {{ $checked ? 'translate-x-5 bg-white' : 'translate-x-0 bg-gray-600' }}"
            :class="enabled[{{ $jsKey }}] ? 'translate-x-5 bg-white' : 'translate-x-0 bg-gray-600'"></span>
    </span>
    <span class="text-sm font-bold text-ink" aria-hidden="true"
        x-text="enabled[{{ $jsKey }}] ? @js($onText) : @js($offText)">{{ $checked ? $onText : $offText }}</span>
</label>
