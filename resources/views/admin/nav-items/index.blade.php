@extends('admin.layout')

@section('title', $location === 'bip' ? 'BIP — menu boczne' : 'Menu nawigacji')

@section('content')
    @php
        // Po błędach walidacji z modala odtwarzamy jego stan ze starych danych,
        // żeby użytkownik nie tracił wpisanych wartości i pozostał w kontekście.
        $reopen = null;
        if ($errors->any()) {
            $eid = old('editing_id');
            $reopen = [
                'editingId' => (string) ($eid ?? ''),
                'action' => $eid ? route('admin.pozycje-menu.update', $eid) : route('admin.pozycje-menu.store'),
                'label' => old('label', ''),
                'url' => old('url', ''),
                'type' => old('type', 'link'),
                'location' => old('location', $location),
                'parentId' => (string) old('parent_id', ''),
                'module' => old('module', ''),
                'isButton' => (bool) old('is_button'),
                'buttonColor' => old('button_color') ?: '#2563eb',
                'buttonColorEnabled' => (bool) old('button_color'),
                'accentColor' => old('accent_color') ?: \App\Support\ThemePalette::colors()[0],
                'accentEnabled' => (bool) old('accent_color'),
                'highlight' => (bool) old('highlight'),
                'isTransparent' => (bool) old('is_transparent_dropdown'),
                'isMega' => (bool) old('is_mega'),
                'isHeading' => (bool) old('is_column_heading'),
                'megaImage' => old('mega_image_url', ''),
                'megaSize' => old('mega_size', 'md'),
                'megaExtraTitle' => old('mega_extra_title', ''),
                'hideAllBtn' => (bool) old('hide_all_projects_btn'),
                'hideArchiveBtn' => (bool) old('hide_archive_btn'),
                'megaSideTitle' => old('mega_side_title', ''),
                'megaSideLinks' => array_values((array) old('mega_side_links', [])),
                'megaImageAlt' => old('mega_image_alt', ''),
                'description' => old('description', ''),
                'isActive' => (bool) old('is_active'),
            ];
        }
    @endphp

    <div x-data="menuBuilder({{ Js::from($reopen) }}, {{ Js::from(route('admin.pozycje-menu.store')) }})"
        @keydown.escape.window="close()">

        @include('admin.partials.content-nav-tabs')

        {{-- Wskaźnik zapisu po drag & drop (ukryty do czasu użycia) --}}
        <div id="nav-dnd-status" role="status" aria-live="polite" class="mb-3 hidden px-3 py-2 text-sm rounded-md bg-white"></div>

        {{-- Układ jak w module „Strona” TYPO3: drzewo pozycji | szczegóły wybranej pozycji. --}}
        <style>@media (max-width: 767px) { .nav-split { grid-template-columns: 1fr !important; } }</style>
        <div class="nav-split" style="display:grid;grid-template-columns:minmax(17rem,24rem) minmax(0,1fr);gap:1.25rem;align-items:start">
            <nav aria-label="Struktura menu — {{ \App\Models\NavItem::LOCATIONS[$location] ?? $location }}" class="rounded-md bg-white" style="min-width:0">
                <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.5rem;padding:.875rem .875rem .5rem">
                    <h2 class="text-sm font-bold text-ink">
                        <i class="fa-solid fa-bars mr-1 text-brand-dark" aria-hidden="true"></i> {{ \App\Models\NavItem::LOCATIONS[$location] ?? $location }}
                        <span class="font-normal text-muted">({{ $navItems->count() }})</span>
                    </h2>
                    {{-- „Dodaj” jako menu: nowa pozycja, strony z drzewa albo pozycje z pliku XML (WAI-ARIA menu button). --}}
                    <div class="flex shrink-0 items-center gap-1.5">
                        @if (Route::has('admin.pozycje-menu.eksport-xml'))
                            <a href="{{ route('admin.pozycje-menu.eksport-xml', ['location' => $location]) }}"
                                class="inline-flex shrink-0 items-center rounded-md bg-gray-100 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2"
                                style="min-height:2.25rem;padding:.375rem .75rem;gap:.375rem">
                                <i class="fa-solid fa-file-export" aria-hidden="true"></i> Eksport XML<span class="sr-only"> — pobierz strukturę tego menu</span>
                            </a>
                        @endif
                        @include('admin.nav-items._add-menu', ['menuId' => 'nav-add-menu', 'location' => $location, 'parentId' => null, 'primary' => true])
                    </div>
                </div>
                <div style="padding:.5rem">
                    @forelse ($navItems as $index => $item)
                        @if ($loop->first)<ul role="tree" aria-label="Struktura menu" class="space-y-1">@endif
                            @include('admin.nav-items._tree-node', ['item' => $item, 'level' => 1, 'selected' => $selected, 'location' => $location])
                        @if ($loop->last)</ul>@endif
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-200 py-10 text-center text-muted">
                            <i class="fa-solid fa-bars mb-2 text-2xl text-gray-300" aria-hidden="true"></i>
                            <p class="text-sm">Brak pozycji. Dodaj pierwszą przyciskiem powyżej.</p>
                        </div>
                    @endforelse
                </div>
                <p class="text-muted" style="font-size:.75rem;padding:.25rem .875rem .875rem">
                    <i class="fa-solid fa-grip-vertical mr-1" aria-hidden="true"></i>
                    Kolejność: przeciągnij pozycję albo użyj przycisków „W górę / W dół” po prawej (działają z klawiatury).
                    @if ($location === 'main') „Menu działań” pobiera zawartość z <a href="{{ route('admin.kategorie.index') }}" class="font-bold text-brand-dark hover:underline">kategorii działań</a>.@endif
                </p>
            </nav>

            <section aria-labelledby="nav-pane-heading" style="min-width:0">
                @if ($selected)
                    @include('admin.nav-items._details', ['selected' => $selected, 'navItems' => $navItems, 'location' => $location])
                @else
                    <h2 id="nav-pane-heading" class="sr-only">Szczegóły pozycji</h2>
                    <div class="rounded-lg border border-dashed border-gray-300 bg-white px-4 py-12 text-center text-sm text-muted">Wybierz pozycję w drzewie albo dodaj nową.</div>
                @endif
            </section>
        </div>

        {{-- ── Podgląd menu na stronie: prawdziwa strona w ramce, odświeżana po zmianach ── --}}
        @if (in_array($location, ['main', 'footer'], true))
            <details class="mt-5 rounded-md bg-white" x-data @nav-preview-refresh.window="if ($refs.frame.src) $refs.frame.src = $refs.frame.dataset.src + '?podglad=' + Date.now()" @toggle="if ($el.open && ! $refs.frame.src) $refs.frame.src = $refs.frame.dataset.src">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-3 text-sm font-bold text-ink [&::-webkit-details-marker]:hidden">
                    <span><i class="fa-solid fa-eye mr-1.5 text-brand-dark" aria-hidden="true"></i>Podgląd menu na stronie</span>
                    <i class="fa-solid fa-chevron-down text-xs text-muted" aria-hidden="true"></i>
                </summary>
                <div class="border-t border-gray-100 p-3">
                    <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs text-ink">Strona główna serwisu — tak menu widzą odwiedzający. Po przeciągnięciu pozycji podgląd odświeża się sam.</p>
                        <button type="button" @click="$refs.frame.src = $refs.frame.dataset.src + (($refs.frame.dataset.src.includes('?') ? '&' : '?') + 'podglad=' + Date.now())"
                            class="inline-flex min-h-9 items-center gap-1.5 rounded-md bg-gray-100 px-3 text-xs font-bold text-ink hover:bg-brand-light"><i class="fa-solid fa-rotate" aria-hidden="true"></i> Odśwież</button>
                    </div>
                    <iframe x-ref="frame" data-src="{{ route('home') }}" title="Podgląd strony głównej z menu"
                        @load="@if ($location === 'footer') try { $el.contentWindow.scrollTo(0, $el.contentDocument.body.scrollHeight) } catch (e) {} @endif"
                        class="h-[26rem] w-full rounded border border-gray-200 bg-white"></iframe>
                </div>
            </details>
        @endif

        {{-- ── Okno „Z pliku XML”: import struktury menu (pozycje dopisywane na końcu) ── --}}
        @if (Route::has('admin.pozycje-menu.import-xml'))
            {{-- Podgląd: plik czytany w przeglądarce (DOMParser nie wczytuje zewnętrznych encji), drzewo z polami wyboru;
                 odznaczone pozycje idą jako skip[] (ścieżka indeksów, np. „0.2”) i serwer pomija je razem z podpozycjami.
                 Serwer i tak sprawdza plik od nowa — podgląd tylko pomaga wybrać. --}}
            <dialog id="nav-import-xml" aria-labelledby="nav-import-xml-title" aria-describedby="nav-import-xml-desc" class="m-auto overflow-hidden rounded-md bg-white p-0 text-ink backdrop:bg-black/50" style="width:min(40rem,calc(100vw - 2rem))"
                x-data="{
                    nodes: [], error: '',
                    read(e) {
                        this.nodes = []; this.error = '';
                        const file = e.target.files[0];
                        if (! file) return;
                        file.text().then(txt => {
                            const doc = new DOMParser().parseFromString(txt, 'application/xml');
                            if (doc.querySelector('parsererror') || doc.doctype || doc.documentElement.nodeName !== 'menu') {
                                this.error = 'To nie jest poprawny plik struktury menu (element <menu> z pozycjami <item>, bez DOCTYPE).';
                                return;
                            }
                            const out = [];
                            const walk = (el, path, depth) => {
                                [...el.children].filter(c => c.nodeName === 'item').forEach((it, i) => {
                                    const p = path === '' ? String(i) : path + '.' + i;
                                    out.push({ path: p, depth, label: (it.getAttribute('label') || '').trim(), url: it.getAttribute('url') || '', hidden: it.getAttribute('is_active') === '0', on: true });
                                    walk(it, p, depth + 1);
                                });
                            };
                            walk(doc.documentElement, '', 0);
                            this.nodes = out;
                            if (! out.length) this.error = 'Plik nie zawiera żadnych pozycji <item>.';
                        }).catch(() => { this.error = 'Nie udało się odczytać pliku.'; });
                    },
                    blocked(n) { return this.nodes.some(o => ! o.on && n.path.startsWith(o.path + '.')); },
                    get chosen() { return this.nodes.filter(n => n.on && n.label && ! this.blocked(n)).length; },
                    setAll(v) { this.nodes.forEach(n => { n.on = v; }); },
                 }">
                <form method="POST" action="{{ route('admin.pozycje-menu.import-xml') }}" enctype="multipart/form-data" class="flex flex-col" style="max-height:85vh">
                    @csrf
                    <input type="hidden" name="location" value="{{ $location }}">
                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                        <h2 id="nav-import-xml-title" class="text-lg font-bold">Dodaj pozycje z pliku XML</h2>
                        <button type="button" onclick="this.closest('dialog').close()" class="rounded p-2 text-muted hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"><i class="fa-solid fa-xmark" aria-hidden="true"></i><span class="sr-only">Zamknij</span></button>
                    </div>
                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                        <p id="nav-import-xml-desc" class="text-sm text-ink">
                            Wczytaj plik z „Eksport XML” (także z innej witryny). Pozycje zostaną <strong>dopisane na końcu</strong> menu „{{ \App\Models\NavItem::LOCATIONS[$location] ?? $location }}” — obecne pozostaną bez zmian.
                            @if ($location !== 'main') Podpozycje z pliku trafią na ten sam poziom (to menu nie ma podmenu). @endif
                        </p>
                        <div>
                            <label for="nav-import-file" class="mb-1 block text-sm font-bold">Plik XML</label>
                            <input id="nav-import-file" type="file" name="xml" accept=".xml,application/xml,text/xml" required @change="read($event)"
                                class="block w-full text-sm text-ink file:mr-3 file:rounded file:border-0 file:bg-brand-light file:px-3 file:py-2 file:text-sm file:font-bold file:text-brand-dark">
                            @error('xml') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                            <p x-show="error" x-cloak x-text="error" role="alert" class="mt-1 text-sm text-red-700"></p>
                        </div>

                        {{-- Podgląd pozycji z pliku --}}
                        <fieldset x-show="nodes.length" x-cloak>
                            <legend class="mb-1 text-sm font-bold">Pozycje z pliku</legend>
                            <div class="mb-2 flex flex-wrap items-center gap-2 text-xs">
                                <button type="button" @click="setAll(true)" class="rounded-md bg-gray-100 px-2.5 py-1.5 font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Zaznacz wszystko</button>
                                <button type="button" @click="setAll(false)" class="rounded-md bg-gray-100 px-2.5 py-1.5 font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Odznacz</button>
                                <span class="ml-auto font-bold" role="status" aria-live="polite">Do dodania: <span x-text="chosen"></span> z <span x-text="nodes.length"></span></span>
                            </div>
                            <ul role="list" class="overflow-y-auto rounded-md bg-gray-50 p-2" style="max-height:18rem">
                                <template x-for="n in nodes" :key="n.path">
                                    <li>
                                        <label class="flex cursor-pointer items-center gap-3 rounded py-1.5 pr-2 hover:bg-white" :style="'padding-left:' + (0.25 + n.depth * 1.25) + 'rem'">
                                            <input type="checkbox" x-model="n.on" :disabled="blocked(n) || ! n.label" class="h-4 w-4 flex-none rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-sm font-bold" :class="(n.on && ! blocked(n) && n.label) ? 'text-ink' : 'text-muted'" x-text="n.label || '(bez etykiety — zostanie pominięta)'"></span>
                                                <span class="block truncate font-mono text-xs text-muted" x-text="n.url"></span>
                                            </span>
                                            <span x-show="n.hidden" class="flex-none text-xs font-bold text-ink"><i class="fa-solid fa-eye-slash mr-1 text-muted" aria-hidden="true"></i>ukryta</span>
                                        </label>
                                        <template x-if="! n.on"><input type="hidden" name="skip[]" :value="n.path"></template>
                                    </li>
                                </template>
                            </ul>
                            <p class="mt-1 text-xs text-muted">Odznaczenie pozycji pomija też jej podpozycje.</p>
                        </fieldset>
                        @if ($location === 'main' && $parentOptions->isNotEmpty())
                            <div>
                                <label for="nav-import-parent" class="mb-1 block text-sm font-bold">Gdzie dodać</label>
                                <select id="nav-import-parent" name="parent_id" class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                                    <option value="">Jako pozycje główne menu</option>
                                    @foreach ($parentOptions as $po)
                                        <option value="{{ $po->id }}">Pod „{{ $po->label }}”</option>
                                    @endforeach
                                </select>
                                @error('parent_id') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>
                    <div class="flex items-center gap-3 border-t border-gray-100 px-5 py-4">
                        <button type="submit" :disabled="nodes.length > 0 && chosen === 0" class="rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Importuj<span x-show="nodes.length" x-text="' (' + chosen + ')'"></span></button>
                        <button type="button" onclick="this.closest('dialog').close()" class="rounded px-2 py-2 text-sm text-muted hover:text-brand-dark">Anuluj</button>
                    </div>
                </form>
            </dialog>
            @if ($errors->has('xml'))
                <script>document.addEventListener('DOMContentLoaded', () => document.getElementById('nav-import-xml')?.showModal());</script>
            @endif
        @endif

        {{-- ── Okno „Dodaj strony”: wybór kilku stron z drzewa ── --}}
        @if (Route::has('admin.pozycje-menu.ze-stron'))
            <dialog id="nav-from-pages" aria-labelledby="nav-from-pages-title" class="m-auto w-[min(40rem,calc(100vw-2rem))] overflow-hidden rounded-md bg-white p-0 text-ink backdrop:bg-black/50"
                x-data="{ q: '', norm(s) { return (s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/ł/g, 'l'); } }">
                <form method="POST" action="{{ route('admin.pozycje-menu.ze-stron') }}" class="flex max-h-[85vh] flex-col">
                    @csrf
                    <input type="hidden" name="location" value="{{ $location }}">
                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                        <h2 id="nav-from-pages-title" class="text-lg font-bold">Dodaj strony do menu</h2>
                        <button type="button" onclick="this.closest('dialog').close()" class="rounded p-2 text-muted hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"><i class="fa-solid fa-xmark" aria-hidden="true"></i><span class="sr-only">Zamknij</span></button>
                    </div>
                    <div class="space-y-3 border-b border-gray-100 px-5 py-3">
                        <label for="nav-pages-q" class="sr-only">Szukaj strony</label>
                        <input id="nav-pages-q" type="search" x-model="q" placeholder="Szukaj strony…" autocomplete="off" class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                        @if ($location === 'main' && $parentOptions->isNotEmpty())
                            <div>
                                <label for="nav-pages-parent" class="mb-1 block text-sm font-bold">Gdzie dodać</label>
                                <select id="nav-pages-parent" name="parent_id" class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                                    <option value="">Jako pozycje główne menu</option>
                                    @foreach ($parentOptions as $po)
                                        <option value="{{ $po->id }}">Pod „{{ $po->label }}”</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-3" role="group" aria-label="Strony do dodania">
                        @forelse ($pageTree as $node)
                            <label class="flex cursor-pointer items-center gap-3 rounded py-1.5 pr-2 hover:bg-gray-50" style="padding-left: {{ 0.25 + $node['depth'] * 1.25 }}rem"
                                x-show="q.trim() === '' || norm(@js($node['title'].' '.$node['path'])).includes(norm(q))">
                                <input type="checkbox" name="page_ids[]" value="{{ $node['id'] }}" @disabled($node['in_menu']) class="h-4 w-4 flex-none rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-bold {{ $node['in_menu'] ? 'text-muted' : 'text-ink' }}">{{ $node['title'] }}</span>
                                    <span class="block truncate font-mono text-xs text-muted">{{ $node['path'] }}</span>
                                </span>
                                @if ($node['in_menu'])<span class="flex-none text-xs font-bold text-ink"><i class="fa-solid fa-check mr-1 text-brand-dark" aria-hidden="true"></i>już w menu</span>@endif
                            </label>
                        @empty
                            <p class="py-6 text-center text-sm text-ink">Brak opublikowanych stron.</p>
                        @endforelse
                    </div>
                    <div class="flex items-center gap-3 border-t border-gray-100 px-5 py-4">
                        <button type="submit" class="rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Dodaj zaznaczone</button>
                        <button type="button" onclick="this.closest('dialog').close()" class="rounded px-2 py-2 text-sm text-muted hover:text-brand-dark">Anuluj</button>
                    </div>
                </form>
            </dialog>
        @endif

        {{-- ============================ MODAL EDYCJI ============================ --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:p-6" style="display: none">
            {{-- Tło przyciemniające; kliknięcie zamyka modal --}}
            <div class="fixed inset-0 bg-ink/60" @click="close()" aria-hidden="true"></div>

            <div x-ref="panel" role="dialog" aria-modal="true" :aria-label="form.editingId ? 'Edytuj pozycję menu' : 'Nowa pozycja menu'"
                @keydown.tab="trapTab($event)"
                class="relative z-10 my-4 w-full rounded-lg border border-gray-200 bg-white shadow-xl" style="max-width:min(64rem, 100%)">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <h2 class="text-lg font-bold" x-text="form.editingId ? 'Edytuj pozycję menu' : 'Nowa pozycja menu'"></h2>
                    <button type="button" @click="close()"
                        class="rounded p-2 text-muted hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        <span class="sr-only">Zamknij okno bez zapisywania</span>
                    </button>
                </div>

                <form method="POST" :action="form.action" enctype="multipart/form-data" class="space-y-5 px-6 py-5">
                    @csrf
                    <input type="hidden" name="_method" :value="form.editingId ? 'PUT' : 'POST'">
                    <input type="hidden" name="editing_id" :value="form.editingId">

                    @include('admin.nav-items._fields')

                    <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
                        <button type="submit"
                            class="rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                            Zapisz
                        </button>
                        <button type="button" @click="close()"
                            class="rounded px-2 py-2 text-sm text-muted hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                            Anuluj
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Po operacji przenoszenia przywracamy fokus na przeniesioną pozycję, aby
         użytkownik klawiatury nie tracił swojego miejsca w drzewie. --}}
    @if (session('focus_nav'))
        <script>
            (function () {
                var row = document.getElementById('nav-item-{{ (int) session('focus_nav') }}');
                if (!row) return;
                var target = row.querySelector('[data-nav-first-action]');
                if (target) {
                    target.focus();
                    row.scrollIntoView({ block: 'center', behavior: 'smooth' });
                }
            })();
        </script>
    @endif

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js" integrity="sha512-Eezs+g9Lq4TCCq0wae01s9PuNWzHYoCMkE97e2qdkYthpI0pzC3UGB03lgEHn2XM85hDOUF6qgqqszs+iXU4Q==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        (function () {
            var reorderUrl = '{{ route('admin.pozycje-menu.reorder') }}';
            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                || document.querySelector('input[name="_token"]')?.value || '';
            var statusEl = document.getElementById('nav-dnd-status');
            var saveTimer = null;

            function showStatus(msg, isError) {
                statusEl.textContent = msg;
                statusEl.className = 'mb-3 rounded-md px-3 py-2 text-sm font-bold '
                    + (isError ? 'bg-red-50 text-red-800' : 'bg-brand-light text-ink');
                statusEl.classList.remove('hidden');
                clearTimeout(saveTimer);
                saveTimer = setTimeout(() => statusEl.classList.add('hidden'), 3000);
            }

            function collectItems() {
                var items = [];
                var topList = document.querySelector('[role="tree"]');
                if (!topList) return items;
                topList.querySelectorAll(':scope > li[data-id]').forEach(function (li, idx) {
                    items.push({ id: parseInt(li.dataset.id), parent_id: null, order: idx });
                    var childList = li.querySelector(':scope > ul[role="group"]');
                    if (childList) {
                        childList.querySelectorAll(':scope > li[data-id]').forEach(function (child, cidx) {
                            items.push({ id: parseInt(child.dataset.id), parent_id: parseInt(li.dataset.id), order: cidx });
                        });
                    }
                });
                return items;
            }

            function save() {
                var items = collectItems();
                fetch(reorderUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ items: items }),
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.ok) { showStatus('Kolejność zapisana.', false); window.dispatchEvent(new CustomEvent('nav-preview-refresh')); }
                    else showStatus('Błąd zapisu kolejności.', true);
                })
                .catch(function () { showStatus('Błąd sieci — kolejność niezapisana.', true); });
            }

            var sortableOpts = {
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'opacity-50',
                onEnd: save,
            };

            document.addEventListener('DOMContentLoaded', function () {
                var topList = document.querySelector('[role="tree"]');
                if (topList) {
                    new Sortable(topList, sortableOpts);
                    topList.querySelectorAll('[role="group"]').forEach(function (ul) {
                        new Sortable(ul, sortableOpts);
                    });
                }
            });
        })();
    </script>

    <script>
        // Jedno źródło wartości domyślnych formularza pozycji menu (nowa pozycja, pierwsze otwarcie modala).
        // Dodając pole formularza, uzupełnij je tu, w openEdit() oraz w tablicy $reopen na górze widoku.
        function navFormDefaults(action = '') {
            return {
                editingId: '', action, label: '', icon: '', description: '', url: '', type: 'link',
                location: 'main', parentId: '', module: '', isButton: false,
                buttonColor: '#2563eb', buttonColorEnabled: false, accentColor: '{{ \App\Support\ThemePalette::colors()[0] }}', accentEnabled: false, highlight: false,
                isTransparent: false, isMega: false, isHeading: false,
                megaImage: '', megaImageAlt: '', megaSize: 'md', megaExtraTitle: '', hideAllBtn: false, hideArchiveBtn: false, megaSideTitle: '', megaSideLinks: [],
                isActive: true,
            };
        }

        document.addEventListener('alpine:init', () => {
            Alpine.data('menuBuilder', (reopen = null, storeUrl = '') => ({
                open: false,
                trigger: null,
                form: navFormDefaults(storeUrl),

                init() {
                    // Ponowne otwarcie modala po błędach walidacji (bez utraty danych).
                    if (reopen) {
                        this.form = { ...this.form, ...reopen };
                        this.$nextTick(() => this.reveal());
                    }
                },

                blankForm(overrides = {}) {
                    return { ...navFormDefaults(storeUrl), ...overrides };
                },

                openCreate(event) {
                    const el = event.currentTarget;
                    this.trigger = el;
                    const overrides = { location: el.dataset.location || 'main' };
                    // „Dodaj podpozycję": z góry ustawiamy rodzica i typ „link".
                    if (el.dataset.parent) {
                        overrides.parentId = el.dataset.parent;
                        overrides.type = 'link';
                    }
                    this.form = this.blankForm(overrides);
                    this.reveal();
                },

                openEdit(event) {
                    const el = event.currentTarget;
                    const d = el.dataset;
                    this.trigger = el;
                    this.form = {
                        editingId: d.id || '',
                        action: d.action,
                        label: d.label || '',
                        icon: d.icon || '',
                        url: d.url || '',
                        type: d.type || 'link',
                        location: d.location || 'main',
                        parentId: d.parent || '',
                        module: d.module || '',
                        isButton: d.button === '1',
                        buttonColor: d.color || '#2563eb',
                        buttonColorEnabled: !!d.color,
                        accentColor: d.accent || '{{ \App\Support\ThemePalette::colors()[0] }}',
                        accentEnabled: !!d.accent,
                        highlight: d.highlight === '1',
                        isTransparent: d.transparent === '1',
                        isMega: d.mega === '1',
                        isHeading: d.heading === '1',
                        megaImage: d.megaImage || '',
                        megaSize: d.megaSize || 'md',
                        megaExtraTitle: d.megaExtraTitle || '',
                        hideAllBtn: d.hideAllBtn === '1',
                        hideArchiveBtn: d.hideArchiveBtn === '1',
                        megaSideTitle: d.megaSideTitle || '',
                        megaSideLinks: (() => { try { return JSON.parse(d.megaSideLinks || '[]'); } catch (e) { return []; } })(),
                        megaImageAlt: d.megaImageAlt || '',
                        description: d.description || '',
                        isActive: d.active === '1',
                    };
                    this.reveal();
                },

                reveal() {
                    this.open = true;
                    // Fokus natychmiast na pierwsze widoczne pole formularza
                    // (a nie na przycisk zamknięcia w nagłówku).
                    this.$nextTick(() => {
                        const form = this.$refs.panel.querySelector('form');
                        const first = this.focusables(form)[0];
                        if (first) first.focus();
                    });
                },

                close() {
                    if (!this.open) return;
                    this.open = false;
                    // Fokus wraca dokładnie do elementu, który otworzył modal.
                    this.$nextTick(() => this.trigger && this.trigger.focus());
                },

                // Widoczne, aktywne elementy sterujące (domyślnie w całym panelu
                // modala; opcjonalnie w węższym zakresie, np. samym formularzu).
                focusables(root = null) {
                    const selector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
                    return Array.from((root || this.$refs.panel).querySelectorAll(selector))
                        .filter((el) => el.offsetParent !== null || el === document.activeElement);
                },

                // Pułapka fokusu (WCAG 2.4.3): Tab z ostatniego elementu wraca na
                // pierwszy, a Shift+Tab z pierwszego — na ostatni.
                trapTab(event) {
                    const items = this.focusables();
                    if (items.length === 0) return;
                    const first = items[0];
                    const last = items[items.length - 1];
                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                },
            }));
        });
    </script>
@endsection
