@extends('admin.layout')

@section('title', $news->exists ? 'Edytuj news' : 'Nowy news')

@section('content')
    <div data-editor-tabs>
        @if ($news->exists)
            @include('admin.partials.edit-lock', ['lockType' => 'news', 'lockId' => $news->id])
            <div class="mb-6 flex flex-wrap gap-1 border-b border-gray-200">
                <button type="button" data-tab-btn="edit" class="-mb-px border-b-2 border-brand px-4 py-2 text-sm font-bold text-brand">
                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Treść
                </button>
                <button type="button" data-tab-btn="etr" class="-mb-px border-b-2 border-transparent px-4 py-2 text-sm font-bold text-muted hover:text-brand">
                    <i class="fa-solid fa-book-open-reader" aria-hidden="true"></i> ETR
                    @if ($news->etr?->is_enabled)
                        <span class="ml-1 rounded-full bg-sky-100 px-1.5 py-0.5 text-xs text-sky-700">aktywna</span>
                    @endif
                </button>
                <button type="button" data-tab-btn="files" class="-mb-px border-b-2 border-transparent px-4 py-2 text-sm font-bold text-muted hover:text-brand">
                    <i class="fa-solid fa-paperclip" aria-hidden="true"></i> Pliki do pobrania
                    @if ($news->attachments->isNotEmpty())
                        <span class="ml-1 rounded-full bg-gray-100 px-1.5 py-0.5 text-xs">{{ $news->attachments->count() }}</span>
                    @endif
                </button>
                <a href="{{ route('admin.historia.index', ['type' => 'news', 'id' => $news->id]) }}"
                    class="ml-auto -mb-px border-b-2 border-transparent px-4 py-2 text-sm font-bold text-muted hover:text-brand">
                    <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Historia zmian
                </a>
            </div>
        @endif

        <div data-tab-panel="edit">
    @include('admin.partials.template-panel', [
        'templateType'   => 'news',
        'templateFields' => ['news_category_id', 'project_id', 'audience', 'accent_color', 'excerpt', 'content', 'meta_title', 'meta_description'],
    ])

    <form method="POST" action="{{ $news->exists ? route('admin.newsy.update', $news) : route('admin.newsy.store') }}"
        enctype="multipart/form-data" class="mt-4 space-y-6">
        @csrf
        @if ($news->exists) @method('PUT') @endif

        <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="title" class="mb-1 block text-sm font-bold">Tytuł</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $news->title) }}" required
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="slug" class="mb-1 block text-sm font-bold">Slug (adres URL)</label>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted">/aktualnosci/</span>
                        <input type="text" id="slug" name="slug" value="{{ old('slug', $news->slug) }}" placeholder="zostanie wygenerowany z tytułu"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    </div>
                    @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="excerpt" class="mb-1 block text-sm font-bold">Krótki opis</label>
                    <input type="text" id="excerpt" name="excerpt" value="{{ old('excerpt', $news->excerpt) }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('excerpt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="tags" class="mb-1 block text-sm font-bold">Tagi <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <input type="text" id="tags" name="tags"
                        value="{{ old('tags', $news->relationLoaded('tags') ? $news->tags->pluck('name')->implode(', ') : '') }}"
                        placeholder="np. dostępność, wcag, audyt"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Oddziel tagi przecinkami. Nowe tagi zostaną utworzone automatycznie.</p>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="audience" class="mb-1 block text-sm font-bold">Grupa docelowa (kolorystyka)</label>
                    <select id="audience" name="audience" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @foreach ($siteSettings->audienceOptions() as $value => $label)
                            <option value="{{ $value }}" {{ old('audience', $news->audience ?? 'brand') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-muted">Zmienia kolorystykę strony aktualności na kolor wybranej submarki (Ustawienia → Kolory).</p>
                    @error('audience') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="accent_color_text" class="mb-1 block text-sm font-bold">Własny kolor akcentu <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <div class="flex flex-wrap items-center gap-3">
                        <input type="color" id="accent_color_picker" value="{{ old('accent_color', $news->accent_color ?: '#c31432') }}"
                            oninput="document.getElementById('accent_color_text').value = this.value"
                            class="h-10 w-16 rounded border-gray-300" aria-label="Wybierz własny kolor akcentu">
                        <input type="text" id="accent_color_text" name="accent_color" value="{{ old('accent_color', $news->accent_color) }}"
                            placeholder="np. #0d7d4d — puste = jak obok"
                            oninput="if (/^#[0-9a-fA-F]{6}$/.test(this.value)) document.getElementById('accent_color_picker').value = this.value"
                            class="w-48 rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                    </div>
                    <p class="mt-1 text-xs text-muted">Nadpisuje kolorystykę tej strony dowolnym kolorem (ma pierwszeństwo przed grupą docelową obok). Zbyt jasny kolor zostanie przyciemniony przy zapisie (kontrast WCAG). Puste = kolor z grupy docelowej.</p>
                    @error('accent_color') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold">Treść</label>
                @include('admin.partials.editor', ['name' => 'content', 'value' => old('content', $news->content)])
                @error('content') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
            @php $categoryColor = optional($newsCategories->firstWhere('id', old('news_category_id', $news->news_category_id)))->badgeColor(); @endphp
            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <label for="news_category_id" class="mb-1 block text-sm font-bold">Kategoria</label>
                    <div class="flex items-center gap-2">
                        <span id="category-color-preview" @class(['h-5 w-5 flex-none rounded-full ring-1 ring-gray-900/10', 'hidden' => empty($categoryColor)])
                            style="background-color: {{ $categoryColor }}" aria-hidden="true"></span>
                        <select id="news_category_id" name="news_category_id" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            <option value="">— brak —</option>
                            @foreach ($newsCategories as $category)
                                <option value="{{ $category->id }}" data-color="{{ $category->badgeColor() }}" {{ (int) old('news_category_id', $news->news_category_id) === $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @error('news_category_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="project_id" class="mb-1 block text-sm font-bold">Projekt <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <select id="project_id" name="project_id" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        <option value="">— brak —</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ (int) old('project_id', $news->project_id) === $project->id ? 'selected' : '' }}>
                                {{ $project->title }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-muted">News pojawi się jako aktualność na stronie tego projektu.</p>
                    @error('project_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="published_at" class="mb-1 block text-sm font-bold">Opublikowano od</label>
                    <input type="datetime-local" id="published_at" name="published_at"
                        value="{{ old('published_at', optional($news->published_at ?? now())->format('Y-m-d\TH:i')) }}" required
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('published_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <fieldset class="rounded-lg border border-gray-200 bg-gray-50/70 p-4">
                <legend class="px-1 text-sm font-bold text-ink">Status publikacji</legend>
                <div class="flex flex-wrap gap-x-6 gap-y-3">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_published" value="1" {{ old('is_published', $news->is_published ?? true) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-brand focus:ring-brand">
                        <span class="text-sm font-bold">Opublikowany</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $news->is_featured ?? false) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-amber-500 focus:ring-amber-400">
                        <span class="flex items-center gap-1 text-sm font-bold"><i class="fa-solid fa-star text-amber-400" aria-hidden="true"></i> Wyróżnij news</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_archived" value="1" {{ old('is_archived', $news->is_archived ?? false) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-brand focus:ring-brand">
                        <span class="flex items-center gap-1 text-sm font-bold"><i class="fa-solid fa-clock-rotate-left text-muted" aria-hidden="true"></i> Treść archiwalna</span>
                    </label>
                </div>
                <p class="mt-3 text-xs text-muted">Wyróżniony news jest prezentowany w złotej ramce na liście aktualności, stronie głównej i stronie projektu.</p>
            </fieldset>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="space-y-3">
                    @if ($news->exists && $news->image_url)
                        <div>
                            <p class="mb-1 text-sm font-bold">Obecne zdjęcie</p>
                            <img src="{{ $news->image_url }}" alt="{{ $news->image_alt ?: $news->title }}" class="h-32 w-full rounded object-cover">
                            @if ($news->image_width && $news->image_height)
                                <p class="mt-1 text-xs text-muted">Wymiary: {{ $news->image_width }} × {{ $news->image_height }} px</p>
                            @endif
                        </div>
                    @endif

                    <div>
                        <label for="image" class="mb-1 block text-sm font-bold">{{ $news->exists ? 'Zmień zdjęcie' : 'Zdjęcie' }}</label>
                        <input type="file" id="image" name="image" accept="image/*"
                            class="block w-full cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
                        <p id="image-dimensions-preview" class="mt-1 text-xs text-muted"></p>
                        @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="image_alt" class="mb-1 block text-sm font-bold">Opis alternatywny zdjęcia</label>
                        <input type="text" id="image_alt" name="image_alt" value="{{ old('image_alt', $news->image_alt) }}"
                            placeholder="np. Uczestnicy warsztatu przy laptopach w sali szkoleniowej"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        <p class="mt-1 text-xs text-muted">Opisz, co przedstawia zdjęcie — czytają to osoby korzystające z czytników ekranu.</p>
                        @error('image_alt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="rounded-lg border border-dashed border-gray-300 p-3" x-data="unsplashPicker('{{ route('admin.multimedia.unsplash.search') }}')">
                        <p class="mb-2 text-sm font-bold"><i class="fa-solid fa-camera-retro text-muted" aria-hidden="true"></i> …lub pobierz z Unsplash</p>
                        @if (config('services.unsplash.access_key'))
                            <div class="flex gap-2">
                                <input type="text" x-model="query" @keydown.enter.prevent="search()" placeholder="Szukaj zdjęć, np. edukacja"
                                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                <button type="button" @click="search()" :disabled="loading"
                                    class="flex-none rounded bg-brand px-3 py-2 text-sm font-bold text-white hover:bg-brand-dark disabled:opacity-50">
                                    <span x-show="!loading">Szukaj</span><span x-show="loading" x-cloak>…</span>
                                </button>
                            </div>
                            <p x-show="error" x-cloak x-text="error" class="mt-2 text-xs text-red-600"></p>

                            <div x-show="results.length" x-cloak class="mt-3 grid max-h-56 grid-cols-3 gap-2 overflow-y-auto">
                                <template x-for="photo in results" :key="photo.id">
                                    <button type="button" @click="pick(photo)"
                                        class="relative overflow-hidden rounded border-2"
                                        :class="selected && selected.id === photo.id ? 'border-brand' : 'border-transparent hover:border-gray-300'">
                                        <img :src="photo.thumb_url" alt="" class="h-16 w-full object-cover">
                                        <span class="absolute inset-x-0 bottom-0 truncate bg-black/50 px-1 py-0.5 text-[10px] text-white" x-text="photo.author_name"></span>
                                    </button>
                                </template>
                            </div>

                            <div x-show="selected" x-cloak class="mt-3 flex items-center gap-3 rounded bg-brand-light p-2">
                                <img :src="selected?.thumb_url" alt="" class="h-12 w-16 flex-none rounded object-cover">
                                <div class="min-w-0 text-xs">
                                    <p class="font-bold text-ink">Wybrano zdjęcie z Unsplash</p>
                                    <p class="text-muted">Autor: <span x-text="selected?.author_name"></span> — zapisz, aby pobrać.</p>
                                </div>
                                <button type="button" @click="clear()" class="ml-auto flex-none text-xs font-bold text-red-600 hover:text-red-700">Wyczyść</button>
                            </div>

                            <input type="hidden" name="unsplash_full_url" :value="selected ? selected.full_url : ''">
                            <input type="hidden" name="unsplash_download_location" :value="selected ? selected.download_location : ''">
                            <input type="hidden" name="unsplash_author" :value="selected ? selected.author_name : ''">
                            <input type="hidden" name="unsplash_alt" :value="selected ? selected.alt : ''">
                        @else
                            <p class="text-xs text-muted">Aby pobierać zdjęcia z Unsplash, ustaw <code class="rounded bg-gray-100 px-1">UNSPLASH_ACCESS_KEY</code> w pliku <code class="rounded bg-gray-100 px-1">.env</code> (darmowy klucz: unsplash.com/developers).</p>
                        @endif
                    </div>
            </div>
        </div>

        @include('admin.partials.seo-fields', ['model' => $news])

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">Zapisz</button>
            <a href="{{ route('admin.newsy.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
        </div>
    </form>
        </div>{{-- /tab-panel: edit --}}

        @if ($news->exists)
            <div data-tab-panel="etr" class="hidden">
                @php $etrModel = $news->etr; @endphp
                <div class="mb-4 rounded-xl border border-sky-100 bg-sky-50 p-4 text-sm text-sky-800">
                    <strong>Wersja ETR (łatwa do czytania)</strong> — uproszczony tekst dla osób z trudnościami w czytaniu.
                    Gdy włączysz ETR, na stronie artykułu pojawi się przycisk pozwalający przełączyć się na prostszą wersję.
                    <a href="{{ route('etr.about') }}" target="_blank" class="ml-1 underline hover:text-sky-900">Co to jest ETR? →</a>
                </div>

                <form method="POST" action="{{ route('admin.etr.update', ['type' => 'news', 'id' => $news->id]) }}"
                    class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    @csrf @method('PUT')

                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_enabled" value="1"
                            {{ old('is_enabled', $etrModel?->is_enabled ?? false) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                        <span class="font-bold text-ink">Włącz wersję ETR dla tego artykułu</span>
                    </label>

                    <div>
                        <label for="etr_title" class="mb-1 block text-sm font-bold">Tytuł (uproszczony) <span class="font-normal text-muted">— opcjonalny, zastąpi oryginalny tytuł w widoku ETR</span></label>
                        <input type="text" id="etr_title" name="etr_title"
                            value="{{ old('etr_title', $etrModel?->etr_title) }}"
                            placeholder="{{ $news->title }}"
                            class="w-full rounded border-gray-300 focus:border-sky-500 focus:ring-sky-500">
                    </div>

                    <div>
                        <label for="etr_summary" class="mb-1 block text-sm font-bold">Wstęp <span class="font-normal text-muted">— 1–3 zdania prostym językiem</span></label>
                        <textarea id="etr_summary" name="etr_summary" rows="3"
                            placeholder="Krótkie, proste wyjaśnienie o czym jest ten artykuł."
                            class="w-full rounded border-gray-300 focus:border-sky-500 focus:ring-sky-500">{{ old('etr_summary', $etrModel?->etr_summary) }}</textarea>
                    </div>

                    <div>
                        <label for="etr_content" class="mb-1 block text-sm font-bold">Treść ETR <span class="font-normal text-muted">— prosty tekst, jedno zdanie w akapicie</span></label>
                        <textarea id="etr_content" name="etr_content" rows="12"
                            placeholder="Pisz prostymi słowami.&#10;&#10;Krótkie zdania.&#10;&#10;Jedna myśl — jeden akapit."
                            class="w-full rounded border-gray-300 font-mono text-sm focus:border-sky-500 focus:ring-sky-500">{{ old('etr_content', $etrModel?->etr_content) }}</textarea>
                        <p class="mt-1 text-xs text-muted">Puste wiersze tworzą nowe akapity. Nie używaj formatowania HTML.</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="rounded bg-sky-600 px-5 py-2 text-sm font-bold text-white hover:bg-sky-700">Zapisz ETR</button>
                        @if ($etrModel)
                            <form method="POST" action="{{ route('admin.etr.destroy', ['type' => 'news', 'id' => $news->id]) }}"
                                onsubmit="return confirm('Usuń całą wersję ETR tego artykułu?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-sm font-bold text-red-600 hover:text-red-700">Usuń ETR</button>
                            </form>
                        @endif
                    </div>
                </form>
            </div>

            <div data-tab-panel="files" class="hidden">
                @include('admin.partials.attachments', [
                    'attachments' => $news->attachments,
                    'storeRoute' => route('admin.newsy.pliki.store', $news),
                ])
            </div>
        @endif
    </div>{{-- /editor-tabs --}}

    <script>
        (function () {
            const tabs = document.querySelector('[data-editor-tabs]');
            if (!tabs) return;
            const buttons = tabs.querySelectorAll('[data-tab-btn]');
            const panels = tabs.querySelectorAll('[data-tab-panel]');
            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    buttons.forEach(function (b) {
                        const active = b === btn;
                        b.classList.toggle('border-brand', active);
                        b.classList.toggle('text-brand', active);
                        b.classList.toggle('border-transparent', !active);
                        b.classList.toggle('text-muted', !active);
                    });
                    panels.forEach(function (panel) {
                        panel.classList.toggle('hidden', panel.dataset.tabPanel !== btn.dataset.tabBtn);
                    });
                });
            });
        })();

        document.getElementById('news_category_id').addEventListener('change', function (event) {
            const color = event.target.selectedOptions[0].dataset.color || '';
            const preview = document.getElementById('category-color-preview');
            preview.style.backgroundColor = color;
            preview.classList.toggle('hidden', color === '');
        });

        document.getElementById('image').addEventListener('change', function (event) {
            const preview = document.getElementById('image-dimensions-preview');
            const file = event.target.files[0];

            if (!file) {
                preview.textContent = '';
                return;
            }

            const image = new Image();
            image.onload = () => {
                preview.textContent = `Wymiary wybranego pliku: ${image.naturalWidth} × ${image.naturalHeight} px`;
                URL.revokeObjectURL(image.src);
            };
            image.src = URL.createObjectURL(file);
        });

        // Wyszukiwarka i wybór zdjęcia z Unsplash w formularzu newsa.
        window.unsplashPicker = (searchUrl) => ({
            query: '',
            results: [],
            selected: null,
            loading: false,
            error: '',
            async search() {
                if (!this.query.trim()) return;
                this.loading = true;
                this.error = '';
                this.results = [];
                try {
                    const response = await fetch(`${searchUrl}?q=${encodeURIComponent(this.query)}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (!response.ok) {
                        this.error = response.status === 501
                            ? 'Integracja z Unsplash nie jest skonfigurowana.'
                            : 'Nie udało się pobrać wyników z Unsplash.';
                        return;
                    }
                    const data = await response.json();
                    this.results = data;
                    if (!data.length) this.error = 'Brak wyników dla tego zapytania.';
                } catch (e) {
                    this.error = 'Błąd połączenia z Unsplash.';
                } finally {
                    this.loading = false;
                }
            },
            pick(photo) {
                this.selected = photo;
                // Wybór z Unsplash i plik wykluczają się — wyczyść wgrany plik.
                const fileInput = document.getElementById('image');
                if (fileInput) fileInput.value = '';
            },
            clear() {
                this.selected = null;
            },
        });
    </script>
@endsection
