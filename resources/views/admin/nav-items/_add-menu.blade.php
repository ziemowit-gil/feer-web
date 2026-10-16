{{--
    Przycisk „Dodaj” jako menu (WAI-ARIA menu button): Nowa pozycja · Ze stron · Z pliku XML.
    Zmienne: $menuId (id listy), $location, $parentId (null = pozycje główne; id = podpozycje tej pozycji),
    $primary (true = główny przycisk marki, false = przycisk drugorzędny z panelu szczegółów),
    $btnClass / $btnStyle (wygląd przycisku drugorzędnego).
    Okna „Ze stron” i „Z pliku XML” są w index.blade.php; przy podpozycji ustawiamy w nich od razu „Gdzie dodać”.
    Wymaga zakresu Alpine strony menu (openCreate, trigger).
--}}
@php
    $parentId ??= null;
    $primary ??= true;
    $label = $parentId ? 'Dodaj podpozycję' : 'Dodaj';
    $srLabel = $parentId ? 'Dodaj podpozycję' : 'Dodaj do menu';
    $itemCls = 'flex w-full items-center gap-2.5 rounded px-3 py-2 text-left text-sm font-bold text-ink hover:bg-gray-100 focus-visible:bg-gray-100 focus-visible:outline-none';
    // Wybór „Gdzie dodać” w oknie przed jego otwarciem (pusta wartość = pozycje główne).
    $openDialog = fn (string $dialog, string $select) => "closeAdd(false); (() => { const s = document.getElementById('{$select}'); if (s) s.value = '".($parentId ?? '')."'; document.getElementById('{$dialog}').showModal() })()";
@endphp
<div class="relative" x-data="{ addOpen: false, items() { return [...this.$refs.addMenu.querySelectorAll('[role=menuitem]')] }, focusAt(i) { const l = this.items(); l[(i + l.length) % l.length]?.focus() }, openAdd(i) { this.addOpen = true; setTimeout(() => this.focusAt(i), 30) }, closeAdd(back) { this.addOpen = false; if (back) this.$refs.addBtn.focus() } }"
    @keydown.escape.stop="if (addOpen) closeAdd(true)" @click.outside="addOpen = false">
    <button type="button" x-ref="addBtn" @click="addOpen ? closeAdd(false) : openAdd(0)" @keydown.arrow-down.prevent="openAdd(0)" @keydown.arrow-up.prevent="openAdd(-1)"
        aria-haspopup="menu" :aria-expanded="addOpen.toString()" aria-expanded="false" aria-controls="{{ $menuId }}"
        @if ($primary)
            class="inline-flex shrink-0 items-center rounded-md bg-brand-dark text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2"
            style="min-height:2.25rem;padding:.375rem .875rem;gap:.375rem"
        @else
            class="{{ $btnClass }}" style="{{ $btnStyle }}"
        @endif
        >
        <i class="fa-solid fa-plus" aria-hidden="true"></i> {{ $label }}@unless ($parentId)<span class="sr-only"> do menu</span>@endunless
        <i class="fa-solid fa-chevron-down text-[10px] transition-transform" :class="addOpen && 'rotate-180'" aria-hidden="true"></i>
    </button>
    <div id="{{ $menuId }}" x-ref="addMenu" role="menu" aria-label="{{ $srLabel }}" x-show="addOpen" x-cloak
        @keydown.arrow-down.prevent="focusAt(items().indexOf(document.activeElement) + 1)"
        @keydown.arrow-up.prevent="focusAt(items().indexOf(document.activeElement) - 1)"
        @keydown.home.prevent="focusAt(0)" @keydown.end.prevent="focusAt(-1)" @keydown.tab="addOpen = false"
        class="absolute right-0 top-full z-30 mt-1 rounded-lg border border-gray-200 bg-white py-1.5 shadow-xl" style="min-width:15rem">
        <button type="button" role="menuitem" tabindex="-1" @click="closeAdd(false); openCreate($event); trigger = $refs.addBtn"
            data-location="{{ $location }}" @if ($parentId) data-parent="{{ $parentId }}" @endif class="{{ $itemCls }}">
            <i class="fa-solid fa-pen w-4 text-center text-gray-500" aria-hidden="true"></i> {{ $parentId ? 'Nowa podpozycja' : 'Nowa pozycja' }}
        </button>
        @if (Route::has('admin.pozycje-menu.ze-stron'))
            <button type="button" role="menuitem" tabindex="-1" @click="{{ $openDialog('nav-from-pages', 'nav-pages-parent') }}" class="{{ $itemCls }}">
                <i class="fa-solid fa-sitemap w-4 text-center text-gray-500" aria-hidden="true"></i> Ze stron<span class="sr-only"> — wybierz strony z drzewa stron</span>
            </button>
        @endif
        @if (Route::has('admin.pozycje-menu.import-xml'))
            <button type="button" role="menuitem" tabindex="-1" @click="{{ $openDialog('nav-import-xml', 'nav-import-parent') }}" class="{{ $itemCls }}">
                <i class="fa-solid fa-file-import w-4 text-center text-gray-500" aria-hidden="true"></i> Z pliku XML
            </button>
        @endif
    </div>
</div>
