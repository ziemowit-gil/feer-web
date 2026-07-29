{{--
    Komunikat wyjaśniający zakres strefy na stronie. Oczekuje $szoPanelUrl
    (adres Panelu Współpracownika w SZO) lub null, gdy adres nie jest ustawiony.
--}}
<div role="note" class="mb-8 flex items-start gap-3 rounded-lg border border-brand/20 bg-brand-light px-4 py-3 text-sm text-brand-dark">
    <i class="fa-solid fa-circle-info mt-0.5 text-base" aria-hidden="true"></i>
    <p class="min-w-0">
        Na tej stronie znajdziesz wyłącznie komunikaty i odnośniki. Pełny dostęp masz w
        <strong>Panelu Współpracownika</strong> w Systemie Zarządzania Organizacją
        @if (! empty($szoPanelUrl))
            —
            <a href="{{ $szoPanelUrl }}" target="_blank" rel="noopener" class="font-bold underline hover:no-underline">
                przejdź do panelu
                <i class="fa-solid fa-arrow-up-right-from-square text-xs" aria-hidden="true"></i>
                <span class="sr-only">(otwiera się w nowej karcie)</span>
            </a>.
        @else
            .
        @endif
    </p>
</div>
