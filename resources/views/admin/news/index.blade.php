@extends('admin.layout')

@section('title', 'Aktualności')

@section('content')
    <div class="mb-4 flex items-center justify-end gap-2">
        <a href="{{ route('admin.kategorie-newsow.index') }}"
            class="inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-sm font-bold text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2 bg-white hover:bg-brand-light">
            <i class="fa-solid fa-tags" aria-hidden="true"></i> Kategorie ({{ $categories->count() }})
        </a>
        <a href="{{ route('admin.newsy.eksport') }}"
            class="inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-sm font-bold text-muted hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark bg-white hover:bg-brand-light">
            <i class="fa-solid fa-file-csv" aria-hidden="true"></i> Eksportuj CSV
        </a>
        <a href="{{ route('admin.newsy.create') }}" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj aktualność
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

        <div id="bulk-bar" class="mb-3 hidden items-center gap-3 rounded-md bg-brand-light px-4 py-2">
            <span id="bulk-count" class="text-sm font-bold text-blue-800"></span>
            <select name="action" class="rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                <option value="publish">Opublikuj</option>
                <option value="unpublish">Cofnij publikację (szkic)</option>
                <option value="archive">Zarchiwizuj</option>
                <option value="trash">Przenieś do kosza</option>
            </select>
            <button type="button"
                @click="Alpine.store('confirm').ask('Wykonać tę operację na zaznaczonych pozycjach?').then(ok => { if (ok) $el.closest('form').submit() })"
                class="rounded bg-brand-dark px-3 py-1.5 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                Wykonaj
            </button>
        </div>

    {{-- Lista dla redaktora: tytuł jako główny link do edycji, pod nim kategoria, data i tagi; status słowami z datą;
         wyraźne „Edytuj”, pozostałe działania jako opisane ikony. Kontrast: ink/muted na bieli, linki brand-dark. --}}
    <div class="overflow-hidden rounded-md bg-white">
        <table class="w-full text-left text-sm">
            <caption class="sr-only">Aktualności — {{ $news->total() }} pozycji</caption>
            <thead class="bg-gray-50 text-sm text-ink">
                <tr>
                    <th scope="col" class="w-10 px-4 py-3">
                        <input type="checkbox" id="select-all" class="h-4 w-4 rounded border-gray-500" aria-label="Zaznacz wszystkie">
                    </th>
                    <th scope="col" class="px-2 py-3 font-bold">Aktualność</th>
                    <th scope="col" class="hidden w-56 px-4 py-3 font-bold md:table-cell">Status</th>
                    <th scope="col" class="px-4 py-3 text-right font-bold"><span class="sr-only">Działania</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($news as $item)
                    @php
                        $img = $item->imageUrlOrDefault();
                        $isLive = $item->is_published && $item->published_at?->isPast();
                        $isPlanned = $item->is_published && ! $isLive;
                        $meta = array_filter([
                            $item->category->name ?? null,
                            $item->published_at?->format('d.m.Y'),
                            $item->tags->isNotEmpty() ? $item->tags->pluck('name')->implode(', ') : null,
                        ]);
                    @endphp
                    <tr class="group align-middle hover:bg-gray-50 {{ $item->is_clone ? 'bg-amber-50' : '' }}">
                        <td class="px-4 py-2.5">
                            <input type="checkbox" name="ids[]" value="{{ $item->id }}" class="row-check h-4 w-4 rounded border-gray-500" aria-label="Zaznacz: {{ $item->title }}">
                        </td>
                        <td class="px-2 py-2.5">
                            <div class="flex items-center gap-3">
                                @if ($img)
                                    <img src="{{ $img }}" alt="" class="hidden h-12 w-16 flex-none rounded object-cover sm:block" loading="lazy"
                                         style="object-position: {{ $item->image_focal_x ?? 50 }}% {{ $item->image_focal_y ?? 50 }}%">
                                @else
                                    <span class="hidden h-12 w-16 flex-none items-center justify-center rounded bg-gray-100 text-muted sm:flex" title="Bez zdjęcia"><i class="fa-regular fa-image" aria-hidden="true"></i><span class="sr-only">Bez zdjęcia</span></span>
                                @endif
                                <div class="min-w-0">
                                    <a href="{{ route('admin.newsy.edit', $item) }}" class="text-[0.9375rem] font-bold leading-snug text-ink underline-offset-4 hover:text-brand-dark hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                        @if ($item->is_clone)<span class="text-amber-900">Kopia: </span>@endif{{ $item->title }}
                                    </a>
                                    <p class="mt-0.5 text-[0.8125rem] text-muted">{{ $meta ? implode(' · ', $meta) : 'bez kategorii' }}</p>
                                    {{-- Na telefonie status pod tytułem. --}}
                                    <p class="mt-0.5 text-sm font-bold md:hidden {{ $isLive ? 'text-ink' : 'text-muted' }}">{{ $isLive ? 'Opublikowana' : ($isPlanned ? 'Zaplanowana' : 'Szkic') }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="hidden whitespace-nowrap px-4 py-2.5 text-sm md:table-cell">
                            @if ($isLive)
                                <span class="inline-flex items-center gap-1.5 text-ink"><i class="fa-solid fa-circle-check text-brand-dark" aria-hidden="true"></i> <strong>Opublikowana</strong> <span class="text-muted">· {{ $item->published_at->format('d.m.Y') }}</span></span>
                            @elseif ($isPlanned)
                                <span class="inline-flex items-center gap-1.5 text-ink"><i class="fa-solid fa-clock text-brand-dark" aria-hidden="true"></i> <strong>Zaplanowana</strong> <span class="text-muted">· {{ $item->published_at?->format('d.m H:i') }}</span></span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-muted" title="Niewidoczna na stronie"><i class="fa-regular fa-file-lines" aria-hidden="true"></i> <strong>Szkic</strong><span class="sr-only"> — niewidoczna na stronie</span></span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.newsy.edit', $item) }}" class="inline-flex min-h-11 items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 text-sm font-bold text-ink hover:border-brand-dark hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i> Edytuj<span class="sr-only">: {{ $item->title }}</span>
                                </a>
                                @php $iconBtn = 'inline-flex h-11 w-11 items-center justify-center rounded-md text-muted hover:bg-gray-100 hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark'; @endphp
                                @if ($isLive)
                                    <a href="{{ route('news.show', $item) }}" target="_blank" rel="noopener" class="{{ $iconBtn }}" title="Zobacz na stronie"><i class="fa-solid fa-eye" aria-hidden="true"></i><span class="sr-only">Zobacz na stronie: {{ $item->title }} (nowa karta)</span></a>
                                @else
                                    <a href="{{ $item->previewUrl() }}" target="_blank" rel="noopener" class="{{ $iconBtn }}" title="Podgląd wersji roboczej (link ważny 14 dni)"><i class="fa-solid fa-eye" aria-hidden="true"></i><span class="sr-only">Podgląd wersji roboczej: {{ $item->title }} (nowa karta)</span></a>
                                @endif
                                <button type="submit" form="clone-{{ $item->id }}" class="{{ $iconBtn }}" title="Utwórz kopię"><i class="fa-solid fa-copy" aria-hidden="true"></i><span class="sr-only">Utwórz kopię: {{ $item->title }}</span></button>
                                <button type="submit" form="delete-{{ $item->id }}" class="{{ $iconBtn }} hover:text-red-700" title="Przenieś do kosza"><i class="fa-solid fa-trash" aria-hidden="true"></i><span class="sr-only">Przenieś do kosza: {{ $item->title }}</span></button>
                            </div>
                        </td>
                    </tr>
                @empty
                    @include('admin.partials.empty-state', ['icon' => 'fa-newspaper', 'title' => 'Nie ma jeszcze aktualności', 'text' => 'Aktualności pojawiają się na stronie głównej i w archiwum. Zacznij od krótkiej informacji o tym, co u Was nowego.', 'route' => 'admin.newsy.create', 'action' => 'Dodaj aktualność', 'colspan' => 4])
                @endforelse
            </tbody>
        </table>
    </div>
    </form>

    {{-- Formularze kopii i usuwania poza formularzem zbiorczym (formularze nie mogą być zagnieżdżone). --}}
    @foreach ($news as $item)
        <form id="clone-{{ $item->id }}" method="POST" action="{{ route('admin.newsy.klonuj', $item) }}" class="hidden">@csrf</form>
        <form id="delete-{{ $item->id }}" method="POST" action="{{ route('admin.newsy.destroy', $item) }}" class="hidden"
              data-confirm="Przenieść aktualność „{{ $item->title }}” do kosza?" @if ($item->clones_count > 0) data-clone-count="{{ $item->clones_count }}" @endif>
            @csrf
            @method('DELETE')
        </form>
    @endforeach

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
