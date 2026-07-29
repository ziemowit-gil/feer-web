{{--
    Przycisk „Zarządzaj tożsamością" — prowadzi do zarządzania tożsamością
    współpracownika w SZO ({adres bazowy SZO}/tozsamosc). Wyświetlany w strefach
    współpracownika: na stronie /strefa, na stronach typu „Strefa współpracownika"
    (internal_hub) oraz na stronie logowania do stref wewnętrznych. Otwiera się
    w nowej karcie. Gdy adres SZO nie jest skonfigurowany — przycisk się nie
    pojawia. Opcjonalny $margin=false usuwa dolny odstęp (np. gdy pod przyciskiem
    jest jeszcze tekst pomocniczy).
--}}
@php $tozsamoscUrl = $siteSettings->szoTozsamoscUrl(); @endphp
@if ($tozsamoscUrl)
    <div class="{{ ($margin ?? true) ? 'mb-8' : 'mb-3' }}">
        <a href="{{ $tozsamoscUrl }}" target="_blank" rel="noopener"
            class="inline-flex items-center gap-2 rounded-lg bg-brand px-6 py-3 font-bold text-white transition hover:bg-brand-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
            <i class="fa-solid fa-id-card" aria-hidden="true"></i>
            Zarządzaj tożsamością
            <i class="fa-solid fa-arrow-up-right-from-square text-xs" aria-hidden="true"></i>
            <span class="sr-only">(otwiera się w nowej karcie)</span>
        </a>
    </div>
@endif
