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

    @php
        $locLabel = \App\Models\NavItem::LOCATIONS[$location] ?? $location;
        $tool = 'inline-flex items-center rounded-lg border border-gray-300 bg-white text-xs font-bold text-ink hover:border-brand-dark hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
        $toolStyle = 'min-height:2.25rem;padding:.375rem .75rem;gap:.375rem';
        $headBtn = 'rounded p-1.5 text-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
        $branchIds = $navItems->filter(fn ($i) => $i->allChildren->isNotEmpty())->pluck('id')->values();
    @endphp

    {{-- Stan drzewa zapamiętany w przeglądarce (jak w TYPO3): rozwinięte gałęzie — osobno dla witryny i menu.
         Przy pierwszej wizycie wszystko rozwinięte (menu jest małe). Musi być przed drzewem — węzły czytają go w x-data. --}}
    <script>
        window.navTreeState = (() => {
            const key = 'navTreeOpen:' + @js(\App\Models\SiteSetting::current()->id) + ':' + @js($location);
            let ids, stored = null;
            try { stored = localStorage.getItem(key); } catch (e) {}
            try { ids = new Set(stored === null ? @js($branchIds) : JSON.parse(stored)); } catch (e) { ids = new Set(); }
            return {
                paused: false,
                has: id => ids.has(id),
                set(id, open) {
                    if (this.paused) return;
                    open ? ids.add(id) : ids.delete(id);
                    try { localStorage.setItem(key, JSON.stringify([...ids])); } catch (e) {}
                },
            };
        })();
    </script>

    <div x-data="menuBuilder({{ Js::from($reopen) }}, {{ Js::from(route('admin.pozycje-menu.store')) }})"
        @keydown.escape.window="close()">

        @push('content-tab-actions')
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;min-width:0;flex:1 1 20rem">
                @if ($location !== 'bip')
                    {{-- Strony i nawigacja: strony dodaje się do menu z panelu strony; tu zostaje reszta menu. --}}
                    <p class="text-xs text-ink" data-nav-pages-hint>
                        <i class="fa-solid fa-sitemap mr-1 text-muted" aria-hidden="true"></i>
                        Strony dodajesz do menu w <a href="{{ route('admin.podstrony.index') }}" class="font-bold text-brand-dark underline hover:text-ink">Stronach Nawigatorze</a> (wybrana strona → „Pozycja w menu”).
                        Tutaj ustawiasz kolejność, linki zewnętrzne, rozwijane menu i mega menu.
                    </p>
                @endif
            </div>
            <div role="group" aria-label="Akcje menu" style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem">
                @if (in_array($location, ['main', 'footer'], true))
                    <button type="button" class="{{ $tool }}" style="{{ $toolStyle }}"
                        onclick="const d = document.getElementById('podglad-menu'); d.open = true; d.scrollIntoView({ behavior: 'smooth', block: 'start' }); d.querySelector('summary').focus({ preventScroll: true });">
                        <i class="fa-solid fa-eye" aria-hidden="true"></i> Podgląd na stronie
                    </button>
                @endif
                @if (Route::has('admin.pozycje-menu.eksport-xml'))
                    <a href="{{ route('admin.pozycje-menu.eksport-xml', ['location' => $location]) }}" class="{{ $tool }}" style="{{ $toolStyle }}">
                        <i class="fa-solid fa-file-export" aria-hidden="true"></i> Eksport XML<span class="sr-only"> — pobierz strukturę tego menu</span>
                    </a>
                @endif
            </div>
        @endpush

        {{-- Główna akcja widoku po prawej w rzędzie zakładek: „Dodaj” (nowa pozycja, ze stron, z pliku XML). --}}
        @push('content-tab-primary')
            @include('admin.nav-items._add-menu', ['menuId' => 'nav-add-menu', 'location' => $location, 'parentId' => null, 'primary' => true])
        @endpush

        @include('admin.partials.content-nav-tabs')

        {{-- Układ jak w Stronach Nawigatorze i module „Strona” TYPO3: drzewo pozycji | karta wybranej pozycji. --}}
        <div class="nav-split" style="display:grid;grid-template-columns:minmax(16rem,21rem) minmax(0,1fr);gap:1.25rem;align-items:start">
            <nav aria-label="Struktura menu — {{ $locLabel }}" id="nav-tree" class="min-w-0 rounded-md bg-white"
                x-data="{
                    q: '',
                    issuesOnly: false,
                    info: '',
                    panel: '',
                    filter() {
                        const q = this.q.trim().toLowerCase();
                        const only = this.issuesOnly;
                        if (window.navTreeState) window.navTreeState.paused = !! (q || only);
                        const items = [...this.$root.querySelectorAll('[data-nav-node]')];
                        items.forEach(li => { li.hidden = false; });
                        if (! q && ! only) { this.info = ''; return; }
                        const hit = li => (! q || li.dataset.title.includes(q)) && (! only || li.dataset.issue === '1');
                        const hits = items.filter(hit).length;
                        this.info = hits ? 'Pasuje pozycji: ' + hits + ' z ' + items.length + '.' : 'Żadna pozycja nie pasuje.';
                        items.forEach(li => {
                            const sub = [...li.querySelectorAll('[data-nav-node]')].some(hit);
                            li.hidden = ! (hit(li) || sub);
                            if (sub && li.hasAttribute('x-data')) Alpine.$data(li).open = true;
                        });
                    },
                    setAll(open) {
                        this.$root.querySelectorAll('[data-nav-node][x-data]').forEach(li => { Alpine.$data(li).open = open; });
                    },
                }">
                <div class="space-y-2 border-b border-gray-100 px-2.5 py-2">
                    <div class="flex items-center justify-between gap-2">
                        <h2 class="min-w-0 truncate text-sm font-bold text-ink"><span class="sr-only">Struktura menu: </span>{{ \Illuminate\Support\Str::before($locLabel, ' (') }} <span class="font-normal text-muted">({{ $navItems->count() }})</span></h2>
                        <div class="flex flex-none items-center gap-0.5">
                            @if ($branchIds->isNotEmpty())
                                <button type="button" @click="setAll(true)" class="{{ $headBtn }} text-muted hover:bg-gray-100 hover:text-brand-dark" title="Rozwiń wszystko" aria-label="Rozwiń wszystkie gałęzie">
                                    <i class="fa-solid fa-angles-down" aria-hidden="true"></i>
                                </button>
                                <button type="button" @click="setAll(false)" class="{{ $headBtn }} text-muted hover:bg-gray-100 hover:text-brand-dark" title="Zwiń wszystko" aria-label="Zwiń wszystkie gałęzie">
                                    <i class="fa-solid fa-angles-up" aria-hidden="true"></i>
                                </button>
                            @endif
                            <button type="button" @click="panel = panel === 'legend' ? '' : 'legend'" :aria-expanded="(panel === 'legend').toString()" aria-expanded="false" aria-controls="nav-legend" title="Legenda" aria-label="Legenda"
                                :class="panel === 'legend' ? 'bg-brand-light text-ink' : 'text-muted hover:bg-gray-100 hover:text-brand-dark'" class="{{ $headBtn }}">
                                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    @if ($navItems->isNotEmpty())
                        <div>
                            <label for="nav-filter" class="sr-only">Filtruj menu po etykiecie lub adresie</label>
                            <div class="relative">
                                <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-muted" aria-hidden="true"></i>
                                <input type="search" id="nav-filter" x-model="q" @input="filter()" placeholder="Filtruj menu…" autocomplete="off" aria-describedby="nav-filter-status"
                                    class="w-full rounded border-gray-500 py-1 pl-8 text-sm focus:border-brand-dark focus:ring-brand-dark">
                            </div>
                            <p id="nav-filter-status" role="status" aria-live="polite" class="mt-1 text-xs text-muted" :class="info ? '' : 'sr-only'" x-text="info"></p>
                        </div>
                    @endif
                    {{-- Sprawdzenie menu (jak ostrzeżenia w TYPO3): pozycje widoczne, a prowadzące do szkicu, wyłączonej strony lub 404. --}}
                    @if ($issues)
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 rounded bg-amber-50 px-2.5 py-1.5 text-xs text-ink" data-nav-issues>
                            <i class="fa-solid fa-triangle-exclamation text-amber-800" aria-hidden="true"></i>
                            <span class="min-w-0 flex-1">{{ $issues }} {{ trans_choice('pozycja prowadzi|pozycje prowadzą|pozycji prowadzi', $issues) }} do niedostępnej strony</span>
                            <button type="button" @click="issuesOnly = ! issuesOnly; filter()" :aria-pressed="issuesOnly.toString()" aria-pressed="false"
                                class="rounded px-1 font-bold text-brand-dark underline hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                <span x-text="issuesOnly ? 'Pokaż wszystkie' : 'Pokaż tylko je'">Pokaż tylko je</span>
                            </button>
                        </div>
                    @endif
                    {{-- Schowek: pozycja wycięta albo skopiowana (Ctrl+X / Ctrl+C), także z innego menu — np. z menu głównego do stopki. --}}
                    <div id="nav-clip" hidden class="flex flex-wrap items-center gap-x-2 gap-y-1 rounded bg-gray-50 px-2.5 py-1.5 text-xs text-ink">
                        <i class="fa-solid fa-clipboard text-muted" aria-hidden="true"></i>
                        <span class="min-w-0 flex-1" data-clip-text></span>
                        <button type="button" data-clip-paste-end class="rounded px-1 font-bold text-brand-dark underline hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Wklej na końcu tego menu</button>
                        <button type="button" data-clip-clear class="rounded px-1 font-bold text-brand-dark underline hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Wyczyść</button>
                    </div>
                    @php
                        $legendRow = 'flex items-center gap-2';
                        $legendIcon = 'tree-icon relative inline-flex w-4 flex-none justify-center text-[11px] text-gray-500';
                    @endphp
                    <div id="nav-legend" x-show="panel === 'legend'" x-cloak class="rounded bg-gray-50 p-3 text-xs text-ink">
                        <h3 class="mb-1.5 font-bold">Stan pozycji (znaczek na ikonie)</h3>
                        <ul role="list" class="space-y-1">
                            <li class="{{ $legendRow }}"><span class="{{ $legendIcon }}"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span> Bez znaczka — widoczna, link działa</li>
                            <li class="{{ $legendRow }}"><span class="{{ $legendIcon }}"><i class="fa-solid fa-file-lines" aria-hidden="true"></i><i class="tree-ov fa-solid fa-eye-slash text-muted" aria-hidden="true"></i></span> Ukryta w menu</li>
                            <li class="{{ $legendRow }}"><span class="{{ $legendIcon }}"><i class="fa-solid fa-file-lines" aria-hidden="true"></i><i class="tree-ov fa-solid fa-ban text-red-700" aria-hidden="true"></i></span> Moduł wyłączony albo strona wyłączona</li>
                            <li class="{{ $legendRow }}"><span class="{{ $legendIcon }}"><i class="fa-solid fa-file-lines" aria-hidden="true"></i><i class="tree-ov fa-solid fa-link-slash text-red-700" aria-hidden="true"></i></span> Adres nie prowadzi do żadnej strony (błąd 404)</li>
                            <li class="{{ $legendRow }}"><span class="{{ $legendIcon }}"><i class="fa-solid fa-file-lines" aria-hidden="true"></i><i class="tree-ov fa-solid fa-pen text-amber-800" aria-hidden="true"></i></span> Strona to szkic (nieopublikowana)</li>
                            <li class="{{ $legendRow }}"><span class="{{ $legendIcon }}"><i class="fa-solid fa-file-lines" aria-hidden="true"></i><i class="tree-ov fa-solid fa-clock text-brand-dark" aria-hidden="true"></i></span> Strona opublikuje się w ustawionym terminie</li>
                            <li class="{{ $legendRow }}"><span class="{{ $legendIcon }}"><i class="fa-solid fa-file-lines" aria-hidden="true"></i><i class="tree-ov fa-solid fa-key text-ink" aria-hidden="true"></i></span> Strona z ograniczonym dostępem</li>
                            <li class="{{ $legendRow }}"><span class="inline-flex w-4 flex-none justify-center text-[11px] text-muted" aria-hidden="true">Aa</span> Szara etykieta — pozycji nie widać na stronie</li>
                        </ul>
                        <h3 class="mb-1.5 mt-3 font-bold">Rodzaje pozycji</h3>
                        <ul role="list" class="space-y-1">
                            <li class="{{ $legendRow }}"><span class="{{ $legendIcon }}"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span> Strona serwisu</li>
                            <li class="{{ $legendRow }}"><span class="{{ $legendIcon }}"><i class="fa-solid fa-folder" aria-hidden="true"></i></span> Rozwijane menu albo pozycja z podpozycjami</li>
                            <li class="{{ $legendRow }}"><span class="{{ $legendIcon }}"><i class="fa-solid fa-globe" aria-hidden="true"></i></span> Link zewnętrzny</li>
                            <li class="{{ $legendRow }}"><span class="{{ $legendIcon }}"><i class="fa-solid fa-house" aria-hidden="true"></i></span> Strona główna · <i class="fa-solid fa-hashtag" aria-hidden="true"></i> kotwica na stronie głównej</li>
                            <li class="{{ $legendRow }}"><span class="{{ $legendIcon }}"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i></span> Przycisk (CTA)</li>
                            <li class="{{ $legendRow }}"><span class="{{ $legendIcon }}"><i class="fa-solid fa-table-columns" aria-hidden="true"></i></span> Nagłówek kolumny w mega menu</li>
                            <li class="{{ $legendRow }}"><span class="{{ $legendIcon }}"><i class="fa-solid fa-diagram-project" aria-hidden="true"></i></span> Menu działań · <i class="fa-solid fa-sitemap" aria-hidden="true"></i> menu podstron (automatyczne)</li>
                            <li class="{{ $legendRow }}"><span class="inline-flex h-4 w-4 flex-none items-center justify-center rounded-sm bg-brand-light text-[10px] font-bold text-brand-dark" aria-hidden="true">M</span> Strona z modułu (aktualności, materiały…)</li>
                            <li class="{{ $legendRow }}"><span class="{{ $legendIcon }} text-brand-dark"><i class="fa-solid fa-border-all" aria-hidden="true"></i></span> Mega menu · <i class="fa-solid fa-star text-amber-500" aria-hidden="true"></i> wyróżniona</li>
                        </ul>
                        <p class="mt-3 text-muted">Przeciągnij pozycję na górną lub dolną krawędź innej, aby zmienić kolejność, albo na środek pozycji z paska menu, aby zrobić z niej podpozycję. Z klawiatury: Alt+Shift+strzałki. Prawy przycisk myszy, „⋯” albo Shift+F10 otwiera menu akcji pozycji. Strzałki góra/dół przechodzą między pozycjami, w prawo/lewo rozwijają i zwijają gałąź. Ctrl+X / Ctrl+C wycina lub kopiuje pozycję, Ctrl+V wkleja ją za wybraną pozycją, Ctrl+Shift+V — jako jej podpozycję. Schowek działa też między menu głównym a stopką.</p>
                    </div>
                </div>
                {{-- Instrukcja dla czytników ekranu (wizualnie zbędna); skróty muszą być dostępne bez myszy. --}}
                <p id="nav-dnd-help" class="sr-only">
                    Z klawiatury: Alt+Shift+strzałka w górę lub w dół zmienia kolejność, w prawo — wkłada pozycję do poprzedniej, w lewo — wysuwa ją na pasek menu.
                    Shift+F10 lub klawisz Menu otwiera menu akcji pozycji. Strzałki góra i dół przechodzą między pozycjami, w prawo i w lewo rozwijają i zwijają gałąź, Home i End — pierwsza i ostatnia pozycja.
                    Ctrl+X wycina, Ctrl+C kopiuje pozycję; Ctrl+V wkleja ją za pozycją z fokusem, Ctrl+Shift+V — jako jej podpozycję.
                </p>
                <div id="nav-dnd-status" role="status" aria-live="polite" class="sr-only"></div>
                <div id="nav-tree-scroll" class="overflow-y-auto px-1.5 py-1.5" style="max-height:calc(100vh - 14rem)">
                    @if ($navItems->isEmpty())
                        <p class="px-2 py-6 text-center text-sm text-muted">Brak pozycji. Dodaj pierwszą przyciskiem „Dodaj”.</p>
                    @else
                        <ul role="list" data-nav-list data-nav-root aria-describedby="nav-dnd-help">
                            @foreach ($navItems as $item)
                                @include('admin.nav-items._tree-node', ['item' => $item, 'level' => 1, 'selected' => $selected, 'location' => $location, 'health' => $health, 'index' => $loop->index])
                            @endforeach
                        </ul>
                    @endif
                </div>
                @if ($location === 'main')
                    <p class="border-t border-gray-100 px-2.5 py-1.5 text-[11px] text-muted">„Menu działań” pobiera zawartość z <a href="{{ route('admin.kategorie.index') }}" class="font-bold text-brand-dark hover:underline">kategorii działań</a>.</p>
                @endif
            </nav>

            <section aria-labelledby="nav-pane-heading" style="min-width:0">
                @if ($selected)
                    @include('admin.nav-items._details', ['selected' => $selected, 'navItems' => $navItems, 'location' => $location, 'health' => $health, 'history' => $history])
                @else
                    <h2 id="nav-pane-heading" class="sr-only">Szczegóły pozycji</h2>
                    <div class="rounded-md bg-white px-4 py-12 text-center text-sm text-muted">Wybierz pozycję w drzewie albo dodaj nową przyciskiem „Dodaj”.</div>
                @endif
            </section>
        </div>

        {{-- ── Podgląd menu na stronie: prawdziwa strona w ramce, odświeżana po zmianach ── --}}
        @if (in_array($location, ['main', 'footer'], true))
            <details id="podglad-menu" class="mt-5 rounded-md bg-white" x-data @nav-preview-refresh.window="if ($refs.frame.src) $refs.frame.src = $refs.frame.dataset.src + ($refs.frame.dataset.src.includes('?') ? '&' : '?') + 'podglad=' + Date.now()" @toggle="if ($el.open && ! $refs.frame.src) $refs.frame.src = $refs.frame.dataset.src">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-3 text-sm font-bold text-ink [&::-webkit-details-marker]:hidden">
                    <span><i class="fa-solid fa-eye mr-1.5 text-brand-dark" aria-hidden="true"></i>Podgląd menu na stronie</span>
                    <i class="fa-solid fa-chevron-down text-xs text-muted" aria-hidden="true"></i>
                </summary>
                <div class="border-t border-gray-100 p-3">
                    <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs text-ink">{{ $location === 'footer' ? 'Stopka serwisu' : 'Nagłówek serwisu' }} — tak menu widzą odwiedzający (najedź na pozycję, aby zobaczyć rozwijane panele). Po przeciągnięciu pozycji podgląd odświeża się sam.</p>
                        <button type="button" @click="$refs.frame.src = $refs.frame.dataset.src + (($refs.frame.dataset.src.includes('?') ? '&' : '?') + 'podglad=' + Date.now())"
                            class="inline-flex min-h-9 items-center gap-1.5 rounded-md bg-gray-100 px-3 text-xs font-bold text-ink hover:bg-brand-light"><i class="fa-solid fa-rotate" aria-hidden="true"></i> Odśwież</button>
                    </div>
                    <iframe x-ref="frame" data-src="{{ Route::has('admin.pozycje-menu.podglad') ? route('admin.pozycje-menu.podglad', ['location' => $location]) : route('home') }}" title="{{ $location === 'footer' ? 'Podgląd stopki serwisu' : 'Podgląd nagłówka serwisu z menu' }}"
                        class="w-full rounded border border-gray-200 bg-white" style="height:{{ $location === 'footer' ? '18rem' : '34rem' }}"></iframe>
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

    <style>
        @media (max-width: 767px) { .nav-split { grid-template-columns: 1fr !important; } }
        @media (min-width: 768px) { #nav-tree { position: sticky; top: 1rem; } }
        /* Drzewo menu jak drzewo stron: podpozycje z linią pionową spod strzałki rodzica i łącznikiem do wiersza. */
        #nav-tree .tree-sub { margin-left: .75rem; padding-left: .5rem; border-left: 1px solid #c4c9d2; }
        #nav-tree .tree-sub > li { position: relative; }
        #nav-tree .tree-sub > li::before { content: ''; position: absolute; left: -.5rem; top: .75rem; width: .375rem; border-top: 1px solid #c4c9d2; }
        /* Ikona rodzaju z nakładką stanu w prawym dolnym rogu (jak w TYPO3): białe kółko pod znaczkiem. */
        .tree-icon .tree-ov { position: absolute; right: -.375rem; bottom: -.25rem; font-size: .5625rem; line-height: 1; padding: 1px; border-radius: 9999px; background: #fff; }
        #nav-tree .tree-link.is-selected .tree-ov { background: var(--m-tile, var(--color-brand-light, #e8f0ff)); }
        /* Wybrana pozycja: jasne tło marki z kreską z lewej, tekst ink ≥ 4,5:1. */
        #nav-tree .tree-link.is-selected { box-shadow: inset 3px 0 0 var(--m-accent, var(--color-brand-dark, #1752bf)); background: var(--m-tile, var(--color-brand-light, #e8f0ff)); }
        /* Pozycja wycięta do schowka: przerywana ramka i kursywa (bez przezroczystości treści). */
        #nav-tree [data-nav-node].tree-cut > [data-nav-row] > a { outline: 1px dashed #4b5563; outline-offset: -1px; font-style: italic; }
        .admin-metro #nav-tree #nav-filter { min-height: 2.25rem; border-width: 1px; }
        /* Przycisk „⋯” widać po najechaniu na wiersz, a na ekranach dotykowych zawsze. */
        @media (hover: none) { #nav-tree [data-tree-menu-btn] { opacity: 1; } }
        #nav-tree [data-nav-row].tree-ctx-on [data-tree-menu-btn] { opacity: 1; }
        #nav-ctx:focus { outline: none; }
        #nav-ctx [role="menuitem"]:focus-visible { outline: 2px solid var(--color-brand-dark, #1752bf); outline-offset: -2px; }
        #nav-ctx [role="menuitem"][aria-disabled="true"] { color: #6b7280; cursor: default; }
        #nav-ctx [role="menuitem"][aria-disabled="true"]:hover { background: transparent; }
    </style>

    {{-- ═════════ Menu kontekstowe drzewa menu ═════════
         Jedno menu dla wszystkich wierszy: prawy przycisk myszy, „⋯” albo Shift+F10 / klawisz Menu na pozycji.
         WAI-ARIA menu: strzałki, Home/End i pierwsza litera przenoszą fokus, Escape i Tab zamykają i oddają fokus wierszowi. --}}
    @php $ctxItem = 'flex w-full items-center gap-2.5 rounded px-3 py-1.5 text-left text-sm text-ink hover:bg-gray-100 focus-visible:outline-none'; @endphp
    <div id="nav-ctx" role="menu" aria-label="Akcje pozycji" hidden tabindex="-1"
        class="rounded-lg border border-gray-200 bg-white py-1.5 shadow-xl"
        style="position:fixed;z-index:60;min-width:15rem;max-width:calc(100vw - 1rem)">
        <p data-ctx-title class="truncate px-3 pb-1.5 pt-0.5 text-xs font-bold text-muted" aria-hidden="true"></p>
        <button type="button" role="menuitem" tabindex="-1" data-act="edit" class="{{ $ctxItem }}"><i class="fa-solid fa-pen w-4 text-center text-gray-500" aria-hidden="true"></i> Edytuj pozycję</button>
        <button type="button" role="menuitem" tabindex="-1" data-act="view" class="{{ $ctxItem }}"><i class="fa-solid fa-eye w-4 text-center text-gray-500" aria-hidden="true"></i> Zobacz na stronie<span class="sr-only"> (nowa karta)</span></button>
        <button type="button" role="menuitem" tabindex="-1" data-act="add" class="{{ $ctxItem }}"><i class="fa-solid fa-plus w-4 text-center text-gray-500" aria-hidden="true"></i> Dodaj podpozycję</button>
        <button type="button" role="menuitem" tabindex="-1" data-act="page-edit" class="{{ $ctxItem }}"><i class="fa-solid fa-file-pen w-4 text-center text-gray-500" aria-hidden="true"></i> Edytuj stronę</button>
        <button type="button" role="menuitem" tabindex="-1" data-act="page-tree" class="{{ $ctxItem }}"><i class="fa-solid fa-sitemap w-4 text-center text-gray-500" aria-hidden="true"></i> Pokaż w Stronach Nawigatorze</button>
        <button type="button" role="menuitem" tabindex="-1" data-act="toggle" class="{{ $ctxItem }}"><i class="fa-solid fa-eye-slash w-4 text-center text-gray-500" aria-hidden="true" data-ctx-toggle-icon></i> <span data-ctx-toggle>Ukryj w menu</span></button>
        <button type="button" role="menuitem" tabindex="-1" data-act="dup" class="{{ $ctxItem }}"><i class="fa-solid fa-clone w-4 text-center text-gray-500" aria-hidden="true"></i> Duplikuj</button>
        <div role="separator" class="my-1 border-t border-gray-100"></div>
        <button type="button" role="menuitem" tabindex="-1" data-act="cut" class="{{ $ctxItem }}"><i class="fa-solid fa-scissors w-4 text-center text-gray-500" aria-hidden="true"></i> Wytnij <kbd class="ml-auto text-[10px] text-muted">Ctrl+X</kbd></button>
        <button type="button" role="menuitem" tabindex="-1" data-act="copy" class="{{ $ctxItem }}"><i class="fa-solid fa-copy w-4 text-center text-gray-500" aria-hidden="true"></i> Kopiuj <kbd class="ml-auto text-[10px] text-muted">Ctrl+C</kbd></button>
        <button type="button" role="menuitem" tabindex="-1" data-act="copy-one" data-needs-kids class="{{ $ctxItem }}"><i class="fa-solid fa-file w-4 text-center text-gray-500" aria-hidden="true"></i> Kopiuj bez podpozycji</button>
        <button type="button" role="menuitem" tabindex="-1" data-act="paste-after" data-paste hidden class="{{ $ctxItem }}"><i class="fa-solid fa-paste w-4 text-center text-gray-500" aria-hidden="true"></i> <span data-paste-after>Wklej za tą pozycją</span></button>
        <button type="button" role="menuitem" tabindex="-1" data-act="paste-into" data-paste data-needs-hold hidden class="{{ $ctxItem }}"><i class="fa-solid fa-turn-down w-4 text-center text-gray-500" aria-hidden="true"></i> <span data-paste-into>Wklej jako podpozycję</span></button>
        <div role="separator" class="my-1 border-t border-gray-100"></div>
        <button type="button" role="menuitem" tabindex="-1" data-act="up" class="{{ $ctxItem }}"><i class="fa-solid fa-arrow-up w-4 text-center text-gray-500" aria-hidden="true"></i> Przenieś wyżej</button>
        <button type="button" role="menuitem" tabindex="-1" data-act="down" class="{{ $ctxItem }}"><i class="fa-solid fa-arrow-down w-4 text-center text-gray-500" aria-hidden="true"></i> Przenieś niżej</button>
        <button type="button" role="menuitem" tabindex="-1" data-act="indent" class="{{ $ctxItem }}"><i class="fa-solid fa-indent w-4 text-center text-gray-500" aria-hidden="true"></i> Zagnieźdź w poprzedniej</button>
        <button type="button" role="menuitem" tabindex="-1" data-act="outdent" class="{{ $ctxItem }}"><i class="fa-solid fa-outdent w-4 text-center text-gray-500" aria-hidden="true"></i> Wysuń na pasek menu</button>
        <div role="separator" class="my-1 border-t border-gray-100"></div>
        <button type="button" role="menuitem" tabindex="-1" data-act="copy-url" class="{{ $ctxItem }}"><i class="fa-solid fa-link w-4 text-center text-gray-500" aria-hidden="true"></i> Kopiuj adres</button>
        <button type="button" role="menuitem" tabindex="-1" data-act="copy-id" class="{{ $ctxItem }}"><i class="fa-solid fa-hashtag w-4 text-center text-gray-500" aria-hidden="true"></i> Kopiuj ID</button>
        <div role="separator" class="my-1 border-t border-gray-100"></div>
        <button type="button" role="menuitem" tabindex="-1" data-act="delete" class="flex w-full items-center gap-2.5 rounded px-3 py-1.5 text-left text-sm text-red-800 hover:bg-red-50 focus-visible:outline-none"><i class="fa-solid fa-trash w-4 text-center" aria-hidden="true"></i> Usuń z menu</button>
    </div>

    <script>
        // Drzewo menu w duchu TYPO3: przeciąganie, menu kontekstowe, schowek (także między menu), klawiatura.
        // Serwer (NavItemController::place / copy) jest źródłem prawdy: po zmianie przeładowujemy widok.
        (function () {
            const tree = document.getElementById('nav-tree');
            const ctx = document.getElementById('nav-ctx');
            if (! tree || ! ctx) return;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';
            const status = document.getElementById('nav-dnd-status');
            const here = @js($location);
            const selectedId = @js($selected?->id);
            const locLabels = @js(collect(\App\Models\NavItem::LOCATIONS)->map(fn ($l) => \Illuminate\Support\Str::before($l, ' (')));
            const routes = {
                index: @js(route('admin.pozycje-menu.index')),
                place: @js(route('admin.pozycje-menu.umiesc', '__ID__')),
                copy: @js(route('admin.pozycje-menu.kopiuj', '__ID__')),
                toggle: @js(route('admin.pozycje-menu.aktywna', '__ID__')),
                destroy: @js(route('admin.pozycje-menu.destroy', '__ID__')),
            };
            const urlFor = (name, id) => routes[name].replace('__ID__', id);
            const indexUrl = (loc, id, hash) => routes.index + '?location=' + encodeURIComponent(loc) + (id ? '&pozycja=' + id : '') + (hash || '');

            const rowOf = el => el.closest ? el.closest('[data-nav-row]') : null;
            const liOf = el => el.closest('[data-nav-node]');
            const idOf = li => parseInt(li.dataset.id, 10);
            const parentIdOf = li => { const p = li.parentElement.closest('[data-nav-node]'); return p ? idOf(p) : null; };
            const siblingsOf = (li, exceptId) => [...li.parentElement.children].filter(n => n.matches('[data-nav-node]') && idOf(n) !== exceptId);
            const nodeById = id => tree.querySelector('[data-nav-node][data-id="' + id + '"]');
            const announce = msg => { status.textContent = ''; setTimeout(() => { status.textContent = msg; }, 30); };

            async function place(id, body) {
                try {
                    const res = await fetch(urlFor('place', id), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify(Object.assign({ location: here }, body)),
                    });
                    const json = await res.json().catch(() => ({}));
                    if (! res.ok || ! json.ok) {
                        const msg = json.message || 'Nie udało się przenieść pozycji.';
                        announce(msg); alert(msg);
                        return;
                    }
                    const loc = json.location || here;
                    location.href = indexUrl(loc, loc === here ? (selectedId || id) : id);
                } catch (e) {
                    announce('Brak połączenia z serwerem.');
                    alert('Brak połączenia z serwerem. Spróbuj ponownie.');
                }
            }

            // Formularz POST/PATCH/DELETE z tokenem; data-confirm obsługuje wspólne okno potwierdzenia (app.js).
            function submitForm(action, method, confirmText, extra) {
                const f = document.createElement('form');
                f.method = 'POST'; f.action = action; f.hidden = true;
                const add = (name, value) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = name; i.value = value; f.appendChild(i); };
                add('_token', csrf);
                add('_method', method);
                Object.entries(extra || {}).forEach(([k, v]) => add(k, v));
                if (confirmText) f.dataset.confirm = confirmText;
                document.body.appendChild(f);
                f.requestSubmit();
            }

            async function copyText(text, what) {
                try { await navigator.clipboard.writeText(text); announce('Skopiowano ' + what + '.'); }
                catch (e) { window.prompt('Skopiuj ' + what + ':', text); }
            }
            document.querySelectorAll('[data-copy-url]').forEach(b => b.addEventListener('click', () => copyText(b.dataset.copyUrl, 'adres')));

            // ── Przeciąganie: górna/dolna część wiersza = przed/za, środek pozycji z paska = do środka (podpozycja). ──
            let dragLi = null, hoverTimer = null;
            const clearMarks = () => tree.querySelectorAll('[data-drop]').forEach(r => {
                r.removeAttribute('data-drop');
                r.classList.remove('bg-brand-light', 'ring-2', 'ring-brand-dark', 'border-t-2', 'border-b-2', 'border-brand-dark');
            });
            function zoneOf(row, li, e) {
                const r = row.getBoundingClientRect();
                const y = (e.clientY - r.top) / r.height;
                const canInside = li.dataset.canHold === '1' && dragLi.dataset.nestable === '1';
                if (canInside && y >= 0.3 && y <= 0.7) return 'inside';
                const zone = y < 0.5 ? 'before' : 'after';
                // Pozycja z podpozycjami (albo rozwijane menu) nie zejdzie poziom niżej — menu ma jeden poziom zagnieżdżenia.
                if (li.dataset.level === '2' && dragLi.dataset.nestable !== '1') return null;
                return zone;
            }
            tree.addEventListener('dragstart', e => {
                const row = rowOf(e.target);
                if (! row) { e.preventDefault(); return; }
                dragLi = liOf(row);
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', dragLi.dataset.id);
                dragLi.classList.add('opacity-50');
            });
            tree.addEventListener('dragend', () => {
                if (dragLi) dragLi.classList.remove('opacity-50');
                dragLi = null; clearTimeout(hoverTimer); clearMarks();
            });
            tree.addEventListener('dragover', e => {
                if (! dragLi) return;
                const row = rowOf(e.target);
                if (! row) return;
                const li = liOf(row);
                if (dragLi.contains(li)) { clearMarks(); return; }
                const zone = zoneOf(row, li, e);
                clearMarks();
                if (! zone) return;
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                row.dataset.drop = zone;
                if (zone === 'inside') row.classList.add('bg-brand-light', 'ring-2', 'ring-brand-dark');
                else row.classList.add(zone === 'before' ? 'border-t-2' : 'border-b-2', 'border-brand-dark');
                clearTimeout(hoverTimer);
                if (zone === 'inside' && li.hasAttribute('x-data') && ! Alpine.$data(li).open) {
                    hoverTimer = setTimeout(() => { Alpine.$data(li).open = true; }, 700);
                }
            });
            tree.addEventListener('dragleave', e => { if (! tree.contains(e.relatedTarget)) clearMarks(); });
            tree.addEventListener('drop', e => {
                if (! dragLi) return;
                e.preventDefault();
                const row = rowOf(e.target);
                if (! row) return;
                const li = liOf(row);
                if (dragLi.contains(li)) return;
                const zone = zoneOf(row, li, e), id = idOf(dragLi);
                clearMarks();
                if (! zone) return;
                if (zone === 'inside') { place(id, { parent_id: idOf(li), position: 100000 }); return; }
                const sibs = siblingsOf(li, id);
                place(id, { parent_id: parentIdOf(li), position: sibs.indexOf(li) + (zone === 'after' ? 1 : 0) });
            });

            // ── Schowek (Wytnij / Kopiuj / Wklej), zapamiętany dla witryny — działa także między menu głównym a stopką. ──
            const clipBox = document.getElementById('nav-clip');
            const clip = {
                key: 'navTreeClip:' + @js(\App\Models\SiteSetting::current()->id),
                get() { try { return JSON.parse(localStorage.getItem(this.key) || 'null'); } catch (e) { return null; } },
                put(mode, li, kids) {
                    const withKids = !! kids && li.dataset.kids === '1';
                    try { localStorage.setItem(this.key, JSON.stringify({ mode, id: idOf(li), label: li.dataset.label, location: here, kids: withKids })); } catch (e) {}
                    this.render();
                    announce((mode === 'cut' ? 'Wycięto' : 'Skopiowano') + ' „' + li.dataset.label + '”' + (withKids ? ' z podpozycjami' : '') + '. Wklej ją z menu innej pozycji albo skrótem Ctrl+V.');
                },
                clear() { try { localStorage.removeItem(this.key); } catch (e) {} this.render(); },
                render() {
                    const c = this.get();
                    tree.querySelectorAll('.tree-cut').forEach(n => n.classList.remove('tree-cut'));
                    clipBox.hidden = ! c;
                    if (! c) return;
                    clipBox.querySelector('[data-clip-text]').textContent = 'W schowku: „' + c.label + '” ('
                        + (c.mode === 'cut' ? 'wycięta' : (c.kids ? 'kopia z podpozycjami' : 'kopia'))
                        + (c.location !== here ? ', z: ' + (locLabels[c.location] || c.location) : '') + ')';
                    if (c.mode === 'cut' && c.location === here) nodeById(c.id)?.classList.add('tree-cut');
                },
            };
            clipBox.querySelector('[data-clip-clear]').addEventListener('click', () => { clip.clear(); announce('Wyczyszczono schowek.'); });
            clipBox.querySelector('[data-clip-paste-end]').addEventListener('click', () => paste(null, false));
            clip.render();

            // Wklejenie za pozycją (into = false), jako jej podpozycję (into = true) albo na końcu menu (target = null).
            function paste(target, into) {
                const c = clip.get();
                if (! c) { announce('Schowek jest pusty.'); return; }
                const cutLi = c.mode === 'cut' && c.location === here ? nodeById(c.id) : null;
                if (target && (String(c.id) === target.dataset.id || (cutLi && cutLi.contains(target)))) { announce('Nie można wkleić pozycji w nią samą ani w jej podpozycję.'); return; }
                let parentId = null, position = 100000;
                if (target && into) parentId = idOf(target);
                else if (target) {
                    parentId = parentIdOf(target);
                    const sibs = siblingsOf(target, c.location === here ? c.id : 0);
                    position = sibs.indexOf(target) + 1;
                }
                if (c.mode === 'cut') { clip.clear(); place(c.id, { parent_id: parentId, position }); return; }
                submitForm(urlFor('copy', c.id), 'POST', null, { location: here, parent_id: parentId || '', position, z_podpozycjami: c.kids ? '1' : '0' });
            }

            // ── Menu kontekstowe ──
            const ctxItems = () => [...ctx.querySelectorAll('[role=menuitem]')].filter(b => ! b.hidden);
            let ctxLi = null;

            function openCtx(li, x, y, focusFirst) {
                ctxLi = li;
                const d = li.dataset, sibs = siblingsOf(li, 0), idx = sibs.indexOf(li), prev = sibs[idx - 1];
                ctx.querySelector('[data-ctx-title]').textContent = d.label;
                ctx.setAttribute('aria-label', 'Akcje pozycji: ' + d.label);
                ctx.querySelector('[data-ctx-toggle]').textContent = d.active === '1' ? 'Ukryj w menu' : 'Pokaż w menu';
                ctx.querySelector('[data-ctx-toggle-icon]').className = 'fa-solid w-4 text-center text-gray-500 ' + (d.active === '1' ? 'fa-eye-slash' : 'fa-eye');
                ctx.querySelector('[data-act=view]').hidden = ! d.publicUrl;
                ctx.querySelector('[data-act=copy-url]').hidden = ! d.publicUrl;
                ctx.querySelector('[data-act=add]').hidden = d.canHold !== '1';
                ctx.querySelector('[data-act=page-edit]').hidden = ! d.pageEdit;
                ctx.querySelector('[data-act=page-tree]').hidden = ! d.pageTree;
                ctx.querySelector('[data-needs-kids]').hidden = d.kids !== '1';
                ctx.querySelector('[data-act=up]').setAttribute('aria-disabled', String(idx <= 0));
                ctx.querySelector('[data-act=down]').setAttribute('aria-disabled', String(idx >= sibs.length - 1));
                ctx.querySelector('[data-act=indent]').hidden = d.level !== '1' || d.nestable !== '1' || ! prev || prev.dataset.canHold !== '1';
                ctx.querySelector('[data-act=outdent]').hidden = d.level !== '2';
                const c = clip.get(), cutLi = c && c.mode === 'cut' && c.location === here ? nodeById(c.id) : null;
                const pasteOff = ! c || String(c.id) === d.id || (cutLi && cutLi.contains(li));
                ctx.querySelectorAll('[data-paste]').forEach(b => { b.hidden = pasteOff || (b.hasAttribute('data-needs-hold') && d.canHold !== '1'); });
                if (! pasteOff) {
                    const what = (c.mode === 'cut' ? 'Wklej „' : 'Wklej kopię „') + c.label + '”';
                    ctx.querySelector('[data-paste-after]').textContent = what + ' za tą pozycją';
                    ctx.querySelector('[data-paste-into]').textContent = what + ' jako podpozycję';
                }
                tree.querySelectorAll('.tree-ctx-on').forEach(r => r.classList.remove('tree-ctx-on'));
                li.querySelector('[data-nav-row]').classList.add('tree-ctx-on');
                ctx.hidden = false;
                const w = ctx.offsetWidth, h = ctx.offsetHeight;
                ctx.style.left = Math.max(8, Math.min(x, window.innerWidth - w - 8)) + 'px';
                ctx.style.top = Math.max(8, Math.min(y, window.innerHeight - h - 8)) + 'px';
                (focusFirst ? ctxItems()[0] : ctx).focus({ preventScroll: true });
            }

            function closeCtx(returnFocus) {
                if (ctx.hidden) return;
                ctx.hidden = true;
                tree.querySelectorAll('.tree-ctx-on').forEach(r => r.classList.remove('tree-ctx-on'));
                if (returnFocus && ctxLi) ctxLi.querySelector('[data-nav-row] > a')?.focus();
            }

            function move(li, dir) {
                const id = idOf(li), parentId = parentIdOf(li), sibs = siblingsOf(li, 0), idx = sibs.indexOf(li), prev = sibs[idx - 1];
                if (dir === 'up' && idx > 0) place(id, { parent_id: parentId, position: idx - 1 });
                else if (dir === 'down' && idx < sibs.length - 1) place(id, { parent_id: parentId, position: idx + 1 });
                else if (dir === 'indent' && prev && li.dataset.level === '1' && li.dataset.nestable === '1' && prev.dataset.canHold === '1') place(id, { parent_id: idOf(prev), position: 100000 });
                else if (dir === 'outdent' && parentId) {
                    const parentLi = li.parentElement.closest('[data-nav-node]');
                    place(id, { parent_id: null, position: siblingsOf(parentLi, 0).indexOf(parentLi) + 1 });
                } else announce('Nie można przesunąć pozycji w tym kierunku.');
            }

            function run(act) {
                const li = ctxLi, d = li.dataset, id = d.id;
                const btn = ctx.querySelector('[data-act="' + act + '"]');
                if (btn?.getAttribute('aria-disabled') === 'true') return;
                closeCtx(! ['edit', 'add', 'view', 'page-edit', 'page-tree'].includes(act));
                if (act === 'edit') {
                    // Wybrana pozycja ma przycisk „Edytuj pozycję” w karcie; inną najpierw wybieramy (#edytuj otwiera formularz).
                    if (String(selectedId) === id) document.querySelector('[data-nav-edit]')?.click();
                    else location.href = indexUrl(here, id, '#edytuj');
                } else if (act === 'add') {
                    if (String(selectedId) === id) document.querySelector('#nav-add-sub-menu [data-parent]')?.click();
                    else location.href = indexUrl(here, id, '#dodaj');
                } else if (act === 'view') window.open(d.publicUrl, '_blank', 'noopener');
                else if (act === 'page-edit') location.href = d.pageEdit;
                else if (act === 'page-tree') location.href = d.pageTree;
                else if (act === 'toggle') submitForm(urlFor('toggle', id), 'PATCH');
                else if (act === 'dup') submitForm(urlFor('copy', id), 'POST', 'Zduplikować „' + d.label + '”' + (d.kids === '1' ? ' razem z podpozycjami' : '') + '? Kopia stanie zaraz za nią i będzie ukryta.', { z_podpozycjami: '1' });
                else if (act === 'cut') clip.put('cut', li, true);
                else if (act === 'copy') clip.put('copy', li, true);
                else if (act === 'copy-one') clip.put('copy', li, false);
                else if (act === 'paste-after') paste(li, false);
                else if (act === 'paste-into') paste(li, true);
                else if (['up', 'down', 'indent', 'outdent'].includes(act)) move(li, act);
                else if (act === 'copy-url') copyText(d.publicUrl, 'adres');
                else if (act === 'copy-id') copyText(id, 'ID pozycji');
                else if (act === 'delete') submitForm(urlFor('destroy', id), 'DELETE', 'Usunąć „' + d.label + '” z menu?' + (d.kids === '1' ? ' Usunięte zostaną też podpozycje.' : '') + ' Strona, do której prowadzi, zostaje.');
            }

            tree.addEventListener('contextmenu', e => {
                const row = rowOf(e.target);
                if (! row) return;
                e.preventDefault();
                openCtx(liOf(row), e.clientX, e.clientY, false);
            });
            tree.addEventListener('click', e => {
                const btn = e.target.closest('[data-tree-menu-btn]');
                if (! btn) return;
                e.preventDefault();
                const r = btn.getBoundingClientRect();
                openCtx(liOf(btn), r.left, r.bottom + 4, false);
            });
            ctx.addEventListener('click', e => { const b = e.target.closest('[role=menuitem]'); if (b) run(b.dataset.act); });
            ctx.addEventListener('keydown', e => {
                const items = ctxItems(), i = items.indexOf(document.activeElement);
                if (e.key === 'Escape' || e.key === 'Tab') { e.preventDefault(); closeCtx(true); }
                else if (e.key === 'ArrowDown') { e.preventDefault(); items[(i + 1) % items.length].focus(); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); items[(i - 1 + items.length) % items.length].focus(); }
                else if (e.key === 'Home') { e.preventDefault(); items[0].focus(); }
                else if (e.key === 'End') { e.preventDefault(); items[items.length - 1].focus(); }
                else if (e.key.length === 1 && /\S/.test(e.key)) {
                    const k = e.key.toLowerCase();
                    const next = [...items.slice(i + 1), ...items.slice(0, i + 1)].find(b => b.textContent.trim().toLowerCase().startsWith(k));
                    if (next) { e.preventDefault(); next.focus(); }
                }
            });
            document.addEventListener('pointerdown', e => { if (! ctx.hidden && ! ctx.contains(e.target) && ! e.target.closest('[data-tree-menu-btn]')) closeCtx(false); });
            window.addEventListener('scroll', () => closeCtx(false), true);
            window.addEventListener('resize', () => closeCtx(false));

            // ── Klawiatura w drzewie (jak drzewo stron TYPO3) ──
            const focusables = () => [...tree.querySelectorAll('#nav-tree-scroll [data-nav-row] > a')].filter(el => el.offsetParent !== null && ! el.closest('[hidden]'));
            tree.addEventListener('keydown', e => {
                const el = e.target;
                if (! el.matches || ! el.matches('[data-nav-row] > a')) return;
                const li = liOf(el);
                if (e.key === 'ContextMenu' || (e.shiftKey && e.key === 'F10')) {
                    e.preventDefault();
                    const r = el.getBoundingClientRect();
                    openCtx(li, r.left + 32, r.bottom + 2, true);
                    return;
                }
                if (e.altKey && e.shiftKey && ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                    e.preventDefault();
                    move(li, { ArrowUp: 'up', ArrowDown: 'down', ArrowRight: 'indent', ArrowLeft: 'outdent' }[e.key]);
                    return;
                }
                if ((e.ctrlKey || e.metaKey) && ! e.altKey) {
                    const k = e.key.toLowerCase();
                    if (k === 'x') { e.preventDefault(); clip.put('cut', li, true); return; }
                    if (k === 'c' && ! window.getSelection().toString()) { e.preventDefault(); clip.put('copy', li, true); return; }
                    if (k === 'v' && clip.get()) { e.preventDefault(); paste(li, e.shiftKey && li.dataset.canHold === '1'); return; }
                }
                if (e.altKey || e.ctrlKey || e.metaKey || e.shiftKey) return;
                if (! ['ArrowDown', 'ArrowUp', 'ArrowRight', 'ArrowLeft', 'Home', 'End'].includes(e.key)) return;
                e.preventDefault();
                const list = focusables(), i = list.indexOf(el);
                const state = li.hasAttribute('x-data') ? Alpine.$data(li) : null;
                if (e.key === 'ArrowDown') list[i + 1]?.focus();
                else if (e.key === 'ArrowUp') list[i - 1]?.focus();
                else if (e.key === 'Home') list[0]?.focus();
                else if (e.key === 'End') list[list.length - 1]?.focus();
                else if (e.key === 'ArrowRight') {
                    if (state && ! state.open) state.open = true;
                    else if (state) Alpine.nextTick(() => focusables()[focusables().indexOf(el) + 1]?.focus());
                } else if (e.key === 'ArrowLeft') {
                    if (state && state.open) state.open = false;
                    else li.parentElement.closest('[data-nav-node]')?.querySelector(':scope > [data-nav-row] > a')?.focus();
                }
            });

            // Po przekierowaniu: #edytuj otwiera formularz wybranej pozycji, #dodaj — nową podpozycję; fokus wraca na pozycję.
            document.addEventListener('alpine:initialized', () => {
                if (location.hash === '#edytuj') document.querySelector('[data-nav-edit]')?.click();
                else if (location.hash === '#dodaj') document.querySelector('#nav-add-sub-menu [data-parent]')?.click();
                if (location.hash === '#edytuj' || location.hash === '#dodaj') history.replaceState(null, '', location.pathname + location.search);
            });
            const focusId = @js(session('focus_nav'));
            if (focusId) {
                const link = nodeById(focusId)?.querySelector(':scope > [data-nav-row] > a');
                if (link) { link.focus({ preventScroll: true }); link.scrollIntoView({ block: 'center' }); }
            }
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
