{{--
    Objaśnienie pod znakiem zapytania: przycisk „?” obok etykiety rozwija tekst pomocy.
    Klawiatura: Enter/Spacja otwiera, Escape zamyka; czytnik słyszy „Pomoc: <temat>” i stan rozwinięcia.
    Expects: $text (string, może zawierać HTML — przekazuj już escapowany), $topic (np. etykieta pola).
    Optional: $id.
--}}
@php $helpId = $id ?? 'help-'.\Illuminate\Support\Str::random(8); @endphp
<span x-data="{ open: false }" class="relative inline-flex align-middle" @keydown.escape.stop="open = false; $refs.btn.focus()" @click.outside="open = false">
    <button type="button" x-ref="btn" @click="open = !open" :aria-expanded="open.toString()" aria-expanded="false" aria-controls="{{ $helpId }}"
        class="ml-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
        :class="open && 'bg-brand-dark text-white hover:bg-ink'">
        <span aria-hidden="true">?</span><span class="sr-only">Pomoc: {{ $topic ?? 'objaśnienie' }}</span>
    </button>
    <span id="{{ $helpId }}" x-show="open" x-cloak role="note"
        class="absolute left-0 top-full z-30 mt-2 block w-72 max-w-[calc(100vw-2rem)] rounded-md bg-ink p-3 text-left text-xs font-normal leading-relaxed text-white shadow-lg">
        {!! $text !!}
    </span>
</span>
