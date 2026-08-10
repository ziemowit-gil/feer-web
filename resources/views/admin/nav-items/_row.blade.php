@php
    /**
     * Pojedynczy węzeł drzewa menu.
     * Parametry: $item, $level (1 lub 2), $position (1-indeks), $setsize.
     */
    $children      = $level === 1 ? $item->allChildren : collect();
    $hasChildren   = $children->isNotEmpty();
    $isFirst       = $position === 1;
    $isLast        = $position === $setsize;
    $groupId       = 'nav-group-'.$item->id;

    $canHoldChildren = $level === 1 && $item->location === 'main' && ! $item->is_button && in_array($item->type, ['dropdown', 'link'], true);
    $canIndent       = $level === 1 && ! $isFirst && $item->location === 'main' && $item->type === 'link' && ! $item->is_button && ! $hasChildren;
    $canOutdent      = $level === 2;

    // Pasek koloru wg typu (span z lewej strony karty)
    $accentBg = match(true) {
        $item->is_button                => 'bg-brand',
        $item->type === 'dropdown'      => 'bg-blue-400',
        $item->type === 'projects_menu' => 'bg-purple-400',
        default                         => 'bg-gray-200',
    };

    // Kolor odznaki typ
    $badgeClass = match(true) {
        $item->is_button                => 'bg-brand-light text-brand',
        $item->type === 'dropdown'      => 'bg-blue-50 text-blue-700',
        $item->type === 'projects_menu' => 'bg-purple-50 text-purple-700',
        default                         => 'bg-gray-100 text-gray-600',
    };

    $btn = 'inline-flex h-7 w-7 items-center justify-center rounded text-gray-400 transition-colors hover:bg-gray-100 hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand disabled:opacity-25 disabled:pointer-events-none';
@endphp

<li id="nav-item-{{ $item->id }}" data-id="{{ $item->id }}" role="treeitem"
    aria-level="{{ $level }}" aria-setsize="{{ $setsize }}" aria-posinset="{{ $position }}"
    @if ($hasChildren) x-data="{ expanded: true }" :aria-expanded="expanded" @endif
    class="list-none">

    {{-- ══ KARTA POZYCJI ══ --}}
    <div class="group flex items-stretch overflow-hidden rounded-lg border bg-white transition-shadow hover:shadow-sm
        @if ($level === 1) border-gray-200 @else border-gray-100 @endif
        @unless ($item->is_active) opacity-60 @endunless">

        {{-- Pasek koloru --}}
        @if ($level === 1)
            <span class="w-1 flex-none self-stretch {{ $accentBg }}" aria-hidden="true"></span>
        @endif

        {{-- Uchwyt drag & drop --}}
        <span class="drag-handle hidden cursor-grab select-none items-center self-stretch bg-gray-50/70 px-2 text-gray-300 hover:bg-gray-100 hover:text-gray-400 active:cursor-grabbing sm:flex"
            aria-hidden="true" title="Przeciągnij, aby zmienić kolejność">
            <i class="fa-solid fa-grip-vertical text-xs"></i>
        </span>

        {{-- Przycisk rozwijania (tylko gdy ma dzieci) --}}
        @if ($hasChildren)
            <button type="button" @click="expanded = !expanded" :aria-expanded="expanded"
                aria-controls="{{ $groupId }}"
                class="flex w-8 flex-none items-center justify-center self-stretch text-gray-400 hover:bg-gray-50 hover:text-brand focus-visible:outline-none focus-visible:ring-inset focus-visible:ring-2 focus-visible:ring-brand">
                <i class="fa-solid fa-chevron-right text-[10px] transition-transform duration-200"
                    :class="{ 'rotate-90': expanded }" aria-hidden="true"></i>
                <span class="sr-only" x-text="expanded ? 'Zwiń podpozycje' : 'Rozwiń podpozycje'"></span>
            </button>
        @else
            <span class="w-8 flex-none" aria-hidden="true"></span>
        @endif

        {{-- Treść główna --}}
        <div class="flex min-w-0 flex-1 flex-col justify-center gap-1 px-3 py-2.5">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <span class="font-semibold leading-tight text-ink">{{ $item->label }}</span>
                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $badgeClass }}">
                    {{ \App\Models\NavItem::TYPES[$item->type] ?? $item->type }}
                    @if ($item->is_button) &middot; CTA @endif
                </span>
                @unless ($item->is_active)
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700">ukryta</span>
                @endunless
            </div>

            @if (($item->url && ! $item->isDropdown()) || ($item->isDropdown() && $item->module))
                <span class="truncate font-mono text-xs text-muted">
                    @if ($item->isDropdown())
                        moduł: {{ \App\Models\SiteSetting::MODULES[$item->module] ?? $item->module }}
                    @else
                        {{ $item->url }}
                        @if ($item->module) &nbsp;&middot;&nbsp;{{ \App\Models\SiteSetting::MODULES[$item->module] ?? $item->module }} @endif
                    @endif
                </span>
            @endif
        </div>

        {{-- Przyciski akcji — widoczne przy hover / focus-within --}}
        <div class="flex flex-none items-center gap-px self-stretch border-l border-gray-100 px-1.5
                    opacity-0 transition-opacity focus-within:opacity-100 group-hover:opacity-100"
            role="group" aria-label="Akcje dla {{ $item->label }}">

            <form method="POST" action="{{ route('admin.pozycje-menu.przenies', $item) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="action" value="up">
                <button type="submit" class="{{ $btn }}" title="W górę" @disabled($isFirst)>
                    <i class="fa-solid fa-arrow-up text-xs" aria-hidden="true"></i>
                    <span class="sr-only">Przenieś „{{ $item->label }}" w górę</span>
                </button>
            </form>

            <form method="POST" action="{{ route('admin.pozycje-menu.przenies', $item) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="action" value="down">
                <button type="submit" class="{{ $btn }}" title="W dół" @disabled($isLast)>
                    <i class="fa-solid fa-arrow-down text-xs" aria-hidden="true"></i>
                    <span class="sr-only">Przenieś „{{ $item->label }}" w dół</span>
                </button>
            </form>

            @if ($canIndent)
                <form method="POST" action="{{ route('admin.pozycje-menu.przenies', $item) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="indent">
                    <button type="submit" class="{{ $btn }}" title="Zagnieźdź jako podpozycję">
                        <i class="fa-solid fa-arrow-right-long text-xs" aria-hidden="true"></i>
                        <span class="sr-only">Zagnieźdź „{{ $item->label }}"</span>
                    </button>
                </form>
            @endif

            @if ($canOutdent)
                <form method="POST" action="{{ route('admin.pozycje-menu.przenies', $item) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="outdent">
                    <button type="submit" class="{{ $btn }}" title="Wysuń na poziom główny">
                        <i class="fa-solid fa-arrow-left-long text-xs" aria-hidden="true"></i>
                        <span class="sr-only">Wysuń „{{ $item->label }}"</span>
                    </button>
                </form>
            @endif

            <span class="mx-0.5 h-4 w-px bg-gray-200" aria-hidden="true"></span>

            @if ($canHoldChildren)
                <button type="button" @click="openCreate($event)"
                    data-parent="{{ $item->id }}" data-location="{{ $item->location }}"
                    class="{{ $btn }}" title="Dodaj podpozycję">
                    <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i>
                    <span class="sr-only">Dodaj podpozycję do „{{ $item->label }}"</span>
                </button>
            @endif

            <button type="button" data-nav-first-action @click="openEdit($event)"
                data-id="{{ $item->id }}"
                data-action="{{ route('admin.pozycje-menu.update', $item) }}"
                data-label="{{ $item->label }}"
                data-icon="{{ $item->icon }}"
                data-url="{{ $item->url }}"
                data-type="{{ $item->type }}"
                data-location="{{ $item->location }}"
                data-parent="{{ $item->parent_id }}"
                data-module="{{ $item->module }}"
                data-button="{{ $item->is_button ? 1 : 0 }}"
                data-color="{{ $item->button_color }}"
                data-transparent="{{ $item->is_transparent_dropdown ? 1 : 0 }}"
                data-active="{{ $item->is_active ? 1 : 0 }}"
                class="{{ $btn }}" title="Edytuj">
                <i class="fa-solid fa-pen text-xs" aria-hidden="true"></i>
                <span class="sr-only">Edytuj „{{ $item->label }}"</span>
            </button>

            <form method="POST" action="{{ route('admin.pozycje-menu.destroy', $item) }}"
                onsubmit="return confirm('Usunąć „{{ addslashes($item->label) }}"?{{ $item->isDropdown() ? ' Usunięte zostaną też podpozycje.' : '' }}');">
                @csrf @method('DELETE')
                <button type="submit" class="{{ $btn }} hover:text-red-600 focus-visible:ring-red-500" title="Usuń">
                    <i class="fa-solid fa-trash text-xs" aria-hidden="true"></i>
                    <span class="sr-only">Usuń „{{ $item->label }}"</span>
                </button>
            </form>
        </div>
    </div>

    {{-- ══ PODPOZYCJE ══ --}}
    @if ($hasChildren)
        <ul id="{{ $groupId }}" role="group" x-show="expanded"
            class="relative mt-1.5 space-y-1.5 border-l-2 border-gray-200 pl-5 ml-3">
            @foreach ($children as $ci => $child)
                @include('admin.nav-items._row', [
                    'item'     => $child,
                    'level'    => 2,
                    'position' => $ci + 1,
                    'setsize'  => $children->count(),
                ])
            @endforeach
        </ul>
    @endif
</li>
