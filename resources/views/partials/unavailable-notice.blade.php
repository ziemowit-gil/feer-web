{{--
    Pełnoekranowy zamiennik treści, gdy strona (lub inny obiekt) jest wyłączona albo w trybie „w budowie".
    Nowy styl: pasek marki, duża ikona w ramce, komunikat i przyciski dalej (strona główna, wyłączenia treści).
    Wymaga: $entity (wipIsFull(), wipMessage(), disabledMessage(), title). Opcjonalnie: $backUrl, $backLabel.
--}}
@php
    $backUrl = $backUrl ?? route('home');
    $backLabel = $backLabel ?? 'Wróć na stronę główną';
    $isWip = $entity->wipIsFull();
    $icon = $isWip ? 'fa-person-digging' : 'fa-circle-pause';
    $message = $isWip ? $entity->wipMessage() : $entity->disabledMessage();
    $tag = $isWip ? 'W budowie' : 'Tymczasowo wyłączone';
@endphp
@include('partials.flat-page-styles')

<div class="fp-head">
    <span class="fp-tag" style="margin-bottom:1rem">{{ $tag }}</span>
    <h1 class="fp-h1">{{ $entity->title }}</h1>
    <span class="fp-bar" aria-hidden="true"></span>
</div>
<section class="fp-wrap" aria-label="Komunikat o niedostępnej treści">
    <div class="fp-box fp-box-note" style="display:flex;gap:1.25rem;align-items:flex-start">
        <span aria-hidden="true" style="display:flex;flex:none;width:3.25rem;height:3.25rem;align-items:center;justify-content:center;border:2px solid #1d1d1a;border-radius:9999px;background:#fff;color:#1d1d1a;font-size:1.25rem"><i class="fa-solid {{ $icon }}"></i></span>
        <div>
            <p class="fp-p" style="font-size:1.1rem;margin:0">{{ $message }}</p>
            <p class="fp-p" style="margin:.75rem 0 0">Sprawdź <a href="{{ route('exclusions.index') }}">listę treści tymczasowo wyłączonych</a> albo wróć na stronę.</p>
        </div>
    </div>

    <div style="margin-top:1.5rem;display:flex;flex-wrap:wrap;gap:.75rem">
        <a href="{{ $backUrl }}" class="fp-btn"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>{{ $backLabel }}</a>
        <a href="{{ route('contact.show') }}" class="fp-link" style="align-self:center">Zapytaj o tę treść</a>
    </div>
</section>
