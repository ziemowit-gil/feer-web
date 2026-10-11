@extends('admin.layout')

@section('title', 'Działania')

@section('content')
    <style>
        /* Widok drzewa działań w zwykłym CSS (niezależny od zbudowanych klas Tailwinda). */
        .pt-wrap { display: grid; gap: 1rem; align-items: start; }
        @media (min-width: 1024px) { .pt-wrap { grid-template-columns: 22rem minmax(0, 1fr); } .pt-tree { position: sticky; top: 1rem; } }
        .pt-card { border: 1px solid #e5e7eb; border-radius: .75rem; background: #fff; }
        .pt-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .75rem 1rem; border-bottom: 1px solid #e5e7eb; font-weight: 700; }
        .pt-search { margin: .75rem; }
        .pt-search input { width: 100%; min-height: 2.5rem; border: 1px solid #d1d5db; border-radius: .5rem; padding: .4rem .75rem; font-size: .875rem; }
        .pt-list { list-style: none; margin: 0; padding: 0 .5rem .75rem; max-height: calc(100vh - 14rem); overflow-y: auto; }
        .pt-branch { list-style: none; margin: 0 0 0 1.1rem; padding: 0 0 0 .25rem; border-left: 1px solid #e5e7eb; }
        .pt-row { display: flex; align-items: stretch; gap: .125rem; }
        .pt-toggle { display: inline-flex; width: 1.75rem; flex: none; align-items: center; justify-content: center; color: #4b5563; font-size: .7rem; }
        .pt-rot { transform: rotate(90deg); }
        .pt-link { display: flex; flex: 1; min-width: 0; min-height: 2.25rem; align-items: center; gap: .5rem; border-radius: .5rem; padding: .25rem .5rem; font-size: .875rem; color: #1d1d1a; text-decoration: none; }
        .pt-link:hover { background: #f3f4f6; }
        .pt-link.is-sel { background: var(--color-brand-dark); color: #fff; font-weight: 700; }
        .pt-link:focus-visible, .pt-toggle:focus-visible { outline: 2px solid var(--color-brand-dark); outline-offset: 1px; }
        .pt-ico { flex: none; width: 1rem; text-align: center; font-size: .75rem; opacity: .7; }
        .pt-title { min-width: 0; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .pt-title.is-off { opacity: .65; }
        .pt-mini { font-size: .7rem; color: #92400e; } .is-sel .pt-mini { color: #fff; }
        .pt-chip { flex: none; border-radius: 9999px; background: #fef3c7; color: #78350f; font-size: .65rem; font-weight: 700; padding: .05rem .45rem; }
        .pt-count { flex: none; border-radius: 9999px; background: #e5e7eb; color: #1d1d1a; font-size: .7rem; font-weight: 700; padding: .05rem .5rem; } .is-sel .pt-count { background: #fff; }
        .pt-body { padding: 1.25rem; }
        .pt-meta { display: grid; gap: 1rem 1.5rem; grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr)); margin: 1.25rem 0; }
        .pt-meta dt { font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #4b5563; }
        .pt-meta dd { margin: .15rem 0 0; font-size: .95rem; }
        .pt-badges { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .4rem; }
        .pt-badge { display: inline-flex; align-items: center; gap: .35rem; border-radius: 9999px; padding: .15rem .65rem; font-size: .75rem; font-weight: 700; background: #f3f4f6; color: #1d1d1a; }
        .pt-badge.ok { background: #dcfce7; color: #14532d; } .pt-badge.warn { background: #fef3c7; color: #78350f; } .pt-badge.info { background: #dbeafe; color: #1e3a8a; }
        .pt-ib { display: inline-flex; width: 1.9rem; height: 1.9rem; align-items: center; justify-content: center; border-radius: 9999px; background: #f3f4f6; color: #1d1d1a; font-size: .85rem; cursor: help; }
        .pt-ib.ok { background: #dcfce7; color: #14532d; } .pt-ib.warn { background: #fef3c7; color: #78350f; } .pt-ib.info { background: #dbeafe; color: #1e3a8a; } .pt-ib.off { background: #e5e7eb; color: #374151; }
        .pt-ib:focus-visible { outline: 2px solid var(--color-brand-dark); outline-offset: 2px; }
        .pt-legend { display: flex; flex-wrap: wrap; gap: .35rem 1rem; margin: .6rem 0 0; padding: .5rem .75rem; border-radius: .5rem; background: #f9fafb; font-size: .75rem; color: #374151; }
        .pt-legend > span { display: inline-flex; align-items: center; gap: .35rem; } .pt-legend .pt-ib { width: 1.4rem; height: 1.4rem; font-size: .7rem; cursor: default; }
        .pt-legend strong { font-weight: 700; color: #1d1d1a; margin-right: .25rem; }
        .pt-actions { display: flex; flex-wrap: wrap; gap: .5rem; border-top: 1px solid #e5e7eb; padding-top: 1rem; margin-top: .25rem; }
        .pt-btn { display: inline-flex; min-height: 2.25rem; align-items: center; gap: .4rem; border: 1px solid #d1d5db; border-radius: .5rem; background: #fff; padding: .35rem .85rem; font-size: .8rem; font-weight: 700; color: #1d1d1a; text-decoration: none; cursor: pointer; }
        .pt-btn:hover { background: #f3f4f6; } .pt-btn.primary { background: var(--color-brand-dark); border-color: var(--color-brand-dark); color: #fff; } .pt-btn.danger { border-color: #fecaca; color: #b91c1c; }
        .pt-btn:focus-visible { outline: 2px solid var(--color-brand-dark); outline-offset: 2px; }
        .pt-move { display: flex; flex-wrap: wrap; align-items: flex-end; gap: .5rem; margin-top: .85rem; }
        .pt-move select { min-height: 2.25rem; border: 1px solid #d1d5db; border-radius: .5rem; padding: .25rem 2rem .25rem .6rem; font-size: .875rem; min-width: 14rem; }
        .pt-label { display: block; margin-bottom: .2rem; font-size: .75rem; font-weight: 700; }
        .pt-titlebar { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 1rem; }
        .pt-h2 { margin: 0; font-size: 1.6rem; line-height: 1.2; font-weight: 800; color: #1d1d1a; }
        .pt-h3 { margin: 0; font-size: 1.05rem; font-weight: 800; color: #1d1d1a; }
        .pt-url { display: inline-flex; align-items: center; gap: .4rem; margin-top: .35rem; font-size: .85rem; color: #1752bf; text-decoration: none; }
        .pt-url:hover { text-decoration: underline; } .pt-url i { font-size: .7rem; }
        .pt-url:focus-visible, .pt-a:focus-visible, .pt-details summary:focus-visible, .pt-details-plain summary:focus-visible { outline: 2px solid var(--color-brand-dark); outline-offset: 2px; }
        .pt-a { color: #1752bf; text-decoration: underline; text-underline-offset: 3px; }
        .pt-btn-lg { min-height: 2.75rem; padding: .5rem 1.1rem; font-size: .9rem; }
        .pt-btn-sm { min-height: 2rem; padding: .25rem .65rem; font-size: .75rem; }
        .pt-status { display: flex; flex-wrap: wrap; gap: .4rem; list-style: none; margin: 1rem 0 0; padding: 0; }
        .pt-badge.off { background: #e5e7eb; color: #374151; }
        .pt-muted { font-weight: 400; color: #4b5563; }
        .pt-gap { margin-top: 1rem; }
        .pt-sechead { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem; }
        .pt-help { margin: .35rem 0 .85rem; font-size: .8rem; color: #4b5563; }
        .pt-rows { list-style: none; margin: 0; padding: 0; display: grid; gap: .375rem; }
        .pt-item { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; border-radius: .5rem; background: #f9fafb; padding: .5rem .75rem; }
        .pt-item:hover { background: #f3f4f6; }
        .pt-item-title { font-weight: 700; color: #1d1d1a; min-width: 0; }
        .pt-details > summary, .pt-details-plain > summary { cursor: pointer; font-weight: 700; font-size: .9rem; color: #1d1d1a; }
        .pt-details-plain { padding: 0 .25rem; } .pt-details-plain > summary { font-weight: 600; font-size: .8rem; color: #4b5563; }
        .pt-empty { margin: 0; padding: 1.25rem 1rem; text-align: center; color: #4b5563; background: #f9fafb; border-radius: .75rem; }
    </style>

    <div class="mb-4" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem">
        <div role="group" aria-label="Widok" style="display:flex;gap:.5rem">
            <span class="pt-btn" aria-current="true" style="background:#1d1d1a;color:#fff;border-color:#1d1d1a"><i class="fa-solid fa-sitemap" aria-hidden="true"></i> Drzewo</span>
            <a href="{{ route('admin.projekty.index', ['widok' => 'lista']) }}" class="pt-btn"><i class="fa-solid fa-list" aria-hidden="true"></i> Lista i filtry</a>
        </div>
        <a href="{{ route('admin.projekty.create') }}" class="pt-btn primary">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Nowe działanie
        </a>
    </div>

@php
    /** Ikony stanu działań — jedna definicja dla nagłówka, listy poddziałań i legendy. [ikona, klasa koloru, etykieta] */
    $ptIcons = [
        'published'   => ['fa-circle-check',    'ok',   'Opublikowane'],
        'draft'       => ['fa-pen',             'warn', 'Szkic (niewidoczne publicznie)'],
        'completed'   => ['fa-flag-checkered',  'off',  'Zrealizowane'],
        'free'        => ['fa-gift',            'ok',   'Bezpłatne'],
        'paid'        => ['fa-coins',           'warn', 'Odpłatne'],
        'paid_offer'  => ['fa-briefcase',       'warn', 'Usługa wyłącznie odpłatna'],
        'sub'         => ['fa-code-branch',     'info', 'Poddziałanie (forma udziału)'],
        'unavailable' => ['fa-ban',             'off',  'Obecnie niedostępne'],
        'branch'      => ['fa-diagram-project', '',     'Ma poddziałania (w drzewie)'],
        'leaf'        => ['fa-folder-closed',   '',     'Bez poddziałań (w drzewie)'],
    ];
    $ptIcon = fn (string $key, ?string $extra = null) => sprintf(
        '<span class="pt-ib %s" title="%s" tabindex="0"><i class="fa-solid %s" aria-hidden="true"></i><span class="sr-only">%s</span></span>',
        $ptIcons[$key][1], e($ptIcons[$key][2] . ($extra ? ' — ' . $extra : '')), $ptIcons[$key][0], e($ptIcons[$key][2] . ($extra ? ' — ' . $extra : ''))
    );
@endphp
    <div class="pt-wrap">
        {{-- Lewy panel: drzewo --}}
        <nav class="pt-card pt-tree" aria-label="Drzewo działań"
            x-data="{ q: '', filter() { const q = this.q.trim().toLowerCase(); this.$root.querySelectorAll('[data-tree-node]').forEach(li => { li.style.display = (! q || li.dataset.title.includes(q) || li.querySelector('[data-tree-node][data-title*=&quot;' + q.replace(/[&quot;\\]/g, '') + '&quot;]')) ? '' : 'none'; }); } }">
            <div class="pt-head"><span><i class="fa-solid fa-diagram-project" aria-hidden="true"></i> Działania <span style="font-weight:400;color:#4b5563">({{ $total }})</span></span></div>
            <div class="pt-search">
                <label for="pt-q" class="sr-only">Filtruj drzewo działań</label>
                <input type="search" id="pt-q" x-model="q" @input="filter()" placeholder="Filtruj drzewo…">
            </div>
            @php $roots = $byParent->get(0, collect()); @endphp
            @if ($roots->isEmpty())
                <p class="pt-empty" style="margin:.75rem">Brak projektów. Dodaj pierwszy.</p>
            @else
                <ul role="list" class="pt-list">
                    @foreach ($roots as $node)
                        @include('admin.projects.partials.tree-node', ['node' => $node, 'byParent' => $byParent, 'selected' => $selected, 'openIds' => $openIds])
                    @endforeach
                </ul>
            @endif
        </nav>

        {{-- Prawy panel: szczegóły --}}
        <div style="min-width:0">
            @if (! $selected)
                <div class="pt-empty">
                    <i class="fa-solid fa-hand-pointer" aria-hidden="true" style="font-size:1.5rem"></i>
                    <p style="margin:.5rem 0 0;font-weight:700;color:#1d1d1a">Wybierz działanie z drzewa</p>
                    <p style="margin:.25rem 0 0;font-size:.9rem">Zobaczysz jego szczegóły, poddziałania i szybkie akcje.</p>
                </div>
            @else
                @php
                    // Status wybranego działania: ikona + tekst (bez zgadywania z legendy).
                    $ptStatus = array_values(array_filter([
                        $selected->is_published ? 'published' : 'draft',
                        $selected->is_completed ? 'completed' : null,
                        $selected->is_paid ? 'paid' : 'free',
                        $selected->isPaidOffer() ? 'paid_offer' : null,
                        $selected->parent_id ? 'sub' : null,
                        $selected->parent_id && ! $selected->is_offered ? 'unavailable' : null,
                    ]));
                    $publicUrl = route('projects.show', $selected);
                    $publicPath = parse_url($publicUrl, PHP_URL_PATH) ?: '/';
                    $parent = $selected->parent_id ? $byParent->flatten()->firstWhere('id', $selected->parent_id) : null;
                    // Podstrony działania (zakładki, sekcje, menu boczne) — całe drzewo, także szkice.
                    $subPages = $selected->pageTree();
                    $flatPages = [];
                    $walk = function ($nodes, $depth) use (&$walk, &$flatPages) { foreach ($nodes as $n) { $flatPages[] = [$n, $depth]; $walk($n->tree_children ?? collect(), $depth + 1); } };
                    $walk($subPages, 0);
                    $displayLabels = ['tab' => 'Zakładka', 'inline' => 'Sekcja w treści', 'accordion' => 'Akordeon', 'link' => 'Odnośnik'];
                @endphp

                <div class="pt-card pt-body">
                    <div class="pt-titlebar">
                        <div style="min-width:0">
                            <h2 class="pt-h2">{{ $selected->title }}</h2>
                            <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="pt-url"><code>{{ $publicPath }}</code><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i><span class="sr-only"> (podgląd publiczny, nowa karta)</span></a>
                        </div>
                        <a href="{{ route('admin.projekty.edit', $selected) }}" class="pt-btn primary pt-btn-lg"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edytuj działanie</a>
                    </div>

                    <ul class="pt-status" role="list" aria-label="Stan działania">
                        @foreach ($ptStatus as $key)
                            <li class="pt-badge {{ $ptIcons[$key][1] }}"><i class="fa-solid {{ $ptIcons[$key][0] }}" aria-hidden="true"></i>{{ $ptIcons[$key][2] }}</li>
                        @endforeach
                        @if ($selected->category)<li class="pt-badge"><i class="fa-solid fa-tag" aria-hidden="true"></i>{{ $selected->category->name }}</li>@endif
                    </ul>

                    <dl class="pt-meta">
                        <div><dt>Działanie nadrzędne</dt><dd>@if ($parent)<a href="{{ route('admin.projekty.index', ['wybrana' => $parent->id]) }}" class="pt-a">{{ $parent->title }}</a>@else Najwyższy poziom @endif</dd></div>
                        <div><dt>Kolejność</dt><dd>{{ $selected->order }}</dd></div>
                        <div><dt>Ostatnia zmiana</dt><dd>{{ $selected->updated_at?->format('d.m.Y, H:i') }}</dd></div>
                    </dl>

                    <div class="pt-actions" role="group" aria-label="Akcje działania">
                        <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="pt-btn"><i class="fa-solid fa-eye" aria-hidden="true"></i> Podgląd<span class="sr-only"> publiczny (nowa karta)</span></a>
                        <form method="POST" action="{{ route('admin.projekty.widocznosc', $selected) }}">@csrf @method('PATCH')
                            <button type="submit" class="pt-btn"><i class="fa-solid {{ $selected->is_published ? 'fa-eye-slash' : 'fa-globe' }}" aria-hidden="true"></i> {{ $selected->is_published ? 'Cofnij publikację' : 'Opublikuj' }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.projekty.kopiuj', $selected) }}" onsubmit="return confirm('Utworzyć kopię działania „{{ addslashes($selected->title) }}” jako szkic? Daty i etapy zostaną wyczyszczone.')">@csrf
                            <button type="submit" class="pt-btn"><i class="fa-solid fa-clone" aria-hidden="true"></i> Kopiuj jako nową edycję</button>
                        </form>
                        <form method="POST" action="{{ route('admin.projekty.destroy', $selected) }}" style="margin-left:auto" onsubmit="return confirm('Przenieść działanie „{{ addslashes($selected->title) }}” do kosza?')">@csrf @method('DELETE')
                            <button type="submit" class="pt-btn danger"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                        </form>
                    </div>
                </div>

                {{-- Podstrony działania: to z nich powstają zakładki / menu boczne na stronie działania. --}}
                <section class="pt-card pt-body pt-gap" aria-labelledby="pt-pages-h">
                    <div class="pt-sechead">
                        <h3 id="pt-pages-h" class="pt-h3">Podstrony i menu boczne <span class="pt-muted">({{ count($flatPages) }})</span></h3>
                        <a href="{{ route('admin.podstrony.create', ['project_id' => $selected->id, 'project_display' => 'tab']) }}" class="pt-btn"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj podstronę</a>
                    </div>
                    <p class="pt-help">Na stronie działania pojawiają się obok „O projekcie” — jako zakładki albo pozycje menu bocznego (zależnie od ustawień), sekcje w treści lub odnośniki.</p>
                    @if (empty($flatPages))
                        <p class="pt-empty">Brak podstron. Dodaj np. „Formularz zgłoszeniowy” albo „Ile to kosztuje?”.</p>
                    @else
                        <ul role="list" class="pt-rows">
                            @foreach ($flatPages as [$pg, $depth])
                                <li class="pt-item" style="padding-left: {{ 0.75 + $depth * 1.25 }}rem">
                                    @if ($depth)<i class="fa-solid fa-turn-up fa-rotate-90 pt-muted" aria-hidden="true"></i>@endif
                                    <span class="pt-item-title">{{ $pg->title }}</span>
                                    @if (! $depth)<span class="pt-badge info">{{ $displayLabels[$pg->project_display] ?? 'Odnośnik' }}</span>@endif
                                    @unless ($pg->is_published)<span class="pt-badge warn"><i class="fa-solid fa-pen" aria-hidden="true"></i>Szkic</span>@endunless
                                    <a href="{{ route('admin.podstrony.edit', $pg) }}" class="pt-btn pt-btn-sm" style="margin-left:auto"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edytuj<span class="sr-only"> podstronę „{{ $pg->title }}”</span></a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section class="pt-card pt-body pt-gap" aria-labelledby="pt-children-h">
                    <div class="pt-sechead">
                        <h3 id="pt-children-h" class="pt-h3">Poddziałania <span class="pt-muted">({{ $children->count() }})</span></h3>
                        <a href="{{ route('admin.projekty.create', ['parent_id' => $selected->id]) }}" class="pt-btn"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj poddziałanie</a>
                    </div>
                    <p class="pt-help">Formy udziału w działaniu, np. kolejne grupy szkoleniowe albo warianty usługi.</p>
                    @if ($children->isEmpty())
                        <p class="pt-empty">To działanie nie ma jeszcze poddziałań.</p>
                    @else
                        <ul role="list" class="pt-rows">
                            @foreach ($children as $ch)
                                <li class="pt-item">
                                    <a href="{{ route('admin.projekty.index', ['wybrana' => $ch->id]) }}" class="pt-item-title pt-a">{{ $ch->title }}</a>
                                    <span class="pt-badge {{ $ch->is_paid ? 'warn' : 'ok' }}"><i class="fa-solid {{ $ch->is_paid ? 'fa-coins' : 'fa-gift' }}" aria-hidden="true"></i>{{ $ch->is_paid ? 'Odpłatne' : 'Bezpłatne' }}</span>
                                    @unless ($ch->is_offered)<span class="pt-badge off"><i class="fa-solid fa-ban" aria-hidden="true"></i>Niedostępne</span>@endunless
                                    @unless ($ch->is_published)<span class="pt-badge warn"><i class="fa-solid fa-pen" aria-hidden="true"></i>Szkic</span>@endunless
                                    <a href="{{ route('admin.projekty.edit', $ch) }}" class="pt-btn pt-btn-sm" style="margin-left:auto"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edytuj<span class="sr-only"> „{{ $ch->title }}”</span></a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                {{-- Przeniesienie w drzewie: rzadka operacja — zwinięta. --}}
                <details class="pt-card pt-body pt-gap pt-details">
                    <summary><i class="fa-solid fa-arrows-up-down-left-right" aria-hidden="true"></i> Przenieś w drzewie</summary>
                    <form method="POST" action="{{ route('admin.projekty.przenies', $selected) }}" class="pt-move">
                        @csrf @method('PATCH')
                        <div>
                            <label for="pt-parent" class="pt-label">Nowe działanie nadrzędne</label>
                            <select id="pt-parent" name="parent_id">
                                <option value="">— poziom główny —</option>
                                @foreach ($parentOptions as $po)
                                    <option value="{{ $po->id }}" @selected($selected->parent_id === $po->id)>{{ $po->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="pt-btn">Przenieś</button>
                        <p class="pt-help" style="flex-basis:100%;margin:0">Adres URL działania się nie zmienia. Działanie nie może trafić do samego siebie ani do własnego poddziałania.</p>
                    </form>
                </details>

                {{-- Legenda ikon drzewa (lewy panel) — zwinięta, na żądanie. --}}
                <details class="pt-gap pt-details-plain">
                    <summary>Co oznaczają ikony w drzewie?</summary>
                    <p class="pt-legend">
                        @foreach ($ptIcons as $key => [$ico, $cls, $label])
                            <span><span class="pt-ib {{ $cls }}" aria-hidden="true"><i class="fa-solid {{ $ico }}"></i></span>{{ $label }}</span>
                        @endforeach
                    </p>
                </details>
            @endif
        </div>
    </div>
@endsection
