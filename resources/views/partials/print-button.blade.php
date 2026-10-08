{{-- Przycisk „Drukuj tę stronę" (włączany w Ustawieniach). Układ do druku: patrz <style media="print"> w layouts/site. --}}
@if ($siteSettings->show_print_button ?? true)
    <button type="button" onclick="window.print()" class="print-btn no-print">
        <i class="fa-solid fa-print" aria-hidden="true"></i> Drukuj tę stronę
    </button>
    @once
        <style>
            .print-btn { display: inline-flex; align-items: center; gap: .5rem; min-height: 2.5rem; padding: .4rem 1rem; border: 2px solid #1d1d1a; border-radius: .375rem; background: #fff; color: #1d1d1a; font-size: .9rem; font-weight: 700; cursor: pointer; }
            .print-btn:hover { background: #1d1d1a; color: #fff; }
            .print-btn:focus-visible { outline: 3px solid var(--color-brand); outline-offset: 3px; }
        </style>
    @endonce
@endif
