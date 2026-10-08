{{--
    Pasek filtrów list admina (GET). Parametry:
      $action      — URL formularza (route indexu)
      $status      — bieżąca wartość statusu ('', 'published', 'draft')
      $sort        — bieżąca wartość sortowania
      $sortOptions — [wartość => etykieta]
      $categories  — (opcjonalnie) kolekcja kategorii {id,name} do selecta
      $categoryId  — (opcjonalnie) bieżąca kategoria
      $q           — (opcjonalnie) fraza wyszukiwania po tytule
      $total       — (opcjonalnie) łączna liczba wyników do wyświetlenia
--}}
@php
    $categories ??= null;
    $categoryId ??= '';
    $q ??= '';
    $total ??= null;
    $typeGroups ??= null;
    $typeValue ??= '';
    $hasFilters = filled($q) || filled($status) || filled($categoryId) || filled($typeValue) || (filled($sort) && $sort !== array_key_first($sortOptions));
@endphp

@once
    <style>
        /* Pasek filtrów w zwykłym CSS (nie zależy od zbudowanych klas Tailwinda): jeden rząd, zawija się na wąskich ekranach. */
        .lf-bar { display: flex; flex-wrap: wrap; align-items: flex-end; gap: .75rem 1rem; margin-bottom: 1rem; padding: .75rem 1rem; border: 1px solid #e5e7eb; border-radius: .75rem; background: #fff; }
        .lf-search { flex: 1 1 14rem; min-width: 12rem; }
        .lf-field { display: flex; flex-direction: column; }
        .lf-label { margin-bottom: .25rem; font-size: .7rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #4b5563; }
        .lf-bar select, .lf-bar input[type="text"] { min-height: 2.5rem; border: 1px solid #d1d5db; border-radius: .5rem; font-size: .875rem; }
        .lf-bar select { padding: .4rem 2rem .4rem .75rem; }
        .lf-bar input[type="text"] { width: 100%; padding: .4rem .75rem .4rem 2rem; }
        .lf-bar select:focus, .lf-bar input[type="text"]:focus { outline: 2px solid var(--color-brand); outline-offset: 1px; border-color: var(--color-brand); }
        .lf-actions { display: flex; align-items: center; gap: .5rem; }
        .lf-count { margin-left: auto; align-self: center; font-size: .875rem; color: #4b5563; white-space: nowrap; }
    </style>
@endonce
<form method="GET" action="{{ $action }}" class="lf-bar" role="search" aria-label="Filtry listy">
    <div class="lf-search lf-field">
        <label for="filter-q" class="lf-label">Szukaj</label>
        <div class="relative">
            <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-muted" aria-hidden="true"></i>
            <input type="text" id="filter-q" name="q" value="{{ $q }}" placeholder="Tytuł…"
                class="w-full rounded border-gray-300 py-1.5 pl-8 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
        </div>
    </div>
    <div class="lf-field">
        <label for="filter-status" class="lf-label">Status</label>
        <select id="filter-status" name="status" onchange="this.form.submit()"
            class="rounded border-gray-300 py-1.5 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
            <option value="">Wszystkie</option>
            <option value="published" @selected($status === 'published')>Opublikowane</option>
            <option value="draft" @selected($status === 'draft')>Szkice</option>
        </select>
    </div>

    @if ($typeGroups)
        <div class="lf-field">
            <label for="filter-type" class="lf-label">Typ strony</label>
            <select id="filter-type" name="type" onchange="this.form.submit()"
                class="rounded border-gray-300 py-1.5 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
                <option value="">Wszystkie typy</option>
                @foreach ($typeGroups as $group => $keys)
                    <optgroup label="{{ $group }}">
                        @foreach ($keys as $k)
                            @if (isset(\App\Models\Page::TYPES[$k]))
                                <option value="{{ $k }}" @selected($typeValue === $k)>{{ trim(\Illuminate\Support\Str::before(\App\Models\Page::TYPES[$k], ' (')) }}</option>
                            @endif
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
    @endif

    @if ($categories)
        <div class="lf-field">
            <label for="filter-category" class="lf-label">Kategoria</label>
            <select id="filter-category" name="category" onchange="this.form.submit()"
                class="rounded border-gray-300 py-1.5 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
                <option value="">Wszystkie</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" @selected((string) $categoryId === (string) $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="lf-field">
        <label for="filter-sort" class="lf-label">Sortowanie</label>
        <select id="filter-sort" name="sort" onchange="this.form.submit()"
            class="rounded border-gray-300 py-1.5 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
            @foreach ($sortOptions as $value => $label)
                <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="lf-actions">
        <button type="submit" class="rounded-lg bg-brand px-4 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2" style="min-height:2.5rem">Filtruj</button>
        @if ($hasFilters)
            <a href="{{ $action }}" class="rounded px-2 py-1.5 text-sm font-bold text-ink underline hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">Wyczyść</a>
        @endif
    </div>
    @if ($total !== null)
        <span class="lf-count" aria-live="polite">
            {{ $total }}
            {{ $total === 1 ? 'wynik' : ($total >= 2 && $total <= 4 ? 'wyniki' : 'wyników') }}
        </span>
    @endif
</form>
