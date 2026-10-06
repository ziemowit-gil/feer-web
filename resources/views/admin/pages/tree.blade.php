@extends('admin.layout')

@section('title', 'Strony')

@section('content')
    @include('admin.partials.content-nav-tabs')

    @php
        $canEditSelected = $selected && (! $selected->is_locked || auth()->user()->isAdmin());
        $selectedLive = $selected && $selected->is_published && ($selected->publish_at === null || $selected->publish_at->isPast());
        $btn = 'inline-flex min-h-9 items-center gap-1.5 rounded border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-ink hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand';
    @endphp

    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        {{-- Przełącznik widoku: drzewo (domyślne) / lista z filtrami i operacjami zbiorczymi --}}
        <div class="inline-flex rounded-lg border border-gray-200 bg-white p-0.5 text-xs font-bold" role="group" aria-label="Widok stron">
            <a href="{{ route('admin.podstrony.index', array_filter(['wybrana' => $selected?->id])) }}" aria-current="true"
                class="inline-flex items-center gap-1.5 rounded-md bg-brand px-3 py-1.5 text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1">
                <i class="fa-solid fa-sitemap" aria-hidden="true"></i> Drzewo
            </a>
            <a href="{{ route('admin.podstrony.index', ['widok' => 'lista']) }}"
                class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-muted hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                <i class="fa-solid fa-list" aria-hidden="true"></i> Lista
            </a>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.podstrony.index', ['widok' => 'lista', 'status' => 'trashed']) }}" class="{{ $btn }} font-normal text-muted">
                <i class="fa-solid fa-trash-can text-xs" aria-hidden="true"></i> Kosz
            </a>
            <a href="{{ route('admin.podstrony.eksport') }}" class="{{ $btn }}">
                <i class="fa-solid fa-file-csv" aria-hidden="true"></i> Eksportuj CSV
            </a>
            <a href="{{ route('admin.podstrony.create', array_filter(['parent_id' => $selected?->id])) }}"
                class="inline-flex min-h-9 items-center gap-1.5 rounded bg-brand px-4 py-1.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> {{ $selected ? 'Dodaj podstronę' : 'Dodaj stronę' }}
            </a>
        </div>
    </div>

    <div class="grid items-start gap-4 lg:grid-cols-[minmax(18rem,22rem)_minmax(0,1fr)]">

        {{-- ═════════ Lewy panel: drzewo stron ═════════ --}}
        <nav aria-label="Drzewo stron" class="rounded-lg border border-gray-200 bg-white lg:sticky lg:top-4"
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
            <div class="max-h-[calc(100vh-14rem)] overflow-y-auto p-2">
                @if ($byParent->get(0, collect())->isEmpty())
                    <p class="px-2 py-6 text-center text-sm text-muted">Brak stron. Dodaj pierwszą stronę przyciskiem powyżej.</p>
                @else
                    @include('admin.pages.partials.tree-node', ['nodes' => $byParent->get(0), 'depth' => 0])
                @endif
            </div>
        </nav>

        {{-- ═════════ Prawy panel: szczegóły wybranej strony ═════════ --}}
        <section class="min-w-0 space-y-4" aria-live="polite" aria-labelledby="pane-heading">
            @if ($selected)
                {{-- Ścieżka --}}
                <nav aria-label="Ścieżka strony" class="text-xs text-muted">
                    <a href="{{ route('admin.podstrony.index') }}" class="hover:text-brand hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"><i class="fa-solid fa-house" aria-hidden="true"></i><span class="sr-only">Wszystkie strony</span></a>
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
                                @include('admin.pages.partials.status-chip', ['page' => $selected])
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
                        @include('admin.pages.partials.children-table', ['children' => $children, 'selected' => $selected])
                    @endif
                </div>
            @else
                {{-- Nic nie wybrano: przegląd poziomu głównego --}}
                <div class="rounded-lg border border-gray-200 bg-white p-5">
                    <h2 id="pane-heading" class="text-xl font-bold text-ink">Wszystkie strony</h2>
                    <p class="mt-1 text-sm text-muted">
                        Wybierz stronę w drzewie po lewej, aby zobaczyć jej szczegóły, podstrony i szybkie akcje.
                        Poniżej strony najwyższego poziomu. Filtry, wyszukiwanie po adresie i operacje zbiorcze znajdziesz w widoku
                        <a href="{{ route('admin.podstrony.index', ['widok' => 'lista']) }}" class="font-bold text-brand hover:underline">Lista</a>.
                    </p>
                </div>
                @if ($children->isEmpty())
                    <div class="rounded-lg border border-dashed border-gray-300 bg-white px-4 py-12 text-center text-sm text-muted">Brak stron.</div>
                @else
                    @include('admin.pages.partials.children-table', ['children' => $children, 'selected' => null])
                @endif
            @endif
        </section>
    </div>
@endsection
