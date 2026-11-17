{{--
    Menu akcji kafla pliku (jak „…” przy pliku w menedżerze mediów Joomli): podgląd, szczegóły,
    edycja obrazu, kopiowanie adresu, archiwum. Przycisk widoczny zawsze (też na dotyku i z klawiatury),
    menu to zwykła lista przycisków (wzorzec disclosure), Esc zamyka i wraca do przycisku (onEscape w mediaLibrary).

    Expects: $item (wiersz z MediaLibraryController::index), $menuItem (klasy pozycji).
--}}
@php $id = $item['id']; @endphp
<div class="absolute right-3 top-3" :class="menuId === {{ $id }} ? 'z-30' : 'z-10'" @click.outside="if (menuId === {{ $id }}) menuId = null">
    <button type="button" id="menu-btn-{{ $id }}" @click="toggleMenu({{ $id }})"
        :aria-expanded="(menuId === {{ $id }}).toString()" aria-controls="menu-{{ $id }}"
        class="flex h-9 w-9 items-center justify-center rounded-md bg-white text-ink shadow hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
        :class="menuId === {{ $id }} ? 'bg-brand-light' : ''">
        <i class="fa-solid fa-ellipsis" aria-hidden="true"></i><span class="sr-only">Akcje: {{ $item['file_name'] }}</span>
    </button>
    <ul id="menu-{{ $id }}" x-show="menuId === {{ $id }}" x-cloak role="list"
        class="absolute right-0 top-10 w-52 overflow-hidden rounded-md bg-white py-1 shadow-lg">
        <li>
            <button type="button" @click="openPreview({{ $id }})" aria-haspopup="dialog" class="{{ $menuItem }}">
                <i class="fa-solid fa-eye w-4 text-brand-dark" aria-hidden="true"></i> Podgląd
            </button>
        </li>
        <li>
            <button type="button" @click="menuId = null; openPanel({{ $id }})" aria-haspopup="dialog" class="{{ $menuItem }}">
                <i class="fa-solid fa-circle-info w-4 text-brand-dark" aria-hidden="true"></i> Szczegóły i opis
            </button>
        </li>
        @if ($item['edit'])
            <li>
                <button type="button" @click="menuId = null; $dispatch('media-edit', @js($item['edit']))" aria-haspopup="dialog" class="{{ $menuItem }}">
                    <i class="fa-solid fa-crop-simple w-4 text-brand-dark" aria-hidden="true"></i> Edytuj obraz
                </button>
            </li>
        @endif
        <li>
            <button type="button" @click="copyUrl({{ $id }}, @js($item['url']))" class="{{ $menuItem }}">
                <i class="fa-solid w-4 text-brand-dark" :class="copiedId === {{ $id }} ? 'fa-check' : 'fa-link'" aria-hidden="true"></i>
                <span x-text="copiedId === {{ $id }} ? 'Skopiowano adres' : 'Kopiuj adres'">Kopiuj adres</span>
            </button>
        </li>
        <li>
            <form method="POST" action="{{ route($item['archived'] ? 'admin.multimedia.restore' : 'admin.multimedia.archive', $id) }}">
                @csrf
                @method('PUT')
                <button type="submit" class="{{ $menuItem }}">
                    <i class="fa-solid {{ $item['archived'] ? 'fa-rotate-left' : 'fa-box-archive' }} w-4 text-brand-dark" aria-hidden="true"></i>
                    {{ $item['archived'] ? 'Przywróć z archiwum' : 'Do archiwum' }}
                </button>
            </form>
        </li>
    </ul>
</div>
