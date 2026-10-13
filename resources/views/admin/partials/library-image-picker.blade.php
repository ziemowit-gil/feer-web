{{--
    Pole obrazu: wgranie z dysku albo wybór z biblioteki plików (Multimedia).
    Wybór z biblioteki wysyła {name}_library_id — kontroler kopiuje obraz do rekordu (AttachesLibraryMedia).

    Parametry: $name (pole pliku, np. logo), $label, $current (adres obecnego obrazu lub null),
    opcjonalnie $required (bool), $help (tekst pod polem), $accept (domyślnie image/*).
--}}
@php
    $pickerId = 'lib-'.$name;
    $required = $required ?? false;
@endphp
<div x-data="{
        open: false, loading: false, items: [], q: '', chosen: null, error: '',
        async load() {
            if (this.items.length) return;
            this.loading = true;
            try { const r = await fetch(@js(route('admin.multimedia.images')), { headers: { Accept: 'application/json' } }); this.items = await r.json(); }
            catch (e) { this.error = 'Nie udało się wczytać biblioteki.'; }
            this.loading = false;
        },
        get filtered() { const q = this.q.toLowerCase().trim(); return q ? this.items.filter(i => (i.file_name + ' ' + (i.alt || '')).toLowerCase().includes(q)) : this.items; },
        show() { this.open = true; this.load(); this.$nextTick(() => this.$refs.search?.focus()); },
        pick(item) { this.chosen = item; this.open = false; this.$refs.file.value = ''; this.$nextTick(() => this.$refs.opener.focus()); },
        clear() { this.chosen = null; }
    }" @keydown.escape.window="if (open) { open = false; $refs.opener.focus() }">
    <p class="mb-1 block text-sm font-bold text-ink">{{ $label }} @if ($required)<span aria-hidden="true" class="text-red-600">*</span>@endif</p>

    <div class="flex flex-wrap items-start gap-4">
        <span class="flex h-20 w-32 flex-none items-center justify-center overflow-hidden rounded-md bg-gray-100">
            <template x-if="chosen"><img :src="chosen.url" :alt="'Wybrany obraz: ' + (chosen.alt || chosen.file_name)" class="h-full w-full object-contain"></template>
            @if ($current ?? null)
                <img x-show="! chosen" src="{{ $current }}" alt="Obecny obraz" class="h-full w-full object-contain">
            @else
                <i x-show="! chosen" class="fa-solid fa-image text-2xl text-gray-400" aria-hidden="true"></i>
            @endif
        </span>
        <div class="min-w-0 flex-1 space-y-2">
            <input type="file" id="{{ $name }}" name="{{ $name }}" accept="{{ $accept ?? 'image/*' }}" x-ref="file" @change="if ($event.target.files.length) chosen = null"
                @if ($help ?? null) aria-describedby="{{ $name }}-help" @endif
                aria-label="{{ $label }} — wgraj z dysku"
                class="block w-full text-sm text-ink file:mr-3 file:rounded-md file:border-0 file:bg-brand-dark file:px-4 file:py-2 file:font-bold file:text-white hover:file:bg-ink">
            <div class="flex flex-wrap items-center gap-3">
                <button type="button" x-ref="opener" @click="show()" aria-haspopup="dialog"
                    class="inline-flex min-h-10 items-center gap-2 rounded-md bg-gray-100 px-4 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                    <i class="fa-solid fa-photo-film" aria-hidden="true"></i> Wybierz z biblioteki
                </button>
                <span x-show="chosen" x-cloak class="text-sm text-ink" aria-live="polite">
                    Wybrano: <strong x-text="chosen?.file_name"></strong>
                    <button type="button" @click="clear()" class="ml-1 font-bold text-brand-dark underline underline-offset-2 hover:no-underline">Usuń wybór</button>
                </span>
            </div>
            <input type="hidden" name="{{ $name }}_library_id" :value="chosen ? chosen.id : ''">
            @if ($help ?? null)<p id="{{ $name }}-help" class="text-xs text-muted">{{ $help }}</p>@endif
            @error($name) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Okno wyboru z biblioteki --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink/60 p-4" role="dialog" aria-modal="true" aria-labelledby="{{ $pickerId }}-title"
        @click.self="open = false; $refs.opener.focus()">
        <div class="flex max-h-[85vh] w-full max-w-3xl flex-col rounded-md bg-white shadow-2xl">
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-3">
                <h2 id="{{ $pickerId }}-title" class="text-base font-bold text-ink">Wybierz obraz z biblioteki</h2>
                <button type="button" @click="open = false; $refs.opener.focus()" class="rounded-md p-2 text-ink hover:bg-gray-100" aria-label="Zamknij"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
            </div>
            <div class="px-5 pt-4">
                <label for="{{ $pickerId }}-search" class="sr-only">Szukaj w bibliotece</label>
                <input id="{{ $pickerId }}-search" x-ref="search" type="search" x-model="q" placeholder="Szukaj po nazwie lub opisie…"
                    class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
            </div>
            <div class="flex-1 overflow-y-auto p-5">
                <p x-show="loading" class="text-sm text-ink" role="status">Wczytywanie…</p>
                <p x-show="error" class="text-sm text-ink" x-text="error"></p>
                <p x-show="! loading && ! filtered.length && ! error" class="text-sm text-ink">Brak obrazów{{ ' ' }}<span x-show="q">pasujących do wyszukiwania</span>.</p>
                <ul class="grid grid-cols-2 gap-3 sm:grid-cols-4" role="list">
                    <template x-for="item in filtered" :key="item.id">
                        <li>
                            <button type="button" @click="pick(item)" class="block w-full rounded-md bg-gray-50 p-2 text-left hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                <span class="flex aspect-[4/3] items-center justify-center overflow-hidden rounded bg-white"><img :src="item.url" alt="" class="h-full w-full object-contain" loading="lazy"></span>
                                <span class="mt-1 block truncate text-xs font-bold text-ink" x-text="item.file_name"></span>
                                <span class="sr-only" x-text="item.alt ? 'Opis: ' + item.alt : ''"></span>
                            </button>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
    </div>
</div>
