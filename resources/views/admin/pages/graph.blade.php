@extends('admin.layout')

@section('title', 'Mapa zależności stron')

@section('content')
    @php $btn = 'inline-flex min-h-10 items-center gap-2 rounded-md px-4 text-sm font-bold'; @endphp

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div class="max-w-3xl">
            <h1 class="text-2xl font-bold text-ink">Mapa zależności stron</h1>
            <p class="mt-1 text-sm text-ink">
                Jak strony łączą się ze sobą: strona nadrzędna → podstrona, linki w treści między stronami i podpięcie pod działania.
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
                    <p class="mt-1 text-sm text-ink">Opublikowane, ale nie ma ich w menu, nie mają strony nadrzędnej ani działania i żadna strona do nich nie linkuje — odwiedzający trafią tu tylko z wyszukiwarki.</p>
                    <ul class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm" role="list">
                        @foreach ($warnings['orphans'] as $o)
                            <li><a href="{{ route('admin.podstrony.edit', $o['id']) }}" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline">{{ $o['title'] }}</a></li>
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
    <section class="rounded-md bg-white" aria-labelledby="graf-tytul"
        x-data="pageGraph(@js(['nodes' => $nodes, 'edges' => $edges]))" x-init="init()">
        <h2 id="graf-tytul" class="sr-only">Wykres zależności</h2>
        <div class="flex flex-wrap items-end gap-4 border-b border-gray-100 p-4">
            <div class="min-w-[16rem] flex-1">
                <label for="graf-strona" class="mb-1 block text-sm font-bold text-ink">Pokaż stronę</label>
                <select id="graf-strona" x-model="selected" @change="focusNode(selected)"
                    class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                    <option value="">— wybierz, aby podświetlić powiązania —</option>
                    @foreach (collect($nodes)->sortBy('data.label') as $n)
                        <option value="{{ $n['data']['id'] }}">{{ $n['data']['label'] }}{{ $n['data']['kind'] === 'project' ? ' (działanie)' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <fieldset>
                <legend class="mb-1 text-sm font-bold text-ink">Układ</legend>
                <div class="flex overflow-hidden rounded-md bg-gray-100" role="group">
                    <button type="button" @click="setLayout('tree')" :aria-pressed="layout === 'tree'" :class="layout === 'tree' ? 'bg-brand-dark text-white' : 'text-ink hover:bg-brand-light'" class="px-4 py-2 text-sm font-bold">Hierarchia</button>
                    <button type="button" @click="setLayout('force')" :aria-pressed="layout === 'force'" :class="layout === 'force' ? 'bg-brand-dark text-white' : 'text-ink hover:bg-brand-light'" class="px-4 py-2 text-sm font-bold">Powiązania</button>
                </div>
            </fieldset>
            <fieldset class="flex flex-wrap gap-x-4 gap-y-2 text-sm text-ink">
                <legend class="mb-1 w-full text-sm font-bold text-ink">Pokaż</legend>
                <label class="flex items-center gap-2"><input type="checkbox" x-model="show.parent" @change="applyFilters()" class="h-4 w-4 rounded border-gray-500 text-brand-dark focus:ring-brand-dark"> Strony nadrzędne</label>
                <label class="flex items-center gap-2"><input type="checkbox" x-model="show.link" @change="applyFilters()" class="h-4 w-4 rounded border-gray-500 text-brand-dark focus:ring-brand-dark"> Linki w treści</label>
                <label class="flex items-center gap-2"><input type="checkbox" x-model="show.project" @change="applyFilters()" class="h-4 w-4 rounded border-gray-500 text-brand-dark focus:ring-brand-dark"> Działania</label>
                <label class="flex items-center gap-2"><input type="checkbox" x-model="show.drafts" @change="applyFilters()" class="h-4 w-4 rounded border-gray-500 text-brand-dark focus:ring-brand-dark"> Szkice</label>
            </fieldset>
            <button type="button" @click="fit()" class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light"><i class="fa-solid fa-expand" aria-hidden="true"></i> Dopasuj</button>
        </div>

        <div class="grid lg:grid-cols-[minmax(0,1fr)_18rem]">
            <div x-ref="canvas" class="h-[34rem] w-full bg-gray-50" role="img" aria-label="Wykres zależności stron — ta sama treść jest w tabeli pod wykresem."></div>

            <aside class="border-t border-gray-100 p-4 lg:border-l lg:border-t-0" aria-live="polite">
                <template x-if="! info">
                    <div class="text-sm text-ink">
                        <p class="font-bold">Kliknij stronę na wykresie</p>
                        <p class="mt-1 text-muted">albo wybierz ją z listy powyżej, aby zobaczyć jej powiązania.</p>
                        <h3 class="mt-5 text-xs font-bold uppercase tracking-widest text-muted">Legenda</h3>
                        <ul class="mt-2 space-y-2" role="list">
                            <li class="flex items-center gap-2"><span class="inline-block h-4 w-4 rounded-full bg-brand-dark" aria-hidden="true"></span> Strona opublikowana</li>
                            <li class="flex items-center gap-2"><span class="inline-block h-4 w-4 rounded-full border-2 border-gray-500 bg-white" aria-hidden="true"></span> Szkic</li>
                            <li class="flex items-center gap-2"><span class="inline-block h-4 w-4 rounded-full border-[3px] border-ink bg-[#EA8F00]" aria-hidden="true"></span> Strona bez wejścia</li>
                            <li class="flex items-center gap-2"><span class="inline-block h-4 w-4 rotate-45 bg-ink" aria-hidden="true"></span> Działanie</li>
                            <li class="flex items-center gap-2"><span class="inline-block h-0.5 w-6 bg-gray-500" aria-hidden="true"></span> Strona nadrzędna → podstrona</li>
                            <li class="flex items-center gap-2"><span class="inline-block w-6 border-t-2 border-dashed border-brand-dark" aria-hidden="true"></span> Link w treści</li>
                            <li class="flex items-center gap-2"><span class="inline-block w-6 border-t-2 border-dotted border-ink" aria-hidden="true"></span> Podpięta pod działanie</li>
                        </ul>
                    </div>
                </template>
                <template x-if="info">
                    <div class="space-y-3 text-sm text-ink">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-muted" x-text="info.type"></p>
                            <h3 class="text-base font-bold" x-text="info.label"></h3>
                            <p x-show="info.kind === 'page'" x-text="(info.published ? 'Opublikowana' : 'Szkic') + (info.menu ? ' · w menu' : '') + (info.orphan ? ' · bez wejścia' : '')"></p>
                        </div>
                        <div x-show="info.parents.length"><p class="font-bold">Nadrzędna</p><p x-text="info.parents.join(', ')"></p></div>
                        <div x-show="info.children.length"><p class="font-bold">Podstrony (<span x-text="info.children.length"></span>)</p><p x-text="info.children.join(', ')"></p></div>
                        <div x-show="info.linksOut.length"><p class="font-bold">Linkuje do</p><p x-text="info.linksOut.join(', ')"></p></div>
                        <div x-show="info.linksIn.length"><p class="font-bold">Linkują tutaj</p><p x-text="info.linksIn.join(', ')"></p></div>
                        <div class="flex flex-wrap gap-2 pt-1">
                            <a x-show="info.edit" :href="info.edit" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink">Edytuj</a>
                            <a x-show="info.url" :href="info.url" target="_blank" rel="noopener" class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light">Zobacz<span class="sr-only"> (nowa karta)</span></a>
                        </div>
                    </div>
                </template>
            </aside>
        </div>
    </section>

    {{-- Wersja tekstowa --}}
    <section id="tabela-zaleznosci" class="mt-8" aria-labelledby="tabela-tytul">
        <h2 id="tabela-tytul" class="mb-3 text-lg font-bold text-ink">Zależności w tabeli</h2>
        <div class="overflow-x-auto rounded-md bg-white">
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/cytoscape/3.30.2/cytoscape.min.js" crossorigin="anonymous"></script>
<script>
    window.pageGraph = function (data) {
        return {
            cy: null, layout: 'tree', selected: '', info: null,
            show: { parent: true, link: true, project: true, drafts: true },
            init() {
                if (! window.cytoscape) { this.$refs.canvas.textContent = 'Nie udało się wczytać wykresu — skorzystaj z tabeli poniżej.'; return; }
                this.cy = window.cytoscape({
                    container: this.$refs.canvas,
                    elements: [...data.nodes, ...data.edges],
                    wheelSensitivity: 0.2, minZoom: 0.15, maxZoom: 1.6,
                    style: [
                        { selector: 'node', style: { 'label': 'data(label)', 'font-size': 11, 'font-family': 'Montserrat, sans-serif', 'color': '#1d1d1a', 'text-wrap': 'ellipsis', 'text-max-width': 140, 'text-valign': 'bottom', 'text-margin-y': 4, 'width': 18, 'height': 18, 'background-color': '#1752bf' } },
                        { selector: 'node[!published]', style: { 'background-color': '#ffffff', 'border-width': 2, 'border-color': '#6b7280' } },
                        { selector: 'node[?orphan]', style: { 'background-color': '#EA8F00', 'border-width': 3, 'border-color': '#1d1d1a' } },
                        { selector: 'node[kind = "project"]', style: { 'shape': 'diamond', 'background-color': '#1d1d1a', 'width': 22, 'height': 22, 'font-weight': 'bold' } },
                        { selector: 'edge', style: { 'width': 1.5, 'curve-style': 'bezier', 'target-arrow-shape': 'triangle', 'arrow-scale': 0.8, 'line-color': '#6b7280', 'target-arrow-color': '#6b7280' } },
                        { selector: 'edge[kind = "link"]', style: { 'line-style': 'dashed', 'line-color': '#1752bf', 'target-arrow-color': '#1752bf' } },
                        { selector: 'edge[kind = "project"]', style: { 'line-style': 'dotted', 'line-color': '#1d1d1a', 'target-arrow-color': '#1d1d1a' } },
                        { selector: '.faded', style: { 'opacity': 0.15 } },
                        { selector: 'node.focused', style: { 'border-width': 4, 'border-color': '#1d1d1a', 'width': 26, 'height': 26, 'font-weight': 'bold', 'z-index': 10 } },
                    ],
                });
                this.cy.on('tap', 'node', (e) => { this.selected = e.target.id(); this.focusNode(this.selected); });
                this.cy.on('tap', (e) => { if (e.target === this.cy) this.clearFocus(); });
                this.runLayout();
            },
            runLayout() {
                const opts = this.layout === 'tree'
                    ? { name: 'breadthfirst', directed: true, spacingFactor: 1.15, padding: 20, animate: false }
                    : { name: 'cose', padding: 20, animate: false, nodeRepulsion: 8000, idealEdgeLength: 80 };
                this.cy.elements(':visible').layout(opts).run();
            },
            setLayout(name) { this.layout = name; this.runLayout(); },
            fit() { this.cy && this.cy.fit(undefined, 20); },
            applyFilters() {
                if (! this.cy) return;
                this.cy.batch(() => {
                    this.cy.edges().forEach((e) => e.style('display', this.show[e.data('kind')] ? 'element' : 'none'));
                    this.cy.nodes('[kind = "page"]').forEach((n) => n.style('display', (n.data('published') || this.show.drafts) ? 'element' : 'none'));
                    this.cy.nodes('[kind = "project"]').forEach((n) => n.style('display', this.show.project ? 'element' : 'none'));
                });
                this.runLayout();
            },
            names(col) { return col.map((n) => n.data('label')); },
            focusNode(id) {
                if (! this.cy) return;
                if (! id) { this.clearFocus(); return; }
                const node = this.cy.getElementById(id);
                if (! node.length) return;
                const hood = node.closedNeighborhood();
                this.cy.elements().addClass('faded').removeClass('focused');
                hood.removeClass('faded');
                node.addClass('focused');
                this.cy.animate({ fit: { eles: hood, padding: 60 }, duration: 250 });
                const kindEdges = (kind, dir) => node.connectedEdges('[kind = "' + kind + '"]').filter((e) => (dir === 'out' ? e.source() : e.target()).id() === id).map((e) => dir === 'out' ? e.target() : e.source());
                this.info = Object.assign({}, node.data(), {
                    parents: this.names(kindEdges('parent', 'in').concat(kindEdges('project', 'in'))),
                    children: this.names(kindEdges('parent', 'out').concat(kindEdges('project', 'out'))),
                    linksOut: this.names(kindEdges('link', 'out')),
                    linksIn: this.names(kindEdges('link', 'in')),
                });
            },
            clearFocus() {
                this.selected = ''; this.info = null;
                this.cy && this.cy.elements().removeClass('faded focused');
            },
        };
    };
</script>
@endpush
