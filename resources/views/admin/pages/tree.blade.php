@extends('admin.layout')

@section('title', 'Strony')

@section('content')
    @php
        $canEditSelected = $selected && (! $selected->is_locked || auth()->user()->isAdmin());
        $selectedLive = $selected && $selected->is_published && ($selected->publish_at === null || $selected->publish_at->isPast());
        $btn = 'inline-flex min-h-9 items-center gap-1.5 rounded border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-ink hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand';
        $tool = 'inline-flex min-h-9 items-center gap-1.5 rounded px-2.5 py-1.5 text-xs font-bold text-muted hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand';
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
            get twoCols() { return this.wide && this.open; },
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
         x-init="try { if (localStorage.getItem('admin-sidebar') === null) $store.adminNav.collapsed = true; } catch (e) {}">

    @push('content-tab-actions')
        <div class="flex items-center gap-0.5" role="group" aria-label="Układ widoku">
            <button type="button" @click="toggle()" :aria-expanded="open.toString()" aria-controls="page-tree" aria-expanded="true" class="{{ $tool }}">
                <i class="fa-solid" :class="open ? 'fa-table-columns' : 'fa-sitemap'" aria-hidden="true"></i>
                <span x-text="open ? 'Ukryj drzewo' : 'Pokaż drzewo'">Ukryj drzewo</span>
            </button>
            <button type="button" @click="$store.adminNav.toggleCollapsed()" :aria-pressed="$store.adminNav.collapsed.toString()" aria-pressed="false"
                class="{{ $tool }}" title="Zwiń lub rozwiń menu boczne panelu">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
                <span x-text="$store.adminNav.collapsed ? 'Rozwiń menu' : 'Zwiń menu'">Zwiń menu</span>
            </button>
        </div>
        <span class="mx-1 hidden h-5 w-px bg-gray-200 sm:block" aria-hidden="true"></span>
        <a href="{{ route('admin.podstrony.index', ['widok' => 'lista', 'status' => 'trashed']) }}" class="{{ $tool }}">
            <i class="fa-solid fa-trash-can" aria-hidden="true"></i> Kosz
        </a>
        <a href="{{ route('admin.podstrony.eksport') }}" class="{{ $tool }}">
            <i class="fa-solid fa-file-csv" aria-hidden="true"></i> CSV
        </a>
        <a href="{{ route('admin.podstrony.create', array_filter(['parent_id' => $selected?->id])) }}"
            class="ml-1 inline-flex min-h-9 items-center gap-1.5 rounded bg-brand px-4 py-1.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> {{ $selected ? 'Dodaj podstronę' : 'Dodaj stronę' }}
        </a>
    @endpush

    @include('admin.partials.content-nav-tabs')

    <div class="grid items-start gap-4"
         :style="twoCols ? 'grid-template-columns:' + width + 'px 0.75rem minmax(0,1fr);column-gap:0' : ''">

        {{-- ═════════ Lewy panel: drzewo stron ═════════ --}}
        <nav aria-label="Drzewo stron" id="page-tree" x-show="open" class="min-w-0 rounded-lg border border-gray-200 bg-white" :style="twoCols ? 'position:sticky;top:1rem;align-self:start' : ''"
            x-data="{
                q: '',
                filter() {
                    const q = this.q.trim().toLowerCase();
                    const items = this.$root.querySelectorAll('[data-tree-node]');
                    items.forEach(li => { li.hidden = false; });
                    if (! q) return;
                    items.forEach(li => {
                        const self = li.dataset.title.includes(q);
                        const sub = [...li.querySelectorAll('[data-tree-node]')].some(d => d.dataset.title.includes(q));
                        li.hidden = ! (self || sub);
                        if (sub && li.hasAttribute('x-data')) Alpine.$data(li).open = true;
                    });
                },
                setAll(open) {
                    this.$root.querySelectorAll('[data-tree-node][x-data]').forEach(li => { Alpine.$data(li).open = open; });
                },
            }">
            <div class="space-y-2 border-b border-gray-100 p-3">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-sm font-bold text-ink"><i class="fa-solid fa-sitemap mr-1 text-brand" aria-hidden="true"></i> Struktura serwisu <span class="font-normal text-muted">({{ $total }})</span></h2>
                    <div class="flex gap-1">
                        <button type="button" @click="setAll(true)" class="rounded p-1.5 text-xs text-muted hover:bg-gray-100 hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" title="Rozwiń wszystko" aria-label="Rozwiń wszystkie gałęzie">
                            <i class="fa-solid fa-angles-down" aria-hidden="true"></i>
                        </button>
                        <button type="button" @click="setAll(false)" class="rounded p-1.5 text-xs text-muted hover:bg-gray-100 hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" title="Zwiń wszystko" aria-label="Zwiń wszystkie gałęzie">
                            <i class="fa-solid fa-angles-up" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label for="tree-filter" class="sr-only">Filtruj drzewo po tytule</label>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-muted" aria-hidden="true"></i>
                        <input type="search" id="tree-filter" x-model="q" @input="filter()" placeholder="Filtruj drzewo…" autocomplete="off"
                            class="w-full rounded border-gray-300 py-1.5 pl-8 text-sm focus:border-brand focus:ring-brand">
                    </div>
                </div>
            </div>
            <p id="tree-dnd-help" class="border-b border-gray-100 px-3 py-2 text-xs text-muted">
                <i class="fa-solid fa-hand" aria-hidden="true"></i>
                Przeciągnij stronę: na górną lub dolną krawędź innej — zmienia kolejność, na środek — wkłada ją jako podstronę.
                Z klawiatury: zaznacz stronę i użyj <kbd class="rounded border border-gray-300 bg-gray-50 px-1">Alt</kbd>+<kbd class="rounded border border-gray-300 bg-gray-50 px-1">Shift</kbd>+strzałek
                (góra/dół — kolejność, w prawo — w głąb poprzedniej, w lewo — poziom wyżej).
            </p>
            <div id="tree-dnd-status" role="status" aria-live="polite" class="sr-only"></div>
            <div class="overflow-y-auto p-2" style="max-height:calc(100vh - 16rem)" id="tree-scroll">
                @if ($byParent->get(0, collect())->isEmpty())
                    <p class="px-2 py-6 text-center text-sm text-muted">Brak stron. Dodaj pierwszą stronę przyciskiem powyżej.</p>
                @else
                    @include('admin.pages.partials.tree-node', ['nodes' => $byParent->get(0), 'depth' => 0, 'canDrag' => true, 'inheritedDisabled' => $inheritedDisabled])
                @endif
                <div data-tree-root-drop class="mt-2 hidden rounded border-2 border-dashed border-brand/50 px-3 py-3 text-center text-xs font-bold text-brand">
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
            <span class="h-12 w-1 rounded bg-gray-300 transition group-hover:bg-brand group-focus-visible:bg-brand group-focus-visible:ring-2 group-focus-visible:ring-brand group-focus-visible:ring-offset-1" aria-hidden="true"></span>
        </div>

        {{-- ═════════ Prawy panel: szczegóły wybranej strony ═════════ --}}
        <section class="min-w-0 space-y-4" aria-labelledby="pane-heading">
            @if ($selected)
                {{-- Ścieżka --}}
                <nav aria-label="Ścieżka strony" class="text-xs text-muted">
                    <a href="{{ route('admin.podstrony.index') }}" class="hover:text-brand hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"><i class="fa-solid fa-house" aria-hidden="true"></i><span class="sr-only">Poziom główny</span></a>
                    @foreach ($rootline as $ancestor)
                        <span aria-hidden="true" class="mx-1">›</span>
                        <a href="{{ route('admin.podstrony.index', ['wybrana' => $ancestor->id]) }}" class="hover:text-brand hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">{{ $ancestor->title }}</a>
                    @endforeach
                    <span aria-hidden="true" class="mx-1">›</span>
                    <span class="font-semibold text-ink" aria-current="page">{{ $selected->title }}</span>
                </nav>

                {{-- Karta strony --}}
                <div class="rounded-lg border border-gray-200 bg-white p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 id="pane-heading" class="text-xl font-bold text-ink">{{ $selected->title }}</h2>
                            <p class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                                @include('admin.pages.partials.status-chip', ['page' => $selected, 'inherited' => isset($inheritedDisabled[$selected->id])])
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 font-semibold text-gray-700">{{ \App\Models\Page::TYPES[$selected->type] ?? $selected->type }}</span>
                                @if ($selected->isWip())<span class="rounded-full bg-orange-100 px-2 py-0.5 font-bold text-orange-700"><i class="fa-solid fa-person-digging" aria-hidden="true"></i> WIP</span>@endif
                                @if ($selected->is_featured)<span class="rounded-full bg-amber-100 px-2 py-0.5 font-bold text-amber-700"><i class="fa-solid fa-star" aria-hidden="true"></i> Wyróżniona</span>@endif
                                @if ($selected->is_system)<span class="rounded-full bg-slate-100 px-2 py-0.5 font-bold text-slate-600"><i class="fa-solid fa-lock" aria-hidden="true"></i> Systemowa</span>@endif
                                @if ($selected->is_locked)<span class="rounded-full bg-slate-100 px-2 py-0.5 font-bold text-slate-600"><i class="fa-solid fa-user-lock" aria-hidden="true"></i> Zablokowana</span>@endif
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($canEditSelected)
                                <a href="{{ route('admin.podstrony.edit', $selected) }}"
                                    class="inline-flex min-h-9 items-center gap-1.5 rounded bg-brand px-4 py-1.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
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
                        <p class="mt-3 rounded border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800" role="status">
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
                            <dd class="mt-0.5">@if ($rootline->isNotEmpty())<a href="{{ route('admin.podstrony.index', ['wybrana' => $rootline->last()->id]) }}" class="text-brand hover:underline">{{ $rootline->last()->title }}</a>@else<span class="text-muted">— strona najwyższego poziomu</span>@endif</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Kolejność</dt><dd class="mt-0.5">{{ $selected->order }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">W menu głównym</dt><dd class="mt-0.5">{{ ! $selected->parent_id && $selected->show_in_menu ? 'Tak' : 'Nie' }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Nawigacja po podstronach</dt><dd class="mt-0.5">{{ $selected->show_side_nav ? (\App\Models\Page::SIDE_NAV_STYLES[$selected->side_nav_style] ?? 'Boczne drzewo') : 'Wyłączona' }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Ostatnia zmiana</dt><dd class="mt-0.5">{{ $selected->updated_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
                        @if ($selected->publish_at && ! $selectedLive && $selected->is_published)
                            <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Publikacja</dt><dd class="mt-0.5">{{ $selected->publish_at->format('d.m.Y H:i') }}</dd></div>
                        @endif
                        @if ($personsCount)
                            <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Osoby</dt><dd class="mt-0.5"><a href="{{ route('admin.osoby.index') }}" class="text-brand hover:underline">{{ $personsCount }} {{ trans_choice('osoba|osoby|osób', $personsCount) }}</a></dd></div>
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
                            @unless ($selected->is_system)
                                <form method="POST" action="{{ route('admin.podstrony.destroy', $selected) }}" class="ml-auto" data-confirm="Usunąć stronę „{{ $selected->title }}”? Trafi do kosza.">
                                    @csrf @method('DELETE') <input type="hidden" name="wybrana" value="{{ $selected->id }}">
                                    <button type="submit" class="inline-flex min-h-9 items-center gap-1.5 rounded border border-red-200 bg-white px-3 py-1.5 text-xs font-bold text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500">
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
                                <select id="move-parent" name="parent_id" class="w-full rounded border-gray-300 py-1.5 text-sm focus:border-brand focus:ring-brand">
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
                r.classList.remove('bg-brand-light', 'ring-2', 'ring-brand', 'border-t-2', 'border-b-2', 'border-brand');
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

            function zoneOf(row, e) {
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
                if (zone === 'inside') row.classList.add('bg-brand-light', 'ring-2', 'ring-brand');
                else row.classList.add(zone === 'before' ? 'border-t-2' : 'border-b-2', 'border-brand');

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
                if (e.key === 'ArrowUp' && idx > 0) send(id, parentId, idx - 1);
                else if (e.key === 'ArrowDown' && idx < sibs.length - 1) send(id, parentId, idx + 1);
                else if (e.key === 'ArrowRight' && idx > 0) send(id, idOf(sibs[idx - 1]), 100000);   // w głąb poprzedniej
                else if (e.key === 'ArrowLeft' && parentId) {                                         // poziom wyżej, tuż za rodzicem
                    const parentLi = li.parentElement.closest('[data-tree-node]');
                    const uncles = siblingsOf(parentLi, 0);
                    send(id, parentIdOf(parentLi), uncles.indexOf(parentLi) + 1);
                } else announce('Nie można przesunąć strony w tym kierunku.');
            });
        })();
    </script>
@endsection
