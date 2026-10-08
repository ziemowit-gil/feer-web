@extends('admin.layout')

@section('title', 'Projekty')

@section('content')
    <style>
        /* Widok drzewa projektów w zwykłym CSS (niezależny od zbudowanych klas Tailwinda). */
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
            <i class="fa-solid fa-plus" aria-hidden="true"></i> {{ $selected ? 'Dodaj podprojekt' : 'Dodaj projekt' }}
        </a>
    </div>

    <div class="pt-wrap">
        {{-- Lewy panel: drzewo --}}
        <nav class="pt-card pt-tree" aria-label="Drzewo projektów"
            x-data="{ q: '', filter() { const q = this.q.trim().toLowerCase(); this.$root.querySelectorAll('[data-tree-node]').forEach(li => { li.style.display = (! q || li.dataset.title.includes(q) || li.querySelector('[data-tree-node][data-title*=&quot;' + q.replace(/[&quot;\\]/g, '') + '&quot;]')) ? '' : 'none'; }); } }">
            <div class="pt-head"><span><i class="fa-solid fa-diagram-project" aria-hidden="true"></i> Projekty <span style="font-weight:400;color:#4b5563">({{ $total }})</span></span></div>
            <div class="pt-search">
                <label for="pt-q" class="sr-only">Filtruj drzewo projektów</label>
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
                    <p style="margin:.5rem 0 0;font-weight:700;color:#1d1d1a">Wybierz projekt z drzewa</p>
                    <p style="margin:.25rem 0 0;font-size:.9rem">Zobaczysz jego szczegóły, podprojekty i szybkie akcje.</p>
                </div>
            @else
                <div class="pt-card pt-body">
                    <h2 style="margin:0;font-size:1.5rem;font-weight:800;font-style:italic">{{ $selected->title }}</h2>
                    <div class="pt-badges">
                        <span class="pt-badge {{ $selected->is_published ? 'ok' : 'warn' }}"><i class="fa-solid {{ $selected->is_published ? 'fa-circle-check' : 'fa-pen' }}" aria-hidden="true"></i>{{ $selected->is_published ? 'Opublikowany' : 'Szkic' }}</span>
                        @if ($selected->is_completed)<span class="pt-badge">Zrealizowany</span>@endif
                        <span class="pt-badge {{ $selected->is_paid ? 'warn' : 'ok' }}"><i class="fa-solid fa-coins" aria-hidden="true"></i>{{ $selected->is_paid ? 'Odpłatny' : 'Bezpłatny' }}</span>
                        @if ($selected->parent_id)<span class="pt-badge info"><i class="fa-solid fa-code-branch" aria-hidden="true"></i>Podprojekt (forma udziału){{ $selected->is_offered ? '' : ' — obecnie niedostępna' }}</span>@endif
                        @if ($selected->category)<span class="pt-badge">{{ $selected->category->name }}</span>@endif
                    </div>

                    <dl class="pt-meta">
                        <div><dt>Adres</dt><dd><code>/projekty/{{ $selected->slug }}</code></dd></div>
                        <div><dt>Projekt nadrzędny</dt><dd>@if ($selected->parent_id && ($par = $byParent->flatten()->firstWhere('id', $selected->parent_id)))<a href="{{ route('admin.projekty.index', ['wybrana' => $par->id]) }}" style="color:var(--color-brand);text-decoration:underline">{{ $par->title }}</a>@else — najwyższy poziom @endif</dd></div>
                        <div><dt>Kolejność</dt><dd>{{ $selected->order }}</dd></div>
                        <div><dt>Ostatnia zmiana</dt><dd>{{ $selected->updated_at?->format('d.m.Y H:i') }}</dd></div>
                    </dl>

                    <div class="pt-actions" role="group" aria-label="Akcje projektu">
                        <a href="{{ route('admin.projekty.edit', $selected) }}" class="pt-btn primary"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edytuj</a>
                        <a href="{{ route('projects.show', $selected) }}" target="_blank" rel="noopener" class="pt-btn"><i class="fa-solid fa-eye" aria-hidden="true"></i> Podgląd publiczny<span class="sr-only"> (nowa karta)</span></a>
                        <form method="POST" action="{{ route('admin.projekty.widocznosc', $selected) }}">@csrf @method('PATCH')
                            <button type="submit" class="pt-btn"><i class="fa-solid {{ $selected->is_published ? 'fa-eye-slash' : 'fa-eye' }}" aria-hidden="true"></i> {{ $selected->is_published ? 'Cofnij publikację' : 'Opublikuj' }}</button>
                        </form>
                        <a href="{{ route('admin.projekty.create', ['parent_id' => $selected->id]) }}" class="pt-btn"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj podprojekt</a>
                        <form method="POST" action="{{ route('admin.projekty.destroy', $selected) }}" style="margin-left:auto" onsubmit="return confirm('Przenieść projekt „{{ addslashes($selected->title) }}” do kosza?')">@csrf @method('DELETE')
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
                        <p style="flex-basis:100%;margin:0;font-size:.75rem;color:#4b5563">Adres URL projektu się nie zmienia. Projekt nie może trafić do samego siebie ani do własnego podprojektu.</p>
                    </form>
                </div>

                <div class="pt-card pt-body" style="margin-top:1rem">
                    <h3 style="margin:0 0 .75rem;font-size:1rem;font-weight:800">Podprojekty <span style="font-weight:400;color:#4b5563">({{ $children->count() }})</span></h3>
                    @if ($children->isEmpty())
                        <p class="pt-empty">Ten projekt nie ma jeszcze podprojektów (form udziału).</p>
                    @else
                        <ul role="list" style="list-style:none;margin:0;padding:0;display:grid;gap:.5rem">
                            @foreach ($children as $ch)
                                <li style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;border:1px solid #e5e7eb;border-radius:.5rem;padding:.5rem .75rem">
                                    <a href="{{ route('admin.projekty.index', ['wybrana' => $ch->id]) }}" style="font-weight:700;color:#1d1d1a;text-decoration:none">{{ $ch->title }}</a>
                                    <span class="pt-badge {{ $ch->is_paid ? 'warn' : 'ok' }}">{{ $ch->is_paid ? 'Odpłatny' : 'Bezpłatny' }}</span>
                                    @unless ($ch->is_offered)<span class="pt-badge">Obecnie niedostępny</span>@endunless
                                    @unless ($ch->is_published)<span class="pt-badge warn">Szkic</span>@endunless
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
