@extends('admin.layout')

@section('title', 'Strony')

@section('content')
    @php
        $canEditSelected = $selected && (! $selected->is_locked || auth()->user()->isAdmin());
        $selectedLive = $selected && $selected->is_published && ($selected->publish_at === null || $selected->publish_at->isPast());
        $btn = 'inline-flex min-h-9 items-center gap-1.5 rounded border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-ink hover:border-brand-dark hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
        $tool = 'inline-flex items-center rounded-lg border border-gray-300 bg-white text-xs font-bold text-ink hover:border-brand-dark hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
        $toolStyle = 'min-height:2.25rem;padding:.375rem .75rem;gap:.375rem';
        $seg = 'inline-flex items-center text-xs font-bold text-ink hover:bg-gray-50 hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-dark';
        $segStyle = 'min-height:2.25rem;padding:.375rem .75rem;gap:.375rem;background:transparent';
    @endphp

    {{-- Układ dwukolumnowy jest wyliczany w JS (matchMedia + style wbudowane), a nie klasami Tailwinda:
         działa nawet wtedy, gdy po wdrożeniu nie przebudowano CSS, i nigdy nie zostawia przyklejonego
         drzewa nad treścią. Uwaga: w atrybucie x-data nie wolno używać komentarzy //. --}}
    <div x-data="{
            open: (() => { try { return localStorage.getItem('pages-tree-open') !== '0'; } catch (e) { return true; } })(),
            width: (() => { try { return parseInt(localStorage.getItem('pages-tree-w'), 10) || 320; } catch (e) { return 320; } })(),
            wide: window.matchMedia('(min-width: 768px)').matches,
            init() {
                const mq = window.matchMedia('(min-width: 768px)');
                mq.addEventListener('change', e => { this.wide = e.matches; });
            },
            full: (() => { try { return localStorage.getItem('pages-tree-full') === '1'; } catch (e) { return false; } })(),
            get twoCols() { return this.wide && this.open && ! this.full; },
            setFull(v) { this.full = v; if (v) this.open = true; this.persist('pages-tree-full', v ? '1' : '0'); },
            persist(k, v) { try { localStorage.setItem(k, v); } catch (e) {} },
            toggle() { this.open = ! this.open; this.persist('pages-tree-open', this.open ? '1' : '0'); },
            setWidth(w) { this.width = Math.min(560, Math.max(224, Math.round(w))); this.persist('pages-tree-w', this.width); },
            drag(e) {
                const startX = e.clientX, start = this.width;
                const move = ev => this.setWidth(start + ev.clientX - startX);
                const up = () => { window.removeEventListener('pointermove', move); window.removeEventListener('pointerup', up); document.body.classList.remove('select-none'); };
                document.body.classList.add('select-none');
                window.addEventListener('pointermove', move);
                window.addEventListener('pointerup', up);
            },
            resizeKey(e) {
                const step = e.shiftKey ? 48 : 16;
                if (e.key === 'ArrowLeft') this.setWidth(this.width - step);
                else if (e.key === 'ArrowRight') this.setWidth(this.width + step);
                else if (e.key === 'Home') this.setWidth(224);
                else if (e.key === 'End') this.setWidth(560);
                else return;
                e.preventDefault();
            },
         }"
         x-init="(() => { try { if (localStorage.getItem('admin-sidebar') === null) $store.adminNav.collapsed = true; } catch (e) {} })()">

    @push('content-tab-actions')
        {{-- Grupa 1: widok (co widzę) --}}
        <div role="group" aria-label="Widok i filtry" style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem">
            <span class="text-muted" style="font-size:.6875rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase" aria-hidden="true">Widok</span>
            <div style="display:inline-flex;overflow:hidden;border:1px solid #d1d5db;border-radius:.5rem;background:#fff">
                <button type="button" @click="toggle()" :aria-expanded="open.toString()" aria-controls="page-tree" aria-expanded="true"
                    class="{{ $seg }}" style="{{ $segStyle }}">
                    <i class="fa-solid" :class="open ? 'fa-table-columns' : 'fa-sitemap'" aria-hidden="true"></i>
                    <span x-text="open ? 'Ukryj drzewo' : 'Pokaż drzewo'">Ukryj drzewo</span>
                </button>
                <button type="button" @click="setFull(! full)" :aria-pressed="full.toString()" aria-pressed="false"
                    class="{{ $seg }}" style="{{ $segStyle }};border-left:1px solid #d1d5db" title="Drzewo na całą szerokość ekranu, w kolumnach (bez panelu szczegółów)">
                    <i class="fa-solid" :class="full ? 'fa-compress' : 'fa-expand'" aria-hidden="true"></i>
                    <span x-text="full ? 'Drzewo i szczegóły' : 'Drzewo na całą stronę'">Drzewo na całą stronę</span>
                </button>
                <button type="button" @click="$store.adminNav.toggleCollapsed()" :aria-pressed="$store.adminNav.collapsed.toString()" aria-pressed="false"
                    class="{{ $seg }}" style="{{ $segStyle }};border-left:1px solid #d1d5db" title="Zwiń lub rozwiń menu boczne panelu">
                    <i class="fa-solid fa-bars" aria-hidden="true"></i>
                    <span x-text="$store.adminNav.collapsed ? 'Rozwiń menu' : 'Zwiń menu'">Zwiń menu</span>
                </button>
            </div>
            <button type="button" onclick="openBulkDialog()" aria-haspopup="dialog" class="{{ $tool }}" style="{{ $toolStyle }}">
                <i class="fa-solid fa-filter" aria-hidden="true"></i> Filtry i operacje
            </button>
        </div>

        {{-- Grupa 2: akcje (co robię) --}}
        <div role="group" aria-label="Akcje strony" style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem">
            <a href="{{ route('admin.podstrony.index', ['widok' => 'lista', 'status' => 'trashed']) }}" class="{{ $tool }}" style="{{ $toolStyle }}">
                <i class="fa-solid fa-trash-can" aria-hidden="true"></i> Kosz
            </a>
            <a href="{{ route('admin.podstrony.eksport') }}" class="{{ $tool }}" style="{{ $toolStyle }}">
                <i class="fa-solid fa-file-csv" aria-hidden="true"></i> Eksport CSV
            </a>
            <span style="width:1px;height:1.5rem;background:#d1d5db" aria-hidden="true"></span>
            <a href="{{ route('admin.podstrony.create', array_filter(['parent_id' => $selected?->id])) }}"
                class="inline-flex items-center rounded bg-brand-dark text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2"
                style="min-height:2.25rem;padding:.375rem 1rem;gap:.375rem">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> {{ $selected ? 'Dodaj podstronę' : 'Dodaj stronę' }}
            </a>
        </div>
    @endpush

    @include('admin.partials.content-nav-tabs')

    <div class="grid items-start gap-4"
         :style="twoCols ? 'grid-template-columns:' + width + 'px 0.75rem minmax(0,1fr);column-gap:0' : ''">

        {{-- ═════════ Lewy panel: drzewo stron ═════════ --}}
        <nav aria-label="Drzewo stron" id="page-tree" x-show="open" :class="full && 'tree-full'" class="min-w-0 rounded-md bg-white" :style="twoCols ? 'position:sticky;top:1rem;align-self:start' : ''"
            x-data="{
                q: '',
                filter() {
                    const q = this.q.trim().toLowerCase();
                    const items = this.$root.querySelectorAll('[data-tree-node]');
                    const groups = this.$root.querySelectorAll('[data-tree-group]');
                    items.forEach(li => { li.hidden = false; });
                    groups.forEach(g => { g.hidden = false; });
                    if (! q) return;
                    groups.forEach(g => {
                        const hit = g.dataset.title.includes(q);
                        g.hidden = ! hit;
                        if (hit) Alpine.$data(g).open = true;
                    });
                    items.forEach(li => {
                        const self = li.dataset.title.includes(q);
                        const sub = [...li.querySelectorAll('[data-tree-node]')].some(d => d.dataset.title.includes(q));
                        li.hidden = ! (self || sub);
                        if (sub && li.hasAttribute('x-data')) Alpine.$data(li).open = true;
                    });
                },
                setAll(open) {
                    this.$root.querySelectorAll('[data-tree-node][x-data], [data-tree-group]').forEach(li => { Alpine.$data(li).open = open; });
                },
                // Układ drzewa: „az” — strony z podstronami na górze, w obu grupach alfabetycznie; „order” — zapisana kolejność
                // (tylko w niej da się przeciąganiem zmieniać kolejność). Wybór zapamiętany w przeglądarce.
                sort: (() => { try { return localStorage.getItem('pageTreeSort') || 'az'; } catch (e) { return 'az'; } })(),
                setSort(mode) {
                    this.sort = mode;
                    try { localStorage.setItem('pageTreeSort', mode); } catch (e) {}
                    this.applySort();
                },
                applySort() {
                    const az = this.sort === 'az';
                    this.$root.dataset.sort = this.sort;
                    this.$root.querySelectorAll('[data-tree-sep]').forEach(n => n.remove());
                    this.$root.querySelectorAll('ul[data-tree-list]').forEach(ul => {
                        const items = [...ul.children].filter(n => n.matches('[data-tree-node]'));
                        items.sort(az
                            ? (a, b) => (b.dataset.folder - a.dataset.folder) || a.dataset.title.localeCompare(b.dataset.title, 'pl')
                            : (a, b) => a.dataset.pos - b.dataset.pos);
                        items.forEach(li => ul.appendChild(li));
                        const firstPage = items.find(li => li.dataset.folder === '0');
                        if (az && firstPage && firstPage !== items[0] && ! ul.classList.contains('tree-sub')) {
                            const sep = document.createElement('li');
                            sep.className = 'tree-sep'; sep.setAttribute('aria-hidden', 'true'); sep.dataset.treeSep = '';
                            ul.insertBefore(sep, firstPage);
                        }
                    });
                },
            }" x-init="applySort()">
            <div class="space-y-2 border-b border-gray-100 p-3">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-sm font-bold text-ink"><i class="fa-solid fa-sitemap mr-1 text-brand-dark" aria-hidden="true"></i> Struktura serwisu <span class="font-normal text-muted">({{ $total }})</span></h2>
                    <div class="flex items-center gap-1">
                        <div role="group" aria-label="Układ drzewa" class="mr-1 flex flex-none whitespace-nowrap rounded bg-gray-100 p-0.5 text-[11px] font-bold">
                            <button type="button" @click="setSort('az')" :aria-pressed="(sort === 'az').toString()" title="Strony z podstronami na górze, alfabetycznie"
                                :class="sort === 'az' ? 'bg-white text-ink shadow-sm' : 'text-muted hover:text-ink'"
                                class="rounded px-1.5 py-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">A–Z</button>
                            <button type="button" @click="setSort('order')" :aria-pressed="(sort === 'order').toString()" title="Zapisana kolejność — tylko w niej przeciąganiem zmienisz kolejność"
                                :class="sort === 'order' ? 'bg-white text-ink shadow-sm' : 'text-muted hover:text-ink'"
                                class="rounded px-1.5 py-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Kolejność</button>
                        </div>
                        <button type="button" @click="setAll(true)" class="rounded p-1.5 text-xs text-muted hover:bg-gray-100 hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark" title="Rozwiń wszystko" aria-label="Rozwiń wszystkie gałęzie">
                            <i class="fa-solid fa-angles-down" aria-hidden="true"></i>
                        </button>
                        <button type="button" @click="setAll(false)" class="rounded p-1.5 text-xs text-muted hover:bg-gray-100 hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark" title="Zwiń wszystko" aria-label="Zwiń wszystkie gałęzie">
                            <i class="fa-solid fa-angles-up" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label for="tree-filter" class="sr-only">Filtruj drzewo po tytule</label>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-muted" aria-hidden="true"></i>
                        <input type="search" id="tree-filter" x-model="q" @input="filter()" placeholder="Filtruj drzewo…" autocomplete="off"
                            class="w-full rounded border-gray-500 py-1.5 pl-8 text-sm focus:border-brand-dark focus:ring-brand-dark">
                    </div>
                </div>
            </div>
            {{-- Instrukcja przeciągania tylko dla czytników ekranu (wizualnie zbędna — przeciąganie widać po kursorze);
                 skróty klawiszowe muszą zostać dostępne dla osób bez myszy. --}}
            <p id="tree-dnd-help" class="sr-only">
                Przeciągnij stronę: na górną lub dolną krawędź innej — zmienia kolejność (tylko w układzie „Kolejność”), na środek — wkłada ją jako podstronę.
                Z klawiatury: zaznacz stronę i użyj Alt+Shift+strzałek
                (góra/dół — kolejność, w prawo — w głąb poprzedniej, w lewo — poziom wyżej).
            </p>
            <div id="tree-dnd-status" role="status" aria-live="polite" class="sr-only"></div>
            <div class="overflow-y-auto p-2" :style="full ? '' : 'max-height:calc(100vh - 16rem)'" style="max-height:calc(100vh - 16rem)" id="tree-scroll"
                @click="if (full && $event.target.closest('a[href]')) setFull(false)">
                @if ($treeRoots->isEmpty() && $projectGroups->isEmpty())
                    <p class="px-2 py-6 text-center text-sm text-muted">Brak stron. Dodaj pierwszą stronę przyciskiem powyżej.</p>
                @else
                    @include('admin.pages.partials.tree-node', ['nodes' => $treeRoots, 'depth' => 0, 'canDrag' => true, 'inheritedDisabled' => $inheritedDisabled])
                    @if ($projectGroups->isNotEmpty())
                        {{-- Podstrony działań: każda grupa zwinięta, dopóki nie jest w niej wybrana strona. --}}
                        <h3 class="mx-2 mb-1 mt-3 border-t border-gray-200 pt-2 text-[11px] font-bold uppercase tracking-wide text-muted">Podstrony działań</h3>
                        <ul role="list" data-tree-groups>
                            @foreach ($projectGroups as $g)
                                <li data-tree-group data-title="{{ \Illuminate\Support\Str::lower($g['title'] . ' ' . $g['pages']->pluck('title')->implode(' ')) }}" x-data="{ open: {{ $g['open'] ? 'true' : 'false' }} }">
                                    <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-expanded="{{ $g['open'] ? 'true' : 'false' }}" aria-controls="tree-proj-{{ $g['id'] }}"
                                        class="flex min-h-7 w-full items-center gap-2 rounded py-0.5 pr-1.5 text-left text-sm font-semibold text-ink hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                        <span class="flex w-6 flex-none justify-center text-[11px] text-muted"><i class="fa-solid fa-chevron-right transition-transform" :class="open ? 'rotate-90' : ''" aria-hidden="true"></i></span>
                                        <span class="inline-flex h-4 w-4 flex-none items-center justify-center rounded-sm bg-[#166534] text-[10px] font-bold leading-none text-white" aria-hidden="true">D</span>
                                        <span class="min-w-0 flex-1 truncate"><span class="sr-only">Działanie: </span>{{ $g['title'] }}</span>
                                        <span class="rounded-full bg-gray-100 px-1.5 text-[10px] font-bold text-muted"><span class="sr-only">podstron: </span>{{ $g['pages']->count() }}</span>
                                    </button>
                                    <div id="tree-proj-{{ $g['id'] }}" x-show="open" @unless ($g['open']) x-cloak @endunless>
                                        @include('admin.pages.partials.tree-node', ['nodes' => $g['pages'], 'depth' => 1, 'canDrag' => true, 'inheritedDisabled' => $inheritedDisabled])
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endif
                <div data-tree-root-drop class="mt-2 hidden rounded border-2 border-dashed border-brand/50 px-3 py-3 text-center text-xs font-bold text-brand-dark">
                    Upuść tutaj, aby przenieść na poziom główny
                </div>
            </div>
        </nav>

        {{-- Separator: przeciąganie myszą lub strzałkami zmienia szerokość drzewa (WAI-ARIA window splitter). --}}
        <div x-show="twoCols" x-cloak role="separator" aria-orientation="vertical" tabindex="0"
             aria-label="Szerokość drzewa stron" aria-controls="page-tree"
             :aria-valuenow="width" aria-valuemin="224" aria-valuemax="560"
             @pointerdown.prevent="drag($event)" @keydown="resizeKey($event)"
             class="group flex h-full cursor-col-resize items-center justify-center self-stretch focus-visible:outline-none" style="min-height:8rem">
            <span class="h-12 w-1 rounded bg-gray-300 transition group-hover:bg-brand-dark group-focus-visible:bg-brand-dark group-focus-visible:ring-2 group-focus-visible:ring-brand-dark group-focus-visible:ring-offset-1" aria-hidden="true"></span>
        </div>

        {{-- ═════════ Prawy panel: szczegóły wybranej strony ═════════ --}}
        <section class="min-w-0 space-y-4" aria-labelledby="pane-heading" x-show="! full">
            @if ($selected)
                {{-- Ścieżka --}}
                <nav aria-label="Ścieżka strony" class="text-xs text-muted">
                    <a href="{{ route('admin.podstrony.index') }}" class="hover:text-brand-dark hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"><i class="fa-solid fa-house" aria-hidden="true"></i><span class="sr-only">Poziom główny</span></a>
                    @foreach ($rootline as $ancestor)
                        <span aria-hidden="true" class="mx-1">›</span>
                        <a href="{{ route('admin.podstrony.index', ['wybrana' => $ancestor->id]) }}" class="hover:text-brand-dark hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $ancestor->title }}</a>
                    @endforeach
                    <span aria-hidden="true" class="mx-1">›</span>
                    <span class="font-semibold text-ink" aria-current="page">{{ $selected->title }}</span>
                </nav>

                {{-- Karta strony --}}
                <div class="p-5 rounded-md bg-white">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 id="pane-heading" class="text-xl font-bold text-ink">{{ $selected->title }}</h2>
                            <p class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                                @include('admin.pages.partials.status-chip', ['page' => $selected, 'inherited' => isset($inheritedDisabled[$selected->id])])
                                <span class="font-bold text-muted">{{ \App\Models\Page::types()[$selected->type] ?? $selected->type }}</span>
                                @if ($selected->isWip())<span class="font-bold text-ink"><i class="fa-solid fa-person-digging" aria-hidden="true"></i> WIP</span>@endif
                                @if ($selected->is_featured)<span class="font-bold text-ink"><i class="fa-solid fa-star" aria-hidden="true"></i> Wyróżniona</span>@endif
                                @if ($selected->is_system)<span class="font-bold text-muted"><i class="fa-solid fa-lock" aria-hidden="true"></i> Systemowa</span>@endif
                                @if ($selected->is_locked)<span class="font-bold text-muted"><i class="fa-solid fa-user-lock" aria-hidden="true"></i> Zablokowana</span>@endif
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($canEditSelected)
                                <a href="{{ route('admin.podstrony.edit', $selected) }}"
                                    class="inline-flex min-h-9 items-center gap-1.5 rounded bg-brand-dark px-4 py-1.5 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i> Edytuj
                                </a>
                            @endif
                            <a href="{{ $selectedLive ? $selected->publicUrl() : $selected->previewUrl() }}" target="_blank" rel="noopener" class="{{ $btn }}">
                                <i class="fa-solid fa-eye" aria-hidden="true"></i> {{ $selectedLive ? 'Podgląd publiczny' : 'Podgląd roboczy' }}<span class="sr-only"> (nowa karta)</span>
                            </a>
                        </div>
                    </div>

                    @if (isset($inheritedDisabled[$selected->id]))
                        @php $disabledParent = $rootline->firstWhere('id', $inheritedDisabled[$selected->id]); @endphp
                        <p class="mt-3 rounded-md bg-red-50 px-3 py-2 text-xs text-red-800" role="status">
                            <i class="fa-solid fa-ban mr-1" aria-hidden="true"></i>
                            Ta strona jest niedostępna dla odwiedzających, bo wyłączono stronę nadrzędną
                            @if ($disabledParent)„<a href="{{ route('admin.podstrony.index', ['wybrana' => $disabledParent->id]) }}" class="font-bold underline">{{ $disabledParent->title }}</a>”@endif.
                            Włącz ją, aby przywrócić dostęp do podstron.
                        </p>
                    @endif

                    @unless ($canEditSelected)
                        <p class="mt-3 rounded border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">
                            <i class="fa-solid fa-user-lock mr-1" aria-hidden="true"></i> Strona jest zablokowana do edycji przez administratora.
                        </p>
                    @endunless

                    <dl class="mt-4 grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2 xl:grid-cols-3">
                        <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Adres</dt><dd class="mt-0.5 break-all font-mono text-xs">/{{ $selected->slug }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Strona nadrzędna</dt>
                            <dd class="mt-0.5">@if ($rootline->isNotEmpty())<a href="{{ route('admin.podstrony.index', ['wybrana' => $rootline->last()->id]) }}" class="text-brand-dark hover:underline">{{ $rootline->last()->title }}</a>@else<span class="text-muted">— strona najwyższego poziomu</span>@endif</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Kolejność</dt><dd class="mt-0.5">{{ $selected->order }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">W menu głównym</dt><dd class="mt-0.5">{{ ! $selected->parent_id && $selected->show_in_menu ? 'Tak' : 'Nie' }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Nawigacja po podstronach</dt><dd class="mt-0.5">{{ $selected->show_side_nav ? (\App\Models\Page::SIDE_NAV_STYLES[$selected->side_nav_style] ?? 'Boczne drzewo') : 'Wyłączona' }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Ostatnia zmiana</dt><dd class="mt-0.5">{{ $selected->updated_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
                        @if ($selected->publish_at && ! $selectedLive && $selected->is_published)
                            <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Publikacja</dt><dd class="mt-0.5">{{ $selected->publish_at->format('d.m.Y H:i') }}</dd></div>
                        @endif
                        @if ($personsCount)
                            <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Osoby</dt><dd class="mt-0.5"><a href="{{ route('admin.osoby.index') }}" class="text-brand-dark hover:underline">{{ $personsCount }} {{ trans_choice('osoba|osoby|osób', $personsCount) }}</a></dd></div>
                        @endif
                    </dl>

                    {{-- Szybkie akcje --}}
                    @if ($canEditSelected)
                        <div class="mt-5 flex flex-wrap gap-2 border-t border-gray-100 pt-4" role="group" aria-label="Szybkie akcje strony">
                            <form method="POST" action="{{ route('admin.podstrony.widocznosc', $selected) }}">
                                @csrf @method('PATCH') <input type="hidden" name="wybrana" value="{{ $selected->id }}">
                                <button type="submit" class="{{ $btn }}">
                                    <i class="fa-solid {{ $selected->is_published ? 'fa-eye-slash' : 'fa-eye text-green-600' }}" aria-hidden="true"></i> {{ $selected->is_published ? 'Cofnij publikację' : 'Opublikuj' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.podstrony.wylacz', $selected) }}">
                                @csrf @method('PATCH') <input type="hidden" name="wybrana" value="{{ $selected->id }}">
                                <button type="submit" class="{{ $btn }}">
                                    <i class="fa-solid {{ $selected->is_disabled ? 'fa-power-off text-green-600' : 'fa-ban' }}" aria-hidden="true"></i> {{ $selected->is_disabled ? 'Włącz stronę' : 'Wyłącz stronę' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.podstrony.wyroznienie', $selected) }}">
                                @csrf @method('PATCH') <input type="hidden" name="wybrana" value="{{ $selected->id }}">
                                <button type="submit" class="{{ $btn }}">
                                    <i class="fa-{{ $selected->is_featured ? 'solid text-amber-500' : 'regular' }} fa-star" aria-hidden="true"></i> {{ $selected->is_featured ? 'Usuń wyróżnienie' : 'Wyróżnij' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.podstrony.clone', $selected) }}" data-confirm="Zduplikować stronę „{{ $selected->title }}”? Kopia zostanie zapisana jako szkic.">
                                @csrf
                                <button type="submit" class="{{ $btn }}"><i class="fa-solid fa-clone" aria-hidden="true"></i> Klonuj</button>
                            </form>
                            @if ($projectOptions->isNotEmpty() && ! $selected->is_system)
                                <div class="relative" x-data="{ open: false }" @keydown.escape="open = false" @click.outside="open = false">
                                    <button type="button" class="{{ $btn }}" @click="open = ! open" :aria-expanded="open.toString()" aria-controls="to-project-panel">
                                        <i class="fa-solid fa-diagram-project" aria-hidden="true"></i> Przenieś do działania
                                    </button>
                                    <form id="to-project-panel" x-show="open" x-cloak method="POST" action="{{ route('admin.podstrony.do-projektu', $selected) }}"
                                        class="absolute left-0 top-full z-30 mt-2 w-80 space-y-3 rounded-lg border border-gray-200 bg-white p-4 shadow-xl">
                                        @csrf
                                        <div>
                                            <label for="to-project" class="mb-1 block text-xs font-bold text-ink">Działanie</label>
                                            <select id="to-project" name="project_id" required class="w-full rounded border-gray-500 py-1.5 text-sm focus:border-brand-dark focus:ring-brand-dark">
                                                <option value="" disabled @selected(! $selected->project_id)>— wybierz działanie —</option>
                                                @foreach ($projectOptions as $po)
                                                    <option value="{{ $po->id }}" @selected($selected->project_id === $po->id)>{{ $po->title }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label for="to-project-display" class="mb-1 block text-xs font-bold text-ink">Pokaż w projekcie jako</label>
                                            <select id="to-project-display" name="project_display" class="w-full rounded border-gray-500 py-1.5 text-sm focus:border-brand-dark focus:ring-brand-dark">
                                                @foreach (\App\Models\Page::PROJECT_DISPLAYS as $v => $l)
                                                    <option value="{{ $v }}" @selected(($selected->project_display ?? 'tab') === $v || ($v === 'tab' && ! $selected->project_id))>{{ \Illuminate\Support\Str::before($l, ' (') }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <label class="flex items-start gap-2 text-xs text-ink">
                                            <input type="checkbox" name="hide_from_menu" value="1" checked class="mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                                            <span>Usuń z menu głównego (ukryj stronę i pozycje menu, które do niej prowadzą)</span>
                                        </label>
                                        <p class="text-xs text-muted">Strona razem z podstronami trafi do drzewa podstron projektu. Adres URL się nie zmienia.</p>
                                        <div class="flex items-center gap-2">
                                            <button type="submit" class="rounded bg-brand-dark px-4 py-1.5 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Przenieś</button>
                                            <button type="button" @click="open = false" class="text-sm text-muted hover:text-brand-dark">Anuluj</button>
                                        </div>
                                    </form>
                                </div>
                            @endif
                            @unless ($selected->is_system)
                                <form method="POST" action="{{ route('admin.podstrony.destroy', $selected) }}" class="ml-auto" data-confirm="Usunąć stronę „{{ $selected->title }}”? Trafi do kosza.">
                                    @csrf @method('DELETE') <input type="hidden" name="wybrana" value="{{ $selected->id }}">
                                    <button type="submit" class="inline-flex min-h-9 items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-bold text-red-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 bg-red-50 hover:bg-red-100">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń
                                    </button>
                                </form>
                            @endunless
                        </div>

                        {{-- Przeniesienie w drzewie --}}
                        <form method="POST" action="{{ route('admin.podstrony.przenies', $selected) }}" class="mt-4 flex flex-wrap items-end gap-2 rounded-lg bg-gray-50 p-3">
                            @csrf @method('PATCH')
                            <div class="min-w-56 flex-1">
                                <label for="move-parent" class="mb-1 block text-xs font-bold text-ink">Przenieś w drzewie</label>
                                <select id="move-parent" name="parent_id" class="w-full rounded border-gray-500 py-1.5 text-sm focus:border-brand-dark focus:ring-brand-dark">
                                    <option value="" @selected(! $selected->parent_id)>— poziom główny —</option>
                                    @foreach ($moveOptions as $opt)
                                        <option value="{{ $opt['id'] }}" @selected($selected->parent_id === $opt['id']) @disabled($opt['disabled'])>{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="{{ $btn }}"><i class="fa-solid fa-arrows-up-down-left-right" aria-hidden="true"></i> Przenieś</button>
                            <p class="basis-full text-xs text-muted">Adres URL strony nie zmienia się. Strona nie może trafić do samej siebie ani do własnej podstrony.</p>
                        </form>
                    @endif
                </div>

                {{-- Podstrony --}}
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-ink">Podstrony <span class="font-normal text-muted">({{ $children->count() }})</span></h3>
                        <a href="{{ route('admin.podstrony.create', ['parent_id' => $selected->id]) }}" class="{{ $btn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj podstronę</a>
                    </div>
                    @if ($children->isEmpty())
                        <div class="rounded-lg border border-dashed border-gray-300 bg-white px-4 py-8 text-center text-sm text-muted">
                            Ta strona nie ma jeszcze podstron.
                        </div>
                    @else
                        @include('admin.pages.partials.children-table', ['children' => $children, 'selected' => $selected, 'inheritedDisabled' => $inheritedDisabled])
                    @endif
                </div>
            @else
                {{-- Serwis bez stron --}}
                <h2 id="pane-heading" class="sr-only">Szczegóły strony</h2>
                <div class="rounded-lg border border-dashed border-gray-300 bg-white px-4 py-12 text-center text-sm text-muted">
                    Nie ma jeszcze żadnych stron. Dodaj pierwszą przyciskiem „Dodaj stronę”.
                </div>
            @endif
        </section>
    </div>

    {{-- ═════════ Modal: filtry i operacje zbiorcze ═════════
         Natywny <dialog> + showModal(): przeglądarka sama zamyka go klawiszem Escape, ogranicza fokus do
         okna, oznacza tło jako nieaktywne i oddaje fokus przyciskowi, który go otworzył (WCAG 2.1.2, 2.4.3, 4.1.2). --}}
    <style>
        /* Drzewo stron: podstrony z linią pionową spod strzałki rodzica i krótkim łącznikiem do każdego wiersza. */
        #page-tree .tree-sub { margin-left: .75rem; padding-left: .625rem; border-left: 2px solid #c4c9d2; }
        #page-tree .tree-sub > li { position: relative; }
        #page-tree .tree-sub > li::before { content: ''; position: absolute; left: -.625rem; top: .875rem; width: .5rem; border-top: 2px solid #c4c9d2; }
        /* Widok „Drzewo na całą stronę”: gałęzie najwyższego poziomu w kolumnach, bez dzielenia gałęzi między kolumny. */
        #page-tree.tree-full #tree-scroll > ul { columns: 22rem; column-gap: 1.5rem; }
        #page-tree.tree-full #tree-scroll > ul > li { break-inside: avoid; }
        #page-tree .tree-sep { margin: .375rem .5rem; border-top: 1px solid #e5e7eb; }
        #bulk-dialog::backdrop { background: rgba(17, 24, 39, .55); }
        #bulk-dialog { margin: auto; }
    </style>
    <dialog id="bulk-dialog" aria-labelledby="bulk-title" aria-describedby="bulk-desc"
        class="rounded-xl border border-gray-200 bg-white p-0 text-ink shadow-2xl"
        style="width: min(60rem, calc(100vw - 2rem)); max-height: calc(100vh - 2rem); overflow: hidden"
        x-data="{
            count: 0,
            action: 'publish',
            confirming: false,
            labels: { publish: 'Opublikuj', unpublish: 'Cofnij publikację (szkic)', disable: 'Wyłącz (wraz z podstronami)', enable: 'Włącz', feature: 'Wyróżnij', unfeature: 'Cofnij wyróżnienie', trash: 'Przenieś do kosza' },
            update() { this.count = this.$root.querySelectorAll('input[name=\'ids[]\']:checked').length; this.confirming = false; },
            setAll(on) {
                this.$root.querySelectorAll('[data-bulk-item]:not([hidden]) > label input[name=\'ids[]\']:not(:disabled)').forEach(c => { c.checked = on; });
                this.update();
            },
            filterList(q) {
                q = q.trim().toLowerCase();
                this.$root.querySelectorAll('[data-bulk-item]').forEach(li => {
                    const self = li.dataset.title.includes(q);
                    const sub = [...li.querySelectorAll('[data-bulk-item]')].some(d => d.dataset.title.includes(q));
                    li.hidden = q !== '' && ! (self || sub);
                });
            },
            ask() { if (this.count > 0) { this.confirming = true; this.$nextTick(() => this.$refs.confirmBtn.focus()); } },
         }"
        @click="if ($event.target === $el) $el.close()">
        <div style="display: flex; flex-direction: column; max-height: calc(100vh - 2rem)">
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-4">
                <div>
                    <h2 id="bulk-title" class="text-lg font-bold">Filtry i operacje zbiorcze</h2>
                    <p id="bulk-desc" class="mt-0.5 text-sm text-muted">Przefiltruj strony w widoku listy albo zmień status wielu stron naraz. Klawisz Escape zamyka okno.</p>
                </div>
                <button type="button" onclick="this.closest('dialog').close()"
                    class="inline-flex min-h-9 flex-none items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-bold hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark bg-white hover:bg-brand-light text-ink">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i> Zamknij
                </button>
            </div>

            <div class="overflow-y-auto px-6 py-5" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(19rem, 100%), 1fr)); gap: 2rem; align-items: start; overflow-x: hidden">

                {{-- Filtry: przechodzą do zakładki „Lista stron” z gotowymi parametrami --}}
                <form method="GET" action="{{ route('admin.podstrony.index') }}" class="space-y-4" style="min-width: 0">
                    <input type="hidden" name="widok" value="lista">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-muted">Filtry</h3>
                    <div>
                        <label for="bulk-q" class="mb-1 block text-sm font-bold">Szukaj w tytule lub adresie</label>
                        <input type="search" id="bulk-q" name="q" autocomplete="off" autofocus
                            class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                    </div>
                    <div>
                        <label for="bulk-status" class="mb-1 block text-sm font-bold">Status</label>
                        <select id="bulk-status" name="status" class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                            <option value="">Wszystkie</option>
                            <option value="published">Opublikowane</option>
                            <option value="draft">Szkice</option>
                            <option value="trashed">Kosz</option>
                        </select>
                    </div>
                    <div>
                        <label for="bulk-sort" class="mb-1 block text-sm font-bold">Sortowanie</label>
                        <select id="bulk-sort" name="sort" class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                            <option value="default">Domyślne (kolejność)</option>
                            <option value="title_asc">Tytuł A–Z</option>
                            <option value="title_desc">Tytuł Z–A</option>
                        </select>
                    </div>
                    <button type="submit" class="inline-flex min-h-10 items-center gap-2 rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Pokaż wyniki w liście
                    </button>
                </form>

                {{-- Operacje zbiorcze: własna lista stron z polami wyboru --}}
                <form method="POST" action="{{ route('admin.podstrony.bulk') }}" class="space-y-4" style="min-width: 0" @submit="if (! confirming) { $event.preventDefault(); ask(); }">
                    @csrf
                    <h3 class="text-sm font-bold uppercase tracking-wide text-muted">Operacje zbiorcze</h3>
                    <fieldset class="space-y-2">
                        <legend class="mb-1 text-sm font-bold">Wybierz strony</legend>
                        <label for="bulk-list-filter" class="sr-only">Filtruj listę stron po tytule</label>
                        <input type="search" id="bulk-list-filter" placeholder="Filtruj listę stron…" autocomplete="off" @input="filterList($event.target.value)"
                            class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <button type="button" @click="setAll(true)" class="rounded-md px-2.5 py-1 font-bold hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark bg-white hover:bg-brand-light text-ink">Zaznacz widoczne</button>
                            <button type="button" @click="setAll(false)" class="rounded-md px-2.5 py-1 font-bold hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark bg-white hover:bg-brand-light text-ink">Odznacz</button>
                            <span class="ml-auto font-bold" role="status" aria-live="polite">Zaznaczono: <span x-text="count">0</span></span>
                        </div>
                        <div class="overflow-y-auto p-1 rounded-md bg-white" style="max-height: 18rem" tabindex="0" aria-label="Lista stron do zaznaczenia">
                            @include('admin.pages.partials.bulk-node', ['nodes' => $byParent->get(0, collect()), 'depth' => 0])
                        </div>
                    </fieldset>

                    <div>
                        <label for="bulk-action" class="mb-1 block text-sm font-bold">Operacja</label>
                        <select id="bulk-action" name="action" x-model="action" @change="confirming = false"
                            class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                            <option value="publish">Opublikuj</option>
                            <option value="unpublish">Cofnij publikację (szkic)</option>
                            <option value="disable">Wyłącz (wraz z podstronami)</option>
                            <option value="enable">Włącz (przywróć dostępność)</option>
                            <option value="feature">Wyróżnij</option>
                            <option value="unfeature">Cofnij wyróżnienie</option>
                            <option value="trash">Przenieś do kosza</option>
                        </select>
                    </div>

                    <div x-show="! confirming">
                        <button type="submit" :disabled="count === 0" :aria-disabled="(count === 0).toString()"
                            class="inline-flex min-h-10 items-center gap-2 rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                            <i class="fa-solid fa-play" aria-hidden="true"></i> Wykonaj
                        </button>
                        <p class="mt-1 text-xs text-muted" x-show="count === 0">Zaznacz co najmniej jedną stronę.</p>
                    </div>

                    {{-- Potwierdzenie w oknie (bez drugiego modala nad natywnym dialogiem) --}}
                    <div x-show="confirming" x-cloak role="alertdialog" aria-labelledby="bulk-confirm-text" class="rounded-md bg-amber-50 p-4">
                        <p id="bulk-confirm-text" class="text-sm font-bold text-amber-900">
                            Wykonać operację „<span x-text="labels[action]"></span>” na <span x-text="count"></span> zaznaczonych stronach?
                        </p>
                        <p class="mt-1 text-xs text-amber-900" x-show="action === 'disable'">Wyłączenie obejmuje też wszystkie podstrony zaznaczonych stron.</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button type="submit" x-ref="confirmBtn"
                                class="inline-flex min-h-10 items-center gap-2 rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                                Tak, wykonaj
                            </button>
                            <button type="button" @click="confirming = false"
                                class="inline-flex min-h-10 items-center rounded-md px-4 py-2 text-sm font-bold hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark bg-white hover:bg-brand-light text-ink">
                                Anuluj
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </dialog>

    <script>
        // Otwarcie modala z blokadą przewijania tła; zamknięcie (przycisk, Escape, kliknięcie tła) ją zdejmuje.
        function openBulkDialog() {
            const dialog = document.getElementById('bulk-dialog');
            if (! dialog || dialog.open) return;
            document.documentElement.style.overflow = 'hidden';
            dialog.showModal();
        }
        document.getElementById('bulk-dialog')?.addEventListener('close', function () {
            document.documentElement.style.overflow = '';
        });
    </script>
    </div>

    <script>
        // Przeciąganie stron w drzewie. Upuszczenie na górną/dolną czwartą część wiersza
        // ustawia stronę przed/za nią (to samo rodzeństwo), na środek — jako ostatnią podstronę.
        // Serwer (PageController::reorder) jest źródłem prawdy: po sukcesie przeładowujemy widok.
        (function () {
            const tree = document.querySelector('nav[aria-label="Drzewo stron"]');
            if (! tree) return;

            const url = @js(route('admin.podstrony.uloz'));
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const status = document.getElementById('tree-dnd-status');
            const rootDrop = tree.querySelector('[data-tree-root-drop]');
            const selectedId = @js($selected?->id);
            let dragLi = null, hoverTimer = null;

            const rowOf = el => el.closest('[data-tree-row]');
            const liOf = el => el.closest('[data-tree-node]');
            const idOf = li => parseInt(li.dataset.pageId, 10);
            const parentIdOf = li => { const p = li.parentElement.closest('[data-tree-node]'); return p ? idOf(p) : 0; };
            const siblingsOf = (li, exceptId) => [...li.parentElement.children].filter(n => n.matches('[data-tree-node]') && idOf(n) !== exceptId);
            const clearMarks = () => tree.querySelectorAll('[data-drop]').forEach(r => {
                r.removeAttribute('data-drop');
                r.classList.remove('bg-brand-light', 'ring-2', 'ring-brand-dark', 'border-t-2', 'border-b-2', 'border-brand-dark');
            });
            const announce = msg => { status.textContent = ''; setTimeout(() => status.textContent = msg, 30); };

            async function send(id, parentId, position) {
                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ id, parent_id: parentId || null, position }),
                    });
                    const json = await res.json().catch(() => ({}));
                    if (! res.ok || ! json.ok) {
                        announce(json.message || 'Nie udało się przenieść strony.');
                        alert(json.message || 'Nie udało się przenieść strony.');
                        return;
                    }
                    const target = new URL(location.href);
                    target.searchParams.set('wybrana', selectedId || id);
                    location.href = target.toString();
                } catch (e) {
                    announce('Brak połączenia z serwerem.');
                    alert('Brak połączenia z serwerem. Spróbuj ponownie.');
                }
            }

            // W układzie A–Z kolejność na ekranie nie jest zapisaną kolejnością — przeciąganie tylko zmienia rodzica.
            const azMode = () => tree.dataset.sort === 'az';

            function zoneOf(row, e) {
                if (azMode()) return 'inside';
                const r = row.getBoundingClientRect();
                const y = (e.clientY - r.top) / r.height;
                return y < 0.25 ? 'before' : (y > 0.75 ? 'after' : 'inside');
            }

            tree.addEventListener('dragstart', e => {
                const row = e.target.closest ? rowOf(e.target) : null;
                if (! row || ! row.dataset.draggable) { e.preventDefault(); return; }
                dragLi = liOf(row);
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', String(idOf(dragLi)));
                dragLi.classList.add('opacity-50');
                rootDrop.classList.remove('hidden');
            });

            tree.addEventListener('dragend', () => {
                if (dragLi) dragLi.classList.remove('opacity-50');
                dragLi = null; clearTimeout(hoverTimer);
                clearMarks(); rootDrop.classList.add('hidden');
            });

            tree.addEventListener('dragover', e => {
                if (! dragLi) return;
                if (e.target.closest('[data-tree-root-drop]')) { e.preventDefault(); clearMarks(); rootDrop.classList.add('bg-brand-light'); return; }
                const row = rowOf(e.target);
                if (! row) return;
                const li = liOf(row);
                if (dragLi.contains(li)) { clearMarks(); return; }          // nie na siebie ani własnego potomka
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                const zone = zoneOf(row, e);
                clearMarks();
                row.dataset.drop = zone;
                if (zone === 'inside') row.classList.add('bg-brand-light', 'ring-2', 'ring-brand-dark');
                else row.classList.add(zone === 'before' ? 'border-t-2' : 'border-b-2', 'border-brand-dark');

                // Zwinięta gałąź rozwija się po chwili zawisu nad jej środkiem.
                clearTimeout(hoverTimer);
                if (zone === 'inside' && li.hasAttribute('x-data') && ! Alpine.$data(li).open) {
                    hoverTimer = setTimeout(() => { Alpine.$data(li).open = true; }, 700);
                }
            });

            tree.addEventListener('dragleave', e => { if (! tree.contains(e.relatedTarget)) clearMarks(); });

            tree.addEventListener('drop', e => {
                if (! dragLi) return;
                e.preventDefault();
                const id = idOf(dragLi);
                if (e.target.closest('[data-tree-root-drop]')) {
                    const roots = siblingsOf(tree.querySelector('[data-tree-node]'), id);
                    clearMarks(); send(id, 0, roots.length); return;
                }
                const row = rowOf(e.target);
                if (! row) return;
                const li = liOf(row);
                if (dragLi.contains(li)) return;
                const zone = zoneOf(row, e);
                clearMarks();
                if (zone === 'inside') {
                    send(id, idOf(li), 100000);                              // na koniec podstron
                } else {
                    const sibs = siblingsOf(li, id);
                    const idx = sibs.indexOf(li) + (zone === 'after' ? 1 : 0);
                    send(id, parentIdOf(li), idx);
                }
            });

            // Klawiatura: Alt+Shift+strzałki na linku strony w drzewie.
            tree.addEventListener('keydown', e => {
                if (! (e.altKey && e.shiftKey) || ! ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key)) return;
                const row = e.target.closest ? rowOf(e.target) : null;
                if (! row || ! row.dataset.draggable) return;
                e.preventDefault();
                const li = liOf(row), id = idOf(li), parentId = parentIdOf(li);
                const sibs = siblingsOf(li, 0), idx = sibs.indexOf(li);
                if (azMode() && (e.key === 'ArrowUp' || e.key === 'ArrowDown')) { announce('Kolejność zmienisz w układzie „Kolejność” (przełącznik nad drzewem).'); return; }
                if (e.key === 'ArrowUp' && idx > 0) send(id, parentId, idx - 1);
                else if (e.key === 'ArrowDown' && idx < sibs.length - 1) send(id, parentId, idx + 1);
                else if (e.key === 'ArrowRight' && idx > 0) send(id, idOf(sibs[idx - 1]), 100000);   // w głąb poprzedniej
                else if (e.key === 'ArrowLeft' && parentId) {                                         // poziom wyżej, tuż za rodzicem
                    const parentLi = li.parentElement.closest('[data-tree-node]');
                    send(id, parentIdOf(parentLi), parseInt(parentLi.dataset.pos, 10) + 1);
                } else announce('Nie można przesunąć strony w tym kierunku.');
            });
        })();
    </script>
@endsection
