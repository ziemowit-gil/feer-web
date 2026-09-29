{{--
    Współdzielone pola formularza pozycji menu.
    Wymaga otaczającego zakresu Alpine z obiektem `form` (label, url, type,
    location, parentId, module, isButton, buttonColor, buttonColorEnabled,
    isTransparent, isActive, editingId) oraz zmiennych PHP $parentOptions, $pages.
    Wszystkie pola sterowane są przez Alpine (x-model), dzięki czemu ten sam
    partial obsługuje modal (dynamiczne dane) i zapasową stronę formularza.
--}}

<div>
    <label for="nav-location" class="mb-1 block text-sm font-bold">Lokalizacja</label>
    <select id="nav-location" name="location" x-model="form.location"
        class="w-full rounded border-gray-300 focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand">
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
        class="w-full rounded border-gray-300 focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand">
    @error('label') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>

<div x-show="form.location === 'main'" x-cloak>
    <label for="nav-icon" class="mb-1 block text-sm font-bold">Ikona <span class="font-normal text-muted">(opcjonalnie — używana w stylu nav „Ikony + etykiety")</span></label>
    <input type="text" id="nav-icon" name="icon" x-model="form.icon" placeholder="np. bi-house-door" data-icon-picker data-icon-format="name"
        class="w-full rounded border-gray-300 font-mono text-sm focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand">
    <p class="mt-1 text-xs text-muted">Klasa Bootstrap Icons bez prefiksu <code>bi</code> — np. <code>bi-people</code>, <code>bi-envelope</code>. Pełna lista: <a href="https://icons.getbootstrap.com/" target="_blank" rel="noopener" class="text-brand underline">icons.getbootstrap.com</a></p>
    @error('icon') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>

<div x-show="form.location === 'main'" x-cloak>
    <label for="nav-type" class="mb-1 block text-sm font-bold">Typ pozycji</label>
    <select id="nav-type" name="type" x-model="form.type"
        class="w-full rounded border-gray-300 focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand">
        @foreach (\App\Models\NavItem::TYPES as $value => $option)
            <option value="{{ $value }}">{{ $option }}</option>
        @endforeach
    </select>
    @error('type') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    <p class="mt-1 text-xs text-muted" x-show="form.type === 'volunteering'" x-cloak>
        Pozycja prowadzi automatycznie do listy ogłoszeń o wolontariacie (/wolontariat) i ukrywa się, gdy moduł jest wyłączony. Możesz wyróżnić ją jako przycisk (CTA) poniżej.
    </p>
</div>

<div x-show="form.type === 'link' || form.location === 'footer' || form.location === 'bip'" x-cloak>
    {{-- Wybór dokumentu BIP — tylko gdy lokalizacja to "Menu BIP" --}}
    @if (!empty($bipDocuments) && $bipDocuments->isNotEmpty())
        <div x-show="form.location === 'bip'" x-cloak>
            <label for="nav-bip-picker" class="mb-1 block text-sm font-bold">Wybierz dokument BIP <span class="font-normal text-muted">(opcjonalnie)</span></label>
            <select id="nav-bip-picker" class="mb-2 w-full rounded border-gray-300 focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand"
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

    {{-- Wybór strony — tylko gdy lokalizacja to nie "Menu BIP" --}}
    @if ($pages->isNotEmpty())
        <div x-show="form.location !== 'bip'" x-cloak>
            <label for="nav-page-picker" class="mb-1 block text-sm font-bold">Wybierz stronę <span class="font-normal text-muted">(opcjonalnie)</span></label>
            <select id="nav-page-picker" class="mb-2 w-full rounded border-gray-300 focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand"
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
        class="w-full rounded border-gray-300 focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand">
    <p class="mt-1 text-xs text-muted">Wybór dokumentu lub strony powyżej uzupełni to pole — możesz też wpisać dowolny adres ręcznie.</p>
    @error('url') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>

<div x-show="form.type === 'link' && form.location === 'main'" x-cloak>
    <label for="nav-parent" class="mb-1 block text-sm font-bold">Podpozycja w menu (pod „Rozwijanym menu" lub linkiem)</label>
    <select id="nav-parent" name="parent_id" x-model="form.parentId"
        class="w-full rounded border-gray-300 focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand">
        <option value="">— pozycja główna (na pasku menu) —</option>
        @foreach ($parentOptions as $option)
            {{-- Pozycja nie może być własnym rodzicem — chowamy ją z listy podczas edycji. --}}
            <option value="{{ $option->id }}" x-show="String(form.editingId) !== '{{ $option->id }}'">{{ $option->label }}</option>
        @endforeach
    </select>
    @error('parent_id') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>

<div>
    <label for="nav-module" class="mb-1 block text-sm font-bold">Widoczna tylko gdy moduł włączony</label>
    <select id="nav-module" name="module" x-model="form.module"
        class="w-full rounded border-gray-300 focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand">
        <option value="">— zawsze widoczna —</option>
        @foreach (\App\Models\SiteSetting::MODULES as $value => $option)
            <option value="{{ $value }}">{{ $option }}</option>
        @endforeach
    </select>
    @error('module') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>

<label class="flex items-center gap-2" x-show="(form.type === 'link' || form.type === 'volunteering') && form.parentId === ''" x-cloak>
    <input type="checkbox" name="is_button" value="1" x-model="form.isButton"
        class="rounded border-gray-300 text-brand focus-visible:ring-2 focus-visible:ring-brand">
    <span class="text-sm font-bold">Wyróżnij jako przycisk (CTA)</span>
</label>

<div x-show="(form.type === 'link' || form.type === 'volunteering') && form.parentId === '' && form.isButton" x-cloak>
    <span class="mb-1 block text-sm font-bold">Kolor przycisku <span class="font-normal text-muted">(opcjonalnie)</span></span>
    <div class="flex items-center gap-3">
        <input type="hidden" name="button_color" :value="form.buttonColorEnabled ? form.buttonColor : ''">
        <input type="text" x-model="form.buttonColor" :disabled="!form.buttonColorEnabled" aria-label="Kod koloru (hex)" data-color-picker
            placeholder="#2563eb" pattern="#[0-9a-fA-F]{6}"
            class="w-40 rounded border-gray-300 font-mono text-sm focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand disabled:bg-gray-100 disabled:text-muted">
        <label class="flex items-center gap-2 text-sm text-muted">
            <input type="checkbox" x-model="form.buttonColorEnabled" class="rounded border-gray-300 text-brand focus-visible:ring-2 focus-visible:ring-brand">
            Własny kolor
        </label>
    </div>
    <p class="mt-1 text-xs text-muted">Kolor tła przycisku. Tekst automatycznie dobiera czerń lub biel dla kontrastu (WCAG). Wyłącz „Własny kolor", aby użyć koloru marki.</p>
    @error('button_color') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>

<div x-show="form.location === 'main' && form.parentId !== ''" x-cloak>
    <label for="nav-description" class="mb-1 block text-sm font-bold">Krótki opis <span class="font-normal text-muted">(opcjonalnie — widoczny w mega menu)</span></label>
    <input type="text" id="nav-description" name="description" x-model="form.description" maxlength="255" placeholder="np. Szkolenia i audyty dostępności cyfrowej"
        class="w-full rounded border-gray-300 focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand">
    @error('description') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>

<label class="flex items-start gap-2" x-show="form.location === 'main' && form.parentId === '' && (form.type === 'dropdown' || form.type === 'link' || form.type === 'projects')" x-cloak>
    <input type="checkbox" name="is_mega" value="1" x-model="form.isMega"
        class="mt-0.5 rounded border-gray-300 text-brand focus-visible:ring-2 focus-visible:ring-brand">
    <span>
        <span class="block text-sm font-bold">Mega menu (panel na całą szerokość)</span>
        <span class="block text-xs text-muted">Podpozycje (podstrony powiązanej strony albo kategorie projektów z ich projektami) układają się w kolumny z ikoną i opisem. Bez podpozycji działa jak zwykła pozycja.</span>
    </span>
</label>

<div x-show="form.isMega && form.location === 'main' && form.parentId === ''" x-cloak class="space-y-3 rounded-lg border border-gray-100 bg-gray-50 p-4">
    <p class="text-xs font-bold uppercase tracking-wide text-muted">Grafika promocyjna w mega menu <span class="font-normal normal-case">(opcjonalnie — kolumna boczna nad opisem)</span></p>
    <template x-if="form.megaImage">
        <img :src="form.megaImage" alt="" class="h-24 w-full rounded-md border border-gray-200 object-cover">
    </template>
    <div>
        <label for="nav-mega-image-url" class="mb-1 block text-xs font-bold text-muted">Adres grafiki</label>
        <input type="text" id="nav-mega-image-url" name="mega_image_url" x-model="form.megaImage" placeholder="/storage/… albo https://…"
            class="w-full rounded border-gray-300 text-sm focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand">
        <p class="mt-1 text-xs text-muted">Skopiuj adres z biblioteki <a href="{{ route('admin.multimedia.index') }}" target="_blank" rel="noopener" class="text-brand underline">Multimedia</a> albo wgraj plik poniżej (zalecane 640×360 px).</p>
    </div>
    <div>
        <label for="nav-mega-image-file" class="mb-1 block text-xs font-bold text-muted">Wgraj plik</label>
        <input type="file" id="nav-mega-image-file" name="mega_image_file" accept="image/*"
            class="block w-full text-sm text-muted file:mr-3 file:rounded file:border-0 file:bg-brand-light file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-brand">
        @error('mega_image_file') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="nav-mega-image-alt" class="mb-1 block text-xs font-bold text-muted">Tekst alternatywny grafiki</label>
        <input type="text" id="nav-mega-image-alt" name="mega_image_alt" x-model="form.megaImageAlt" maxlength="255" placeholder="Co przedstawia grafika (puste = dekoracyjna)"
            class="w-full rounded border-gray-300 text-sm focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand">
    </div>
    <label class="flex items-center gap-2 text-sm" x-show="form.megaImage">
        <input type="checkbox" name="remove_mega_image" value="1" class="rounded border-gray-300 text-brand focus-visible:ring-2 focus-visible:ring-brand">
        <span>Usuń grafikę</span>
    </label>
</div>

<label class="flex items-center gap-2" x-show="form.type === 'dropdown' || form.type === 'projects' || form.type === 'pages'" x-cloak>
    <input type="checkbox" name="is_transparent_dropdown" value="1" x-model="form.isTransparent"
        class="rounded border-gray-300 text-brand focus-visible:ring-2 focus-visible:ring-brand">
    <span class="text-sm font-bold">Przezroczyste tło rozwijanego panelu</span>
</label>

<label class="flex items-center gap-2">
    <input type="checkbox" name="is_active" value="1" x-model="form.isActive"
        class="rounded border-gray-300 text-brand focus-visible:ring-2 focus-visible:ring-brand">
    <span class="text-sm font-bold">Widoczna w menu</span>
</label>
