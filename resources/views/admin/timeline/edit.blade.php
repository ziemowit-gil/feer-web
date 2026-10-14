@extends('admin.layout')

@section('title', 'Oś czasu')

{{--
    Edytor osi czasu strony „O organizacji”: po lewej wpisy ułożone jak oś (kropka w kolorze znacznika, rok, opis),
    linki schowane pod przyciskiem, kolor z próbek marki; po prawej podgląd na żywo. Stan w Alpine — nazwy pól
    liczone z kolejności na liście, więc zapisana kolejność zawsze odpowiada tej na ekranie.
    Płasko: bez ramek i cieni, kolory marki, tekst ≥ 4,5:1.
--}}

@section('content')
    @if (! $page)
        <div class="max-w-3xl rounded-md bg-gray-50 p-6 text-sm text-ink">
            <p class="font-bold">Brak strony typu „O organizacji”.</p>
            <p class="mt-1">Oś czasu jest częścią strony „O organizacji”. Utwórz taką stronę
                (<a href="{{ route('admin.podstrony.create') }}" class="font-bold text-brand-dark underline underline-offset-2">Dodaj stronę</a> →
                typ „O organizacji”), a następnie wróć tutaj, aby wygodnie edytować historię.</p>
        </div>
    @else
        @php
            $brand = $siteSettings->brand_color ?: '#1E6DFF';
            $rows = collect((array) old('about_timeline', $page->about_timeline ?? []))->values()->map(fn ($r) => [
                'year' => (string) ($r['year'] ?? ''), 'text' => (string) ($r['text'] ?? ''),
                'links' => collect([['url', 'label'], ['url2', 'label2'], ['url3', 'label3']])
                    ->map(fn ($k) => ['url' => (string) ($r[$k[0]] ?? ''), 'label' => (string) ($r[$k[1]] ?? '')])
                    ->filter(fn ($l) => $l['url'] !== '' || $l['label'] !== '')->values()->all(),
                'color' => (string) (($r['color'] ?? '') ?: $brand),
            ])->all();
            $swatches = array_values(array_unique([strtoupper($brand), '#1D1D1A', '#EA8F00', '#1554C4']));
            $field = 'w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark';
            $iconBtn = 'flex h-9 w-9 items-center justify-center rounded text-ink hover:bg-white disabled:text-gray-500 disabled:hover:bg-transparent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
            $textBtn = 'inline-flex min-h-9 items-center gap-2 rounded px-3 text-sm font-bold text-brand-dark hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
        @endphp

        <form method="POST" action="{{ route('admin.os-czasu.update', $page) }}"
            x-data="timelineEditor(@js($rows), @js($brand))"
            @submit="dirty = false"
            class="grid max-w-6xl items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            @csrf
            @method('PUT')

            {{-- ═════ Lewa kolumna: strona, pasek narzędzi, wpisy ═════ --}}
            <div class="min-w-0 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-md bg-white px-5 py-4">
                    <div class="min-w-0">
                        <p class="text-sm text-ink">Oś czasu strony</p>
                        <p class="text-lg font-bold text-ink">{{ $page->title }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        @if ($pages->count() > 1)
                            <label class="flex items-center gap-2 text-sm text-ink">
                                <span>Strona:</span>
                                <select onchange="window.location = '{{ route('admin.os-czasu.edit') }}?page=' + this.value" class="rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                                    @foreach ($pages as $option)
                                        <option value="{{ $option->id }}" @selected($option->id === $page->id)>{{ $option->title }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif
                        <a href="{{ route('admin.podstrony.edit', $page) }}" class="text-sm font-bold text-brand-dark underline underline-offset-2 hover:text-ink">Pełna edycja strony</a>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 rounded-md bg-white px-4 py-3">
                    <p class="mr-auto text-sm text-ink" aria-live="polite"><strong x-text="rows.length"></strong> <span x-text="plural(rows.length)"></span></p>
                    <button type="button" @click="add(0)" class="{{ $textBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Wpis na początku</button>
                    <button type="button" @click="sortByYear()" :disabled="rows.length < 2" class="{{ $textBtn }} disabled:text-gray-500"><i class="fa-solid fa-arrow-down-1-9" aria-hidden="true"></i> Sortuj wg roku</button>
                </div>

                @if ($errors->any())
                    <div class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">Nie zapisano — popraw zaznaczone pola: {{ $errors->first() }}</div>
                @endif

                <p x-show="removed" x-cloak class="flex flex-wrap items-center gap-3 rounded-md bg-gray-50 px-4 py-3 text-sm text-ink" role="status">
                    <span>Usunięto wpis <strong x-text="removed && (removed.row.year || 'bez roku')"></strong>.</span>
                    <button type="button" @click="undo()" class="font-bold text-brand-dark underline underline-offset-2 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Cofnij</button>
                </p>

                <ol role="list" class="space-y-3" aria-label="Wpisy osi czasu">
                    <template x-for="(row, i) in rows" :key="row.uid">
                        <li class="relative rounded-md bg-white py-4 pl-14 pr-4" :aria-label="'Wpis ' + (i + 1) + (row.year ? ': ' + row.year : '')">
                            {{-- Oś: linia i kropka w kolorze znacznika (dekoracja). --}}
                            <span class="absolute bottom-0 left-6 top-0 w-0.5 bg-gray-200" aria-hidden="true"></span>
                            <span class="absolute left-[1.0625rem] top-5 h-4 w-4 rounded-full ring-4 ring-white" :style="'background:' + row.color" aria-hidden="true"></span>

                            <div class="grid gap-3 sm:grid-cols-[8rem_minmax(0,1fr)]">
                                <div>
                                    <label :for="'tl-year-' + row.uid" class="mb-1 block text-xs font-bold text-ink">Rok / etap</label>
                                    <input type="text" :id="'tl-year-' + row.uid" :name="'about_timeline[' + i + '][year]'" x-model="row.year" @input="dirty = true" maxlength="20" placeholder="np. 2015"
                                        class="{{ $field }} text-lg font-bold">
                                </div>
                                <div>
                                    <label :for="'tl-text-' + row.uid" class="mb-1 flex justify-between text-xs font-bold text-ink">
                                        <span>Co się wydarzyło</span>
                                        <span class="font-normal" :class="row.text.length > 450 ? 'text-red-700' : ''" x-text="row.text.length + ' / 500'"></span>
                                    </label>
                                    <textarea :id="'tl-text-' + row.uid" :name="'about_timeline[' + i + '][text]'" x-model="row.text" @input="dirty = true" maxlength="500" rows="2" placeholder="Opis wydarzenia"
                                        class="{{ $field }} text-sm leading-relaxed"></textarea>
                                </div>
                            </div>

                            {{-- Linki: schowane, dopóki nie są potrzebne. --}}
                            <template x-for="(link, l) in row.links" :key="l">
                                <div>
                                    <input type="hidden" :name="'about_timeline[' + i + '][' + (l === 0 ? 'url' : 'url' + (l + 1)) + ']'" :value="link.url">
                                    <input type="hidden" :name="'about_timeline[' + i + '][' + (l === 0 ? 'label' : 'label' + (l + 1)) + ']'" :value="link.label">
                                </div>
                            </template>
                            <input type="hidden" :name="'about_timeline[' + i + '][color]'" :value="row.color">

                            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2">
                                <button type="button" @click="row.showLinks = ! row.showLinks; if (row.showLinks && ! row.links.length) row.links.push({ url: '', label: '' })" :aria-expanded="row.showLinks.toString()" :aria-controls="'tl-links-' + row.uid"
                                    class="{{ $textBtn }} -ml-3">
                                    <i class="fa-solid fa-link" aria-hidden="true"></i> <span x-text="'Linki' + (filledLinks(row) ? ' (' + filledLinks(row) + ')' : '')"></span>
                                </button>

                                <fieldset class="flex items-center gap-1.5">
                                    <legend class="sr-only" x-text="'Kolor znacznika wpisu ' + (i + 1)"></legend>
                                    <span class="mr-1 text-xs text-ink" aria-hidden="true">Kolor</span>
                                    @foreach ($swatches as $sw)
                                        <button type="button" @click="row.color = '{{ $sw }}'; dirty = true" :aria-pressed="(row.color.toUpperCase() === '{{ $sw }}').toString()"
                                            class="flex h-7 w-7 items-center justify-center rounded-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2"
                                            :class="row.color.toUpperCase() === '{{ $sw }}' ? 'ring-2 ring-ink ring-offset-2' : ''" style="background: {{ $sw }}">
                                            <span class="sr-only">Kolor {{ $sw }}</span>
                                        </button>
                                    @endforeach
                                    <label class="relative flex h-7 w-7 cursor-pointer items-center justify-center rounded-full bg-gray-100 text-xs text-ink hover:bg-gray-200" title="Własny kolor">
                                        <i class="fa-solid fa-eye-dropper" aria-hidden="true"></i>
                                        <span class="sr-only">Własny kolor</span>
                                        <input type="color" x-model="row.color" @input="dirty = true" class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
                                    </label>
                                </fieldset>

                                <div class="ml-auto flex items-center gap-0.5 rounded bg-gray-50">
                                    <button type="button" @click="move(i, -1)" :disabled="i === 0" class="{{ $iconBtn }}" :aria-label="'Przenieś wyżej: wpis ' + (row.year || i + 1)"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                                    <button type="button" @click="move(i, 1)" :disabled="i === rows.length - 1" class="{{ $iconBtn }}" :aria-label="'Przenieś niżej: wpis ' + (row.year || i + 1)"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                                    <button type="button" @click="add(i + 1)" class="{{ $iconBtn }}" :aria-label="'Dodaj wpis pod: ' + (row.year || i + 1)"><i class="fa-solid fa-plus" aria-hidden="true"></i></button>
                                    <button type="button" @click="remove(i)" class="{{ $iconBtn }} text-red-700" :aria-label="'Usuń wpis ' + (row.year || i + 1)"><i class="fa-regular fa-trash-can" aria-hidden="true"></i></button>
                                </div>
                            </div>

                            <div x-show="row.showLinks" x-cloak :id="'tl-links-' + row.uid" class="mt-3 space-y-2 rounded-md bg-gray-50 p-3">
                                <template x-for="(link, l) in row.links" :key="l">
                                    <div class="grid items-center gap-2 sm:grid-cols-[minmax(0,3fr)_minmax(0,2fr)_auto]">
                                        <label class="sr-only" :for="'tl-url-' + row.uid + '-' + l" x-text="'Adres linku ' + (l + 1)"></label>
                                        <input type="url" :id="'tl-url-' + row.uid + '-' + l" x-model="link.url" @input="dirty = true" maxlength="255" placeholder="https://…" class="{{ $field }} text-sm">
                                        <label class="sr-only" :for="'tl-label-' + row.uid + '-' + l" x-text="'Etykieta linku ' + (l + 1)"></label>
                                        <input type="text" :id="'tl-label-' + row.uid + '-' + l" x-model="link.label" @input="dirty = true" maxlength="120" placeholder="Etykieta, np. Wpis w KRS" class="{{ $field }} text-sm">
                                        <button type="button" @click="row.links.splice(l, 1); dirty = true" class="{{ $iconBtn }} text-red-700" :aria-label="'Usuń link ' + (l + 1)"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                                    </div>
                                </template>
                                <button type="button" x-show="row.links.length < 3" @click="row.links.push({ url: '', label: '' }); dirty = true" class="{{ $textBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj link</button>
                                <p x-show="row.links.length >= 3" class="text-xs text-ink">Najwyżej 3 linki przy jednym wpisie.</p>
                            </div>
                        </li>
                    </template>
                </ol>

                <p x-show="rows.length === 0" class="rounded-md bg-white px-5 py-8 text-center text-sm text-ink">Oś czasu jest pusta — dodaj pierwszy wpis.</p>

                <button type="button" @click="add(rows.length)" class="flex min-h-12 w-full items-center justify-center gap-2 rounded-md bg-white text-sm font-bold text-brand-dark hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj wpis na końcu
                </button>
            </div>

            {{-- ═════ Prawa kolumna: zapis i podgląd na żywo ═════ --}}
            <div class="space-y-4 lg:sticky lg:top-4">
                <div class="rounded-md bg-white p-5">
                    <p x-show="dirty" x-cloak class="mb-3 text-sm font-bold text-ink" role="status"><i class="fa-solid fa-circle-exclamation mr-1 text-brand-dark" aria-hidden="true"></i> Masz niezapisane zmiany</p>
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Zapisz oś czasu</button>
                        <a href="{{ $page->publicUrl() }}" target="_blank" rel="noopener" class="text-sm font-bold text-brand-dark underline underline-offset-2 hover:text-ink">Zobacz stronę<span class="sr-only"> (nowa karta)</span></a>
                    </div>
                    <p class="mt-3 text-xs text-ink">Wpisy bez roku i opisu są pomijane przy zapisie.</p>
                </div>

                <section class="rounded-md bg-white p-5" aria-labelledby="tl-preview-h">
                    <h2 id="tl-preview-h" class="text-sm font-bold text-ink">Podgląd</h2>
                    <ol role="list" class="mt-4 max-h-[60vh] space-y-4 overflow-y-auto pr-1" aria-hidden="true">
                        <template x-for="row in rows.filter(r => r.year || r.text)" :key="'p' + row.uid">
                            <li class="relative pl-6">
                                <span class="absolute bottom-[-1rem] left-[0.3125rem] top-1 w-0.5 bg-gray-200"></span>
                                <span class="absolute left-0 top-1 h-3 w-3 rounded-full" :style="'background:' + row.color"></span>
                                <p class="text-sm font-bold text-ink" x-text="row.year"></p>
                                <p class="text-sm leading-snug text-ink" x-text="row.text"></p>
                                <p class="mt-0.5 flex flex-wrap gap-x-3 text-xs font-bold text-brand-dark underline underline-offset-2">
                                    <template x-for="link in row.links.filter(k => k.url)"><span x-text="link.label || link.url"></span></template>
                                </p>
                            </li>
                        </template>
                    </ol>
                    <p x-show="! rows.some(r => r.year || r.text)" class="mt-3 text-sm text-ink">Tu zobaczysz oś czasu.</p>
                </section>
            </div>
        </form>

        <script>
            function timelineEditor(initial, brand) {
                let seq = 0;
                const make = (r = {}) => ({ uid: ++seq, year: r.year || '', text: r.text || '', links: (r.links || []).map(l => ({ ...l })), color: r.color || brand, showLinks: false });

                return {
                    rows: initial.map(make),
                    dirty: false,
                    removed: null,
                    init() {
                        window.addEventListener('beforeunload', (e) => { if (this.dirty) { e.preventDefault(); e.returnValue = ''; } });
                    },
                    plural(n) { return n === 1 ? 'wpis' : ([2, 3, 4].includes(n % 10) && ! [12, 13, 14].includes(n % 100) ? 'wpisy' : 'wpisów'); },
                    filledLinks(row) { return row.links.filter(l => l.url).length; },
                    add(at) {
                        const row = make();
                        this.rows.splice(at, 0, row);
                        this.dirty = true;
                        this.$nextTick(() => document.getElementById('tl-year-' + row.uid)?.focus());
                    },
                    move(i, d) {
                        const j = i + d;
                        if (j < 0 || j >= this.rows.length) return;
                        [this.rows[i], this.rows[j]] = [this.rows[j], this.rows[i]];
                        this.dirty = true;
                    },
                    remove(i) {
                        this.removed = { row: this.rows[i], index: i };
                        this.rows.splice(i, 1);
                        this.dirty = true;
                    },
                    undo() {
                        if (! this.removed) return;
                        this.rows.splice(Math.min(this.removed.index, this.rows.length), 0, this.removed.row);
                        const uid = this.removed.row.uid;
                        this.removed = null;
                        this.$nextTick(() => document.getElementById('tl-year-' + uid)?.focus());
                    },
                    // Sortuje po pierwszej liczbie w polu „Rok / etap” (wpisy bez liczby zostają na końcu, w dotychczasowej kolejności).
                    sortByYear() {
                        const num = (r) => { const m = String(r.year).match(/\d{3,4}/); return m ? parseInt(m[0], 10) : 1e9; };
                        this.rows = this.rows.map((r, k) => [r, k]).sort((a, b) => (num(a[0]) - num(b[0])) || (a[1] - b[1])).map(p => p[0]);
                        this.dirty = true;
                    },
                };
            }
        </script>
    @endif
@endsection
