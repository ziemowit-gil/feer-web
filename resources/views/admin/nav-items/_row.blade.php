@php
    /**
     * Pojedynczy węzeł drzewa menu (role="treeitem").
     * Parametry: $item, $level (1 lub 2), $position (1-indeks), $setsize.
     */
    $children = $level === 1 ? $item->allChildren : collect();
    $hasChildren = $children->isNotEmpty();
    $isFirst = $position === 1;
    $isLast = $position === $setsize;
    $groupId = 'nav-group-'.$item->id;

    // Ta sama logika, co po stronie kontrolera — decyduje, które przyciski
    // przenoszenia pokazać, żeby nie oferować niedozwolonych operacji.
    $canHoldChildren = $level === 1 && $item->location === 'main' && ! $item->is_button && in_array($item->type, ['dropdown', 'link'], true);
    $canIndent = $level === 1 && ! $isFirst && $item->location === 'main' && $item->type === 'link' && ! $item->is_button && ! $hasChildren;
    $canOutdent = $level === 2;

    $btn = 'inline-flex h-8 w-8 items-center justify-center rounded text-muted transition-colors hover:bg-gray-100 hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:text-muted';
@endphp

<li id="nav-item-{{ $item->id }}" role="treeitem" aria-level="{{ $level }}" aria-setsize="{{ $setsize }}" aria-posinset="{{ $position }}"
    @if ($hasChildren) x-data="{ expanded: true }" :aria-expanded="expanded" @endif
    class="list-none py-1">

    <div class="flex flex-wrap items-center gap-x-2 gap-y-2 rounded px-2 py-1.5 hover:bg-gray-50 @if ($level === 2) ml-6 border-l-2 border-gray-100 pl-3 @endif">

        {{-- Rozwijanie / zwijanie gałęzi (odzwierciedla aria-expanded) --}}
        @if ($hasChildren)
            <button type="button" @click="expanded = !expanded" :aria-expanded="expanded" aria-controls="{{ $groupId }}"
                class="inline-flex h-6 w-6 flex-none items-center justify-center rounded text-gray-500 hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="{ '-rotate-90': !expanded }" aria-hidden="true"></i>
                <span class="sr-only" x-text="expanded ? 'Zwiń podpozycje pozycji {{ $item->label }}' : 'Rozwiń podpozycje pozycji {{ $item->label }}'"></span>
            </button>
        @elseif ($level === 1)
            <span class="inline-block h-6 w-6 flex-none" aria-hidden="true"></span>
        @endif

        {{-- Etykieta + metadane --}}
        <span class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-1">
            <span class="font-medium text-ink">{{ $item->label }}</span>

            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">{{ \App\Models\NavItem::TYPES[$item->type] ?? $item->type }}</span>

            @if ($item->isDropdown())
                @if ($item->module)
                    <span class="text-xs text-muted">moduł: {{ \App\Models\SiteSetting::MODULES[$item->module] ?? $item->module }}</span>
                @endif
            @else
                <span class="truncate font-mono text-xs text-muted" title="{{ $item->url }}">{{ $item->url }}</span>
                @if ($item->module)
                    <span class="text-xs text-muted">({{ \App\Models\SiteSetting::MODULES[$item->module] ?? $item->module }})</span>
                @endif
            @endif

            @if ($item->is_button)
                <span class="rounded-full bg-brand-light px-2 py-0.5 text-xs font-bold text-brand">Przycisk (CTA)</span>
            @endif

            @unless ($item->is_active)
                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800">Ukryta</span>
            @endunless
        </span>

        {{-- Przyciski akcji — dostępna alternatywa dla przeciągania myszą --}}
        <div class="flex flex-none items-center gap-0.5">
            {{-- W górę --}}
            <form method="POST" action="{{ route('admin.pozycje-menu.przenies', $item) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="action" value="up">
                <button type="submit" class="{{ $btn }}" @disabled($isFirst)>
                    <i class="fa-solid fa-arrow-up text-sm" aria-hidden="true"></i>
                    <span class="sr-only">Przenieś „{{ $item->label }}” w górę</span>
                </button>
            </form>

            {{-- W dół --}}
            <form method="POST" action="{{ route('admin.pozycje-menu.przenies', $item) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="action" value="down">
                <button type="submit" class="{{ $btn }}" @disabled($isLast)>
                    <i class="fa-solid fa-arrow-down text-sm" aria-hidden="true"></i>
                    <span class="sr-only">Przenieś „{{ $item->label }}” w dół</span>
                </button>
            </form>

            {{-- Zagnieźdź (zwiększ poziom) --}}
            @if ($canIndent)
                <form method="POST" action="{{ route('admin.pozycje-menu.przenies', $item) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="action" value="indent">
                    <button type="submit" class="{{ $btn }}">
                        <i class="fa-solid fa-arrow-right-long text-sm" aria-hidden="true"></i>
                        <span class="sr-only">Zagnieźdź „{{ $item->label }}” jako podpozycję powyższej pozycji</span>
                    </button>
                </form>
            @endif

            {{-- Wysuń (zmniejsz poziom) --}}
            @if ($canOutdent)
                <form method="POST" action="{{ route('admin.pozycje-menu.przenies', $item) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="action" value="outdent">
                    <button type="submit" class="{{ $btn }}">
                        <i class="fa-solid fa-arrow-left-long text-sm" aria-hidden="true"></i>
                        <span class="sr-only">Wysuń „{{ $item->label }}” na najwyższy poziom</span>
                    </button>
                </form>
            @endif

            <span class="mx-1 h-5 w-px bg-gray-200" aria-hidden="true"></span>

            {{-- Dodaj podpozycję --}}
            @if ($canHoldChildren)
                <button type="button" @click="openCreate($event)" data-parent="{{ $item->id }}" data-location="{{ $item->location }}" class="{{ $btn }}">
                    <i class="fa-solid fa-plus text-sm" aria-hidden="true"></i>
                    <span class="sr-only">Dodaj podpozycję w „{{ $item->label }}”</span>
                </button>
            @endif

            {{-- Edytuj (otwiera modal) --}}
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
                class="{{ $btn }}">
                <i class="fa-solid fa-pen text-sm" aria-hidden="true"></i>
                <span class="sr-only">Edytuj „{{ $item->label }}”</span>
            </button>

            {{-- Usuń --}}
            <form method="POST" action="{{ route('admin.pozycje-menu.destroy', $item) }}"
                onsubmit="return confirm('Usunąć pozycję menu &quot;{{ $item->label }}&quot;?{{ $item->isDropdown() ? ' Usunięte zostaną też jej podpozycje.' : '' }}');">
                @csrf
                @method('DELETE')
                <button type="submit" class="{{ $btn }} hover:text-red-700 focus-visible:ring-red-600">
                    <i class="fa-solid fa-trash text-sm" aria-hidden="true"></i>
                    <span class="sr-only">Usuń „{{ $item->label }}”</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Podpozycje (poziom 2) --}}
    @if ($hasChildren)
        <ul id="{{ $groupId }}" role="group" x-show="expanded" class="mt-1">
            @foreach ($children as $ci => $child)
                @include('admin.nav-items._row', ['item' => $child, 'level' => 2, 'position' => $ci + 1, 'setsize' => $children->count()])
            @endforeach
        </ul>
    @endif
</li>
