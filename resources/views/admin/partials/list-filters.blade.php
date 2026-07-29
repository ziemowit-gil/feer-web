{{--
    Pasek filtrów list admina (GET). Parametry:
      $action      — URL formularza (route indexu)
      $status      — bieżąca wartość statusu ('', 'published', 'draft')
      $sort        — bieżąca wartość sortowania
      $sortOptions — [wartość => etykieta]
      $categories  — (opcjonalnie) kolekcja kategorii {id,name} do selecta
      $categoryId  — (opcjonalnie) bieżąca kategoria
--}}
@php
    $categories ??= null;
    $categoryId ??= '';
    $hasFilters = filled($status) || filled($categoryId) || (filled($sort) && $sort !== array_key_first($sortOptions));
@endphp

<form method="GET" action="{{ $action }}" class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-3">
    <div>
        <label for="filter-status" class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Status</label>
        <select id="filter-status" name="status" onchange="this.form.submit()"
            class="rounded border-gray-300 py-1.5 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
            <option value="">Wszystkie</option>
            <option value="published" @selected($status === 'published')>Opublikowane</option>
            <option value="draft" @selected($status === 'draft')>Szkice</option>
        </select>
    </div>

    @if ($categories)
        <div>
            <label for="filter-category" class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Kategoria</label>
            <select id="filter-category" name="category" onchange="this.form.submit()"
                class="rounded border-gray-300 py-1.5 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
                <option value="">Wszystkie</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" @selected((string) $categoryId === (string) $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div>
        <label for="filter-sort" class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Sortowanie</label>
        <select id="filter-sort" name="sort" onchange="this.form.submit()"
            class="rounded border-gray-300 py-1.5 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
            @foreach ($sortOptions as $value => $label)
                <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <button type="submit" class="rounded bg-brand px-3 py-1.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
        Filtruj
    </button>
    @if ($hasFilters)
        <a href="{{ $action }}" class="rounded px-2 py-1.5 text-sm text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">Wyczyść</a>
    @endif
</form>
