@php
    /**
     * Pozycja w drzewie menu — zwarty wiersz jak w drzewie stron (pages/partials/tree-node) i w TYPO3.
     * Parametry: $item, $level (1|2), $selected, $location, $health (MenuLinkCheck::forItems), $index (pozycja wśród rodzeństwa).
     *
     * Nawigacja z listami zagnieżdżonymi (nie widżet ARIA tree): link do pozycji + przycisk rozwijania z aria-expanded,
     * cele ≥ 24 px (WCAG 2.5.8). Atrybuty data-* czyta skrypt drzewa w index.blade.php (przeciąganie, menu kontekstowe,
     * schowek, klawiatura).
     */
    $isSel = $selected && $selected->id === $item->id;
    $kids = $level === 1 ? $item->allChildren : collect();
    $h = $health[$item->id] ?? ['state' => 'ok', 'kind' => 'page', 'issue' => false, 'label' => 'widoczna', 'page' => null];
    $isOpen = $kids->isNotEmpty() && $selected && ($selected->id === $item->id || $selected->parent_id === $item->id);
    // Ikona typu zawsze widoczna (jak w TYPO3); nakładka stanu tylko dla wyjątków.
    $icon = match (true) {
        $item->is_button => 'fa-bullhorn',
        $item->is_column_heading => 'fa-table-columns',
        $item->type === 'projects' => 'fa-diagram-project',
        $item->type === 'pages' => 'fa-sitemap',
        $item->type === 'volunteering' => 'fa-people-carry-box',
        $item->type === 'events' => 'fa-calendar',
        $item->type === 'faq' => 'fa-circle-question',
        $item->type === 'dropdown' || $kids->isNotEmpty() => 'fa-folder',
        $h['kind'] === 'external' => 'fa-globe',
        $h['kind'] === 'anchor' => 'fa-hashtag',
        $h['kind'] === 'mail' => 'fa-envelope',
        $h['kind'] === 'phone' => 'fa-phone',
        $h['kind'] === 'home' => 'fa-house',
        $h['kind'] === 'none' => 'fa-link',
        default => 'fa-file-lines',
    };
    $ov = \App\Support\MenuLinkCheck::overlay($h['state']);
    $offSite = in_array($h['state'], ['hidden', 'module_off'], true);
    $canHold = $level === 1 && $item->location === 'main' && ! $item->is_button && in_array($item->type, ['dropdown', 'link', 'projects'], true);
    $nestable = $item->location === 'main' && $kids->isEmpty() && ! $item->is_button && ! in_array($item->type, ['dropdown', 'projects', 'volunteering', 'events', 'faq'], true);
    // Adres do „Zobacz na stronie” i „Kopiuj adres” (pusty dla rozwijanego menu bez adresu).
    $publicUrl = match ($h['kind']) {
        'external', 'mail', 'phone' => (string) $item->url,
        'anchor' => url('/').$item->url,
        'none' => '',
        default => \Illuminate\Support\Str::startsWith((string) $item->url, ['http://', 'https://']) ? (string) $item->url : url((string) $item->url),
    };
    $page = $h['page'] ?? null;
    $canPages = $page && auth()->user()->canAccessModule('pages');
@endphp
<li data-nav-node data-id="{{ $item->id }}" data-pos="{{ $index }}" data-level="{{ $level }}" data-label="{{ $item->label }}"
    data-title="{{ \Illuminate\Support\Str::lower($item->label.' '.$item->url) }}" data-kids="{{ $kids->isNotEmpty() ? 1 : 0 }}"
    data-can-hold="{{ $canHold ? 1 : 0 }}" data-nestable="{{ $nestable ? 1 : 0 }}" data-active="{{ $item->is_active ? 1 : 0 }}"
    data-issue="{{ $h['issue'] && $item->is_active ? 1 : 0 }}" data-public-url="{{ $publicUrl }}"
    @if ($canPages) data-page-edit="{{ route('admin.podstrony.edit', $page) }}" data-page-tree="{{ route('admin.podstrony.index', ['wybrana' => $page->id]) }}" @endif
    @if ($kids->isNotEmpty()) x-data="{ open: {{ $isOpen ? 'true' : 'false' }} || !! window.navTreeState?.has({{ $item->id }}) }" x-init="$watch('open', v => window.navTreeState?.set({{ $item->id }}, v))" @endif>
    <div class="group flex items-stretch gap-0.5 rounded" data-nav-row draggable="true">
        @if ($kids->isNotEmpty())
            <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                aria-controls="nav-branch-{{ $item->id }}"
                :aria-label="(open ? 'Zwiń' : 'Rozwiń') + ': ' + $root.dataset.label" aria-label="{{ $isOpen ? 'Zwiń' : 'Rozwiń' }}: {{ $item->label }}"
                class="flex min-h-6 w-6 flex-none items-center justify-center rounded text-[10px] text-muted hover:bg-gray-100 hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                <i class="fa-solid fa-chevron-right transition-transform" :class="open ? 'rotate-90' : ''" aria-hidden="true"></i>
            </button>
        @else
            <span class="w-6 flex-none" aria-hidden="true"></span>
        @endif

        <a href="{{ route('admin.pozycje-menu.index', ['location' => $location, 'pozycja' => $item->id]) }}" data-nav-first-action title="{{ $item->label }}"
            @if ($isSel) aria-current="true" @endif
            class="tree-link flex min-h-6 min-w-0 flex-1 items-center gap-1.5 rounded-sm px-1.5 py-px text-sm leading-snug transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark
                {{ $isSel ? 'is-selected bg-brand-light text-ink font-semibold' : 'text-ink hover:bg-gray-100' }}">
            <span class="tree-icon relative inline-flex w-4 flex-none justify-center" aria-hidden="true">
                <i class="fa-solid {{ $icon }} text-xs {{ $isSel ? 'text-brand-dark' : 'text-gray-500' }}"></i>
                @if ($ov)<i data-tree-chip class="tree-ov fa-solid {{ $ov[0] }} {{ $ov[1] }}"></i>@endif
            </span>
            {{-- „M” = strona z modułu (lista aktualności, materiały…), bez znaczka = zwykła strona CMS albo link zewnętrzny. --}}
            @if ($item->isModuleLink())
                <span class="inline-flex h-4 w-4 flex-none items-center justify-center rounded-sm border border-brand-dark bg-brand-light text-[10px] font-bold leading-none text-brand-dark" title="Strona z modułu">M<span class="sr-only"> — strona z modułu</span></span>
            @endif
            <span class="min-w-0 flex-1 truncate {{ $offSite ? 'text-muted' : '' }}">{{ $item->label }}</span>
            @if ($h['kind'] === 'external')<i class="fa-solid fa-arrow-up-right-from-square text-[10px] text-gray-500" aria-hidden="true"></i><span class="sr-only">(link zewnętrzny)</span>@endif
            @if ($item->is_mega && $level === 1)<i class="fa-solid fa-border-all text-[10px] text-brand-dark" title="Mega menu" aria-hidden="true"></i><span class="sr-only">(mega menu)</span>@endif
            @if ($item->highlight)<i class="fa-solid fa-star text-[10px] text-amber-500" aria-hidden="true"></i><span class="sr-only">(wyróżniona)</span>@endif
            <span class="sr-only">({{ $h['label'] }}){{ $isSel ? ' Wybrana pozycja.' : '' }}</span>
            @if ($kids->isNotEmpty())
                <span data-tree-count class="rounded-full bg-gray-100 px-1.5 text-[10px] font-bold text-muted"><span class="sr-only">podpozycji: </span>{{ $kids->count() }}</span>
            @endif
        </a>
        {{-- Akcje wiersza dla myszy i dotyku; z klawiatury to samo menu otwiera Shift+F10 lub klawisz Menu na linku. --}}
        <button type="button" data-tree-menu-btn tabindex="-1" aria-hidden="true" title="Akcje pozycji"
            class="flex min-h-6 w-6 flex-none items-center justify-center rounded text-xs text-muted opacity-0 hover:bg-gray-100 hover:text-brand-dark group-hover:opacity-100">
            <i class="fa-solid fa-ellipsis" aria-hidden="true"></i>
        </button>
    </div>
    @if ($kids->isNotEmpty())
        <div id="nav-branch-{{ $item->id }}" x-show="open" @unless ($isOpen) x-cloak @endunless>
            <ul role="list" class="tree-sub" data-nav-list>
                @foreach ($kids as $child)
                    @include('admin.nav-items._tree-node', ['item' => $child, 'level' => 2, 'selected' => $selected, 'location' => $location, 'health' => $health, 'index' => $loop->index])
                @endforeach
            </ul>
        </div>
    @endif
</li>
