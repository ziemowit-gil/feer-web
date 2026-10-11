@props(['status'])

{{-- Komunikat po akcji (np. wysłano link do resetu hasła). Płasko, kolory firmowe, kontrast ≥ 4,5:1. --}}
@if ($status)
    <div role="status" {{ $attributes->merge(['class' => 'flex items-start gap-2 rounded-md bg-brand-light px-4 py-3 text-sm font-bold text-ink']) }}>
        <i class="fa-solid fa-circle-check mt-0.5 text-brand-dark" aria-hidden="true"></i>
        <span>{{ $status }}</span>
    </div>
@endif
