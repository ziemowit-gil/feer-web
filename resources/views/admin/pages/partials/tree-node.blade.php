{{--
    Gałąź drzewa stron (widok dwupanelowy) — wywoływana rekurencyjnie.
    Zmienne: $nodes (kolekcja Page), $byParent, $selected, $openIds, $depth.

    Nawigacja z listami zagnieżdżonymi (nie widżet ARIA tree): linki + przyciski
    rozwijania z aria-expanded/aria-controls, cele ≥ 24 px wysokości (2.5.8), zwarte wiersze jak w drzewie stron TYPO3.
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
            // Ikona jak w TYPO3: typ strony zawsze widoczny; zwykła strona z podstronami to folder.
            $icon = $typeIcons[$node->type] ?? ($kids->isNotEmpty() ? ($isOpen ? 'fa-folder-open' : 'fa-folder') : 'fa-file-lines');
            // Nakładka stanu na ikonie (tylko wyjątki — opublikowana strona jej nie ma): wyłączona, szkic, zaplanowana, dostęp ograniczony.
            $nodeRestricted = $node->access_level && ! in_array($node->access_level, ['inherit', 'public'], true);
            [$ovIcon, $ovClass, $ovLabel] = match (true) {
                $state === 'disabled' => ['fa-ban', 'text-red-700', $node->is_disabled ? 'wyłączona' : 'wyłączona przez stronę nadrzędną'],
                $state === 'draft' => ['fa-pen', 'text-muted', 'szkic'],
                $node->is_published && $node->publish_at && $node->publish_at->isFuture() => ['fa-clock', 'text-brand-dark', 'zaplanowana'],
                $nodeRestricted => ['fa-key', 'text-ink', 'dostęp ograniczony: '.(\App\Models\Page::ACCESS_LEVELS[$node->access_level] ?? $node->access_level)],
                default => [null, '', 'opublikowana'],
            };
            // Dane dla menu kontekstowego drzewa (tree.blade.php): adresy podglądu i uprawnienia wiersza.
            $nodeLive = $node->is_published && ($node->publish_at === null || $node->publish_at->isPast());
        @endphp
        <li data-tree-node data-page-id="{{ $node->id }}" data-pos="{{ $loop->index }}" data-project="{{ $node->project_id ?: 0 }}" data-folder="{{ $kids->isNotEmpty() ? 1 : 0 }}" data-state="{{ $state }}" data-menu="{{ ! empty(($menuPositions ?? [])[$node->id] ?? null) ? 1 : 0 }}" data-title="{{ \Illuminate\Support\Str::lower($node->title) }}"
            data-label="{{ $node->title }}" data-published="{{ $node->is_published ? 1 : 0 }}" data-system="{{ $node->is_system ? 1 : 0 }}"
            data-can-edit="{{ ! $node->is_locked || auth()->user()->isAdmin() ? 1 : 0 }}"
            data-public-url="{{ $node->publicUrl() }}" data-view-url="{{ $nodeLive ? $node->publicUrl() : $node->previewUrl() }}"
            @if ($kids->isNotEmpty()) x-data="{ open: {{ $isOpen ? 'true' : 'false' }} || !! window.pageTreeState?.has({{ $node->id }}) }" x-init="$watch('open', v => window.pageTreeState?.set({{ $node->id }}, v))" @endif>
            <div class="group flex items-stretch gap-0.5 rounded" data-tree-row @if ($canDrag && (! $node->is_locked || auth()->user()->isAdmin())) draggable="true" data-draggable="1" @endif>
                @if ($kids->isNotEmpty())
                    <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                        aria-controls="tree-branch-{{ $node->id }}"
                        :aria-label="(open ? 'Zwiń' : 'Rozwiń') + ': {{ addslashes($node->title) }}'" aria-label="{{ $isOpen ? 'Zwiń' : 'Rozwiń' }}: {{ $node->title }}"
                        class="flex min-h-6 w-6 flex-none items-center justify-center rounded text-[10px] text-muted hover:bg-gray-100 hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                        <i class="fa-solid fa-chevron-right transition-transform" :class="open ? 'rotate-90' : ''" aria-hidden="true"></i>
                    </button>
                @else
                    <span class="w-6 flex-none" aria-hidden="true"></span>
                @endif

                <a href="{{ route('admin.podstrony.index', ['wybrana' => $node->id]) }}" title="{{ $node->title }}" @if ($isSel) aria-current="true" @endif
                    class="tree-link flex min-h-6 min-w-0 flex-1 items-center gap-1.5 rounded-sm px-1.5 py-px text-sm leading-snug transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark
                        {{ $isSel ? 'is-selected bg-brand-light text-ink font-semibold' : 'text-ink hover:bg-gray-100' }}">
                    <span class="tree-icon relative inline-flex w-4 flex-none justify-center" aria-hidden="true">
                        <i class="fa-solid {{ $icon }} text-xs {{ $isSel ? 'text-brand-dark' : 'text-gray-500' }}"></i>
                        @if ($ovIcon)<i data-tree-chip class="tree-ov fa-solid {{ $ovIcon }} {{ $ovClass }}"></i>@endif
                    </span>
                    <span class="min-w-0 flex-1 truncate {{ $state === 'live' ? '' : 'text-muted' }}">{{ $node->title }}</span>
                    @if ($node->project_id)<span class="inline-flex h-4 w-4 flex-none items-center justify-center rounded-sm text-[10px] font-bold leading-none" style="background:#166534;color:#fff" aria-hidden="true">D</span><span class="sr-only">(podstrona działania)</span>@endif
                    @if ($node->is_system)<i class="fa-solid fa-lock text-[10px] opacity-60" aria-hidden="true"></i><span class="sr-only">(systemowa)</span>@endif
                    @if ($nodeRestricted && $ovIcon !== 'fa-key')<i class="fa-solid fa-key text-[10px] text-gray-500" aria-hidden="true"></i><span class="sr-only">(dostęp ograniczony: {{ \App\Models\Page::ACCESS_LEVELS[$node->access_level] ?? $node->access_level }})</span>@endif
                    @if (! empty(($menuPositions ?? [])[$node->id] ?? null))
                        @php $nodeMenu = collect(($menuPositions ?? [])[$node->id])->map(fn ($p) => $p['location_label'].($p['parent'] ? ' › '.$p['parent'] : ''))->unique()->implode(', '); @endphp
                        <i class="fa-solid fa-bars text-[10px] text-brand-dark" title="W menu: {{ $nodeMenu }}" aria-hidden="true"></i><span class="sr-only">(w menu: {{ $nodeMenu }})</span>
                    @endif
                    @if ($node->is_featured)<i class="fa-solid fa-star text-[10px] text-amber-500" aria-hidden="true"></i><span class="sr-only">(wyróżniona)</span>@endif
                    <span class="sr-only">({{ $ovLabel }}){{ $isSel ? ' Wybrana strona.' : '' }}</span>
                    @if ($kids->isNotEmpty())
                        <span data-tree-count class="rounded-full px-1.5 text-[10px] font-bold bg-gray-100 text-muted"><span class="sr-only">podstron: </span>{{ $kids->count() }}</span>
                    @endif
                </a>
                {{-- Akcje wiersza dla myszy i dotyku; z klawiatury to samo menu otwiera Shift+F10 lub klawisz Menu na linku. --}}
                <button type="button" data-tree-menu-btn tabindex="-1" aria-hidden="true" title="Akcje strony"
                    class="flex min-h-6 w-6 flex-none items-center justify-center rounded text-xs text-muted opacity-0 hover:bg-gray-100 hover:text-brand-dark group-hover:opacity-100">
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
