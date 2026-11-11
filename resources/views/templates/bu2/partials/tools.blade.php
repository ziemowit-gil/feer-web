{{-- Pas pod treścią BU2: przycisk „Drukuj” (zawsze, jak na wzorze) i opcjonalnie data publikacji ($date). --}}
<div class="bu2-tools-row no-print">
    <button type="button" class="bu2-print" onclick="window.print()">
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 2h12v5H6zm-2 7h16a2 2 0 0 1 2 2v6h-4v5H6v-5H2v-6a2 2 0 0 1 2-2zm4 8v3h8v-6H8zm10-5a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/></svg>
        Drukuj
    </button>
    @if (! empty($date))
        <p class="bu2-date">Opublikowano: <time datetime="{{ $date->toDateString() }}">{{ $date->translatedFormat('j F Y') }}</time></p>
    @endif
</div>
