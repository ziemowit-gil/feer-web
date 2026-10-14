{{--
    Gałąź drzewa stron (widok dwupanelowy) — wywoływana rekurencyjnie.
    Zmienne: $nodes (kolekcja Page), $byParent, $selected, $openIds, $depth.

    Nawigacja z listami zagnieżdżonymi (nie widżet ARIA tree): linki + przyciski
    rozwijania z aria-expanded/aria-controls, cele ≥ 28 px wysokości (2.5.8 wymaga min. 24 px).
--}}
@php
    $typeIcons = \App\Models\Page::typeIcons();
@endphp
<ul role="list" data-tree-list class="{{ $depth > 0 ? 'tree-sub' : '' }}">
    @foreach ($nodes as $node)
        @php
            $kids = $byParent->get($node->id, collect());
            $isSel = $selected && $selected->id === $node->id;
            $isOpen = in_array($node->id, $openIds, true); // domyślnie zwinięte — rozwinięta tylko droga do wybranej strony
            $state = ($node->is_disabled || isset($inheritedDisabled[$node->id])) ? 'disabled' : ($node->is_published ? 'live' : 'draft');
            $icon = $kids->isNotEmpty() ? ($isOpen ? 'fa-folder-open' : 'fa-folder') : ($typeIcons[$node->type] ?? 'fa-file-lines');
            // Dane dla menu kontekstowego drzewa (tree.blade.php): adresy podglądu i uprawnienia wiersza.
            $nodeLive = $node->is_published && ($node->publish_at === null || $node->publish_at->isPast());
        @endphp
        <li data-tree-node data-page-id="{{ $node->id }}" data-pos="{{ $loop->index }}" data-folder="{{ $kids->isNotEmpty() ? 1 : 0 }}" data-state="{{ $state }}" data-menu="{{ $node->show_in_menu ? 1 : 0 }}" data-title="{{ \Illuminate\Support\Str::lower($node->title) }}"
            data-label="{{ $node->title }}" data-published="{{ $node->is_published ? 1 : 0 }}" data-system="{{ $node->is_system ? 1 : 0 }}"
            data-can-edit="{{ ! $node->is_locked || auth()->user()->isAdmin() ? 1 : 0 }}"
            data-public-url="{{ $node->publicUrl() }}" data-view-url="{{ $nodeLive ? $node->publicUrl() : $node->previewUrl() }}" @if ($kids->isNotEmpty()) x-data="{ open: {{ $isOpen ? 'true' : 'false' }} }" @endif>
            <div class="group flex items-stretch gap-0.5 rounded" data-tree-row @if ($canDrag && (! $node->is_locked || auth()->user()->isAdmin())) draggable="true" data-draggable="1" @endif>
                @if ($kids->isNotEmpty())
                    <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                        aria-controls="tree-branch-{{ $node->id }}"
                        :aria-label="(open ? 'Zwiń' : 'Rozwiń') + ': {{ addslashes($node->title) }}'" aria-label="{{ $isOpen ? 'Zwiń' : 'Rozwiń' }}: {{ $node->title }}"
                        class="flex min-h-7 w-6 flex-none items-center justify-center rounded text-[11px] text-muted hover:bg-gray-100 hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                        <i class="fa-solid fa-chevron-right transition-transform" :class="open ? 'rotate-90' : ''" aria-hidden="true"></i>
                    </button>
                @else
                    <span class="w-6 flex-none" aria-hidden="true"></span>
                @endif

                <a href="{{ route('admin.podstrony.index', ['wybrana' => $node->id]) }}" title="{{ $node->title }}" @if ($isSel) aria-current="true" @endif
                    class="flex min-h-7 min-w-0 flex-1 items-center gap-2 rounded px-1.5 py-0.5 text-sm transition {{ $depth === 0 ? 'font-semibold' : '' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark
                        {{ $isSel ? 'bg-brand-dark text-white font-bold' : 'text-ink hover:bg-gray-100' }}">
                    <i class="fa-solid {{ $icon }} w-4 flex-none text-center text-xs {{ $isSel ? 'text-white' : 'text-gray-400' }}" aria-hidden="true"></i>
                    <span class="min-w-0 flex-1 truncate {{ $node->is_published && ! $node->is_disabled && ! isset($inheritedDisabled[$node->id]) ? '' : 'opacity-70' }}">{{ $node->title }}</span>
                    @if ($node->project_id)<span class="inline-flex h-4 w-4 flex-none items-center justify-center rounded-sm text-[10px] font-bold leading-none" style="{{ $isSel ? 'background:#fff;color:#166534' : 'background:#166534;color:#fff' }}" aria-hidden="true">D</span><span class="sr-only">(podstrona działania)</span>@endif
                    @if ($node->is_system)<i class="fa-solid fa-lock text-[10px] opacity-60" aria-hidden="true"></i><span class="sr-only">(systemowa)</span>@endif
                    @if ($node->access_level && ! in_array($node->access_level, ['inherit', 'public'], true))<i class="fa-solid fa-key text-[10px] {{ $isSel ? 'text-white' : 'text-gray-500' }}" aria-hidden="true"></i><span class="sr-only">(dostęp ograniczony: {{ \App\Models\Page::ACCESS_LEVELS[$node->access_level] ?? $node->access_level }})</span>@endif
                    @if ($node->is_featured)<i class="fa-solid fa-star text-[10px] {{ $isSel ? 'text-white' : 'text-amber-500' }}" aria-hidden="true"></i><span class="sr-only">(wyróżniona)</span>@endif
                    @unless ($isSel)
                        <span data-tree-chip class="inline-flex">@include('admin.pages.partials.status-chip', ['page' => $node, 'compact' => true, 'inherited' => isset($inheritedDisabled[$node->id])])</span>
                    @else
                        <span class="sr-only">Wybrana strona. </span>
                    @endunless
                    @if ($kids->isNotEmpty())
                        <span data-tree-count class="rounded-full px-1.5 text-[10px] font-bold {{ $isSel ? 'bg-white/25 text-white' : 'bg-gray-100 text-muted' }}"><span class="sr-only">podstron: </span>{{ $kids->count() }}</span>
                    @endif
                </a>
                {{-- Akcje wiersza dla myszy i dotyku; z klawiatury to samo menu otwiera Shift+F10 lub klawisz Menu na linku. --}}
                <button type="button" data-tree-menu-btn tabindex="-1" aria-hidden="true" title="Akcje strony"
                    class="flex min-h-7 w-7 flex-none items-center justify-center rounded text-xs text-muted opacity-0 hover:bg-gray-100 hover:text-brand-dark group-hover:opacity-100">
                    <i class="fa-solid fa-ellipsis" aria-hidden="true"></i>
                </button>
            </div>

            @if ($kids->isNotEmpty())
                <div id="tree-branch-{{ $node->id }}" x-show="open" @if (! $isOpen) x-cloak @endif>
                    @include('admin.pages.partials.tree-node', ['nodes' => $kids, 'depth' => $depth + 1, 'canDrag' => $canDrag, 'inheritedDisabled' => $inheritedDisabled])
                </div>
            @endif
        </li>
    @endforeach
</ul>
