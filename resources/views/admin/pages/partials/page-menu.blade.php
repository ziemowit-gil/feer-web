{{--
    „Strony i nawigacja”: karta „W menu nawigacji” w panelu wybranej strony (drzewo stron). Pokazuje, gdzie w menu
    jest strona (menu główne, stopka, BIP; także dawne „Pokaż w menu głównym”), a administrator może tu dodać stronę
    do menu, zmienić napis i widoczność pozycji albo usunąć ją z menu. Linki zewnętrzne, nagłówki, menu rozwijane
    i mega-menu zostają w Menu nawigacji. Zmienne: $page, $positions (PageMenuPositions), $mainMenuParents, $btn.
    Dostępność: każde pole ma etykietę, przyciski mówią, czego dotyczą (sr-only z nazwą pozycji), cele ≥ 36 px.
--}}
@php
    $canMenu = auth()->user()->isAdmin() && \Illuminate\Support\Facades\Route::has('admin.podstrony.menu.store');
    $pmInp = 'rounded border-gray-500 py-1.5 text-sm focus:border-brand-dark focus:ring-brand-dark';
@endphp
<details id="menu-nawigacji" class="scroll-mt-24 rounded-lg bg-white p-4" @if ($errors->has('parent_id') || $errors->has('label')) open @endif>
    <summary class="flex cursor-pointer flex-wrap items-center gap-x-2 gap-y-1 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
        <span class="font-bold text-ink"><i class="fa-solid fa-bars mr-1 text-brand-dark" aria-hidden="true"></i> W menu nawigacji</span>
        <span class="text-muted">— {{ $positions === [] ? 'nie ma jej w menu' : collect($positions)->map(fn ($p) => $p['location_label'].($p['parent'] ? ' › '.$p['parent'] : ''))->unique()->implode(', ') }}</span>
        <span class="ml-auto text-xs font-bold text-brand-dark underline">{{ $canMenu ? 'Zmień' : 'Pokaż' }}</span>
    </summary>
    @if ($canMenu)
        <p class="mt-3 text-xs"><a href="{{ route('admin.pozycje-menu.index') }}" class="font-bold text-brand-dark underline">Całe menu: kolejność, linki, nagłówki, mega-menu</a></p>
    @endif

    @if ($positions === [])
        <p class="mt-2 text-sm text-muted">Tej strony nie ma w menu. Odwiedzający trafią na nią z innych stron, wyszukiwarki albo menu bocznego działu.</p>
    @else
        <ul class="mt-3 space-y-2" role="list">
            @foreach ($positions as $pos)
                <li class="rounded-md bg-gray-50 p-3">
                    <p class="text-sm text-ink">
                        <span class="font-bold">{{ $pos['location_label'] }}</span>@if ($pos['parent']) › {{ $pos['parent'] }}@endif
                        › „{{ $pos['label'] }}”
                        @unless ($pos['active'])<span class="ml-1 text-xs font-bold text-muted">(ukryta)</span>@endunless
                        @if ($pos['auto'])<span class="block text-xs text-muted">Dopisywana automatycznie przez „Menu podstron” (ustawienie strony „Pokaż w menu”).</span>@endif
                    </p>
                    @if ($canMenu && $pos['item'])
                        <div class="mt-2 flex flex-wrap items-end gap-2">
                            <form method="POST" action="{{ route('admin.podstrony.menu.update', [$page, $pos['item']]) }}" class="flex flex-wrap items-end gap-2">
                                @csrf @method('PATCH')
                                <div>
                                    <label for="pm-label-{{ $pos['item']->id }}" class="mb-1 block text-xs font-bold text-ink">Napis w menu</label>
                                    <input id="pm-label-{{ $pos['item']->id }}" type="text" name="label" value="{{ $pos['label'] }}" maxlength="120" required class="{{ $pmInp }}">
                                </div>
                                <label class="flex min-h-9 items-center gap-2 text-sm">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" @checked($pos['active']) class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                                    Widoczna
                                </label>
                                <button type="submit" class="{{ $btn }}"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Zapisz<span class="sr-only"> pozycję „{{ $pos['label'] }}”</span></button>
                            </form>
                            <form method="POST" action="{{ route('admin.podstrony.menu.destroy', [$page, $pos['item']]) }}"
                                  onsubmit="return confirm(@js('Usunąć „'.$pos['label'].'” z menu? Strona zostanie, zniknie tylko pozycja w menu.'))">
                                @csrf @method('DELETE')
                                <button type="submit" class="{{ $btn }}"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Usuń z menu<span class="sr-only">: „{{ $pos['label'] }}”</span></button>
                            </form>
                            <a href="{{ route('admin.pozycje-menu.index', ['location' => $pos['location'], 'pozycja' => $pos['item']->id]) }}" class="{{ $btn }}"><i class="fa-solid fa-sliders" aria-hidden="true"></i> Więcej opcji<span class="sr-only"> pozycji „{{ $pos['label'] }}”</span></a>
                        </div>
                    @elseif ($canMenu && $pos['auto'])
                        <form method="POST" action="{{ route('admin.podstrony.menu.auto', $page) }}" class="mt-2">
                            @csrf @method('PATCH')
                            <button type="submit" class="{{ $btn }}"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Nie dopisuj automatycznie</button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if ($canMenu)
        <form method="POST" action="{{ route('admin.podstrony.menu.store', $page) }}" class="mt-4 rounded-md bg-gray-50 p-3" aria-labelledby="pm-add-heading"
              x-data="{ loc: 'main' }">
            @csrf
            <p id="pm-add-heading" class="text-xs font-bold uppercase tracking-wide text-muted">Dodaj tę stronę do menu</p>
            <div class="mt-2 flex flex-wrap items-end gap-2">
                <div>
                    <label for="pm-location" class="mb-1 block text-xs font-bold text-ink">Gdzie</label>
                    <select id="pm-location" name="location" x-model="loc" class="{{ $pmInp }}">
                        <option value="main">Menu główne</option>
                        <option value="footer">Stopka</option>
                    </select>
                </div>
                <div x-show="loc === 'main'">
                    <label for="pm-parent" class="mb-1 block text-xs font-bold text-ink">Pod pozycją</label>
                    <select id="pm-parent" name="parent_id" class="{{ $pmInp }}">
                        <option value="">— jako główna pozycja —</option>
                        @foreach ($mainMenuParents as $parentItem)
                            <option value="{{ $parentItem->id }}">{{ $parentItem->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="pm-new-label" class="mb-1 block text-xs font-bold text-ink">Napis (opcjonalnie)</label>
                    <input id="pm-new-label" type="text" name="label" maxlength="120" placeholder="{{ $page->title }}" class="{{ $pmInp }}">
                </div>
                <button type="submit" class="{{ $btn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj do menu</button>
            </div>
            @error('parent_id')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
        </form>
    @endif
</details>
<script>if (location.hash === '#menu-nawigacji') { document.getElementById('menu-nawigacji').open = true; }</script>
