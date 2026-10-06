@php
    /**
     * Prawy panel: szczegóły wybranej pozycji menu, jej akcje, podgląd kolumn mega menu i podpozycje.
     * Zmienne: $selected, $navItems (pozycje najwyższego poziomu), $location.
     */
    $item = $selected;
    $parent = $item->parent_id ? $item->parent : null;
    $siblings = $parent ? $parent->allChildren : $navItems;
    $pos = $siblings->search(fn ($s) => $s->id === $item->id);
    $isFirst = $pos === 0;
    $isLast = $pos === $siblings->count() - 1;
    $children = $parent ? collect() : $item->allChildren;
    $canHold = ! $parent && $item->location === 'main' && ! $item->is_button && in_array($item->type, ['dropdown', 'link', 'projects'], true);
    $canIndent = ! $parent && $pos > 0 && $item->location === 'main' && $item->type === 'link' && ! $item->is_button && $children->isEmpty();
    $canOutdent = (bool) $parent;
    $btn = 'inline-flex items-center rounded-lg border border-gray-300 bg-white text-xs font-bold text-ink hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand disabled:cursor-not-allowed disabled:opacity-40';
    $btnStyle = 'min-height:2.25rem;padding:.375rem .75rem;gap:.375rem';
    $mega = $item->isMega() ? $item->megaSections() : [];
@endphp

@if ($parent)
    <nav aria-label="Ścieżka pozycji" class="mb-2 text-xs text-muted">
        <a href="{{ route('admin.pozycje-menu.index', ['location' => $location, 'pozycja' => $parent->id]) }}" class="hover:text-brand hover:underline">{{ $parent->label }}</a>
        <span aria-hidden="true" class="mx-1">›</span><span class="font-semibold text-ink" aria-current="page">{{ $item->label }}</span>
    </nav>
@endif

<div class="rounded-lg border border-gray-200 bg-white" style="padding: 1.25rem">
    <div style="display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:.75rem">
        <div style="min-width:0">
            <h2 id="nav-pane-heading" class="text-xl font-bold text-ink">{{ $item->label }}</h2>
            <p class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                <span class="rounded-full bg-gray-100 px-2 py-0.5 font-semibold text-gray-700">{{ \App\Models\NavItem::TYPES[$item->type] ?? $item->type }}</span>
                @if ($item->is_button)<span class="rounded-full bg-brand-light px-2 py-0.5 font-bold text-brand">Przycisk (CTA)</span>@endif
                @if ($item->is_column_heading)<span class="rounded-full bg-indigo-50 px-2 py-0.5 font-bold text-indigo-700"><i class="fa-solid fa-table-columns" aria-hidden="true"></i> Nagłówek kolumny</span>@endif
                @if ($item->is_mega && ! $parent)<span class="rounded-full bg-indigo-50 px-2 py-0.5 font-bold text-indigo-700">Mega menu</span>@endif
                @if ($item->is_active)
                    <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 font-bold text-green-700"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Widoczna</span>
                @else
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 font-bold text-amber-700"><i class="fa-solid fa-eye-slash" aria-hidden="true"></i> Ukryta</span>
                @endif
            </p>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:.5rem">
            <button type="button" data-nav-first-action @click="openEdit($event)"
                @include('admin.nav-items._edit-attrs', ['item' => $item])
                class="inline-flex items-center rounded bg-brand text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2"
                style="min-height:2.25rem;padding:.375rem 1rem;gap:.375rem">
                <i class="fa-solid fa-pen" aria-hidden="true"></i> Edytuj
            </button>
            @if ($canHold)
                <button type="button" @click="openCreate($event)" data-parent="{{ $item->id }}" data-location="{{ $item->location }}" class="{{ $btn }}" style="{{ $btnStyle }}">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj podpozycję
                </button>
            @endif
        </div>
    </div>

    <dl class="mt-4 grid gap-x-6 gap-y-3 text-sm" style="grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr))">
        <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Adres</dt>
            <dd class="mt-0.5 break-all font-mono text-xs">
                @if ($item->isDropdown() && blank($item->url)) <span class="text-muted">— (rozwijane menu bez własnego adresu)</span>
                @else {{ $item->url ?: '—' }} @endif
            </dd></div>
        <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Pozycja nadrzędna</dt>
            <dd class="mt-0.5">@if ($parent)<a href="{{ route('admin.pozycje-menu.index', ['location' => $location, 'pozycja' => $parent->id]) }}" class="text-brand hover:underline">{{ $parent->label }}</a>@else<span class="text-muted">— pasek menu</span>@endif</dd></div>
        <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Kolejność</dt><dd class="mt-0.5">{{ $pos !== false ? $pos + 1 : '—' }} z {{ $siblings->count() }}</dd></div>
        @if ($item->module)<div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Widoczna gdy moduł włączony</dt><dd class="mt-0.5">{{ \App\Models\SiteSetting::MODULES[$item->module] ?? $item->module }}</dd></div>@endif
        @if ($item->icon)<div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Ikona</dt><dd class="mt-0.5"><i class="bi {{ $item->icon }}" aria-hidden="true"></i> <span class="font-mono text-xs">{{ $item->icon }}</span></dd></div>@endif
        @if ($item->description)<div style="grid-column: 1 / -1"><dt class="text-xs font-bold uppercase tracking-wide text-muted">Opis w mega menu</dt><dd class="mt-0.5">{{ $item->description }}</dd></div>@endif
        @if ($item->is_mega && ! $parent)<div><dt class="text-xs font-bold uppercase tracking-wide text-muted">Wielkość pozycji</dt><dd class="mt-0.5">{{ \App\Models\NavItem::MEGA_SIZES[$item->megaSize()] ?? $item->megaSize() }}</dd></div>@endif
    </dl>

    {{-- Akcje: położenie, widoczność, usunięcie --}}
    <div class="mt-5 border-t border-gray-100 pt-4" style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem 1.5rem">
        <div role="group" aria-label="Położenie w menu" style="display:flex;flex-wrap:wrap;align-items:center;gap:.375rem">
            <span class="text-muted" style="font-size:.6875rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase" aria-hidden="true">Położenie</span>
            @foreach ([['up', 'fa-arrow-up', 'W górę', $isFirst], ['down', 'fa-arrow-down', 'W dół', $isLast]] as [$act, $ico, $lbl, $dis])
                <form method="POST" action="{{ route('admin.pozycje-menu.przenies', $item) }}">@csrf @method('PATCH')
                    <input type="hidden" name="action" value="{{ $act }}">
                    <button type="submit" class="{{ $btn }}" style="{{ $btnStyle }}" @disabled($dis)><i class="fa-solid {{ $ico }}" aria-hidden="true"></i> {{ $lbl }}<span class="sr-only"> „{{ $item->label }}”</span></button>
                </form>
            @endforeach
            @if ($canIndent)
                <form method="POST" action="{{ route('admin.pozycje-menu.przenies', $item) }}">@csrf @method('PATCH')
                    <input type="hidden" name="action" value="indent">
                    <button type="submit" class="{{ $btn }}" style="{{ $btnStyle }}"><i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i> Zagnieźdź<span class="sr-only"> „{{ $item->label }}”</span></button>
                </form>
            @endif
            @if ($canOutdent)
                <form method="POST" action="{{ route('admin.pozycje-menu.przenies', $item) }}">@csrf @method('PATCH')
                    <input type="hidden" name="action" value="outdent">
                    <button type="submit" class="{{ $btn }}" style="{{ $btnStyle }}"><i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i> Wysuń na pasek<span class="sr-only"> „{{ $item->label }}”</span></button>
                </form>
            @endif
        </div>
        <div role="group" aria-label="Widoczność i usuwanie" style="display:flex;flex-wrap:wrap;align-items:center;gap:.375rem;margin-left:auto">
            <form method="POST" action="{{ route('admin.pozycje-menu.aktywna', $item) }}">@csrf @method('PATCH')
                <button type="submit" class="{{ $btn }}" style="{{ $btnStyle }}">
                    <i class="fa-solid {{ $item->is_active ? 'fa-eye-slash' : 'fa-eye text-green-600' }}" aria-hidden="true"></i> {{ $item->is_active ? 'Ukryj w menu' : 'Pokaż w menu' }}
                </button>
            </form>
            <form method="POST" action="{{ route('admin.pozycje-menu.destroy', $item) }}"
                onsubmit="return confirm('Usunąć „{{ addslashes($item->label) }}”?{{ $children->isNotEmpty() ? ' Usunięte zostaną też podpozycje.' : '' }}');">@csrf @method('DELETE')
                <button type="submit" class="inline-flex items-center rounded-lg border border-red-200 bg-white text-xs font-bold text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500" style="{{ $btnStyle }}">
                    <i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Podgląd kolumn mega menu --}}
@if ($mega)
    <div class="mt-4 rounded-lg border border-gray-200 bg-white" style="padding: 1.25rem">
        <h3 class="text-sm font-bold text-ink"><i class="fa-solid fa-table-columns mr-1 text-brand" aria-hidden="true"></i> Układ w mega menu</h3>
        <p class="mt-0.5 text-xs text-muted">Tak podpozycje układają się w kolumny. Nagłówek kolumny dodasz w formularzu podpozycji (pole „Nagłówek kolumny”).</p>
        <div class="mt-3" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(11rem,1fr));gap:.75rem">
            @foreach ($mega as $section)
                <div class="rounded-lg bg-gray-50" style="padding:.75rem">
                    <p class="text-xs font-bold text-ink">{{ $section['heading']['label'] ?? 'Bez nagłówka' }}</p>
                    <ul class="mt-1 space-y-0.5 text-xs text-muted" role="list">
                        @foreach (array_slice($section['links'], 0, 6) as $link)<li class="truncate">{{ $link[1] }}</li>@endforeach
                        @if (count($section['links']) > 6)<li>… i {{ count($section['links']) - 6 }} więcej</li>@endif
                        @if ($section['links'] === [])<li>—</li>@endif
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- Podpozycje --}}
@unless ($parent)
    @if ($canHold || $children->isNotEmpty())
        <div class="mt-4">
            <h3 class="mb-2 text-sm font-bold text-ink">Podpozycje <span class="font-normal text-muted">({{ $children->count() }})</span></h3>
            @if ($children->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-300 bg-white px-4 py-8 text-center text-sm text-muted">Ta pozycja nie ma jeszcze podpozycji.</div>
            @else
                <ul role="list" class="divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white">
                    @foreach ($children as $child)
                        <li style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;padding:.5rem .75rem">
                            <a href="{{ route('admin.pozycje-menu.index', ['location' => $location, 'pozycja' => $child->id]) }}" class="min-w-0 flex-1 text-sm font-semibold text-ink hover:text-brand" style="min-width:10rem">
                                {{ $child->label }}
                                @if ($child->is_column_heading)<span class="ml-1 rounded-full bg-indigo-50 px-1.5 text-[10px] font-bold text-indigo-700">nagłówek kolumny</span>@endif
                                @unless ($child->is_active)<span class="ml-1 rounded-full bg-amber-100 px-1.5 text-[10px] font-bold text-amber-700">ukryta</span>@endunless
                                <span class="block truncate font-mono text-xs font-normal text-muted">{{ $child->url }}</span>
                            </a>
                            <button type="button" @click="openEdit($event)"
                                @include('admin.nav-items._edit-attrs', ['item' => $child])
                                class="{{ $btn }}" style="{{ $btnStyle }}"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edytuj<span class="sr-only"> „{{ $child->label }}”</span></button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif
@endunless
