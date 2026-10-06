{{--
    Gałąź drzewa stron (widok dwupanelowy) — wywoływana rekurencyjnie.
    Zmienne: $nodes (kolekcja Page), $byParent, $selected, $openIds, $depth.

    Nawigacja z listami zagnieżdżonymi (nie widżet ARIA tree): linki + przyciski
    rozwijania z aria-expanded/aria-controls, cele ≥ 44 px wysokości (2.5.8).
--}}
@php
    $typeIcons = [
        'about' => 'fa-building', 'faq' => 'fa-circle-question', 'event' => 'fa-calendar', 'schedule' => 'fa-calendar-days',
        'links_hub' => 'fa-table-cells-large', 'tiles_grid' => 'fa-table-cells', 'internal' => 'fa-lock', 'internal_hub' => 'fa-user-lock',
        'bip_move' => 'fa-landmark', 'service' => 'fa-briefcase', 'guide' => 'fa-list-ol', 'glossary' => 'fa-book', 'case_study' => 'fa-chart-line',
        'brand_assets' => 'fa-palette', 'wspolpraca' => 'fa-handshake', 'legacy' => 'fa-clock-rotate-left', 'training_institution' => 'fa-graduation-cap',
    ];
@endphp
<ul role="list" data-tree-list class="{{ $depth > 0 ? 'ml-3 border-l border-gray-200 pl-1' : '' }} space-y-0.5">
    @foreach ($nodes as $node)
        @php
            $kids = $byParent->get($node->id, collect());
            $isSel = $selected && $selected->id === $node->id;
            $isOpen = in_array($node->id, $openIds, true) || $isSel;
            $icon = $kids->isNotEmpty() ? ($isOpen ? 'fa-folder-open' : 'fa-folder') : ($typeIcons[$node->type] ?? 'fa-file-lines');
        @endphp
        <li data-tree-node data-page-id="{{ $node->id }}" data-title="{{ \Illuminate\Support\Str::lower($node->title) }}" @if ($kids->isNotEmpty()) x-data="{ open: {{ $isOpen ? 'true' : 'false' }} }" @endif>
            <div class="group flex items-stretch gap-0.5 rounded" data-tree-row @if ($canDrag && (! $node->is_locked || auth()->user()->isAdmin())) draggable="true" data-draggable="1" @endif>
                @if ($kids->isNotEmpty())
                    <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                        aria-controls="tree-branch-{{ $node->id }}"
                        :aria-label="(open ? 'Zwiń' : 'Rozwiń') + ': {{ addslashes($node->title) }}'" aria-label="{{ $isOpen ? 'Zwiń' : 'Rozwiń' }}: {{ $node->title }}"
                        class="flex min-h-9 w-7 flex-none items-center justify-center rounded text-[11px] text-muted hover:bg-gray-100 hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                        <i class="fa-solid fa-chevron-right transition-transform" :class="open ? 'rotate-90' : ''" aria-hidden="true"></i>
                    </button>
                @else
                    <span class="w-7 flex-none" aria-hidden="true"></span>
                @endif

                <a href="{{ route('admin.podstrony.index', ['wybrana' => $node->id]) }}" @if ($isSel) aria-current="true" @endif
                    class="flex min-h-9 min-w-0 flex-1 items-center gap-2 rounded px-2 py-1.5 text-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand
                        {{ $isSel ? 'bg-brand text-white font-bold' : 'text-ink hover:bg-gray-100' }}">
                    <i class="fa-solid {{ $icon }} w-4 flex-none text-center text-xs {{ $isSel ? 'text-white' : 'text-gray-400' }}" aria-hidden="true"></i>
                    <span class="min-w-0 flex-1 truncate {{ $node->is_published && ! $node->is_disabled && ! isset($inheritedDisabled[$node->id]) ? '' : 'opacity-70' }}">{{ $node->title }}</span>
                    @if ($node->is_system)<i class="fa-solid fa-lock text-[10px] opacity-60" aria-hidden="true"></i><span class="sr-only">(systemowa)</span>@endif
                    @if ($node->is_featured)<i class="fa-solid fa-star text-[10px] {{ $isSel ? 'text-white' : 'text-amber-500' }}" aria-hidden="true"></i><span class="sr-only">(wyróżniona)</span>@endif
                    @unless ($isSel)
                        @include('admin.pages.partials.status-chip', ['page' => $node, 'compact' => true, 'inherited' => isset($inheritedDisabled[$node->id])])
                    @else
                        <span class="sr-only">Wybrana strona. </span>
                    @endunless
                    @if ($kids->isNotEmpty())
                        <span class="rounded-full px-1.5 text-[10px] font-bold {{ $isSel ? 'bg-white/25 text-white' : 'bg-gray-100 text-muted' }}"><span class="sr-only">podstron: </span>{{ $kids->count() }}</span>
                    @endif
                </a>
            </div>

            @if ($kids->isNotEmpty())
                <div id="tree-branch-{{ $node->id }}" x-show="open" @if (! $isOpen) x-cloak @endif>
                    @include('admin.pages.partials.tree-node', ['nodes' => $kids, 'depth' => $depth + 1, 'canDrag' => $canDrag, 'inheritedDisabled' => $inheritedDisabled])
                </div>
            @endif
        </li>
    @endforeach
</ul>
