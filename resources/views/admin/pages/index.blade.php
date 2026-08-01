@extends('admin.layout')

@section('title', 'Strony')

@section('content')
    @include('admin.partials.content-nav-tabs')

    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.podstrony.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
            <i class="fa-solid fa-plus"></i> Dodaj stronę
        </a>
    </div>

    @include('admin.partials.list-filters', [
        'action' => route('admin.podstrony.index'),
        'q' => $q,
        'status' => $status,
        'sort' => $sort,
        'sortOptions' => ['default' => 'Domyślne (kolejność)', 'title_asc' => 'Tytuł A–Z', 'title_desc' => 'Tytuł Z–A'],
    ])

    <form id="bulk-pages-form" method="POST" action="{{ route('admin.podstrony.bulk') }}">
        @csrf

        <div id="bulk-pages-bar" class="mb-3 hidden items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2">
            <span id="bulk-pages-count" class="text-sm font-bold text-blue-800"></span>
            <select name="action" class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                <option value="publish">Opublikuj</option>
                <option value="unpublish">Cofnij publikację (szkic)</option>
                <option value="trash">Przenieś do kosza</option>
            </select>
            <button type="button"
                @click="Alpine.store('confirm').ask('Wykonać tę operację na zaznaczonych stronach?').then(ok => { if (ok) $el.closest('form').submit() })"
                class="rounded bg-brand px-3 py-1.5 text-sm font-bold text-white hover:bg-brand-dark">
                Wykonaj
            </button>
        </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="w-8 px-4 py-3">
                        <input type="checkbox" id="pages-select-all" class="rounded border-gray-300" aria-label="Zaznacz wszystkie strony">
                    </th>
                    <th class="px-4 py-3">Tytuł</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3">Typ</th>
                    <th class="px-4 py-3">Kolejność</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($pages as $page)
                    <tr @if ($page->is_featured) class="ring-1 ring-inset ring-amber-300 bg-amber-50/40" @endif>
                        <td class="px-4 py-3">
                            @unless ($page->is_system)
                                <input type="checkbox" name="ids[]" value="{{ $page->id }}" class="page-row-check rounded border-gray-300" aria-label="Zaznacz {{ $page->title }}">
                            @else
                                <span class="block h-4 w-4"></span>
                            @endunless
                        </td>
                        <td class="px-4 py-3 font-medium">
                            @if ($page->parent_id)
                                <span class="pl-4 text-muted">↳</span>
                            @endif
                            {{ $page->title }}
                            @if ($page->parent)
                                <span class="ml-1 text-xs font-normal text-muted">(w: {{ $page->parent->title }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-muted">/{{ $page->slug }}</td>
                        <td class="px-4 py-3">
                            @php
                                $typeIcons = ['standard' => 'fa-file-lines', 'event' => 'fa-calendar-day', 'schedule' => 'fa-calendar-days', 'about' => 'fa-people-group', 'faq' => 'fa-circle-question', 'bip_move' => 'fa-landmark'];
                            @endphp
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-ink" title="Typ strony">
                                <i class="fa-solid {{ $typeIcons[$page->type] ?? 'fa-file-lines' }} text-muted" aria-hidden="true"></i>
                                {{ \App\Models\Page::TYPES[$page->type] ?? $page->type }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.podstrony.kolejnosc', $page) }}" class="flex items-center gap-1">
                                @csrf
                                @method('PATCH')
                                <input type="number" name="order" min="0" value="{{ $page->order }}" aria-label="Kolejność strony {{ $page->title }}"
                                    class="w-16 rounded border-gray-300 py-1 text-sm focus:border-brand focus:ring-brand">
                                <button type="submit" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" title="Zapisz kolejność"><i class="fa-solid fa-check"></i></button>
                            </form>
                        </td>
                        <td class="px-4 py-3">
                            @if ($page->is_featured)
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700"><i class="fa-solid fa-star"></i> Wyróżniona</span>
                            @endif
                            @if ($page->is_published && ($page->publish_at === null || $page->publish_at->isPast()))
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Opublikowana</span>
                            @elseif ($page->is_published)
                                <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700" title="Pojawi się {{ $page->publish_at?->format('d.m.Y H:i') }}"><i class="fa-regular fa-clock"></i> Zaplanowana</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-500">Szkic</span>
                            @endif
                            @if ($page->is_disabled)
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700"><i class="fa-solid fa-ban"></i> Wyłączona</span>
                            @endif
                            @if ($page->isWip())
                                <span class="rounded-full bg-orange-100 px-2 py-0.5 text-xs font-bold text-orange-700"><i class="fa-solid fa-person-digging"></i> W przygotowaniu</span>
                            @endif
                            @if (! $page->parent_id && $page->show_in_menu)
                                <span class="rounded-full bg-brand-light px-2 py-0.5 text-xs font-bold text-brand">W menu</span>
                            @endif
                            @if ($page->is_system)
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700"><i class="fa-solid fa-lock"></i> Systemowa</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <form method="POST" action="{{ route('admin.podstrony.wyroznienie', $page) }}">
                                    @csrf
                                    @method('PATCH')
                                    @if ($page->is_featured)
                                        <button type="submit" class="text-amber-500 hover:text-muted" title="Usuń wyróżnienie" aria-label="Usuń wyróżnienie strony {{ $page->title }}"><i class="fa-solid fa-star" aria-hidden="true"></i></button>
                                    @else
                                        <button type="submit" class="text-muted hover:text-amber-500" title="Wyróżnij stronę" aria-label="Wyróżnij stronę {{ $page->title }}"><i class="fa-regular fa-star" aria-hidden="true"></i></button>
                                    @endif
                                </form>
                                @unless ($page->parent_id)
                                    <a href="{{ route('admin.podstrony.create', ['parent_id' => $page->id]) }}"
                                        class="inline-flex items-center gap-1 text-xs font-bold text-brand hover:text-brand-dark" title="Dodaj podstronę jako podrzędną w „{{ $page->title }}”">
                                        <i class="fa-solid fa-plus" aria-hidden="true"></i> podstrona
                                    </a>
                                @endunless
                                @if ($page->is_published && ($page->publish_at === null || $page->publish_at->isPast()))
                                    <a href="{{ route('page.show', $page) }}" target="_blank" class="text-muted hover:text-brand" title="Podgląd"><i class="fa-solid fa-eye"></i></a>
                                @else
                                    <a href="{{ $page->previewUrl() }}" target="_blank" rel="noopener" class="text-amber-600 hover:text-amber-700" title="{{ $page->is_published ? 'Zaplanowana — podgląd wersji roboczej' : 'Podgląd wersji roboczej (link ważny 14 dni)' }}"><i class="fa-solid fa-eye"></i></a>
                                @endif
                                <a href="{{ route('admin.podstrony.edit', $page) }}" class="text-muted hover:text-brand" title="Edytuj"><i class="fa-solid fa-pen"></i></a>
                                <form method="POST" action="{{ route('admin.podstrony.clone', $page) }}" data-confirm="Zduplikować stronę „{{ $page->title }}"? Kopia zostanie zapisana jako szkic.">
                                    @csrf
                                    <button type="submit" class="text-muted hover:text-brand" title="Klonuj stronę" aria-label="Klonuj stronę {{ $page->title }}"><i class="fa-solid fa-clone" aria-hidden="true"></i></button>
                                </form>
                                <form method="POST" action="{{ route('admin.podstrony.widocznosc', $page) }}">
                                    @csrf
                                    @method('PATCH')
                                    @if ($page->is_published)
                                        <button type="submit" class="text-muted hover:text-amber-600" title="Ukryj (cofnij publikację)"><i class="fa-solid fa-eye-slash"></i></button>
                                    @else
                                        <button type="submit" class="text-muted hover:text-green-600" title="Opublikuj (pokaż)"><i class="fa-solid fa-eye"></i></button>
                                    @endif
                                </form>
                                <form method="POST" action="{{ route('admin.podstrony.wylacz', $page) }}">
                                    @csrf
                                    @method('PATCH')
                                    @if ($page->is_disabled)
                                        <button type="submit" class="text-red-600 hover:text-green-600" title="Włącz stronę (jest wyłączona)"><i class="fa-solid fa-ban"></i></button>
                                    @else
                                        <button type="submit" class="text-muted hover:text-red-600" title="Wyłącz stronę"><i class="fa-solid fa-power-off"></i></button>
                                    @endif
                                </form>
                                @if ($page->is_system)
                                    <span class="cursor-not-allowed text-gray-300" title="Strony systemowej nie można usunąć"><i class="fa-solid fa-trash"></i></span>
                                @else
                                    <form method="POST" action="{{ route('admin.podstrony.destroy', $page) }}" data-confirm="Usunąć stronę „{{ $page->title }}"?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń stronę {{ $page->title }}"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-muted">Brak stron. Dodaj pierwszą powyżej.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </form>

    @if ($pages->hasPages())
        <div class="mt-4">{{ $pages->links() }}</div>
    @endif

    <script>
        (function () {
            const selectAll = document.getElementById('pages-select-all');
            const bar       = document.getElementById('bulk-pages-bar');
            const countEl   = document.getElementById('bulk-pages-count');

            function updateBar() {
                const checked = document.querySelectorAll('.page-row-check:checked');
                bar.classList.toggle('hidden', checked.length === 0);
                bar.classList.toggle('flex', checked.length > 0);
                countEl.textContent = 'Zaznaczono: ' + checked.length;
            }

            selectAll.addEventListener('change', () => {
                document.querySelectorAll('.page-row-check').forEach(cb => { cb.checked = selectAll.checked; });
                updateBar();
            });

            document.querySelectorAll('.page-row-check').forEach(cb => {
                cb.addEventListener('change', updateBar);
            });
        })();
    </script>
@endsection
