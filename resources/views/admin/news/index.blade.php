@extends('admin.layout')

@section('title', 'Aktualności')

@section('content')
    <div class="mb-4 flex items-center justify-end gap-2">
        <a href="{{ route('admin.newsy.eksport') }}"
            class="inline-flex items-center gap-1.5 rounded border border-gray-300 bg-white px-3 py-2 text-sm font-bold text-muted hover:border-gray-400 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
            <i class="fa-solid fa-file-csv" aria-hidden="true"></i> Eksportuj CSV
        </a>
        <a href="{{ route('admin.newsy.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj news
        </a>
    </div>

    @include('admin.partials.list-filters', [
        'action' => route('admin.newsy.index'),
        'q' => $q,
        'status' => $status,
        'categories' => $categories,
        'categoryId' => $category,
        'sort' => $sort,
        'sortOptions' => ['date_desc' => 'Najnowsze', 'date_asc' => 'Najstarsze', 'title_asc' => 'Tytuł A–Z', 'title_desc' => 'Tytuł Z–A'],
        'total' => $news->total(),
    ])

    <form id="bulk-form" method="POST" action="{{ route('admin.newsy.bulk') }}">
        @csrf

        <div id="bulk-bar" class="mb-3 hidden items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2">
            <span id="bulk-count" class="text-sm font-bold text-blue-800"></span>
            <select name="action" class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                <option value="publish">Opublikuj</option>
                <option value="unpublish">Cofnij publikację (szkic)</option>
                <option value="archive">Zarchiwizuj</option>
                <option value="trash">Przenieś do kosza</option>
            </select>
            <button type="button"
                @click="Alpine.store('confirm').ask('Wykonać tę operację na zaznaczonych pozycjach?').then(ok => { if (ok) $el.closest('form').submit() })"
                class="rounded bg-brand px-3 py-1.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                Wykonaj
            </button>
        </div>

    {{-- Tabela z konfigurowalnymi kolumnami --}}
    <div
        x-data="{
            open: false,
            cols: (() => {
                try { return { thumb: true, cat: true, tags: false, date: true, ...JSON.parse(localStorage.getItem('news-cols') || '{}') }; }
                catch (e) { return { thumb: true, cat: true, tags: false, date: true }; }
            })(),
        }"
        x-effect="localStorage.setItem('news-cols', JSON.stringify(cols))"
        x-cloak>

        {{-- Przycisk wyboru kolumn --}}
        <div class="mb-2 flex justify-end">
            <div class="relative">
                <button type="button" @click="open = !open" :aria-expanded="open"
                    class="inline-flex items-center gap-1.5 rounded border border-gray-300 bg-white px-3 py-1.5 text-xs font-bold text-muted hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                    <i class="fa-solid fa-sliders" aria-hidden="true"></i> Kolumny
                    <i class="fa-solid fa-chevron-down text-[10px] transition" :class="open ? 'rotate-180' : ''" aria-hidden="true"></i>
                </button>
                <div x-show="open" @click.outside="open = false" x-transition
                    class="absolute right-0 top-full z-20 mt-1 min-w-[160px] rounded-lg border border-gray-200 bg-white py-2 shadow-lg">
                    <p class="mb-1 px-3 text-[10px] font-bold uppercase tracking-wide text-muted">Pokaż kolumny</p>
                    @foreach (['thumb' => 'Miniatura', 'cat' => 'Kategoria', 'tags' => 'Tagi', 'date' => 'Data'] as $key => $label)
                        <label class="flex cursor-pointer items-center gap-2 px-3 py-1.5 hover:bg-gray-50">
                            <input type="checkbox" x-model="cols.{{ $key }}" class="rounded border-gray-300 text-brand focus:ring-brand">
                            <span class="text-sm">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                    <tr>
                        <th class="w-8 px-4 py-3">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300" aria-label="Zaznacz wszystkie">
                        </th>
                        <th x-show="cols.thumb" class="w-16 px-4 py-3">Foto</th>
                        <th class="px-4 py-3">Tytuł</th>
                        <th x-show="cols.cat" class="px-4 py-3">Kategoria</th>
                        <th x-show="cols.tags" class="px-4 py-3">Tagi</th>
                        <th x-show="cols.date" class="px-4 py-3">Data</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Akcje</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($news as $item)
                        @php $img = $item->imageUrlOrDefault(); @endphp
                        <tr class="{{ $item->is_clone ? 'bg-amber-50' : '' }}">
                            <td class="px-4 py-3">
                                <input type="checkbox" name="ids[]" value="{{ $item->id }}" class="row-check rounded border-gray-300" aria-label="Zaznacz {{ $item->title }}">
                            </td>
                            <td x-show="cols.thumb" class="px-4 py-2">
                                @if ($img)
                                    <img src="{{ $img }}" alt="" class="h-10 w-14 rounded object-cover" loading="lazy"
                        style="object-position: {{ $item->image_focal_x ?? 50 }}% {{ $item->image_focal_y ?? 50 }}%">
                                @else
                                    <div class="h-10 w-14 rounded bg-gray-100 flex items-center justify-center">
                                        <i class="fa-regular fa-image text-gray-300 text-xs" aria-hidden="true"></i>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium">
                                @if ($item->is_featured)
                                    <i class="fa-solid fa-star mr-1 text-amber-400" title="Wyróżniony" aria-hidden="true"></i>
                                @endif
                                @if ($item->is_clone)
                                    <span class="mr-1 inline-flex items-center gap-1 rounded bg-amber-100 px-1.5 py-0.5 text-xs font-bold text-amber-700">
                                        <i class="fa-solid fa-copy" aria-hidden="true"></i> Kopia
                                    </span>
                                @endif
                                {{ $item->title }}
                            </td>
                            <td x-show="cols.cat" class="px-4 py-3 text-muted">{{ $item->category->name ?? '—' }}</td>
                            <td x-show="cols.tags" class="px-4 py-3 text-muted">{{ $item->tags->pluck('name')->implode(', ') ?: '—' }}</td>
                            <td x-show="cols.date" class="px-4 py-3 text-muted">{{ $item->published_at?->format('d.m.Y') }}</td>
                            <td class="px-4 py-3">
                                @if ($item->is_published && $item->published_at?->isPast())
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Opublikowany</span>
                                @elseif ($item->is_published)
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">Zaplanowany</span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-500">Szkic</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-3">
                                    @if ($item->is_published && $item->published_at <= now())
                                        <a href="{{ route('news.show', $item) }}" target="_blank" class="text-muted hover:text-brand" title="Podgląd"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>
                                    @else
                                        <a href="{{ $item->previewUrl() }}" target="_blank" rel="noopener" class="text-amber-600 hover:text-amber-700" title="Podgląd wersji roboczej (link ważny 14 dni)"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>
                                    @endif
                                    <a href="{{ route('admin.newsy.edit', $item) }}" class="text-muted hover:text-brand" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                    <form method="POST" action="{{ route('admin.newsy.klonuj', $item) }}">
                                        @csrf
                                        <button type="submit" class="text-muted hover:text-brand" title="Klonuj"><i class="fa-solid fa-copy"></i></button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.newsy.destroy', $item) }}"
                                        data-confirm="Usunąć news „{{ $item->title }}"?"
                                        @if ($item->clones_count > 0) data-clone-count="{{ $item->clones_count }}" @endif>
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń news {{ $item->title }}"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-muted">Brak newsów. Dodaj pierwszy powyżej.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </form>

    @if ($news->hasPages())
        <div class="mt-4">{{ $news->links() }}</div>
    @endif

    <script>
        const selectAll = document.getElementById('select-all');
        const bar = document.getElementById('bulk-bar');
        const countEl = document.getElementById('bulk-count');

        function updateBar() {
            const checked = document.querySelectorAll('.row-check:checked');
            bar.classList.toggle('hidden', checked.length === 0);
            bar.classList.toggle('flex', checked.length > 0);
            countEl.textContent = `Zaznaczono: ${checked.length}`;
        }

        selectAll.addEventListener('change', () => {
            document.querySelectorAll('.row-check').forEach(cb => { cb.checked = selectAll.checked; });
            updateBar();
        });

        document.querySelectorAll('.row-check').forEach(cb => {
            cb.addEventListener('change', updateBar);
        });
    </script>
@endsection
