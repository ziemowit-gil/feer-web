@extends('admin.layout')

@section('title', 'Strony')

@section('content')
    @include('admin.partials.content-nav-tabs')

    <div class="mb-4 flex items-center justify-end gap-2">
        <a href="{{ route('admin.podstrony.eksport') }}"
            class="inline-flex items-center gap-1.5 rounded border border-gray-300 bg-white px-3 py-2 text-sm font-bold text-muted hover:border-gray-400 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
            <i class="fa-solid fa-file-csv" aria-hidden="true"></i> Eksportuj CSV
        </a>
        <a href="{{ route('admin.podstrony.create') }}"
            class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj stronę
        </a>
    </div>

    @include('admin.partials.list-filters', [
        'action'      => route('admin.podstrony.index'),
        'q'           => $q,
        'status'      => $status,
        'sort'        => $sort,
        'sortOptions' => ['default' => 'Domyślne (kolejność)', 'title_asc' => 'Tytuł A–Z', 'title_desc' => 'Tytuł Z–A'],
        'total'       => $pages->total(),
    ])

    <form id="bulk-pages-form" method="POST" action="{{ route('admin.podstrony.bulk') }}">
        @csrf

        {{-- Pasek operacji zbiorczych --}}
        <div id="bulk-pages-bar"
            class="mb-3 hidden items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2">
            <span id="bulk-pages-count" class="text-sm font-bold text-blue-800"></span>
            <select name="action" class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                <option value="publish">Opublikuj</option>
                <option value="unpublish">Cofnij publikację (szkic)</option>
                <option value="trash">Przenieś do kosza</option>
            </select>
            <button type="button"
                @click="Alpine.store('confirm').ask('Wykonać tę operację na zaznaczonych stronach?').then(ok => { if (ok) $el.closest('form').submit() })"
                class="rounded bg-brand px-3 py-1.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                Wykonaj
            </button>
        </div>

        <div
            x-data="{
                open: false,
                cols: (() => {
                    try { return { thumb: false, order: true, ...JSON.parse(localStorage.getItem('pages-cols') || '{}') }; }
                    catch (e) { return { thumb: false, order: true }; }
                })(),
            }"
            x-effect="localStorage.setItem('pages-cols', JSON.stringify(cols))"
            x-cloak>

            {{-- Konfigurator kolumn --}}
            <div class="mb-2 flex justify-end">
                <div class="relative">
                    <button type="button" @click="open = !open" :aria-expanded="open"
                        class="inline-flex items-center gap-1.5 rounded border border-gray-300 bg-white px-3 py-1.5 text-xs font-bold text-muted hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                        <i class="fa-solid fa-sliders" aria-hidden="true"></i> Kolumny
                        <i class="fa-solid fa-chevron-down text-[10px] transition" :class="open ? 'rotate-180' : ''" aria-hidden="true"></i>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-transition
                        class="absolute right-0 top-full z-20 mt-1 min-w-[150px] rounded-lg border border-gray-200 bg-white py-2 shadow-lg">
                        <p class="mb-1 px-3 text-[10px] font-bold uppercase tracking-wide text-muted">Pokaż kolumny</p>
                        @foreach (['thumb' => 'Miniatura', 'order' => 'Kolejność'] as $key => $label)
                            <label class="flex cursor-pointer items-center gap-2 px-3 py-1.5 hover:bg-gray-50">
                                <input type="checkbox" x-model="cols.{{ $key }}"
                                    class="rounded border-gray-300 text-brand focus:ring-brand">
                                <span class="text-sm">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50/80">
                        <tr>
                            <th class="w-8 px-4 py-3">
                                <input type="checkbox" id="pages-select-all"
                                    class="rounded border-gray-300 text-brand focus:ring-brand"
                                    aria-label="Zaznacz wszystkie strony">
                            </th>
                            <th x-show="cols.thumb" class="w-16 px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Foto</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Strona</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Status</th>
                            <th x-show="cols.order" class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Kolejność</th>
                            <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-muted">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @php
                            $typeColors = [
                                'standard'             => 'bg-gray-100 text-gray-700',
                                'event'                => 'bg-blue-50 text-blue-700',
                                'schedule'             => 'bg-indigo-50 text-indigo-700',
                                'about'                => 'bg-green-50 text-green-700',
                                'faq'                  => 'bg-amber-50 text-amber-700',
                                'training_institution' => 'bg-teal-50 text-teal-700',
                                'bip_move'             => 'bg-slate-100 text-slate-600',
                                'internal'             => 'bg-slate-100 text-slate-600',
                                'internal_hub'         => 'bg-purple-50 text-purple-700',
                                'links_hub'            => 'bg-violet-50 text-violet-700',
                                'wspolpraca'           => 'bg-orange-50 text-orange-700',
                                'legacy'               => 'bg-stone-100 text-stone-600',
                                'brand_assets'         => 'bg-amber-50 text-amber-700',
                                'about_person'         => 'bg-emerald-50 text-emerald-700',
                            ];
                        @endphp

                        @forelse ($pages as $page)
                            @php
                                $badgeClass = $typeColors[$page->type] ?? 'bg-gray-100 text-gray-700';
                            @endphp
                            <tr class="group hover:bg-gray-50/60 @if ($page->is_featured) bg-amber-50/40 @endif">

                                {{-- Checkbox --}}
                                <td class="px-4 py-3">
                                    @unless ($page->is_system)
                                        <input type="checkbox" name="ids[]" value="{{ $page->id }}"
                                            class="page-row-check rounded border-gray-300 text-brand focus:ring-brand"
                                            aria-label="Zaznacz {{ $page->title }}">
                                    @else
                                        <span class="block h-4 w-4"></span>
                                    @endunless
                                </td>

                                {{-- Miniatura --}}
                                <td x-show="cols.thumb" class="px-4 py-3">
                                    @if (filled($page->content_image))
                                        <img src="{{ $page->content_image }}" alt=""
                                            class="h-10 w-14 rounded object-cover" loading="lazy">
                                    @else
                                        <div class="flex h-10 w-14 items-center justify-center rounded bg-gray-100">
                                            <i class="fa-regular fa-image text-xs text-gray-300" aria-hidden="true"></i>
                                        </div>
                                    @endif
                                </td>

                                {{-- Tytuł + typ + slug --}}
                                <td class="px-4 py-3">
                                    @if ($page->parent_id)
                                        <span class="mr-1 text-gray-300" aria-hidden="true">↳</span>
                                    @endif
                                    <span class="font-semibold text-ink">{{ $page->title }}</span>
                                    @if ($page->parent)
                                        <span class="ml-1 text-xs text-muted">(w: {{ $page->parent->title }})</span>
                                    @endif
                                    <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                        <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $badgeClass }}">
                                            {{ \App\Models\Page::TYPES[$page->type] ?? $page->type }}
                                        </span>
                                        <span class="font-mono text-xs text-muted">/{{ $page->slug }}</span>
                                    </div>
                                </td>

                                {{-- Status --}}
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @if ($page->is_published && ($page->publish_at === null || $page->publish_at->isPast()))
                                            <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-bold text-green-700">
                                                <span class="h-1.5 w-1.5 rounded-full bg-green-500" aria-hidden="true"></span>Opublikowana
                                            </span>
                                        @elseif ($page->is_published)
                                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-bold text-blue-700"
                                                title="Pojawi się {{ $page->publish_at?->format('d.m.Y H:i') }}">
                                                <i class="fa-regular fa-clock" aria-hidden="true"></i> Zaplanowana
                                            </span>
                                        @else
                                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-bold text-gray-500">Szkic</span>
                                        @endif

                                        @if ($page->is_disabled)
                                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-bold text-red-700">
                                                <i class="fa-solid fa-ban" aria-hidden="true"></i> Wyłączona
                                            </span>
                                        @endif
                                        @if ($page->isWip())
                                            <span class="rounded-full bg-orange-100 px-2 py-0.5 text-[11px] font-bold text-orange-700">
                                                <i class="fa-solid fa-person-digging" aria-hidden="true"></i> WIP
                                            </span>
                                        @endif
                                        @if ($page->is_featured)
                                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-700">
                                                <i class="fa-solid fa-star" aria-hidden="true"></i> Wyróżniona
                                            </span>
                                        @endif
                                        @if (! $page->parent_id && $page->show_in_menu)
                                            <span class="rounded-full bg-brand-light px-2 py-0.5 text-[11px] font-bold text-brand">W menu</span>
                                        @endif
                                        @if ($page->is_system)
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">
                                                <i class="fa-solid fa-lock" aria-hidden="true"></i> Systemowa
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Kolejność --}}
                                <td x-show="cols.order" class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.podstrony.kolejnosc', $page) }}" class="flex items-center gap-1">
                                        @csrf @method('PATCH')
                                        <input type="number" name="order" min="0" value="{{ $page->order }}"
                                            aria-label="Kolejność strony {{ $page->title }}"
                                            class="w-16 rounded border-gray-300 py-1 text-sm focus:border-brand focus:ring-brand">
                                        <button type="submit"
                                            class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                            title="Zapisz kolejność" aria-label="Zapisz kolejność">
                                            <i class="fa-solid fa-check text-xs" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </td>

                                {{-- Akcje --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">

                                        {{-- Edytuj --}}
                                        <a href="{{ route('admin.podstrony.edit', $page) }}"
                                            class="inline-flex items-center gap-1.5 rounded border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-ink hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                            <i class="fa-solid fa-pen text-xs" aria-hidden="true"></i> Edytuj
                                        </a>

                                        {{-- Podgląd --}}
                                        @if ($page->is_published && ($page->publish_at === null || $page->publish_at->isPast()))
                                            <a href="{{ $page->publicUrl() }}" target="_blank" rel="noopener"
                                                class="inline-flex h-[30px] w-[30px] items-center justify-center rounded border border-gray-200 bg-white text-muted hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                                title="Podgląd publiczny" aria-label="Podgląd publiczny {{ $page->title }}">
                                                <i class="fa-solid fa-eye text-xs" aria-hidden="true"></i>
                                            </a>
                                        @else
                                            <a href="{{ $page->previewUrl() }}" target="_blank" rel="noopener"
                                                class="inline-flex h-[30px] w-[30px] items-center justify-center rounded border border-amber-200 bg-amber-50 text-amber-600 hover:text-amber-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400"
                                                title="Podgląd roboczy" aria-label="Podgląd roboczy {{ $page->title }}">
                                                <i class="fa-solid fa-eye text-xs" aria-hidden="true"></i>
                                            </a>
                                        @endif

                                        {{-- Dropdown: pozostałe akcje --}}
                                        <div class="relative" x-data="{ open: false }">
                                            <button type="button" @click="open = !open" :aria-expanded="open"
                                                class="inline-flex h-[30px] w-[30px] items-center justify-center rounded border border-gray-200 bg-white text-muted hover:border-gray-300 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                                aria-label="Więcej akcji dla {{ $page->title }}">
                                                <i class="fa-solid fa-ellipsis-vertical text-xs" aria-hidden="true"></i>
                                            </button>

                                            <div x-show="open" @click.outside="open = false"
                                                x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="opacity-0 scale-95"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                class="absolute right-0 top-full z-20 mt-1 w-52 rounded-lg border border-gray-200 bg-white py-1.5 shadow-lg">

                                                <form method="POST" action="{{ route('admin.podstrony.wyroznienie', $page) }}">
                                                    @csrf @method('PATCH')
                                                    <button type="submit"
                                                        class="flex w-full items-center gap-3 px-4 py-2 text-sm text-ink hover:bg-gray-50">
                                                        @if ($page->is_featured)
                                                            <i class="fa-solid fa-star w-4 text-center text-amber-500" aria-hidden="true"></i> Usuń wyróżnienie
                                                        @else
                                                            <i class="fa-regular fa-star w-4 text-center text-muted" aria-hidden="true"></i> Wyróżnij
                                                        @endif
                                                    </button>
                                                </form>

                                                @unless ($page->parent_id || $page->isAboutPerson())
                                                    <a href="{{ route('admin.podstrony.create', ['parent_id' => $page->id]) }}"
                                                        class="flex items-center gap-3 px-4 py-2 text-sm text-ink hover:bg-gray-50">
                                                        <i class="fa-solid fa-plus w-4 text-center text-muted" aria-hidden="true"></i> Dodaj podstronę
                                                    </a>
                                                @endunless

                                                <form method="POST" action="{{ route('admin.podstrony.clone', $page) }}"
                                                    data-confirm="Zduplikować stronę „{{ $page->title }}"? Kopia zostanie zapisana jako szkic.">
                                                    @csrf
                                                    <button type="submit"
                                                        class="flex w-full items-center gap-3 px-4 py-2 text-sm text-ink hover:bg-gray-50">
                                                        <i class="fa-solid fa-clone w-4 text-center text-muted" aria-hidden="true"></i> Klonuj
                                                    </button>
                                                </form>

                                                <hr class="my-1 border-gray-100">

                                                <form method="POST" action="{{ route('admin.podstrony.widocznosc', $page) }}">
                                                    @csrf @method('PATCH')
                                                    <button type="submit"
                                                        class="flex w-full items-center gap-3 px-4 py-2 text-sm text-ink hover:bg-gray-50">
                                                        @if ($page->is_published)
                                                            <i class="fa-solid fa-eye-slash w-4 text-center text-muted" aria-hidden="true"></i> Cofnij publikację
                                                        @else
                                                            <i class="fa-solid fa-eye w-4 text-center text-green-600" aria-hidden="true"></i> Opublikuj
                                                        @endif
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('admin.podstrony.wylacz', $page) }}">
                                                    @csrf @method('PATCH')
                                                    <button type="submit"
                                                        class="flex w-full items-center gap-3 px-4 py-2 text-sm text-ink hover:bg-gray-50">
                                                        @if ($page->is_disabled)
                                                            <i class="fa-solid fa-power-off w-4 text-center text-green-600" aria-hidden="true"></i> Włącz stronę
                                                        @else
                                                            <i class="fa-solid fa-ban w-4 text-center text-muted" aria-hidden="true"></i> Wyłącz stronę
                                                        @endif
                                                    </button>
                                                </form>

                                                @unless ($page->is_system)
                                                    <hr class="my-1 border-gray-100">
                                                    <form method="POST" action="{{ route('admin.podstrony.destroy', $page) }}"
                                                        data-confirm="Usunąć stronę „{{ $page->title }}"?">
                                                        @csrf @method('DELETE')
                                                        <button type="submit"
                                                            class="flex w-full items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                            <i class="fa-solid fa-trash w-4 text-center" aria-hidden="true"></i> Usuń
                                                        </button>
                                                    </form>
                                                @endunless
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center">
                                    <div class="mx-auto max-w-xs">
                                        <i class="fa-regular fa-file text-4xl text-gray-200" aria-hidden="true"></i>
                                        <p class="mt-3 font-semibold text-muted">Brak stron</p>
                                        <p class="mt-1 text-sm text-muted">Żadna strona nie pasuje do filtrów lub lista jest pusta.</p>
                                        <a href="{{ route('admin.podstrony.create') }}"
                                            class="mt-4 inline-flex items-center gap-1.5 rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj pierwszą stronę
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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

            selectAll.addEventListener('change', function () {
                document.querySelectorAll('.page-row-check').forEach(function (cb) {
                    cb.checked = selectAll.checked;
                });
                updateBar();
            });

            document.querySelectorAll('.page-row-check').forEach(function (cb) {
                cb.addEventListener('change', updateBar);
            });
        })();
    </script>
@endsection
