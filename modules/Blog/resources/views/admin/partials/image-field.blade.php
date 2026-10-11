{{--
    Pole zdjęcia w panelu bloga: wgranie pliku albo wybór z biblioteki multimediów
    (kopiowany po zapisie), usunięcie obecnego, tekst alternatywny i — opcjonalnie —
    „zdjęcie dekoracyjne" (wtedy alt="" na stronie). Wzorowane na formularzu FEER Pasków.
    Zmienne: $legend, $fileName, $currentUrl, $currentAlt, $removeName, $altName, $altValue,
    $altLabel, $altHelp, opcjonalnie $decorativeName + $decorativeValue, $prefix (unikalne id).
--}}
@php $p = $prefix ?? 'img'; @endphp
<fieldset class="space-y-4 rounded-lg bg-gray-50 p-4"
    x-data="{
        lib: [], open: false, loading: false, err: '', chosenId: '', chosenUrl: '',
        async load() {
            this.open = ! this.open;
            if (! this.open || this.lib.length) return;
            this.loading = true; this.err = '';
            try {
                const r = await fetch(@js(route('admin.multimedia.images')), { headers: { Accept: 'application/json' } });
                if (! r.ok) throw new Error();
                this.lib = await r.json();
                if (! this.lib.length) this.err = 'Biblioteka nie zawiera jeszcze zdjęć.';
            } catch (e) { this.err = 'Nie udało się wczytać biblioteki zdjęć.'; }
            this.loading = false;
        },
        pick(m) {
            this.chosenId = m.id; this.chosenUrl = m.url;
            const alt = document.getElementById(@js($p . '-alt'));
            if (alt && ! alt.value && m.alt) alt.value = m.alt;
        },
    }">
    <legend class="px-1 text-sm font-bold">{{ $legend }}</legend>

    @if ($currentUrl)
        <div class="flex flex-wrap items-center gap-4">
            <img src="{{ $currentUrl }}" alt="{{ $currentAlt ?: 'Aktualne zdjęcie' }}" class="h-24 w-36 rounded-md object-cover">
            <label class="flex items-center gap-2 text-sm font-bold">
                <input type="checkbox" name="{{ $removeName }}" value="1" class="rounded border-gray-400 text-brand-dark focus:ring-brand-dark"> Usuń zdjęcie
            </label>
        </div>
    @endif

    <div>
        <label for="{{ $p }}-file" class="mb-1 block text-sm font-bold">Wgraj plik</label>
        <input type="file" id="{{ $p }}-file" name="{{ $fileName }}" accept="image/*"
            class="block w-full cursor-pointer text-sm text-ink file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand-dark file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-ink">
        @error($fileName) <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div>
        <p class="mb-1 text-sm font-bold">Albo wybierz z biblioteki multimediów</p>
        <button type="button" @click="load()" :aria-expanded="open.toString()" class="rounded-lg border border-gray-400 bg-white px-4 py-2 text-sm font-bold text-ink hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
            <i class="fa-solid fa-images mr-1.5" aria-hidden="true"></i><span x-text="open ? 'Zwiń bibliotekę' : 'Otwórz bibliotekę zdjęć'">Otwórz bibliotekę zdjęć</span>
        </button>
        <input type="hidden" name="library_media_id" :value="chosenId">
        <p x-show="loading" class="mt-2 text-xs text-muted" role="status">Wczytuję zdjęcia…</p>
        <p x-show="err" x-cloak x-text="err" class="mt-2 text-xs text-red-700" role="alert"></p>
        <div x-show="open && lib.length" x-cloak class="mt-3 grid max-h-72 grid-cols-3 gap-2 overflow-y-auto sm:grid-cols-4" role="group" aria-label="Zdjęcia z biblioteki">
            <template x-for="m in lib" :key="m.id">
                <button type="button" @click="pick(m)" :aria-pressed="(chosenId === m.id).toString()" class="overflow-hidden rounded-md border-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark" :class="chosenId === m.id ? 'border-brand-dark' : 'border-transparent'">
                    <img :src="m.url" :alt="m.alt || m.file_name" loading="lazy" class="aspect-[4/3] w-full object-cover">
                </button>
            </template>
        </div>
        <p x-show="chosenId" x-cloak class="mt-2 text-xs font-bold text-brand-dark" role="status">Wybrano zdjęcie z biblioteki — zostanie skopiowane po zapisaniu.</p>
    </div>

    <div>
        <label for="{{ $p }}-alt" class="mb-1 block text-sm font-bold">{{ $altLabel }}</label>
        <input type="text" id="{{ $p }}-alt" name="{{ $altName }}" maxlength="255" value="{{ $altValue }}"
            aria-describedby="{{ $p }}-alt-help" @error($altName) aria-invalid="true" @enderror
            class="w-full rounded border-gray-400 focus:border-brand-dark focus:ring-brand-dark">
        <p id="{{ $p }}-alt-help" class="mt-1 text-xs text-muted">{{ $altHelp }}</p>
        @error($altName) <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    @isset($decorativeName)
        <label class="flex items-start gap-2">
            <input type="checkbox" name="{{ $decorativeName }}" value="1" @checked($decorativeValue) class="mt-0.5 rounded border-gray-400 text-brand-dark focus:ring-brand-dark">
            <span>
                <span class="block text-sm font-bold">Zdjęcie dekoracyjne</span>
                <span class="block text-xs text-muted">Nie niesie informacji — czytniki ekranu je pominą (pusty tekst alternatywny).</span>
            </span>
        </label>
    @endisset
</fieldset>
