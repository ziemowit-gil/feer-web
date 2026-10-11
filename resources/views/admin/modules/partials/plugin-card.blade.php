{{-- Karta modułu instalowanego (rozszerzenia z katalogu modules/).
     Przycisk akcji jest skojarzony atrybutem form="…" z formularzem z partiala
     plugin-forms — dzięki temu karta może stać wewnątrz innego formularza
     (np. ustawień), a HTML pozostaje poprawny (bez zagnieżdżonych <form>).
     Parametry: $card (z App\Support\PluginModules::cards()), $filterable (bool), $heading (h2/h3, domyślnie h3). --}}
@php
    $manifest = $card['manifest'];
    $id = $card['id'];
    $status = $card['status'];
    $formId = 'plugin-module-form-'.$id;
    $titleId = 'plugin-module-'.$id.'-title';
    $heading = in_array($heading ?? 'h3', ['h2', 'h3', 'h4'], true) ? ($heading ?? 'h3') : 'h3';
@endphp
<li @if (! empty($filterable)) x-show="visible('plugin:{{ $id }}')" @endif
    class="flex flex-col gap-3 rounded-md p-4 {{ $status === 'active' ? 'bg-brand-light' : 'bg-gray-50' }}"
    aria-labelledby="{{ $titleId }}">
    <div class="flex items-start gap-3">
        <i class="fa-solid {{ $card['icon'] }} mt-0.5 w-5 flex-none text-center text-lg text-brand-dark" aria-hidden="true"></i>
        <div class="min-w-0 flex-1">
            <{{ $heading }} id="{{ $titleId }}" class="font-bold text-ink">{{ $manifest->name }}</{{ $heading }}>
            @if ($manifest->description)
                <p class="mt-1 text-sm text-muted">{{ $manifest->description }}</p>
            @endif
            @if ($manifest->requires)
                <p class="mt-1 text-xs text-muted">Wymaga: {{ implode(', ', $manifest->requires) }}</p>
            @endif
        </div>
    </div>

    <div class="mt-auto flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-ink">
            <span class="sr-only">Stan: </span>
            <i class="fa-solid {{ $status === 'active' ? 'fa-circle-check text-brand-dark' : ($status === 'inactive' ? 'fa-circle-pause text-muted' : 'fa-circle-down text-muted') }} mr-1" aria-hidden="true"></i>
            <span class="font-bold">{{ $card['status_label'] }}</span>
            <span class="text-xs text-muted">· v{{ $manifest->version }}</span>
        </p>

        @php
            $btn = 'inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
        @endphp
        @if ($status === 'active')
            <button type="submit" form="{{ $formId }}" aria-describedby="{{ $titleId }}"
                class="{{ $btn }} bg-white text-ink ring-1 ring-gray-500 hover:bg-gray-100">
                <i class="fa-solid fa-pause text-[10px]" aria-hidden="true"></i>
                Dezaktywuj
            </button>
        @elseif ($status === 'inactive')
            <button type="submit" form="{{ $formId }}" aria-describedby="{{ $titleId }}"
                class="{{ $btn }} bg-brand-dark text-white hover:bg-ink">
                <i class="fa-solid fa-play text-[10px]" aria-hidden="true"></i>
                Aktywuj
            </button>
        @else
            <button type="submit" form="{{ $formId }}" aria-describedby="{{ $titleId }}"
                class="{{ $btn }} bg-brand-dark text-white hover:bg-ink">
                <i class="fa-solid fa-download text-[10px]" aria-hidden="true"></i>
                Zainstaluj
            </button>
        @endif
    </div>
</li>
