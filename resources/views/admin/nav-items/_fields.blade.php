{{--
    Współdzielone pola formularza pozycji menu.
    Wymaga otaczającego zakresu Alpine z obiektem `form` (label, url, type,
    location, parentId, module, isButton, buttonColor, buttonColorEnabled, accentColor, accentEnabled, highlight,
    isTransparent, isActive, editingId) oraz zmiennych PHP $parentOptions, $pages.
    Wszystkie pola sterowane są przez Alpine (x-model), dzięki czemu ten sam
    partial obsługuje modal (dynamiczne dane) i zapasową stronę formularza.
--}}

{{-- Dwie kolumny od 768 px (pola parami), na telefonie jedna. Styl wbudowany — działa bez przebudowy CSS.
     .nav-wide zajmuje cały rząd (bloki z wieloma kontrolkami). --}}
<style>
    .nav-form-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.25rem 1.5rem; align-items: start; }
    @media (min-width: 768px) {
        .nav-form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .nav-form-grid > .nav-wide { grid-column: 1 / -1; }
    }
</style>

{{-- Formularz podzielony na zakładki: Podstawowe · Wygląd · Menu rozwijane i przyciski. Pola ukrytych zakładek nadal są w formularzu (x-show), więc zapis obejmuje wszystko. --}}
<div x-data="{ tab: 'basic' }"
     x-effect="if ((tab === 'look' && ! (form.location === 'main')) || (tab === 'media' && ! (form.location === 'main' && form.parentId === '' && form.isMega)) || (tab === 'drop' && ! (form.location === 'main' && form.parentId === '' && (form.isMega || ['dropdown', 'projects', 'pages', 'link'].includes(form.type))))) tab = 'basic'"
     class="space-y-5">
    <div role="tablist" aria-label="Sekcje formularza pozycji menu" class="flex flex-wrap gap-1 border-b border-gray-200"
         @keydown.arrow-right.prevent="$event.target.nextElementSibling?.focus()" @keydown.arrow-left.prevent="$event.target.previousElementSibling?.focus()">
        <button type="button" role="tab" id="nav-tab-basic" aria-controls="nav-panel-basic" x-show="true" x-cloak
            @click="tab = 'basic'" :aria-selected="(tab === 'basic').toString()" :tabindex="tab === 'basic' ? 0 : -1"
            class="-mb-px inline-flex items-center gap-2 border-b-2 px-3 py-2 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
            :class="tab === 'basic' ? 'border-brand-dark text-brand-dark' : 'border-transparent text-muted hover:text-ink'">
            <i class="fa-solid fa-pen" aria-hidden="true"></i> Podstawowe
        </button>
        <button type="button" role="tab" id="nav-tab-look" aria-controls="nav-panel-look" x-show="form.location === 'main'" x-cloak
            @click="tab = 'look'" :aria-selected="(tab === 'look').toString()" :tabindex="tab === 'look' ? 0 : -1"
            class="-mb-px inline-flex items-center gap-2 border-b-2 px-3 py-2 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
            :class="tab === 'look' ? 'border-brand-dark text-brand-dark' : 'border-transparent text-muted hover:text-ink'">
            <i class="fa-solid fa-palette" aria-hidden="true"></i> Wygląd
        </button>
        <button type="button" role="tab" id="nav-tab-drop" aria-controls="nav-panel-drop" x-show="form.location === 'main' && form.parentId === '' && (form.isMega || ['dropdown', 'projects', 'pages', 'link'].includes(form.type))" x-cloak
            @click="tab = 'drop'" :aria-selected="(tab === 'drop').toString()" :tabindex="tab === 'drop' ? 0 : -1"
            class="-mb-px inline-flex items-center gap-2 border-b-2 px-3 py-2 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
            :class="tab === 'drop' ? 'border-brand-dark text-brand-dark' : 'border-transparent text-muted hover:text-ink'">
            <i class="fa-solid fa-square-caret-down" aria-hidden="true"></i> Menu rozwijane i przyciski
        </button>
        <button type="button" role="tab" id="nav-tab-media" aria-controls="nav-panel-media" x-show="form.location === 'main' && form.parentId === '' && form.isMega" x-cloak
            @click="tab = 'media'" :aria-selected="(tab === 'media').toString()" :tabindex="tab === 'media' ? 0 : -1"
            class="-mb-px inline-flex items-center gap-2 border-b-2 px-3 py-2 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
            :class="tab === 'media' ? 'border-brand-dark text-brand-dark' : 'border-transparent text-muted hover:text-ink'">
            <i class="fa-solid fa-image" aria-hidden="true"></i> Grafika
        </button>
    </div>

    <div id="nav-panel-basic" role="tabpanel" aria-labelledby="nav-tab-basic" x-show="tab === 'basic'" class="nav-form-grid">
<div>
    <label for="nav-location" class="mb-1 block text-sm font-bold">Lokalizacja</label>
    <select id="nav-location" name="location" x-model="form.location"
        class="w-full rounded border-gray-500 focus-visible:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
        @foreach (\App\Models\NavItem::LOCATIONS as $value => $option)
            <option value="{{ $value }}">{{ $option }}</option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-muted">Stopka wyświetla pozycje zawsze jako zwykłe linki, bez rozwijanych podmenu.</p>
    @error('location') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>

<div>
    <label for="nav-label" class="mb-1 block text-sm font-bold">Etykieta</label>
    <input type="text" id="nav-label" name="label" x-model="form.label" required
        class="w-full rounded border-gray-500 focus-visible:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
    @error('label') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>

<div x-show="form.location === 'main'" x-cloak>
    <label for="nav-icon" class="mb-1 block text-sm font-bold">Ikona <span class="font-normal text-muted">(opcjonalnie — używana w stylu nav „Ikony + etykiety")</span></label>
    <input type="text" id="nav-icon" name="icon" x-model="form.icon" placeholder="np. bi-house-door" data-icon-picker data-icon-format="name"
        class="w-full rounded border-gray-500 font-mono text-sm focus-visible:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
    <p class="mt-1 text-xs text-muted">Kliknij „Wybierz” i wybierz ikonę z listy — możesz szukać po polsku (dom, koperta, telefon). Możesz też wpisać nazwę, np. <code>bi-people</code>.</p>
    @error('icon') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>

<div x-show="form.location === 'main'" x-cloak>
    <label for="nav-type" class="mb-1 block text-sm font-bold">Typ pozycji</label>
    <select id="nav-type" name="type" x-model="form.type"
        class="w-full rounded border-gray-500 focus-visible:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
        @foreach (\App\Models\NavItem::TYPES as $value => $option)
            @if ($siteSettings->isOptionBlocked('nav_item_types', $value))
                {{-- Typ ukryty w Ustawienia → Moduły: widoczny tylko w pozycji, która już go ma. --}}
                {{-- Zawsze w DOM (x-model musi znaleźć wartość), ukryty i wyłączony w pozostałych. --}}
                <option value="{{ $value }}" :hidden="form.type !== @js($value)" :disabled="form.type !== @js($value)">{{ $option }}</option>
            @else
                <option value="{{ $value }}">{{ $option }}</option>
            @endif
        @endforeach
    </select>
    @error('type') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    <p class="mt-1 text-xs text-muted" x-show="form.type === 'volunteering'" x-cloak>
        Pozycja prowadzi automatycznie do listy ofert wolontariatu (/wolontariat) i ukrywa się, gdy moduł jest wyłączony. Możesz wyróżnić ją jako przycisk (CTA) poniżej.
    </p>
</div>

<div x-show="form.type === 'link' || form.location === 'footer' || form.location === 'bip'" x-cloak>
    {{-- Wybór dokumentu BIP — tylko gdy lokalizacja to "Menu BIP" --}}
    @if (!empty($bipDocuments) && $bipDocuments->isNotEmpty())
        <div x-show="form.location === 'bip'" x-cloak>
            <label for="nav-bip-picker" class="mb-1 block text-sm font-bold">Wybierz dokument BIP <span class="font-normal text-muted">(opcjonalnie)</span></label>
            <select id="nav-bip-picker" class="mb-2 w-full rounded border-gray-500 focus-visible:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark"
                @change="
                    let opt = $event.target.options[$event.target.selectedIndex];
                    if (opt.value) {
                        form.url = opt.value;
                        if (!form.label) form.label = opt.dataset.label || '';
                    }
                    $event.target.selectedIndex = 0;">
                <option value="">— wybierz z listy dokumentów BIP —</option>
                @foreach ($bipDocuments as $doc)
                    <option value="/bip/{{ $doc->slug }}" data-label="{{ $doc->title }}">{{ $doc->title }}</option>
                @endforeach
            </select>
        </div>
    @endif

    {{--
        Wybór strony — tylko gdy lokalizacja to nie „Menu BIP”. Przycisk otwiera okno (natywny <dialog>: fokus w oknie,
        Esc zamyka) z drzewem stron i podstron: zwijanie gałęzi, wyszukiwanie po tytule i adresie, szkice oznaczone.
        Wybór wpisuje adres do pola „Link” i — gdy etykieta jest pusta — tytuł strony do etykiety.
    --}}
    @if (! empty($pagePicker))
        <div x-show="form.location !== 'bip'" x-cloak
            x-data="{
                nodes: @js($pagePicker),
                q: '', expanded: {},
                norm(s) { return (s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/ł/g, 'l'); },
                visible(n) { return this.q.trim() !== '' ? this.norm(n.title + ' ' + n.url).includes(this.norm(this.q)) : n.parents.every(id => this.expanded[id]); },
                get shown() { return this.nodes.filter(n => this.visible(n)); },
                get selected() { return this.nodes.find(n => n.url === (form.url || '').replace(/\/$/, '')) || null; },
                openPicker() {
                    // Rozwiń gałąź z aktualnie wybraną stroną, żeby była widoczna od razu.
                    if (this.selected) this.selected.parents.forEach(id => this.expanded[id] = true);
                    this.$refs.dlg.showModal();
                    this.$nextTick(() => (this.$refs.dlg.querySelector('[data-current]') || this.$refs.q).focus());
                },
                pick(n) {
                    form.url = n.url;
                    if (! form.label) form.label = n.title;
                    this.$refs.dlg.close();
                    this.$refs.opener.focus();
                },
                toggleAll(on) { this.nodes.filter(n => n.kids).forEach(n => this.expanded[n.id] = on); },
            }">
            <p class="mb-1 block text-sm font-bold" id="nav-page-picker-label">Wybierz stronę <span class="font-normal text-muted">(opcjonalnie)</span></p>
            <div class="mb-2 flex flex-wrap items-center gap-3">
                <button type="button" x-ref="opener" @click="openPicker()" aria-haspopup="dialog" aria-describedby="nav-page-picker-current"
                    class="inline-flex min-h-10 items-center gap-2 rounded bg-gray-100 px-4 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                    <i class="fa-solid fa-sitemap" aria-hidden="true"></i> Wybierz z drzewa stron…
                </button>
                <span id="nav-page-picker-current" class="min-w-0 text-sm text-ink">
                    <template x-if="selected"><span>Wybrana: <strong x-text="selected.title"></strong></span></template>
                    <template x-if="! selected"><span class="text-muted">Strona nie jest wybrana</span></template>
                </span>
            </div>

            <dialog x-ref="dlg" aria-labelledby="nav-page-picker-title" @keydown.tab.stop @keydown.escape.stop @close="q = ''"
                class="m-auto w-[min(40rem,calc(100vw-2rem))] overflow-hidden rounded-md bg-white p-0 text-ink backdrop:bg-black/50">
                <div class="flex max-h-[85vh] flex-col">
                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                        <h2 id="nav-page-picker-title" class="text-lg font-bold">Wybierz stronę</h2>
                        <button type="button" @click="$refs.dlg.close(); $refs.opener.focus()" class="rounded p-2 text-muted hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"><i class="fa-solid fa-xmark" aria-hidden="true"></i><span class="sr-only">Zamknij bez wyboru</span></button>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 px-5 py-3">
                        <label for="nav-page-picker-q" class="sr-only">Szukaj strony po tytule lub adresie</label>
                        <input id="nav-page-picker-q" x-ref="q" type="search" x-model="q" placeholder="Szukaj strony…" autocomplete="off" class="min-w-0 flex-1 rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                        <button type="button" @click="toggleAll(true)" x-show="q.trim() === ''" class="rounded px-2 py-1.5 text-xs font-bold text-brand-dark hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Rozwiń wszystko</button>
                        <button type="button" @click="toggleAll(false)" x-show="q.trim() === ''" class="rounded px-2 py-1.5 text-xs font-bold text-brand-dark hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Zwiń</button>
                    </div>
                    <p class="sr-only" aria-live="polite" x-text="q.trim() !== '' ? 'Znaleziono: ' + shown.length : ''"></p>
                    <ul role="list" class="min-h-0 flex-1 overflow-y-auto px-3 py-2" aria-label="Strony">
                        <template x-for="n in shown" :key="n.id">
                            <li class="flex items-center gap-1 rounded hover:bg-gray-50" :style="'padding-left:' + (q.trim() === '' ? n.depth * 1.25 : 0) + 'rem'">
                                <template x-if="n.kids && q.trim() === ''">
                                    <button type="button" @click="expanded[n.id] = ! expanded[n.id]" :aria-expanded="(!! expanded[n.id]).toString()" :aria-label="(expanded[n.id] ? 'Zwiń podstrony: ' : 'Rozwiń podstrony: ') + n.title"
                                        class="flex h-9 w-9 flex-none items-center justify-center rounded text-ink hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                        <i class="fa-solid fa-chevron-right text-xs transition-transform motion-reduce:transition-none" :class="expanded[n.id] && 'rotate-90'" aria-hidden="true"></i>
                                    </button>
                                </template>
                                <span x-show="! n.kids || q.trim() !== ''" class="h-9 w-9 flex-none" aria-hidden="true"></span>
                                <button type="button" @click="pick(n)" :data-current="selected && selected.id === n.id ? '' : null" :aria-current="selected && selected.id === n.id ? 'true' : null"
                                    class="flex min-h-10 min-w-0 flex-1 items-center gap-2 rounded px-2 py-1 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                                    :class="selected && selected.id === n.id ? 'bg-brand-light' : ''">
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-bold text-ink" x-text="n.title"></span>
                                        <span class="block truncate font-mono text-xs text-muted" x-text="n.url"></span>
                                    </span>
                                    <span x-show="n.draft" class="flex-none text-xs text-ink">szkic</span>
                                    <span x-show="selected && selected.id === n.id" class="flex-none text-xs font-bold text-ink"><i class="fa-solid fa-check mr-1 text-brand-dark" aria-hidden="true"></i>wybrana</span>
                                </button>
                            </li>
                        </template>
                        <li x-show="shown.length === 0" class="py-6 text-center text-sm text-ink">Nie znaleziono strony.</li>
                    </ul>
                </div>
            </dialog>
        </div>
    @elseif ($pages->isNotEmpty())
        <div x-show="form.location !== 'bip'" x-cloak>
            <label for="nav-page-picker" class="mb-1 block text-sm font-bold">Wybierz stronę <span class="font-normal text-muted">(opcjonalnie)</span></label>
            <select id="nav-page-picker" class="mb-2 w-full rounded border-gray-500 focus-visible:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark"
                @change="if ($event.target.value) { form.url = $event.target.value; } $event.target.selectedIndex = 0;">
                <option value="">— wybierz z listy stron —</option>
                @foreach ($pages as $page)
                    <option value="/{{ $page->slug }}">{{ $page->title }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <label for="nav-url" class="mb-1 block text-sm font-bold">Link</label>
    <input type="text" id="nav-url" name="url" x-model="form.url" placeholder="np. /polityka-prywatnosci, #kontakt lub https://..."
        class="w-full rounded border-gray-500 focus-visible:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
    <p class="mt-1 text-xs text-muted">Wybór dokumentu lub strony powyżej uzupełni to pole — możesz też wpisać dowolny adres ręcznie.</p>
    @error('url') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>

<div x-show="form.type === 'link' && form.location === 'main'" x-cloak>
    <label for="nav-parent" class="mb-1 block text-sm font-bold">Podpozycja w menu (pod „Rozwijanym menu", linkiem albo „Menu działań")</label>
    <select id="nav-parent" name="parent_id" x-model="form.parentId"
        class="w-full rounded border-gray-500 focus-visible:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
        <option value="">— pozycja główna (na pasku menu) —</option>
        @foreach ($parentOptions as $option)
            {{-- Pozycja nie może być własnym rodzicem — chowamy ją z listy podczas edycji. --}}
            <option value="{{ $option->id }}" x-show="String(form.editingId) !== '{{ $option->id }}'">{{ $option->label }}</option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-muted">Podpozycje pod „Menu działań" tworzą dodatkową kolumnę (nagłówek ustawisz w polu „Nagłówek dodatkowej kolumny"). Adres wpisujesz sam, np. archiwum z filtrem: <code>/projekty/archiwum?po=2026-03-01</code>.</p>
    @error('parent_id') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>


<div>
    <label for="nav-module" class="mb-1 block text-sm font-bold">Widoczna tylko gdy moduł włączony</label>
    <select id="nav-module" name="module" x-model="form.module"
        class="w-full rounded border-gray-500 focus-visible:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
        <option value="">— zawsze widoczna —</option>
        @foreach (\App\Models\SiteSetting::MODULES as $value => $option)
            <option value="{{ $value }}">{{ $option }}</option>
        @endforeach
    </select>
    @error('module') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>


<label class="nav-wide flex items-center gap-2">
    <input type="checkbox" name="is_active" value="1" x-model="form.isActive"
        class="rounded border-gray-500 text-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
    <span class="text-sm font-bold">Widoczna w menu</span>
</label>

    </div>

    <div id="nav-panel-look" role="tabpanel" aria-labelledby="nav-tab-look" x-show="tab === 'look'" x-cloak class="nav-form-grid">
<label class="flex items-center gap-2" x-show="(form.type === 'link' || form.type === 'volunteering') && form.parentId === ''" x-cloak>
    <input type="checkbox" name="is_button" value="1" x-model="form.isButton"
        class="rounded border-gray-500 text-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
    <span class="text-sm font-bold">Wyróżnij jako przycisk (CTA)</span>
</label>

<div x-show="(form.type === 'link' || form.type === 'volunteering') && form.parentId === '' && form.isButton" x-cloak>
    <span class="mb-1 block text-sm font-bold">Kolor przycisku <span class="font-normal text-muted">(opcjonalnie)</span></span>
    <div class="flex items-center gap-3">
        <input type="hidden" name="button_color" :value="form.buttonColorEnabled ? form.buttonColor : ''">
        <input type="text" x-model="form.buttonColor" @input="form.buttonColorEnabled = true" :disabled="!form.buttonColorEnabled" aria-label="Kod koloru (hex)" data-color-picker
            placeholder="#2563eb" pattern="#[0-9a-fA-F]{6}"
            class="w-40 rounded border-gray-500 font-mono text-sm focus-visible:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark disabled:bg-gray-100 disabled:text-muted">
        <label class="flex items-center gap-2 text-sm text-muted">
            <input type="checkbox" x-model="form.buttonColorEnabled" class="rounded border-gray-500 text-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
            Własny kolor
        </label>
    </div>
    <p class="mt-1 text-xs text-muted">Kolor tła przycisku. Tekst automatycznie dobiera czerń lub biel dla kontrastu (WCAG). Wyłącz „Własny kolor", aby użyć koloru marki.</p>
    @error('button_color') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>

<div class="nav-wide" x-show="form.location === 'main' && form.parentId === '' && !form.isButton" x-cloak>
    @if (\Illuminate\Support\Facades\Schema::hasColumn('nav_items', 'highlight'))
        <input type="hidden" name="highlight" value="0">
        <label class="mb-4 flex cursor-pointer items-start gap-2 text-sm">
            <input type="checkbox" name="highlight" value="1" x-model="form.highlight" class="mt-0.5 rounded border-gray-500 text-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
            <span><span class="font-bold">Wyróżnij tę pozycję</span> <span class="block text-xs text-muted">Pozycja w górnym menu pogrubiona i w kolorze wyróżnienia (Ustawienia → Kolory). Wyróżnić można np. ważną sekcję albo „Wesprzyj nas”.</span></span>
        </label>
    @endif
    <span class="mb-1 block text-sm font-bold">Kolor pozycji <span class="font-normal text-muted">(opcjonalnie)</span></span>
    <div class="flex items-center gap-3">
        <input type="hidden" name="accent_color" :value="form.accentEnabled ? form.accentColor : ''">
        <input type="text" x-model="form.accentColor" @input="form.accentEnabled = true" :disabled="!form.accentEnabled" aria-label="Kolor pozycji menu (hex)" data-color-picker
            placeholder="#1e6dff" pattern="#[0-9a-fA-F]{6}"
            class="w-40 rounded border-gray-500 font-mono text-sm focus-visible:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark disabled:bg-gray-100 disabled:text-muted">
        <label class="flex items-center gap-2 text-sm text-muted">
            <input type="checkbox" x-model="form.accentEnabled" class="rounded border-gray-500 text-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
            Własny kolor
        </label>
    </div>
    <div class="mt-2 flex flex-wrap items-center gap-2" role="group" aria-label="Kolory z brandbooka FEER">
        <span class="text-xs font-bold text-muted">Kolory motywu:</span>
        @foreach (\App\Support\ThemePalette::swatches() as $bbHex => $bbName)
            <button type="button" @click="form.accentColor = '{{ $bbHex }}'; form.accentEnabled = true"
                class="inline-flex min-h-9 items-center gap-2 rounded-md px-2.5 text-xs font-bold text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark bg-white hover:bg-brand-light">
                <span class="h-4 w-4 rounded-sm border border-gray-300" style="background: {{ $bbHex }}" aria-hidden="true"></span>{{ $bbName }}<span class="sr-only"> ({{ $bbHex }})</span>
            </button>
        @endforeach
    </div>
    <p class="mt-1 text-xs text-muted">Kolorowy wskaźnik pod pozycją w menu głównym; po najechaniu i na aktywnej pozycji pozycja wypełnia się tym kolorem (tekst dobiera czerń lub biel dla kontrastu WCAG).</p>
    @error('accent_color') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>

<div x-show="form.location === 'main' && form.parentId !== ''" x-cloak>
    <label for="nav-description" class="mb-1 block text-sm font-bold">Krótki opis <span class="font-normal text-muted">(opcjonalnie — widoczny w mega menu)</span></label>
    <input type="text" id="nav-description" name="description" x-model="form.description" maxlength="255" placeholder="np. Szkolenia i audyty dostępności cyfrowej"
        class="w-full rounded border-gray-500 focus-visible:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
    @error('description') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>

<label class="nav-wide flex items-start gap-2" x-show="form.location === 'main' && form.parentId !== ''" x-cloak>
    <input type="checkbox" name="is_column_heading" value="1" x-model="form.isHeading"
        aria-describedby="nav-heading-help"
        class="mt-0.5 rounded border-gray-500 text-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
    <span>
        <span class="block text-sm font-bold">Nagłówek kolumny w mega menu</span>
        <span id="nav-heading-help" class="block text-xs text-muted">Ta pozycja otwiera nową kolumnę, a kolejne podpozycje (do następnego nagłówka) trafiają pod nią. Adres jest opcjonalny — bez niego nagłówek jest samym tekstem. W zwykłym i mobilnym menu wygląda jak etykieta grupy.</span>
    </span>
</label>

    </div>

    <div id="nav-panel-drop" role="tabpanel" aria-labelledby="nav-tab-drop" x-show="tab === 'drop'" x-cloak class="nav-form-grid">
<label class="nav-wide flex items-start gap-2" x-show="form.location === 'main' && form.parentId === '' && (form.type === 'dropdown' || form.type === 'link' || form.type === 'projects')" x-cloak>
    <input type="checkbox" name="is_mega" value="1" x-model="form.isMega"
        class="mt-0.5 rounded border-gray-500 text-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
    <span>
        <span class="block text-sm font-bold">Mega menu (panel na całą szerokość)</span>
        <span class="block text-xs text-muted">Podpozycje (podstrony powiązanej strony albo kategorie działań z ich działaniami) układają się w kolumny z ikoną i opisem. Bez podpozycji działa jak zwykła pozycja.</span>
    </span>
</label>

<div x-show="form.type === 'projects' && form.location === 'main' && form.parentId === ''" x-cloak>
    <label for="nav-mega-extra-title" class="mb-1 block text-sm font-bold">Nagłówek dodatkowej kolumny <span class="font-normal text-muted">(opcjonalnie)</span></label>
    <input type="text" id="nav-mega-extra-title" name="mega_extra_title" x-model="form.megaExtraTitle" maxlength="80" placeholder="To już zrobiliśmy"
        class="w-full rounded border-gray-500 focus-visible:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
    <p class="mt-1 text-xs text-muted">Podpozycje dodane pod tą pozycją (przycisk „Dodaj podpozycję") tworzą dodatkową kolumnę z tym nagłówkiem. Adres każdej ustawiasz sam — może prowadzić dowolną stroną, także zewnętrzną.</p>
    @error('mega_extra_title') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>

<fieldset x-show="form.type === 'projects' && form.location === 'main' && form.parentId === ''" x-cloak class="space-y-2 p-4 rounded-md bg-white">
    <legend class="px-1 text-xs font-bold uppercase tracking-wide text-muted">Domyślne przyciski menu działań</legend>
    <p class="text-xs text-muted">Domyślnie widoczne w kolumnie z przyciskami. Zaznacz, aby ukryć.</p>
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="hide_all_projects_btn" value="1" x-model="form.hideAllBtn" class="rounded border-gray-500 text-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
        <span>Ukryj przycisk „Wszystkie działania”</span>
    </label>
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="hide_archive_btn" value="1" x-model="form.hideArchiveBtn" class="rounded border-gray-500 text-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
        <span>Ukryj przycisk „To już zrobiliśmy”</span>
    </label>
</fieldset>

<div x-show="form.isMega && form.location === 'main' && form.parentId === ''" x-cloak>
    <label for="nav-mega-size" class="mb-1 block text-sm font-bold">Wielkość pozycji w mega menu</label>
    <select id="nav-mega-size" name="mega_size" x-model="form.megaSize"
        class="w-full rounded border-gray-500 focus-visible:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
        @foreach (\App\Models\NavItem::MEGA_SIZES as $msValue => $msLabel)
            <option value="{{ $msValue }}">{{ $msLabel }}</option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-muted">Kompaktowe mieści więcej pozycji w rzędzie, duże eksponuje ikony i opisy. Dotyczy też kolumn menu projektów.</p>
    @error('mega_size') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>

<div x-show="(form.isMega || ['dropdown', 'projects', 'pages'].includes(form.type)) && form.location === 'main' && form.parentId === ''" x-cloak class="nav-wide space-y-3 p-4 rounded-md bg-white">
    <p class="text-xs font-bold uppercase tracking-wide text-muted">Własne linki i przyciski w menu rozwijanym (karta boczna mega menu / pod listą)</p>
    <div>
        <label for="nav-mega-side-title" class="mb-1 block text-xs font-bold text-muted">Tytuł karty <span class="font-normal">(opcjonalnie — puste = bez tytułu)</span></label>
        <input type="text" id="nav-mega-side-title" name="mega_side_title" x-model="form.megaSideTitle" maxlength="80" placeholder="np. Działania na starej stronie"
            class="w-full rounded border-gray-500 text-sm focus-visible:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
    </div>
    <template x-for="(link, i) in form.megaSideLinks" :key="i">
        <div class="grid gap-2 p-3 sm:grid-cols-2 rounded-md bg-gray-50">
            <div>
                <label :for="'side-label-' + i" class="mb-0.5 block text-xs font-bold text-muted">Etykieta</label>
                <input type="text" :id="'side-label-' + i" :name="`mega_side_links[${i}][label]`" x-model="link.label" maxlength="80" placeholder="np. Działania (stara strona)"
                    class="w-full rounded border-gray-500 text-sm focus-visible:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
            </div>
            <div>
                <label :for="'side-url-' + i" class="mb-0.5 block text-xs font-bold text-muted">Adres</label>
                <input type="text" :id="'side-url-' + i" :name="`mega_side_links[${i}][url]`" x-model="link.url" maxlength="500" placeholder="https://… albo /strona"
                    class="w-full rounded border-gray-500 text-sm focus-visible:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
            </div>
            <div>
                <label :for="'side-style-' + i" class="mb-0.5 block text-xs font-bold text-muted">Wygląd</label>
                <select :id="'side-style-' + i" :name="`mega_side_links[${i}][style]`" x-model="link.style"
                    class="w-full rounded border-gray-500 text-sm focus-visible:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                    <option value="tile">Przycisk jak w „Na skróty” (obwódka)</option>
                    <option value="tile_filled">Przycisk jak w „Na skróty” (wypełniony)</option>
                    <option value="button">Przycisk (pigułka)</option>
                    <option value="link">Zwykły link</option>
                </select>
            </div>
            <div class="flex items-end justify-between gap-3">
                <label class="flex items-center gap-2 pb-2 text-sm">
                    <input type="checkbox" :name="`mega_side_links[${i}][new_tab]`" value="1" x-model="link.new_tab" class="rounded border-gray-500 text-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                    Nowa karta
                </label>
                <button type="button" @click="form.megaSideLinks.splice(i, 1)"
                    class="rounded px-2 py-1.5 text-sm font-bold text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500">
                    Usuń<span class="sr-only" x-text="' link ' + (i + 1)"></span>
                </button>
            </div>
        </div>
    </template>
    <button type="button" x-show="form.megaSideLinks.length < 8" @click="form.megaSideLinks.push({ label: '', url: '', style: 'tile', new_tab: false })"
        class="inline-flex items-center gap-2 rounded border border-dashed border-gray-300 px-3 py-1.5 text-sm font-bold text-brand-dark hover:border-brand-dark hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
        <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj link lub przycisk
    </button>
    <p class="text-xs text-muted">Gdy dodasz choć jeden link, zastępują domyślne przyciski karty („Wszystkie działania", „Zobacz wszystko"). Nazwa pozycji menu nie jest powtarzana w karcie. Linki widać też w menu mobilnym.</p>
    @error('mega_side_links.*.url') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
</div>

<label class="nav-wide flex items-center gap-2" x-show="form.type === 'dropdown' || form.type === 'projects' || form.type === 'pages'" x-cloak>
    <input type="checkbox" name="is_transparent_dropdown" value="1" x-model="form.isTransparent"
        class="rounded border-gray-500 text-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
    <span class="text-sm font-bold">Przezroczyste tło rozwijanego panelu</span>
</label>

    </div>

    <div id="nav-panel-media" role="tabpanel" aria-labelledby="nav-tab-media" x-show="tab === 'media'" x-cloak class="nav-form-grid">
        <p class="nav-wide text-sm text-muted" x-show="! form.isMega">Grafika promocyjna jest dostępna po włączeniu mega menu (zakładka „Menu rozwijane i przyciski”).</p>
<div x-show="form.isMega && form.location === 'main' && form.parentId === ''" x-cloak class="nav-wide space-y-3 p-4 rounded-md bg-white">
    <p class="text-xs font-bold uppercase tracking-wide text-muted">Grafika promocyjna w mega menu <span class="font-normal normal-case">(opcjonalnie — kolumna boczna nad opisem)</span></p>
    <template x-if="form.megaImage">
        <img :src="form.megaImage" alt="" class="h-24 w-full rounded-md border border-gray-200 object-cover">
    </template>
    <div>
        <label for="nav-mega-image-url" class="mb-1 block text-xs font-bold text-muted">Adres grafiki</label>
        <input type="text" id="nav-mega-image-url" name="mega_image_url" x-model="form.megaImage" placeholder="/storage/… albo https://…"
            class="w-full rounded border-gray-500 text-sm focus-visible:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
        <p class="mt-1 text-xs text-muted">Skopiuj adres z biblioteki <a href="{{ route('admin.multimedia.index') }}" target="_blank" rel="noopener" class="text-brand-dark underline">Multimedia</a> albo wgraj plik poniżej (zalecane 640×360 px).</p>
    </div>
    <div>
        <label for="nav-mega-image-file" class="mb-1 block text-xs font-bold text-muted">Wgraj plik</label>
        <input type="file" id="nav-mega-image-file" name="mega_image_file" accept="image/*"
            class="block w-full text-sm text-muted file:mr-3 file:rounded file:border-0 file:bg-brand-light file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-brand-dark">
        @error('mega_image_file') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="nav-mega-image-alt" class="mb-1 block text-xs font-bold text-muted">Tekst alternatywny grafiki</label>
        <input type="text" id="nav-mega-image-alt" name="mega_image_alt" x-model="form.megaImageAlt" maxlength="255" placeholder="Co przedstawia grafika (puste = dekoracyjna)"
            class="w-full rounded border-gray-500 text-sm focus-visible:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
    </div>
    <label class="flex items-center gap-2 text-sm" x-show="form.megaImage">
        <input type="checkbox" name="remove_mega_image" value="1" class="rounded border-gray-500 text-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
        <span>Usuń grafikę</span>
    </label>
</div>

    </div>
</div>
