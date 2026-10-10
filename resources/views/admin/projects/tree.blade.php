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
        .pt-link.is-sel { background: var(--color-brand); color: #fff; font-weight: 700; }
        .pt-link:focus-visible, .pt-toggle:focus-visible { outline: 2px solid var(--color-brand); outline-offset: 1px; }
        .pt-ico { flex: none; width: 1rem; text-align: center; font-size: .75rem; opacity: .7; }
        .pt-title { min-width: 0; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .pt-title.is-off { opacity: .65; }
        .pt-mini { font-size: .7rem; color: #92400e; } .is-sel .pt-mini { color: #fff; }
        .pt-chip { flex: none; border-radius: 9999px; background: #fef3c7; color: #78350f; font-size: .65rem; font-weight: 700; padding: .05rem .45rem; }
        .pt-count { flex: none; border-radius: 9999px; background: #e5e7eb; color: #1d1d1a; font-size: .7rem; font-weight: 700; padding: .05rem .5rem; } .is-sel .pt-count { background: #fff; }
        .pt-body { padding: 1.25rem; }
        .pt-meta { display: grid; gap: 1rem 1.5rem; grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr)); margin: 1rem 0; }
        .pt-meta dt { font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #4b5563; }
        .pt-meta dd { margin: .15rem 0 0; font-size: .95rem; }
        .pt-badges { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .4rem; }
        .pt-badge { display: inline-flex; align-items: center; gap: .35rem; border-radius: 9999px; padding: .15rem .65rem; font-size: .75rem; font-weight: 700; background: #f3f4f6; color: #1d1d1a; }
        .pt-badge.ok { background: #dcfce7; color: #14532d; } .pt-badge.warn { background: #fef3c7; color: #78350f; } .pt-badge.info { background: #dbeafe; color: #1e3a8a; }
        .pt-ib { display: inline-flex; width: 1.9rem; height: 1.9rem; align-items: center; justify-content: center; border-radius: 9999px; background: #f3f4f6; color: #1d1d1a; font-size: .85rem; cursor: help; }
        .pt-ib.ok { background: #dcfce7; color: #14532d; } .pt-ib.warn { background: #fef3c7; color: #78350f; } .pt-ib.info { background: #dbeafe; color: #1e3a8a; } .pt-ib.off { background: #e5e7eb; color: #374151; }
        .pt-ib:focus-visible { outline: 2px solid var(--color-brand); outline-offset: 2px; }
        .pt-legend { display: flex; flex-wrap: wrap; gap: .35rem 1rem; margin: .6rem 0 0; padding: .5rem .75rem; border-radius: .5rem; background: #f9fafb; border: 1px dashed #d1d5db; font-size: .75rem; color: #374151; }
        .pt-legend > span { display: inline-flex; align-items: center; gap: .35rem; } .pt-legend .pt-ib { width: 1.4rem; height: 1.4rem; font-size: .7rem; cursor: default; }
        .pt-legend strong { font-weight: 700; color: #1d1d1a; margin-right: .25rem; }
        .pt-actions { display: flex; flex-wrap: wrap; gap: .5rem; border-top: 1px solid #e5e7eb; padding-top: 1rem; }
        .pt-btn { display: inline-flex; min-height: 2.25rem; align-items: center; gap: .4rem; border: 1px solid #d1d5db; border-radius: .5rem; background: #fff; padding: .35rem .85rem; font-size: .8rem; font-weight: 700; color: #1d1d1a; text-decoration: none; cursor: pointer; }
        .pt-btn:hover { background: #f3f4f6; } .pt-btn.primary { background: var(--color-brand); border-color: var(--color-brand); color: #fff; } .pt-btn.danger { border-color: #fecaca; color: #b91c1c; }
        .pt-btn:focus-visible { outline: 2px solid var(--color-brand); outline-offset: 2px; }
        .pt-move { display: flex; flex-wrap: wrap; align-items: flex-end; gap: .5rem; margin-top: 1rem; padding: .75rem; border-radius: .5rem; background: #f3f4f6; }
        .pt-move select { min-height: 2.25rem; border: 1px solid #d1d5db; border-radius: .5rem; padding: .25rem 2rem .25rem .6rem; font-size: .875rem; min-width: 14rem; }
        .pt-label { display: block; margin-bottom: .2rem; font-size: .75rem; font-weight: 700; }
        .pt-empty { padding: 2rem 1rem; text-align: center; color: #4b5563; border: 1px dashed #d1d5db; border-radius: .75rem; }
    </style>

    <div class="mb-4" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem">
        <div role="group" aria-label="Widok" style="display:flex;gap:.5rem">
            <span class="pt-btn" aria-current="true" style="background:#1d1d1a;color:#fff;border-color:#1d1d1a"><i class="fa-solid fa-sitemap" aria-hidden="true"></i> Drzewo</span>
            <a href="{{ route('admin.projekty.index', ['widok' => 'lista']) }}" class="pt-btn"><i class="fa-solid fa-list" aria-hidden="true"></i> Lista i filtry</a>
        </div>
        <a href="{{ route('admin.projekty.create', array_filter(['parent_id' => $selected?->id])) }}" class="pt-btn primary">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> {{ $selected ? 'Dodaj poddziałanie' : 'Dodaj działanie' }}
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
                <div class="pt-card pt-body">
                    <h2 style="margin:0;font-size:1.5rem;font-weight:800;font-style:italic">{{ $selected->title }}</h2>
                    <div class="pt-badges" role="group" aria-label="Stan działania">
                        {!! $ptIcon($selected->is_published ? 'published' : 'draft') !!}
                        @if ($selected->is_completed){!! $ptIcon('completed') !!}@endif
                        {!! $ptIcon($selected->is_paid ? 'paid' : 'free') !!}
                        @if ($selected->isPaidOffer()){!! $ptIcon('paid_offer') !!}@endif
                        @if ($selected->parent_id){!! $ptIcon('sub') !!}@unless ($selected->is_offered){!! $ptIcon('unavailable') !!}@endunless @endif
                        @if ($selected->category)<span class="pt-badge" title="Kategoria">{{ $selected->category->name }}</span>@endif
                    </div>
                    <p class="pt-legend" aria-label="Legenda ikon"><strong>Legenda:</strong>
                        @foreach ($ptIcons as $key => [$ico, $cls, $label])
                            <span><span class="pt-ib {{ $cls }}" aria-hidden="true"><i class="fa-solid {{ $ico }}"></i></span>{{ $label }}</span>
                        @endforeach
                    </p>
                    </div>

                    <dl class="pt-meta">
                        <div><dt>Adres</dt><dd><code>/projekty/{{ $selected->slug }}</code></dd></div>
                        <div><dt>Działanie nadrzędne</dt><dd>@if ($selected->parent_id && ($par = $byParent->flatten()->firstWhere('id', $selected->parent_id)))<a href="{{ route('admin.projekty.index', ['wybrana' => $par->id]) }}" style="color:var(--color-brand);text-decoration:underline">{{ $par->title }}</a>@else — najwyższy poziom @endif</dd></div>
                        <div><dt>Kolejność</dt><dd>{{ $selected->order }}</dd></div>
                        <div><dt>Ostatnia zmiana</dt><dd>{{ $selected->updated_at?->format('d.m.Y H:i') }}</dd></div>
                    </dl>

                    <div class="pt-actions" role="group" aria-label="Akcje działania">
                        <a href="{{ route('admin.projekty.edit', $selected) }}" class="pt-btn primary"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edytuj</a>
                        <a href="{{ route('projects.show', $selected) }}" target="_blank" rel="noopener" class="pt-btn"><i class="fa-solid fa-eye" aria-hidden="true"></i> Podgląd publiczny<span class="sr-only"> (nowa karta)</span></a>
                        <form method="POST" action="{{ route('admin.projekty.widocznosc', $selected) }}">@csrf @method('PATCH')
                            <button type="submit" class="pt-btn"><i class="fa-solid {{ $selected->is_published ? 'fa-eye-slash' : 'fa-eye' }}" aria-hidden="true"></i> {{ $selected->is_published ? 'Cofnij publikację' : 'Opublikuj' }}</button>
                        </form>
                        <a href="{{ route('admin.projekty.create', ['parent_id' => $selected->id]) }}" class="pt-btn"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj poddziałanie</a>
                        <form method="POST" action="{{ route('admin.projekty.kopiuj', $selected) }}" onsubmit="return confirm('Utworzyć kopię działania „{{ addslashes($selected->title) }}” jako szkic? Daty i etapy zostaną wyczyszczone.')">@csrf
                            <button type="submit" class="pt-btn"><i class="fa-solid fa-clone" aria-hidden="true"></i> Kopiuj (nowa edycja)</button>
                        </form>
                        <form method="POST" action="{{ route('admin.projekty.destroy', $selected) }}" style="margin-left:auto" onsubmit="return confirm('Przenieść działanie „{{ addslashes($selected->title) }}” do kosza?')">@csrf @method('DELETE')
                            <button type="submit" class="pt-btn danger"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                        </form>
                    </div>

                    <form method="POST" action="{{ route('admin.projekty.przenies', $selected) }}" class="pt-move">
                        @csrf @method('PATCH')
                        <div>
                            <label for="pt-parent" class="pt-label">Przenieś w drzewie</label>
                            <select id="pt-parent" name="parent_id">
                                <option value="">— poziom główny —</option>
                                @foreach ($parentOptions as $po)
                                    <option value="{{ $po->id }}" @selected($selected->parent_id === $po->id)>{{ $po->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="pt-btn"><i class="fa-solid fa-arrows-up-down-left-right" aria-hidden="true"></i> Przenieś</button>
                        <p style="flex-basis:100%;margin:0;font-size:.75rem;color:#4b5563">Adres URL działania się nie zmienia. Działanie nie może trafić do samego siebie ani do własnego podprojektu.</p>
                    </form>
                </div>

                <div class="pt-card pt-body" style="margin-top:1rem">
                    <h3 style="margin:0 0 .75rem;font-size:1rem;font-weight:800">Poddziałania <span style="font-weight:400;color:#4b5563">({{ $children->count() }})</span></h3>
                    @if ($children->isEmpty())
                        <p class="pt-empty">To działanie nie ma jeszcze poddziałań (form udziału).</p>
                    @else
                        <ul role="list" style="list-style:none;margin:0;padding:0;display:grid;gap:.5rem">
                            @foreach ($children as $ch)
                                <li style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;border:1px solid #e5e7eb;border-radius:.5rem;padding:.5rem .75rem">
                                    <a href="{{ route('admin.projekty.index', ['wybrana' => $ch->id]) }}" style="font-weight:700;color:#1d1d1a;text-decoration:none">{{ $ch->title }}</a>
                                    {!! $ptIcon($ch->is_paid ? 'paid' : 'free') !!}
                                    @unless ($ch->is_offered){!! $ptIcon('unavailable') !!}@endunless
                                    @unless ($ch->is_published){!! $ptIcon('draft') !!}@endunless
                                    <a href="{{ route('admin.projekty.edit', $ch) }}" class="pt-btn" style="margin-left:auto"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edytuj</a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection
