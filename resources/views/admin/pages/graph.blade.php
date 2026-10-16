@extends('admin.layout')

@section('title', 'Mapa zależności stron')

@section('content')
    @php $btn = 'inline-flex min-h-10 items-center gap-2 rounded-md px-4 text-sm font-bold'; @endphp

    {{-- Style mapy wbudowane: wdrożenie nie przebudowuje Tailwinda, a te klasy są potrzebne tylko tutaj. --}}
    <style>
        .pg-vp { position: relative; height: 36rem; overflow: auto; background: #f9fafb; cursor: grab; overscroll-behavior: contain; }
        .pg-vp.pg-dragging { cursor: grabbing; user-select: none; }
        @media (max-width: 767px) { .pg-vp { height: 28rem; } }
        .pg-svg { position: absolute; left: 0; top: 0; pointer-events: none; }
        .pg-item { position: absolute; display: flex; align-items: center; gap: 6px; width: 300px; height: 40px; padding: 0 10px 0 4px;
            border-radius: 6px; background: #fff; color: #1a1a1a; font-size: 13px; line-height: 16px; cursor: pointer; text-align: left; }
        .pg-item:hover { background: #e8f0ff; }
        .pg-item:focus { outline: none; }
        .pg-item:focus-visible { outline: 3px solid #1a1a1a; outline-offset: 2px; box-shadow: 0 0 0 2px #fff; }
        .pg-main { font-weight: 700; }
        .pg-k-group { background: #e5e7eb; font-weight: 700; }
        .pg-k-menu { background: #1752bf; color: #fff; font-weight: 700; }
        .pg-k-menu:hover { background: #1a1a1a; }
        .pg-rel { background: #e8f0ff; font-weight: 700; }
        .pg-dim, .pg-k-menu.pg-dim, .pg-k-group.pg-dim { background: #f3f4f6; color: #374151; font-weight: 400; }
        .pg-dim:hover { background: #e8f0ff; }
        .pg-item[aria-selected="true"] { background: #1752bf; color: #fff; font-weight: 700; }
        .pg-chev { display: flex; flex: none; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 4px; font-size: 11px; }
        .pg-chev:hover { background: #dbe7ff; color: #1a1a1a; }
        .pg-chev i { transition: transform .15s; }
        .pg-title { flex: 1; min-width: 0; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow-wrap: anywhere; }
        .pg-meta { display: flex; flex: none; gap: 8px; font-size: 12px; font-weight: 700; color: #4b5563; }
        .pg-k-menu .pg-meta, .pg-item[aria-selected="true"] .pg-meta { color: #fff; }
        .pg-dim .pg-meta { color: #374151; }
        .pg-mark { flex: none; display: inline-block; width: 12px; height: 12px; border-radius: 9999px; }
        .pg-m-pub { background: #1752bf; }
        .pg-m-main { width: 14px; height: 14px; background: #1752bf; box-shadow: 0 0 0 2px #1a1a1a; }
        .pg-m-draft { background: #fff; box-shadow: inset 0 0 0 2px #6b7280; }
        .pg-m-orphan { background: #EA8F00; box-shadow: 0 0 0 2px #1a1a1a; }
        .pg-m-proj { width: 11px; height: 11px; margin: 0 1px; border-radius: 1px; background: #1a1a1a; transform: rotate(45deg); }
        .pg-m-none { display: none; }
        .pg-item[aria-selected="true"] .pg-mark { box-shadow: 0 0 0 2px #fff; }
        .pg-chip { display: flex; align-items: center; gap: 8px; width: 100%; min-height: 40px; padding: 8px 12px; border-radius: 6px;
            background: #fff; color: #1a1a1a; font-size: 13px; line-height: 18px; text-align: left; }
        .pg-chip:hover { background: #e8f0ff; }
        .pg-chip:focus-visible, .pg-link:focus-visible { outline: 3px solid #1a1a1a; outline-offset: 2px; }
        .pg-link { color: #1752bf; font-weight: 700; text-decoration: underline; text-underline-offset: 2px; text-align: left; }
        .pg-link:hover { text-decoration: none; }
        .pg-f-mid { display: grid; grid-template-columns: minmax(0, 1fr) 28px minmax(0, 1.1fr) 28px minmax(0, 1fr); gap: 12px; align-items: center; }
        .pg-f-arrow { text-align: center; font-size: 18px; color: #1752bf; }
        @media (max-width: 767px) { .pg-f-mid { grid-template-columns: minmax(0, 1fr); } .pg-f-arrow { transform: rotate(90deg); } }
        .pg-f-center { border-radius: 8px; background: #1752bf; color: #fff; padding: 16px; }
        .pg-combo { position: absolute; z-index: 30; left: 0; right: 0; top: 100%; margin-top: 4px; max-height: 20rem; overflow: auto;
            border-radius: 6px; background: #fff; box-shadow: 0 8px 24px rgba(0, 0, 0, .18); }
        .pg-opt { display: flex; justify-content: space-between; gap: 12px; padding: 8px 12px; font-size: 14px; color: #1a1a1a; cursor: pointer; }
        .pg-opt[aria-selected="true"] { background: #1752bf; color: #fff; }
        .pg-opt[aria-selected="true"] .pg-opt-type { color: #fff; }
        .pg-opt-type { flex: none; font-size: 12px; color: #4b5563; }
    </style>

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div class="max-w-3xl">
            <h1 class="text-2xl font-bold text-ink">Mapa zależności stron</h1>
            <p class="mt-1 text-sm text-ink">
                Drzewo stron od menu głównego: strona nadrzędna → podstrona, a po wybraniu strony — linki w treści i podpięcie pod działania.
                Pomaga zobaczyć, co się zmieni po przeniesieniu lub usunięciu strony, i znaleźć strony, do których nie da się dojść.
                Wersja tekstowa jest w <a href="#tabela-zaleznosci" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline">tabeli pod wykresem</a>.
            </p>
        </div>
        <p class="text-sm text-ink">
            Stron: <strong>{{ $counts['pages'] }}</strong> · linków między stronami: <strong>{{ $counts['links'] }}</strong> · działań: <strong>{{ $counts['projects'] }}</strong>
        </p>
    </div>

    {{-- Ostrzeżenia dla redakcji --}}
    @if ($warnings['orphans'] || $warnings['draft_links'])
        <section class="mb-6 grid gap-3 lg:grid-cols-2" aria-label="Ostrzeżenia">
            @if ($warnings['orphans'])
                <div class="rounded-md bg-white p-4">
                    <h2 class="text-sm font-bold text-ink"><i class="fa-solid fa-circle-exclamation mr-1" aria-hidden="true"></i>Strony bez wejścia ({{ count($warnings['orphans']) }})</h2>
                    <p class="mt-1 text-sm text-ink">Opublikowane, ale nie ma ich w menu, nie mają strony nadrzędnej ani działania i żadna strona do nich nie linkuje — odwiedzający trafią tu tylko z wyszukiwarki. Na mapie są w gałęzi „Poza menu”. Proponujemy, gdzie je dopiąć (strona o podobnym tytule).</p>
                    <ul class="mt-3 divide-y divide-gray-100 text-sm" role="list">
                        @foreach ($warnings['orphans'] as $o)
                            <li class="flex flex-wrap items-center gap-x-3 gap-y-2 py-2">
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('admin.podstrony.edit', $o['id']) }}" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline">{{ $o['title'] }}</a>
                                    @if ($o['suggest'])
                                        <span class="block text-ink">Proponowane miejsce: pod „{{ $o['suggest']['title'] }}”
                                            <span class="text-muted">— wspólne słowa: {{ implode(', ', $o['suggest']['words']) }}</span></span>
                                    @else
                                        <span class="block text-ink">Brak strony o podobnym tytule — dodaj ją do menu albo wybierz stronę nadrzędną w edycji.</span>
                                    @endif
                                </div>
                                @if ($o['suggest'])
                                    <form method="POST" action="{{ route('admin.podstrony.przenies', $o['id']) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="parent_id" value="{{ $o['suggest']['id'] }}">
                                        <input type="hidden" name="wroc" value="mapa">
                                        <button type="submit" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                                            <i class="fa-solid fa-turn-down" aria-hidden="true"></i> Przenieś pod „{{ \Illuminate\Support\Str::limit($o['suggest']['title'], 30) }}”<span class="sr-only">: {{ $o['title'] }}</span>
                                        </button>
                                    </form>
                                @elseif (Route::has('admin.pozycje-menu.index'))
                                    <a href="{{ route('admin.pozycje-menu.index') }}" class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light"><i class="fa-solid fa-bars" aria-hidden="true"></i> Menu nawigacji<span class="sr-only"> — dodaj: {{ $o['title'] }}</span></a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if ($warnings['draft_links'])
                <div class="rounded-md bg-white p-4">
                    <h2 class="text-sm font-bold text-ink"><i class="fa-solid fa-link-slash mr-1" aria-hidden="true"></i>Linki do szkiców ({{ count($warnings['draft_links']) }})</h2>
                    <p class="mt-1 text-sm text-ink">Opublikowana strona linkuje do strony, która nie jest opublikowana — odwiedzający zobaczą błąd.</p>
                    <ul class="mt-2 space-y-1 text-sm" role="list">
                        @foreach ($warnings['draft_links'] as $w)
                            <li><a href="{{ route('admin.podstrony.edit', $w['from_id']) }}" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline">{{ $w['from'] }}</a> → {{ $w['to'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>
    @endif

    {{-- Wykres --}}
    <section class="rounded-md bg-white" aria-labelledby="graf-tytul" x-data="pageGraph(@js(['nodes' => $nodes, 'edges' => $edges]))">
        <h2 id="graf-tytul" class="sr-only">Wykres zależności</h2>
        <p class="sr-only" aria-live="polite" x-text="announce"></p>

        <div class="flex flex-wrap items-end gap-4 border-b border-gray-100 p-4">
            {{-- Wyszukiwarka stron (combobox z listą podpowiedzi, wzorzec WAI-ARIA). --}}
            <div class="relative min-w-[16rem] flex-1" @click.outside="comboOpen = false">
                <label for="graf-szukaj" class="mb-1 block text-sm font-bold text-ink">Szukaj strony</label>
                <input id="graf-szukaj" type="text" autocomplete="off" x-model="q" placeholder="Wpisz fragment tytułu…"
                    role="combobox" aria-autocomplete="list" aria-controls="graf-wyniki" :aria-expanded="(comboOpen && matches.length > 0).toString()" aria-expanded="false"
                    :aria-activedescendant="comboOpen && matches[active] ? 'graf-opt-' + active : null"
                    @input="comboOpen = true; active = 0" @focus="comboOpen = q !== ''" @keydown="comboKey($event)"
                    class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                <ul id="graf-wyniki" role="listbox" aria-label="Pasujące strony" class="pg-combo" x-show="comboOpen && matches.length > 0" x-cloak>
                    <template x-for="(m, i) in matches" :key="m.id">
                        <li role="option" class="pg-opt" :id="'graf-opt-' + i" :aria-selected="(i === active).toString()"
                            @mousedown.prevent="choose(m)" @mousemove="active = i">
                            <span x-text="m.label"></span><span class="pg-opt-type" x-text="m.type"></span>
                        </li>
                    </template>
                </ul>
                <p class="mt-1 text-xs text-muted" x-show="comboOpen && q.trim() !== '' && matches.length === 0" x-cloak>Brak strony o takim tytule (lub ukrywają ją filtry).</p>
            </div>
            <fieldset>
                <legend class="mb-1 text-sm font-bold text-ink">Widok</legend>
                <div class="flex overflow-hidden rounded-md bg-gray-100" role="group">
                    <button type="button" @click="setMode('tree')" :aria-pressed="(mode === 'tree').toString()" :class="mode === 'tree' ? 'bg-brand-dark text-white' : 'text-ink hover:bg-brand-light'" class="min-h-10 px-4 text-sm font-bold">Drzewo</button>
                    <button type="button" @click="setMode('focus')" :aria-pressed="(mode === 'focus').toString()" :class="mode === 'focus' ? 'bg-brand-dark text-white' : 'text-ink hover:bg-brand-light'" class="min-h-10 px-4 text-sm font-bold">Wokół strony</button>
                </div>
            </fieldset>
            <fieldset class="flex flex-wrap gap-x-4 gap-y-2 text-sm text-ink">
                <legend class="mb-1 w-full text-sm font-bold text-ink">Pokaż</legend>
                <label class="flex min-h-6 items-center gap-2"><input type="checkbox" x-model="show.menu" @change="build()" class="h-4 w-4 rounded border-gray-500 text-brand-dark focus:ring-brand-dark"> Menu</label>
                <label class="flex min-h-6 items-center gap-2"><input type="checkbox" x-model="show.parent" @change="build()" class="h-4 w-4 rounded border-gray-500 text-brand-dark focus:ring-brand-dark"> Strony nadrzędne</label>
                <label class="flex min-h-6 items-center gap-2"><input type="checkbox" x-model="show.link" @change="build()" class="h-4 w-4 rounded border-gray-500 text-brand-dark focus:ring-brand-dark"> Linki w treści</label>
                <label class="flex min-h-6 items-center gap-2"><input type="checkbox" x-model="show.project" @change="build()" class="h-4 w-4 rounded border-gray-500 text-brand-dark focus:ring-brand-dark"> Działania</label>
                <label class="flex min-h-6 items-center gap-2"><input type="checkbox" x-model="show.drafts" @change="build()" class="h-4 w-4 rounded border-gray-500 text-brand-dark focus:ring-brand-dark"> Szkice</label>
            </fieldset>
        </div>

        <div class="grid lg:grid-cols-[minmax(0,1fr)_18rem]">
            <div class="min-w-0">
                {{-- Pasek narzędzi drzewa --}}
                <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 px-4 py-2" x-show="mode === 'tree'" role="toolbar" aria-label="Powiększenie i rozwijanie drzewa">
                    <button type="button" @click="zoomBy(1 / 1.2)" class="{{ $btn }} bg-gray-100 px-3 text-ink hover:bg-brand-light" aria-label="Pomniejsz"><i class="fa-solid fa-magnifying-glass-minus" aria-hidden="true"></i></button>
                    <button type="button" @click="setZoom(1)" style="min-width:4.5rem" class="{{ $btn }} justify-center bg-gray-100 px-3 text-ink hover:bg-brand-light"
                        :aria-label="'Powiększenie ' + Math.round(k * 100) + '% — przywróć 100%'" x-text="Math.round(k * 100) + '%'">100%</button>
                    <button type="button" @click="zoomBy(1.2)" class="{{ $btn }} bg-gray-100 px-3 text-ink hover:bg-brand-light" aria-label="Powiększ"><i class="fa-solid fa-magnifying-glass-plus" aria-hidden="true"></i></button>
                    <button type="button" @click="fit()" class="{{ $btn }} bg-gray-100 px-3 text-ink hover:bg-brand-light"><i class="fa-solid fa-expand" aria-hidden="true"></i> Dopasuj</button>
                    <span class="mx-1 inline-block h-6 w-px bg-gray-200" aria-hidden="true"></span>
                    <button type="button" @click="expandAll(true)" class="{{ $btn }} bg-gray-100 px-3 text-ink hover:bg-brand-light"><i class="fa-solid fa-angles-down" aria-hidden="true"></i> Rozwiń wszystko</button>
                    <button type="button" @click="expandAll(false)" class="{{ $btn }} bg-gray-100 px-3 text-ink hover:bg-brand-light"><i class="fa-solid fa-angles-up" aria-hidden="true"></i> Zwiń</button>
                    <p class="w-full text-xs text-muted">Strzałki: poruszanie po drzewie (→ rozwiń, ← zwiń), Enter: wybierz stronę, Esc: wyczyść. Przeciągnij tło, aby przesunąć; Ctrl + kółko przybliża.</p>
                </div>

                {{-- Drzewo boczne: wiersze (treeitem) na warstwie HTML, łączniki i łuki powiązań na warstwie SVG. --}}
                <div x-ref="vp" class="pg-vp" x-show="mode === 'tree'" :class="dragging ? 'pg-dragging' : ''"
                    @wheel="wheel($event)" @pointerdown="panStart($event)" @pointermove="panMove($event)" @pointerup="panEnd($event)" @pointercancel="panEnd($event)">
                    <div :style="`width:${W * k}px;height:${H * k}px;position:relative`">
                        <div :style="`width:${W}px;height:${H}px;transform:scale(${k});transform-origin:0 0;position:absolute;left:0;top:0`">
                            <svg class="pg-svg" :width="W" :height="H" :viewBox="`0 0 ${W} ${H}`" aria-hidden="true" focusable="false" x-html="svg"></svg>
                            <div x-ref="tree" role="tree" aria-label="Drzewo zależności stron" aria-describedby="graf-tytul">
                                <template x-for="r in rows" :key="r.id">
                                    <div role="treeitem" class="pg-item" :class="cls(r)" :data-id="r.id" :style="`left:${r.x}px;top:${r.y}px`"
                                        :tabindex="r.id === focusId ? 0 : -1" :aria-level="r.depth + 1" :aria-setsize="r.size" :aria-posinset="r.pos"
                                        :aria-expanded="r.hasKids ? String(!! open[r.id]) : null" :aria-selected="String(r.id === selected)" :aria-label="label(r)"
                                        @click="clickRow(r)" @dblclick="r.hasKids && toggle(r.id)" @keydown="key($event, r)" @focus="focusId = r.id">
                                        <span class="pg-chev" aria-hidden="true" @click.stop="r.hasKids && toggle(r.id)">
                                            <i x-show="r.hasKids" class="fa-solid fa-chevron-right" :style="open[r.id] ? 'transform:rotate(90deg)' : ''"></i>
                                        </span>
                                        <span class="pg-mark" :class="markCls(r.id)" aria-hidden="true"></span>
                                        <span class="pg-title" x-text="N[r.id].label"></span>
                                        <span class="pg-meta" aria-hidden="true">
                                            <span x-show="r.hasKids && ! open[r.id]" x-text="'+' + (kids[r.id] || []).length"></span>
                                            <span x-show="linkCount(r.id) > 0"><i class="fa-solid fa-link" style="font-size:10px"></i> <span x-text="linkCount(r.id)"></span></span>
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                    <p x-show="rows.length === 0" x-cloak class="p-6 text-sm text-ink">Brak stron do pokazania — sprawdź przełączniki „Pokaż”.</p>
                </div>

                {{-- Widok „Wokół strony”: wybrana strona w środku, linkujące tutaj po lewej, linkowane po prawej. --}}
                <div x-show="mode === 'focus'" x-cloak style="min-height:20rem" class="space-y-4 bg-gray-50 p-4">
                    <template x-if="! info">
                        <p class="text-sm text-ink">Wybierz stronę w drzewie albo w polu „Szukaj strony”, aby zobaczyć, co jest wokół niej.</p>
                    </template>
                    <template x-if="info">
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-start gap-4">
                                <section class="min-w-[12rem] flex-1" aria-labelledby="ws-nad">
                                    <h3 id="ws-nad" class="mb-2 text-xs font-bold uppercase tracking-widest text-muted">Wyżej w drzewie</h3>
                                    <ul class="space-y-2" role="list">
                                        <template x-for="m in info.above" :key="m.id">
                                            <li><button type="button" class="pg-chip" @click="select(m.id)"><span class="pg-mark" :class="markCls(m.id)" aria-hidden="true"></span><span><span x-text="m.label"></span> <span class="text-xs text-muted" x-text="'(' + m.rel + ')'"></span></span></button></li>
                                        </template>
                                        <li x-show="! info.above.length" class="text-sm text-ink">— nic (strona poza menu, bez strony nadrzędnej i działania)</li>
                                    </ul>
                                </section>
                            </div>
                            <div class="pg-f-mid">
                                <section aria-labelledby="ws-in">
                                    <h3 id="ws-in" class="mb-2 text-xs font-bold uppercase tracking-widest text-muted">Linkują tutaj (<span x-text="info.linksIn.length"></span>)</h3>
                                    <ul class="space-y-2" role="list">
                                        <template x-for="m in info.linksIn" :key="m.id">
                                            <li><button type="button" class="pg-chip" @click="select(m.id)"><span class="pg-mark" :class="markCls(m.id)" aria-hidden="true"></span><span x-text="m.label"></span></button></li>
                                        </template>
                                        <li x-show="! info.linksIn.length" class="text-sm text-ink">— żadna strona</li>
                                    </ul>
                                </section>
                                <div class="pg-f-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></div>
                                <div class="pg-f-center">
                                    <p class="text-xs font-bold uppercase tracking-widest" x-text="info.type"></p>
                                    <h3 class="mt-1 text-lg font-bold" x-text="info.label"></h3>
                                    <p class="mt-1 text-sm" x-text="status(info)"></p>
                                </div>
                                <div class="pg-f-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></div>
                                <section aria-labelledby="ws-out">
                                    <h3 id="ws-out" class="mb-2 text-xs font-bold uppercase tracking-widest text-muted">Linkuje do (<span x-text="info.linksOut.length"></span>)</h3>
                                    <ul class="space-y-2" role="list">
                                        <template x-for="m in info.linksOut" :key="m.id">
                                            <li><button type="button" class="pg-chip" @click="select(m.id)"><span class="pg-mark" :class="markCls(m.id)" aria-hidden="true"></span><span x-text="m.label"></span></button></li>
                                        </template>
                                        <li x-show="! info.linksOut.length" class="text-sm text-ink">— do żadnej strony</li>
                                    </ul>
                                </section>
                            </div>
                            <section aria-labelledby="ws-pod">
                                <h3 id="ws-pod" class="mb-2 text-xs font-bold uppercase tracking-widest text-muted"><span x-text="info.kind === 'project' ? 'Strony działania' : (info.kind === 'menu' ? 'Strony główne' : 'Podstrony')"></span> (<span x-text="info.children.length"></span>)</h3>
                                <ul class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3" role="list">
                                    <template x-for="m in info.children" :key="m.id">
                                        <li><button type="button" class="pg-chip" @click="select(m.id)"><span class="pg-mark" :class="markCls(m.id)" aria-hidden="true"></span><span x-text="m.label"></span></button></li>
                                    </template>
                                </ul>
                                <p x-show="! info.children.length" class="text-sm text-ink">— brak</p>
                            </section>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Panel szczegółów i legenda --}}
            <aside class="border-t border-gray-100 p-4 text-sm text-ink lg:border-l lg:border-t-0" aria-label="Szczegóły wybranej strony">
                <template x-if="! info">
                    <div>
                        <p class="font-bold">Wybierz stronę na wykresie</p>
                        <p class="mt-1 text-muted">Kliknij ją, naciśnij Enter albo znajdź ją w polu „Szukaj strony”. Zobaczysz jej linki w treści i podpięcie pod działanie.</p>
                    </div>
                </template>
                <template x-if="info">
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-muted" x-text="info.type"></p>
                            <h3 class="text-base font-bold" x-text="info.label"></h3>
                            <p x-show="info.kind === 'page'" x-text="status(info)"></p>
                        </div>
                        <div x-show="info.above.length">
                            <p class="font-bold">Wyżej w drzewie</p>
                            <ul role="list"><template x-for="m in info.above" :key="m.id"><li><button type="button" class="pg-link" @click="select(m.id)" x-text="m.label"></button> <span class="text-xs text-muted" x-text="'(' + m.rel + ')'"></span></li></template></ul>
                        </div>
                        <div x-show="info.children.length">
                            <p class="font-bold"><span x-text="info.kind === 'project' ? 'Strony działania' : (info.kind === 'menu' ? 'Strony główne' : 'Podstrony')"></span> (<span x-text="info.children.length"></span>)</p>
                            <ul role="list" class="flex flex-wrap gap-x-3"><template x-for="m in info.children" :key="m.id"><li><button type="button" class="pg-link" @click="select(m.id)" x-text="m.label"></button></li></template></ul>
                        </div>
                        <div x-show="info.linksOut.length">
                            <p class="font-bold">Linkuje do (<span x-text="info.linksOut.length"></span>)</p>
                            <ul role="list" class="flex flex-wrap gap-x-3"><template x-for="m in info.linksOut" :key="m.id"><li><button type="button" class="pg-link" @click="select(m.id)" x-text="m.label"></button></li></template></ul>
                        </div>
                        <div x-show="info.linksIn.length">
                            <p class="font-bold">Linkują tutaj (<span x-text="info.linksIn.length"></span>)</p>
                            <ul role="list" class="flex flex-wrap gap-x-3"><template x-for="m in info.linksIn" :key="m.id"><li><button type="button" class="pg-link" @click="select(m.id)" x-text="m.label"></button></li></template></ul>
                        </div>
                        <div class="flex flex-wrap gap-2 pt-1">
                            <a x-show="info.edit" :href="info.edit" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edytuj</a>
                            <a x-show="info.url" :href="info.url" target="_blank" rel="noopener" class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Otwórz<span class="sr-only"> (nowa karta)</span></a>
                            <a x-show="info.tree" :href="info.tree" class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light"><i class="fa-solid fa-sitemap" aria-hidden="true"></i> Pokaż w drzewie stron</a>
                            <button type="button" @click="clear()" class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light">Wyczyść wybór</button>
                        </div>
                    </div>
                </template>

                <h3 class="mt-6 text-xs font-bold uppercase tracking-widest text-muted">Legenda</h3>
                <ul class="mt-2 space-y-2" role="list">
                    <li class="flex items-center gap-2"><span class="inline-block h-4 w-6 rounded bg-brand-dark" aria-hidden="true"></span> Menu główne</li>
                    <li class="flex items-center gap-2"><span class="inline-block h-4 w-6 rounded bg-gray-200" aria-hidden="true"></span> Grupa (Poza menu, Działania)</li>
                    <li class="flex items-center gap-2"><span class="pg-mark pg-m-main" aria-hidden="true"></span> Strona główna (w menu)</li>
                    <li class="flex items-center gap-2"><span class="pg-mark pg-m-pub" aria-hidden="true"></span> Strona opublikowana</li>
                    <li class="flex items-center gap-2"><span class="pg-mark pg-m-draft" aria-hidden="true"></span> Szkic</li>
                    <li class="flex items-center gap-2"><span class="pg-mark pg-m-orphan" aria-hidden="true"></span> Strona bez wejścia</li>
                    <li class="flex items-center gap-2"><span class="pg-mark pg-m-proj" aria-hidden="true"></span> Działanie</li>
                    <li class="flex items-center gap-2"><i class="fa-solid fa-link w-6 text-center text-xs" aria-hidden="true"></i> Liczba linków w treści (do i z)</li>
                    <li class="flex items-center gap-2"><span class="inline-block w-6 bg-brand-dark" style="height:3px" aria-hidden="true"></span> Menu → strona główna</li>
                    <li class="flex items-center gap-2"><span class="inline-block h-0.5 w-6 bg-gray-500" aria-hidden="true"></span> Strona nadrzędna → podstrona</li>
                    <li class="flex items-center gap-2"><span class="inline-block w-6 border-t-2 border-dashed border-brand-dark" aria-hidden="true"></span> Link w treści (po wybraniu strony)</li>
                    <li class="flex items-center gap-2"><span class="inline-block w-6 border-t-2 border-dotted border-ink" aria-hidden="true"></span> Podpięta pod działanie</li>
                </ul>
            </aside>
        </div>
    </section>

    {{-- Wersja tekstowa --}}
    <section id="tabela-zaleznosci" class="mt-8" aria-labelledby="tabela-tytul">
        <h2 id="tabela-tytul" class="mb-3 text-lg font-bold text-ink">Zależności w tabeli</h2>
        <div class="overflow-x-auto rounded-md bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark" tabindex="0" role="region" aria-labelledby="tabela-tytul">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Strony, ich strony nadrzędne, działania i linki między stronami</caption>
                <thead class="bg-gray-50 text-xs font-bold uppercase tracking-widest text-muted">
                    <tr>
                        <th scope="col" class="px-4 py-3">Strona</th>
                        <th scope="col" class="px-4 py-3">Nadrzędna / działanie</th>
                        <th scope="col" class="px-4 py-3">Linkuje do</th>
                        <th scope="col" class="px-4 py-3">Linkują tutaj</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr class="border-t border-gray-100 align-top">
                            <th scope="row" class="px-4 py-3 font-normal">
                                <a href="{{ $row['edit'] }}" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline">{{ $row['title'] }}</a>
                                <span class="block text-xs text-muted">{{ $row['type'] }} · {{ $row['published'] ? 'opublikowana' : 'szkic' }}{{ $row['menu'] ? ' · w menu' : '' }}{{ $row['orphan'] ? ' · bez wejścia' : '' }}</span>
                            </th>
                            <td class="px-4 py-3 text-ink">
                                {{ $row['parent'] ?? '—' }}
                                @if ($row['project'])<span class="block text-xs text-muted">Działanie: {{ $row['project'] }}</span>@endif
                            </td>
                            <td class="px-4 py-3 text-ink">{{ collect($row['links_out'])->pluck('title')->implode(', ') ?: '—' }}</td>
                            <td class="px-4 py-3 text-ink">{{ collect($row['links_in'])->pluck('title')->implode(', ') ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    /*
     * Mapa zależności stron — drzewo boczne (lewo → prawo) bez zewnętrznych bibliotek.
     * Wiersze to elementy ARIA treeitem (płaskie drzewo z aria-level), łączniki rysuje SVG.
     * Linki w treści i podpięcia pod działania widać jako łuki dopiero po wybraniu strony.
     */
    window.pageGraph = function (data) {
        const ROW = 48, PILL = 300, PH = 40, INDENT = 28, PAD = 16, LANE = 12;
        const C = { brand: '#1752bf', ink: '#1a1a1a', gray: '#6b7280' };
        const norm = (s) => String(s || '').toLowerCase().replace(/ł/g, 'l').normalize('NFD').replace(/[̀-ͯ]/g, '');

        return {
            N: {}, idx: {}, edges: [], kids: {}, up: {}, roots: [], rows: [], rowOf: {}, hi: {},
            svg: '', W: 600, H: 300, k: 1, dragging: false, drag: null,
            open: {}, selected: '', focusId: '', mode: 'tree', announce: '',
            show: { menu: true, parent: true, link: true, project: true, drafts: true },
            q: '', comboOpen: false, active: 0,

            init() {
                data.nodes.forEach((n, i) => { this.N[n.data.id] = n.data; this.idx[n.data.id] = i; });
                this.N['g:out'] = { id: 'g:out', label: 'Poza menu', kind: 'group', type: 'Strony spoza menu, bez strony nadrzędnej' };
                this.N['g:proj'] = { id: 'g:proj', label: 'Działania', kind: 'group', type: 'Strony podpięte pod działania' };
                this.edges = data.edges.map((e) => e.data);
                this.open = { menu: true, 'g:out': true, 'g:proj': true };
                this.build();
                this.focusId = this.rows[0] ? this.rows[0].id : '';
            },

            /* Struktura drzewa zależna od przełączników „Pokaż”. */
            build() {
                const N = this.N, s = this.show, kids = {}, up = {};
                const vis = (id) => !! N[id] && N[id].kind === 'page' && (N[id].published || s.drafts);
                const add = (p, id) => { (kids[p] = kids[p] || []).push(id); up[id] = p; };
                const pages = Object.values(N).filter((n) => vis(n.id)).sort((a, b) => this.idx[a.id] - this.idx[b.id]);
                const top = [];
                pages.forEach((n) => {
                    let p = null;
                    if (s.parent) {
                        // Najbliższy widoczny przodek (ukryte szkice są pomijane).
                        const seen = new Set([n.id]);
                        let a = n.parent;
                        while (a && ! seen.has(a) && ! vis(a)) { seen.add(a); a = N[a] ? N[a].parent : null; }
                        if (a && ! seen.has(a)) p = a;
                    }
                    if (! p) p = n.main ? (s.menu && N.menu ? 'menu' : '') : (n.project && s.project ? n.project : 'g:out');
                    if (p === '') { top.push(n.id); up[n.id] = ''; } else add(p, n.id);
                });
                const byMenu = (a, b) => (N[a].menu_order ?? 1e6) - (N[b].menu_order ?? 1e6);
                if (kids.menu) kids.menu.sort(byMenu);
                top.sort(byMenu);
                if (s.project) {
                    Object.values(N).filter((n) => n.kind === 'project').sort((a, b) => a.label.localeCompare(b.label, 'pl')).forEach((n) => add('g:proj', n.id));
                }
                const roots = [];
                if (kids.menu) roots.push('menu');
                roots.push(...top);
                ['g:out', 'g:proj'].forEach((g) => { if (kids[g]) roots.push(g); });
                roots.forEach((r) => { up[r] = ''; });

                // Strony nieosiągalne od korzeni (pętla w stronach nadrzędnych) trafiają do „Poza menu”.
                const reached = new Set();
                const walk = (id) => { if (reached.has(id)) return; reached.add(id); (kids[id] || []).forEach(walk); };
                roots.forEach(walk);
                const lost = pages.filter((n) => ! reached.has(n.id)).map((n) => n.id);
                lost.forEach((id) => { kids[up[id]] = (kids[up[id]] || []).filter((x) => x !== id); add('g:out', id); });
                if (lost.length && ! roots.includes('g:out')) { roots.splice(roots.includes('g:proj') ? roots.length - 1 : roots.length, 0, 'g:out'); up['g:out'] = ''; }

                this.kids = kids; this.up = up; this.roots = roots;
                if (this.selected && ! (this.selected in up)) this.selected = '';
                this.layout();
            },

            /* Widoczne wiersze, współrzędne i warstwa SVG. */
            layout() {
                const rows = [], rowOf = {};
                const walk = (ids, depth, parent) => ids.forEach((id, i) => {
                    if (rowOf[id] !== undefined) return;
                    const kids = this.kids[id] || [];
                    const r = { id, depth, parent, pos: i + 1, size: ids.length, hasKids: kids.length > 0, x: PAD + depth * INDENT, y: PAD + rows.length * ROW };
                    rowOf[id] = rows.length; rows.push(r);
                    if (r.hasKids && this.open[id]) walk(kids, depth + 1, id);
                });
                walk(this.roots, 0, '');

                const sel = this.selected, rel = sel ? this.related(sel, true) : [];
                const hi = {};
                if (sel) {
                    if (this.up[sel]) hi[this.up[sel]] = true;
                    (this.kids[sel] || []).forEach((c) => { hi[c] = true; });
                    rel.forEach((r) => { hi[r.id] = true; });
                }

                const right = rows.reduce((m, r) => Math.max(m, r.x + PILL), PAD + PILL);
                const gutter = right + 24;
                let out = '<defs>'
                    + '<marker id="pg-arr-b" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 0L10 5L0 10z" fill="' + C.brand + '"/></marker>'
                    + '<marker id="pg-arr-i" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 0L10 5L0 10z" fill="' + C.ink + '"/></marker>'
                    + '</defs>';

                // Łączniki drzewa (kątowe): od dołu rodzica do środka wiersza dziecka.
                rows.forEach((r) => {
                    if (! r.parent || rowOf[r.parent] === undefined) return;
                    const p = rows[rowOf[r.parent]], pk = this.N[r.parent].kind;
                    const on = sel && (r.parent === sel || r.id === sel);
                    let color = C.gray, w = 1.5, dash = '';
                    if (pk === 'menu') { color = C.brand; w = 2.5; }
                    if (pk === 'project') { color = C.ink; w = 2; dash = ' stroke-dasharray="2 3"'; }
                    if (on) { color = C.brand; w = 3; dash = ''; }
                    out += '<path d="M' + (p.x + 14) + ' ' + (p.y + PH) + 'V' + (r.y + PH / 2) + 'H' + r.x + '" fill="none" stroke="' + color + '" stroke-width="' + w + '"' + dash + '/>';
                });

                // Łuki powiązań wybranej strony: przez pas po prawej stronie, każdy w osobnym torze.
                let lanes = 0;
                if (sel && rowOf[sel] !== undefined) {
                    const sr = rows[rowOf[sel]];
                    rel.forEach((e) => {
                        if (rowOf[e.id] === undefined) return;
                        if (e.kind === 'project' && (this.up[e.id] === sel || this.up[sel] === e.id)) return; // już widać w drzewie
                        const or = rows[rowOf[e.id]];
                        const [a, b] = e.dir === 'out' ? [sr, or] : [or, sr];
                        const x = gutter + lanes * LANE; lanes++;
                        const link = e.kind === 'link';
                        out += '<path d="M' + (a.x + PILL) + ' ' + (a.y + PH / 2) + 'H' + x + 'V' + (b.y + PH / 2) + 'H' + (b.x + PILL + 2) + '" fill="none" stroke="' + (link ? C.brand : C.ink)
                            + '" stroke-width="2" stroke-dasharray="' + (link ? '6 4' : '2 3') + '" marker-end="url(#pg-arr-' + (link ? 'b' : 'i') + ')"/>';
                    });
                }

                this.rows = rows; this.rowOf = rowOf; this.hi = hi; this.svg = out;
                this.W = gutter + lanes * LANE + 40;
                this.H = PAD * 2 + rows.length * ROW;
                if (this.focusId && rowOf[this.focusId] === undefined) {
                    let a = this.up[this.focusId];
                    while (a && rowOf[a] === undefined) a = this.up[a];
                    this.focusId = a || (rows[0] ? rows[0].id : '');
                }
            },

            /* Linki w treści i podpięcia pod działania danej strony (bez dublowania). */
            related(id, onlyShown) {
                const out = [], seen = {};
                this.edges.forEach((e) => {
                    if (e.kind !== 'link' && e.kind !== 'project') return;
                    if (onlyShown && ! this.show[e.kind]) return;
                    let r = null;
                    if (e.source === id) r = { id: e.target, kind: e.kind, dir: 'out' };
                    else if (e.target === id) r = { id: e.source, kind: e.kind, dir: 'in' };
                    if (! r || ! this.N[r.id] || seen[r.kind + r.dir + r.id]) return;
                    if (onlyShown && ! (r.id in this.up)) return;
                    seen[r.kind + r.dir + r.id] = true;
                    out.push(r);
                });
                return out;
            },
            linkCount(id) { const n = this.N[id]; return n && n.kind === 'page' ? (n.in || 0) + (n.out || 0) : 0; },

            get info() {
                const n = this.N[this.selected];
                if (! n) return null;
                const N = this.N, rel = this.related(n.id, false);
                const pick = (kind, dir) => rel.filter((r) => r.kind === kind && r.dir === dir).map((r) => N[r.id]);
                const above = [];
                if (n.parent && N[n.parent]) above.push({ ...N[n.parent], rel: 'strona nadrzędna' });
                if (n.main && N.menu) above.push({ ...N.menu, rel: 'w menu' });
                if (n.project && N[n.project]) above.push({ ...N[n.project], rel: 'działanie' });
                const pages = Object.values(N).filter((p) => p.kind === 'page');
                let children = n.kind === 'project' ? pages.filter((p) => p.project === n.id)
                    : n.kind === 'menu' ? pages.filter((p) => p.main).sort((a, b) => a.menu_order - b.menu_order)
                    : pages.filter((p) => p.parent === n.id);
                if (n.kind !== 'menu') children.sort((a, b) => this.idx[a.id] - this.idx[b.id]);
                return { ...n, above, children, linksOut: pick('link', 'out'), linksIn: pick('link', 'in') };
            },
            status(n) {
                if (n.kind !== 'page') return n.kind === 'project' ? 'Działanie' : '';
                return (n.published ? 'Opublikowana' : 'Szkic') + (n.main ? ' · strona główna (w menu)' : (n.menu ? ' · w menu' : '')) + (n.orphan ? ' · bez wejścia' : '');
            },

            /* Wygląd i nazwy wierszy. */
            cls(r) {
                const n = this.N[r.id], c = ['pg-k-' + n.kind];
                if (n.main) c.push('pg-main');
                if (this.selected && r.id !== this.selected) c.push(this.hi[r.id] ? 'pg-rel' : 'pg-dim');
                return c.join(' ');
            },
            markCls(id) {
                const n = this.N[id];
                if (! n || n.kind === 'menu' || n.kind === 'group') return 'pg-m-none';
                if (n.kind === 'project') return 'pg-m-proj';
                if (! n.published) return 'pg-m-draft';
                if (n.orphan) return 'pg-m-orphan';
                return n.main ? 'pg-m-main' : 'pg-m-pub';
            },
            label(r) {
                const n = this.N[r.id], p = [n.label];
                if (n.kind === 'page') {
                    p.push(n.published ? 'opublikowana' : 'szkic');
                    if (n.main) p.push('strona główna w menu');
                    if (n.orphan) p.push('bez wejścia');
                    if (n.in) p.push('linkują tutaj: ' + n.in);
                    if (n.out) p.push('linkuje do: ' + n.out);
                } else if (n.kind === 'project') p.push('działanie');
                else if (n.kind === 'group') p.push('grupa');
                if (r.hasKids) p.push('elementów: ' + this.kids[r.id].length);
                return p.join(', ');
            },

            /* Wybór, rozwijanie, fokus. */
            select(id) {
                const n = this.N[id];
                if (! n || ! (id in this.up)) return;
                if (n.kind === 'group') { this.toggle(id); return; }
                this.selected = id;
                // Rozwiń drogę do strony i do wszystkich jej powiązań, żeby łuki miały gdzie prowadzić.
                [id, ...this.related(id, true).map((r) => r.id)].forEach((x) => {
                    let a = this.up[x];
                    while (a) { this.open[a] = true; a = this.up[a]; }
                });
                this.layout();
                const info = this.info;
                this.announce = 'Wybrano: ' + n.label + '. Linkuje do ' + info.linksOut.length + ', linkują tutaj ' + info.linksIn.length + '.';
                this.$nextTick(() => this.scrollTo(id));
            },
            clear() { this.selected = ''; this.announce = 'Wyczyszczono wybór.'; this.layout(); },
            clickRow(r) { this.focusId = r.id; this.select(r.id); },
            toggle(id, force) {
                this.open[id] = force === undefined ? ! this.open[id] : force;
                this.layout();
            },
            expandAll(on) {
                Object.keys(this.kids).forEach((id) => { this.open[id] = on; });
                if (! on) this.roots.forEach((r) => { if (this.N[r].kind !== 'page') this.open[r] = true; });
                this.layout();
            },
            focusRow(id) {
                this.focusId = id;
                this.$nextTick(() => {
                    const el = this.$refs.tree.querySelector('[data-id="' + CSS.escape(id) + '"]');
                    if (el) { el.focus({ preventScroll: true }); this.scrollTo(id); }
                });
            },
            key(e, r) {
                const i = this.rowOf[r.id], rows = this.rows;
                let t = null;
                switch (e.key) {
                    case 'ArrowDown': t = rows[i + 1]; break;
                    case 'ArrowUp': t = rows[i - 1]; break;
                    case 'Home': t = rows[0]; break;
                    case 'End': t = rows[rows.length - 1]; break;
                    case 'ArrowRight':
                        if (r.hasKids && ! this.open[r.id]) this.toggle(r.id, true);
                        else if (r.hasKids) t = rows[i + 1];
                        break;
                    case 'ArrowLeft':
                        if (r.hasKids && this.open[r.id]) this.toggle(r.id, false);
                        else if (r.parent) t = rows[this.rowOf[r.parent]];
                        break;
                    case 'Enter': case ' ': this.select(r.id); break;
                    case 'Escape': if (! this.selected) return; this.clear(); break;
                    case '*': (r.parent ? this.kids[r.parent] : this.roots).forEach((s) => { this.open[s] = true; }); this.layout(); break;
                    default: return;
                }
                e.preventDefault();
                if (t) this.focusRow(t.id);
            },

            /* Przybliżanie i przesuwanie. */
            scrollTo(id) {
                const vp = this.$refs.vp, r = this.rows[this.rowOf[id]];
                if (! vp || ! r || this.mode !== 'tree') return;
                const k = this.k, top = r.y * k, left = r.x * k;
                if (top < vp.scrollTop + 8 || top + PH * k > vp.scrollTop + vp.clientHeight - 8) vp.scrollTop = top - vp.clientHeight / 2 + (PH * k) / 2;
                if (left < vp.scrollLeft || left + PILL * k > vp.scrollLeft + vp.clientWidth) vp.scrollLeft = Math.max(0, left - 24);
            },
            setZoom(nk, cx, cy) {
                const vp = this.$refs.vp;
                nk = Math.min(1.6, Math.max(0.3, Math.round(nk * 100) / 100));
                const rect = vp.getBoundingClientRect();
                const mx = cx === undefined ? vp.clientWidth / 2 : cx - rect.left, my = cy === undefined ? vp.clientHeight / 2 : cy - rect.top;
                const px = (vp.scrollLeft + mx) / this.k, py = (vp.scrollTop + my) / this.k;
                this.k = nk;
                this.$nextTick(() => { vp.scrollLeft = px * nk - mx; vp.scrollTop = py * nk - my; });
            },
            zoomBy(f) { this.setZoom(this.k * f); },
            fit() {
                const vp = this.$refs.vp;
                this.k = Math.min(1, Math.max(0.3, Math.round(Math.min((vp.clientWidth - 8) / this.W, (vp.clientHeight - 8) / this.H) * 100) / 100));
                this.$nextTick(() => { vp.scrollLeft = 0; vp.scrollTop = 0; });
            },
            wheel(e) {
                if (! (e.ctrlKey || e.metaKey)) return;
                e.preventDefault();
                this.setZoom(this.k * (e.deltaY < 0 ? 1.1 : 1 / 1.1), e.clientX, e.clientY);
            },
            panStart(e) {
                if (e.button !== 0 || e.target.closest('[role="treeitem"]')) return;
                const vp = this.$refs.vp;
                this.drag = { x: e.clientX, y: e.clientY, l: vp.scrollLeft, t: vp.scrollTop };
                this.dragging = true;
                vp.setPointerCapture(e.pointerId);
            },
            panMove(e) {
                if (! this.drag) return;
                const vp = this.$refs.vp;
                vp.scrollLeft = this.drag.l - (e.clientX - this.drag.x);
                vp.scrollTop = this.drag.t - (e.clientY - this.drag.y);
            },
            panEnd() { this.drag = null; this.dragging = false; },

            setMode(m) {
                this.mode = m;
                if (m === 'tree' && this.selected) this.$nextTick(() => this.scrollTo(this.selected));
            },

            /* Wyszukiwarka (combobox). */
            get matches() {
                const q = norm(this.q.trim());
                if (! q) return [];
                return Object.values(this.N)
                    .filter((n) => n.kind !== 'group' && (n.id in this.up) && norm(n.label).includes(q))
                    .sort((a, b) => (norm(b.label).startsWith(q) - norm(a.label).startsWith(q)) || a.label.localeCompare(b.label, 'pl'))
                    .slice(0, 10);
            },
            comboKey(e) {
                const m = this.matches;
                if (e.key === 'ArrowDown') { e.preventDefault(); this.comboOpen = true; this.active = Math.min(this.active + 1, m.length - 1); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); this.active = Math.max(this.active - 1, 0); }
                else if (e.key === 'Enter') { if (m[this.active]) { e.preventDefault(); this.choose(m[this.active]); } }
                else if (e.key === 'Escape') { if (this.comboOpen) { e.preventDefault(); this.comboOpen = false; } else this.q = ''; }
            },
            choose(n) {
                this.q = n.label; this.comboOpen = false; this.active = 0;
                this.select(n.id);
                if (this.mode === 'tree') this.focusRow(n.id);
            },
        };
    };
</script>
@endpush
