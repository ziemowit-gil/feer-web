{{--
    Jeden przycisk na stronie „Dostępność": otwiera panel ułatwień dostępu z paska górnego (kontrast, rozmiar tekstu,
    odstępy itd.) przez zdarzenie okna „a11y-open", które nasłuchuje pasek górny. Bez JS przycisk jest ukryty (panel
    wymaga Alpine), więc nie udaje działania, którego nie ma.
--}}
<div x-data x-cloak class="mt-6">
    <button type="button" @click="window.dispatchEvent(new CustomEvent('a11y-open'))"
        class="inline-flex min-h-12 items-center gap-2 rounded-md bg-brand px-6 text-base font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
        <i class="fa-solid fa-universal-access" aria-hidden="true"></i>
        Włącz ułatwienia dostępności
    </button>
</div>
