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

    @if (session('preview_url'))
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
            <p class="flex items-center gap-2 text-sm font-bold text-amber-800">
                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                Aktualność utworzona na podstawie wydarzenia — sprawdź podgląd przed publikacją.
            </p>
            <a href="{{ session('preview_url') }}" target="_blank" rel="noopener"
                class="inline-flex items-center gap-2 rounded bg-amber-700 px-4 py-1.5 text-sm font-bold text-white hover:bg-amber-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700">
                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Zobacz podgląd
            </a>
        </div>
    @endif

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

            @php
                $extraOpen = $errors->has('audience') || $errors->has('accent_color')
                    || $errors->has('excerpt') || $errors->has('tags')
                    || filled(old('tags')) || filled(old('excerpt'))
                    || ($news->exists && filled($news->accent_color))
                    || ($news->exists && !in_array($news->audience ?? 'brand', ['brand', '']))
                    || ($news->exists && filled($news->excerpt))
                    || ($news->exists && $news->relationLoaded('tags') && $news->tags->isNotEmpty());
            @endphp
            <div x-data="{ extraOpen: {{ $extraOpen ? 'true' : 'false' }} }" class="-mx-6">
                <button type="button" @click="extraOpen = !extraOpen" :aria-expanded="extraOpen"
                    class="flex w-full items-center gap-2 border-t border-gray-100 px-6 py-3 text-sm font-bold text-muted hover:bg-gray-50 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand">
                    <i class="fa-solid fa-sliders text-xs" aria-hidden="true"></i>
                    Dodatkowe
                    <i class="fa-solid fa-chevron-down ml-auto text-xs transition-transform duration-200" :class="{ 'rotate-180': extraOpen }" aria-hidden="true"></i>
                </button>
                <div x-show="extraOpen" x-cloak class="px-6 py-4">
                    <div class="mb-5 grid gap-5 sm:grid-cols-2">
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
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-bold">Treść</label>
                @include('admin.partials.editor', ['name' => 'content', 'value' => old('content', $news->content), 'revisionable' => $news->exists ? ['type' => 'news', 'id' => $news->id] : null])
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
                    @if ($news->exists)
                        <label class="flex items-center gap-2">
                            <input type="hidden" name="is_clone" value="0">
                            <input type="checkbox" name="is_clone" value="1" {{ old('is_clone', $news->is_clone ?? false) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-amber-500 focus:ring-amber-400">
                            <span class="flex items-center gap-1 text-sm font-bold"><i class="fa-solid fa-copy text-amber-500" aria-hidden="true"></i> Kopia</span>
                        </label>
                    @endif
                </div>
                <p class="mt-3 text-xs text-muted">Wyróżniony news jest prezentowany w złotej ramce na liście aktualności, stronie głównej i stronie projektu.</p>
            </fieldset>

            @php $hasUnsplash = (bool) config('services.unsplash.access_key'); @endphp
            <div x-data="imagePickerModal('{{ route('admin.multimedia.unsplash.search') }}')"
                 @keydown.escape.window="open && close()">

                {{-- ===== Podgląd + przycisk otwierający ===== --}}
                <p class="mb-1 text-sm font-bold">Zdjęcie</p>
                <div class="space-y-3">
                    {{-- Aktualnie zapisane zdjęcie (serwer) --}}
                    @if ($news->exists && $news->image_url)
                        <div x-show="!localPreview && !unsplashThumb && !libraryThumb && !deleteImage">
                            <img src="{{ $news->image_url }}" alt="{{ $news->image_alt ?: $news->title }}"
                                class="h-36 w-full rounded-lg border border-gray-200 object-cover">
                            @if ($news->image_width && $news->image_height)
                                <p class="mt-1 text-xs text-muted">Obecne: {{ $news->image_width }} × {{ $news->image_height }} px</p>
                            @endif
                            <button type="button" x-show="!localPreview && !unsplashThumb && !libraryThumb" @click="deleteImage = true"
                                class="mt-1 inline-flex items-center gap-1 text-xs font-bold text-red-600 hover:text-red-700">
                                <i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń zdjęcie
                            </button>
                        </div>
                        <div x-show="deleteImage && !localPreview && !unsplashThumb && !libraryThumb" x-cloak
                            class="flex items-center justify-between rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm">
                            <span class="text-red-700"><i class="fa-solid fa-triangle-exclamation mr-1" aria-hidden="true"></i>Zdjęcie zostanie usunięte po zapisaniu.</span>
                            <button type="button" @click="deleteImage = false" class="text-xs font-bold text-red-600 underline hover:text-red-700">Cofnij</button>
                        </div>
                        <input type="hidden" name="delete_image" :value="deleteImage && !localPreview && !unsplashThumb && !libraryThumb ? '1' : '0'">
                    @endif

                    {{-- Podgląd nowo wybranego (Alpine) --}}
                    <div x-show="localPreview || unsplashThumb || libraryThumb" x-cloak class="relative">
                        <img :src="localPreview || unsplashThumb || libraryThumb" alt=""
                            class="h-36 w-full rounded-lg border border-gray-200 object-cover">
                        <button type="button" @click="clearImage()"
                            class="absolute right-2 top-2 rounded bg-black/60 px-2 py-1 text-xs font-bold text-white hover:bg-black/80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                            aria-label="Usuń wybrane zdjęcie">
                            <i class="fa-solid fa-xmark mr-1" aria-hidden="true"></i>Usuń
                        </button>
                        <p x-show="unsplashAuthor" class="mt-1 text-xs text-muted">Unsplash · autor: <span x-text="unsplashAuthor"></span></p>
                        <p id="image-dimensions-preview" class="mt-1 text-xs text-muted"></p>
                    </div>

                    <button type="button" @click="openModal()" data-modal-trigger
                        class="inline-flex items-center gap-2 rounded border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-ink hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                        <i class="fa-solid fa-image text-muted" aria-hidden="true"></i>
                        <span x-text="localPreview || unsplashThumb || libraryThumb || {{ $news->exists && $news->image_url ? 'true' : 'false' }} ? 'Zmień zdjęcie' : 'Wybierz zdjęcie'"></span>
                    </button>
                    @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                    {{-- Input file — w DOM dzięki x-show (nie x-if), niewidoczny, wciąż w formularzu --}}
                    <input type="file" id="image" name="image" accept="image/*"
                        class="absolute opacity-0 h-0 w-0 overflow-hidden" tabindex="-1" aria-hidden="true"
                        @change="onFileChange($event)">

                    {{-- Ukryte pola Unsplash --}}
                    <input type="hidden" name="unsplash_full_url" :value="unsplashFull">
                    <input type="hidden" name="unsplash_download_location" :value="unsplashDownloadLocation">
                    <input type="hidden" name="unsplash_author" :value="unsplashAuthor">
                    <input type="hidden" name="unsplash_alt" :value="unsplashAlt">
                    {{-- Ukryte pola biblioteki multimediów --}}
                    <input type="hidden" name="library_media_id" :value="libraryMediaId">
                    <input type="hidden" name="library_alt" :value="libraryAlt">
                </div>

                <div class="mt-4">
                    <label for="image_alt" class="mb-1 block text-sm font-bold">Opis alternatywny zdjęcia</label>
                    <input type="text" id="image_alt" name="image_alt" value="{{ old('image_alt', $news->image_alt) }}"
                        placeholder="np. Uczestnicy warsztatu przy laptopach w sali szkoleniowej"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Opisz, co przedstawia zdjęcie — czytają to osoby korzystające z czytników ekranu.</p>
                    @error('image_alt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- ============================= MODAL ============================= --}}
                <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:p-8" style="display:none">
                    <div class="fixed inset-0 bg-ink/60" @click="close()" aria-hidden="true"></div>

                    <div x-ref="dialog" role="dialog" aria-modal="true" aria-labelledby="img-modal-title"
                         @keydown.tab="trapTab($event)"
                         class="relative z-10 my-4 w-full max-w-2xl rounded-xl border border-gray-200 bg-white shadow-2xl">

                        {{-- Nagłówek --}}
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                            <h2 id="img-modal-title" class="text-lg font-bold text-ink">Wybierz zdjęcie</h2>
                            <button type="button" @click="close()"
                                class="rounded p-2 text-muted hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                <span class="sr-only">Zamknij okno</span>
                            </button>
                        </div>

                        {{-- Zakładki --}}
                        <div class="flex gap-1 border-b border-gray-100 px-6" role="tablist" aria-label="Źródło zdjęcia">
                            <button type="button" role="tab" id="tab-file"
                                :aria-selected="tab === 'file'" :tabindex="tab === 'file' ? 0 : -1"
                                @click="tab = 'file'"
                                :class="tab === 'file' ? 'border-b-2 border-brand text-brand font-bold' : 'text-muted hover:text-ink'"
                                class="mr-6 py-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                <i class="fa-solid fa-upload mr-1" aria-hidden="true"></i> Plik z dysku
                            </button>
                            @if ($hasUnsplash)
                            <button type="button" role="tab" id="tab-unsplash"
                                :aria-selected="tab === 'unsplash'" :tabindex="tab === 'unsplash' ? 0 : -1"
                                @click="tab = 'unsplash'"
                                :class="tab === 'unsplash' ? 'border-b-2 border-brand text-brand font-bold' : 'text-muted hover:text-ink'"
                                class="py-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                <i class="fa-solid fa-camera-retro mr-1" aria-hidden="true"></i> Unsplash
                            </button>
                            @else
                            <span class="py-3 text-sm text-gray-300 cursor-not-allowed" title="Skonfiguruj UNSPLASH_ACCESS_KEY, aby odblokować">
                                <i class="fa-solid fa-camera-retro mr-1" aria-hidden="true"></i> Unsplash
                            </span>
                            @endif
                            <button type="button" role="tab" id="tab-library"
                                :aria-selected="tab === 'library'" :tabindex="tab === 'library' ? 0 : -1"
                                aria-controls="tabpanel-library"
                                @click="tab = 'library'; !libraryLoaded && loadLibrary()"
                                :class="tab === 'library' ? 'border-b-2 border-brand text-brand font-bold' : 'text-muted hover:text-ink'"
                                class="ml-4 py-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                <i class="fa-solid fa-photo-film mr-1" aria-hidden="true"></i> Biblioteka
                            </button>
                        </div>

                        <div class="p-6">
                            {{-- Tab: Plik z dysku --}}
                            <div x-show="tab === 'file'" role="tabpanel" aria-labelledby="tab-file" class="space-y-4">
                                <div x-show="!cropMode">
                                    <p class="mb-3 text-sm text-muted">Kliknij poniżej, aby wybrać zdjęcie z komputera. JPG, PNG, WebP, max 2 MB.</p>
                                    <p class="mb-3 text-xs text-muted"><i class="fa-solid fa-circle-info mr-1" aria-hidden="true"></i>System automatycznie konwertuje przesłane zdjęcia do formatu WebP.</p>
                                    <button type="button"
                                        @click="$el.closest('[x-data]').querySelector('#image').click()"
                                        class="inline-flex items-center gap-2 rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                        <i class="fa-solid fa-folder-open" aria-hidden="true"></i> Wybierz plik
                                    </button>
                                    <span x-show="fileName && !cropMode" x-cloak x-text="fileName" class="ml-3 text-sm text-muted"></span>
                                </div>

                                {{-- Podgląd po wyborze / po kadrowaniu --}}
                                <div x-show="localPreview && !cropMode" x-cloak>
                                    <img :src="localPreview" alt="" class="max-h-64 w-full rounded-lg border border-gray-200 object-contain bg-gray-50">
                                    <p x-text="fileDimensions" class="mt-1 text-xs text-muted"></p>
                                </div>

                                {{-- Kadrowanie --}}
                                <div x-show="cropMode" x-cloak class="space-y-3">
                                    <p class="text-sm font-medium text-ink">Zaznacz obszar do zachowania</p>
                                    <p class="text-xs text-muted">Przeciągnij ramkę lub jej narożniki, aby wybrać kadr.</p>
                                    <div class="rounded-lg border border-gray-200 bg-gray-50" style="max-height:320px;">
                                        <img :src="cropImgSrc" data-crop-img alt="" style="display:block;max-width:100%;max-height:320px;">
                                    </div>
                                    <div class="flex gap-2 pt-1">
                                        <button type="button" @click="confirmCrop()"
                                            class="inline-flex items-center gap-2 rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                            <i class="fa-solid fa-crop-simple" aria-hidden="true"></i> Przytnij
                                        </button>
                                        <button type="button" @click="skipCrop()"
                                            class="rounded border border-gray-300 px-4 py-2 text-sm font-bold text-ink hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                            Pomiń kadrowanie
                                        </button>
                                    </div>
                                </div>

                                <div x-show="!cropMode" class="flex gap-3 border-t border-gray-100 pt-4">
                                    <button type="button" @click="close()" :disabled="!localPreview"
                                        :class="localPreview ? 'bg-brand hover:bg-brand-dark cursor-pointer' : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                                        class="rounded px-5 py-2 text-sm font-bold text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                        Wstaw
                                    </button>
                                    <button type="button" @click="close()" class="text-sm text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand rounded px-2">Anuluj</button>
                                </div>
                            </div>

                            {{-- Tab: Unsplash --}}
                            @if ($hasUnsplash)
                            <div x-show="tab === 'unsplash'" role="tabpanel" aria-labelledby="tab-unsplash" class="space-y-4">
                                <div class="flex gap-2">
                                    <label for="unsplash-query" class="sr-only">Szukaj w Unsplash</label>
                                    <input type="text" id="unsplash-query" x-model="query"
                                        @keydown.enter.prevent="search()"
                                        placeholder="Szukaj zdjęć, np. edukacja, warsztaty, sport…"
                                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                    <button type="button" @click="search()" :disabled="loading"
                                        class="flex-none rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                        <span x-show="!loading">Szukaj</span>
                                        <span x-show="loading" x-cloak aria-live="polite">…</span>
                                    </button>
                                </div>

                                <p x-show="error" x-cloak x-text="error" class="text-xs text-red-600" role="alert"></p>

                                <div x-show="results.length" x-cloak
                                    class="grid max-h-72 grid-cols-3 gap-2 overflow-y-auto sm:grid-cols-4"
                                    role="list" aria-label="Wyniki wyszukiwania Unsplash">
                                    <template x-for="photo in results" :key="photo.id">
                                        <button type="button" @click="pickUnsplash(photo)" role="listitem"
                                            :aria-pressed="unsplashSelected && unsplashSelected.id === photo.id"
                                            :class="unsplashSelected && unsplashSelected.id === photo.id ? 'border-brand ring-1 ring-brand' : 'border-transparent hover:border-gray-300'"
                                            class="relative overflow-hidden rounded border-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                            <img :src="photo.thumb_url" :alt="'Zdjęcie autora ' + photo.author_name" class="h-20 w-full object-cover">
                                            <span class="absolute inset-x-0 bottom-0 truncate bg-black/60 px-1 py-0.5 text-[10px] text-white" x-text="photo.author_name"></span>
                                            <span x-show="unsplashSelected && unsplashSelected.id === photo.id"
                                                class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-brand text-white" aria-hidden="true">
                                                <i class="fa-solid fa-check text-[10px]"></i>
                                            </span>
                                        </button>
                                    </template>
                                </div>

                                <div x-show="unsplashSelected" x-cloak
                                    class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-3">
                                    <img :src="unsplashSelected?.thumb_url" alt="" class="h-12 w-16 flex-none rounded object-cover">
                                    <div class="min-w-0 text-xs">
                                        <p class="font-bold text-ink">Wybrano</p>
                                        <p class="text-muted">Autor: <span x-text="unsplashSelected?.author_name"></span></p>
                                    </div>
                                    <button type="button" @click="unsplashSelected = null"
                                        class="ml-auto text-xs font-bold text-red-600 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 rounded px-1">
                                        Wyczyść
                                    </button>
                                </div>

                                <div class="flex gap-3 border-t border-gray-100 pt-4">
                                    <button type="button" @click="confirmUnsplash()" :disabled="!unsplashSelected"
                                        :class="unsplashSelected ? 'bg-brand hover:bg-brand-dark cursor-pointer' : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                                        class="rounded px-5 py-2 text-sm font-bold text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                        Wstaw
                                    </button>
                                    <button type="button" @click="close()" class="text-sm text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand rounded px-2">Anuluj</button>
                                </div>
                            </div>
                            @endif

                            {{-- Tab: Biblioteka multimediów --}}
                            <div id="tabpanel-library" x-show="tab === 'library'" role="tabpanel" aria-labelledby="tab-library" class="space-y-4">

                                <div x-show="libraryLoading" class="flex justify-center py-10">
                                    <span class="text-sm text-muted" aria-live="polite" aria-busy="true">Ładowanie biblioteki…</span>
                                </div>

                                <template x-if="!libraryLoading && libraryResults.length === 0">
                                    <p class="py-6 text-center text-sm text-muted">
                                        Brak zdjęć w bibliotece multimediów.
                                        <a href="{{ route('admin.multimedia.index') }}" target="_blank" rel="noopener"
                                            class="font-bold text-brand hover:text-brand-dark">Otwórz bibliotekę</a>
                                    </p>
                                </template>

                                <template x-if="!libraryLoading && libraryResults.length > 0">
                                    <div class="space-y-3">
                                        <div>
                                            <label for="lib-search" class="sr-only">Filtruj zdjęcia biblioteki</label>
                                            <input type="text" id="lib-search" x-model="librarySearch"
                                                placeholder="Filtruj po nazwie lub opisie…"
                                                class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                        </div>
                                        <div class="grid max-h-72 grid-cols-3 gap-2 overflow-y-auto sm:grid-cols-4"
                                            role="list" aria-label="Zdjęcia z biblioteki multimediów">
                                            <template x-for="img in libraryResults.filter(i => !librarySearch.trim() || (i.file_name + ' ' + (i.alt || '')).toLowerCase().includes(librarySearch.toLowerCase()))" :key="img.id">
                                                <button type="button" @click="pickLibrary(img)" role="listitem"
                                                    :aria-pressed="librarySelected && librarySelected.id === img.id"
                                                    :aria-label="'Wybierz: ' + (img.alt || img.file_name)"
                                                    :class="librarySelected && librarySelected.id === img.id ? 'border-brand ring-1 ring-brand' : 'border-transparent hover:border-gray-300'"
                                                    class="relative overflow-hidden rounded border-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                                    :title="img.alt || img.file_name">
                                                    <img :src="img.url" :alt="img.alt || img.file_name" class="h-20 w-full object-cover">
                                                    <span x-show="librarySelected && librarySelected.id === img.id"
                                                        class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-brand text-white" aria-hidden="true">
                                                        <i class="fa-solid fa-check text-[10px]"></i>
                                                    </span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <div x-show="librarySelected" x-cloak
                                    class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-3">
                                    <img :src="librarySelected?.url" alt="" class="h-12 w-16 flex-none rounded object-cover">
                                    <div class="min-w-0 text-xs">
                                        <p class="font-bold text-ink">Wybrano</p>
                                        <p class="truncate text-muted" x-text="librarySelected?.alt || librarySelected?.file_name"></p>
                                    </div>
                                    <button type="button" @click="librarySelected = null"
                                        class="ml-auto rounded px-1 text-xs font-bold text-red-600 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500">
                                        Wyczyść
                                    </button>
                                </div>

                                <div class="flex gap-3 border-t border-gray-100 pt-4">
                                    <button type="button" @click="confirmLibrary()" :disabled="!librarySelected"
                                        :class="librarySelected ? 'bg-brand hover:bg-brand-dark cursor-pointer' : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                                        class="rounded px-5 py-2 text-sm font-bold text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                        Wstaw
                                    </button>
                                    <button type="button" @click="close()" class="rounded px-2 text-sm text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">Anuluj</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('admin.partials.seo-fields', ['model' => $news])

        <div x-data="{ saving: false }" class="flex items-center gap-3">
            <button type="submit" :disabled="saving" @click="saving = true"
                class="inline-flex items-center gap-2 rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark disabled:opacity-60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <svg x-show="saving" x-cloak class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="saving ? 'Zapisywanie…' : 'Zapisz'"></span>
            </button>
            <a href="{{ route('admin.newsy.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
            @if ($news->exists)
                <form method="POST" action="{{ route('admin.newsy.klonuj', $news) }}" class="ml-auto"
                    data-confirm="Sklonować news „{{ $news->title }}"? Kopia zostanie zapisana jako szkic.">
                    @csrf
                    <button type="submit" class="text-sm font-bold text-muted hover:text-brand">
                        <i class="fa-solid fa-copy mr-1" aria-hidden="true"></i>Klonuj
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.newsy.destroy', $news) }}"
                    data-confirm="Usunąć news „{{ $news->title }}"? Operacji nie można cofnąć."
                    @if (($clonesCount ?? 0) > 0) data-clone-count="{{ $clonesCount }}" @endif>
                    @csrf @method('DELETE')
                    <button type="submit" class="text-sm font-bold text-red-600 hover:text-red-700">
                        <i class="fa-solid fa-trash mr-1" aria-hidden="true"></i>Usuń news
                    </button>
                </form>
            @endif
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

        // Modal wyboru zdjęcia: zakładka „Plik z dysku" + zakładka „Unsplash".
        window.imagePickerModal = (searchUrl) => ({
            open: false,
            tab: 'file',
            deleteImage: false,
            // Plik z dysku
            localPreview: null,
            fileName: '',
            fileDimensions: '',
            // Kadrowanie
            cropMode: false,
            cropImgSrc: '',
            cropperInstance: null,
            pendingFile: null,
            // Unsplash
            query: '',
            results: [],
            loading: false,
            error: '',
            unsplashSelected: null,
            unsplashThumb: '',
            unsplashAuthor: '',
            unsplashFull: '',
            unsplashDownloadLocation: '',
            unsplashAlt: '',
            // Biblioteka multimediów
            libraryResults: [],
            libraryLoaded: false,
            libraryLoading: false,
            librarySearch: '',
            librarySelected: null,
            libraryThumb: '',
            libraryMediaId: '',
            libraryAlt: '',

            openModal() {
                this.open = true;
                this.$nextTick(() => {
                    const sel = 'button:not([disabled]), input:not([disabled])';
                    const first = Array.from(this.$refs.dialog.querySelectorAll(sel))
                        .find(el => el.offsetParent !== null);
                    if (first) first.focus();
                });
            },

            close() {
                this.open = false;
                this.$nextTick(() => this.$el.querySelector('[data-modal-trigger]')?.focus());
            },

            trapTab(event) {
                const sel = 'a[href], button:not([disabled]), input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])';
                const items = Array.from(this.$refs.dialog.querySelectorAll(sel))
                    .filter(el => el.offsetParent !== null);
                if (!items.length) return;
                if (event.shiftKey && document.activeElement === items[0]) {
                    event.preventDefault(); items[items.length - 1].focus();
                } else if (!event.shiftKey && document.activeElement === items[items.length - 1]) {
                    event.preventDefault(); items[0].focus();
                }
            },

            onFileChange(event) {
                const file = event.target.files?.[0];
                if (!file) { this.localPreview = null; this.fileName = ''; return; }
                this.fileName = file.name;
                this.unsplashSelected = null; this.unsplashThumb = ''; this.unsplashFull = '';
                this.unsplashDownloadLocation = ''; this.unsplashAlt = ''; this.unsplashAuthor = '';
                this.librarySelected = null; this.libraryThumb = ''; this.libraryMediaId = ''; this.libraryAlt = '';
                if (!file.type.startsWith('image/')) { this.localPreview = null; return; }
                this.pendingFile = file;
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.cropImgSrc = e.target.result;
                    this.cropMode = true;
                    this.$nextTick(() => this._initCropper());
                };
                reader.readAsDataURL(file);
            },

            _initCropper() {
                const img = this.$el.querySelector('[data-crop-img]');
                if (!img) return;
                const init = () => {
                    if (this.cropperInstance) { this.cropperInstance.destroy(); this.cropperInstance = null; }
                    this.cropperInstance = new Cropper(img, {
                        viewMode: 1,
                        autoCropArea: 1,
                        responsive: true,
                        checkOrientation: false,
                    });
                };
                init();
            },

            confirmCrop() {
                if (!this.cropperInstance) return;
                const mime = this.pendingFile?.type === 'image/png' ? 'image/png' : 'image/jpeg';
                const ext  = mime === 'image/png' ? 'png' : 'jpg';
                const canvas = this.cropperInstance.getCroppedCanvas({ maxWidth: 2400, maxHeight: 2400 });
                canvas.toBlob((blob) => {
                    if (!blob) { this.skipCrop(); return; }
                    const croppedFile = new File([blob], `cropped.${ext}`, { type: mime });
                    const fi = this.$el.querySelector('#image');
                    if (fi && window.DataTransfer) {
                        const dt = new DataTransfer();
                        dt.items.add(croppedFile);
                        fi.files = dt.files;
                    }
                    this.localPreview = URL.createObjectURL(blob);
                    const tmp = new Image();
                    tmp.onload = () => { this.fileDimensions = `Wymiary po kadrowaniu: ${tmp.naturalWidth} × ${tmp.naturalHeight} px`; };
                    tmp.src = this.localPreview;
                    this._destroyCropper();
                }, mime, 0.92);
            },

            skipCrop() {
                const file = this.pendingFile;
                this._destroyCropper();
                if (!file) return;
                const reader = new FileReader();
                reader.onload = (e) => { this.localPreview = e.target.result; };
                reader.readAsDataURL(file);
                const url = URL.createObjectURL(file);
                const tmp = new Image();
                tmp.onload = () => { this.fileDimensions = `Wymiary: ${tmp.naturalWidth} × ${tmp.naturalHeight} px`; URL.revokeObjectURL(url); };
                tmp.src = url;
            },

            _destroyCropper() {
                if (this.cropperInstance) { this.cropperInstance.destroy(); this.cropperInstance = null; }
                this.cropMode = false;
                this.cropImgSrc = '';
                this.pendingFile = null;
            },

            clearImage() {
                this._destroyCropper();
                this.localPreview = null; this.fileName = ''; this.fileDimensions = '';
                const fi = this.$el.querySelector('#image');
                if (fi) fi.value = '';
                this.unsplashSelected = null; this.unsplashThumb = ''; this.unsplashAuthor = '';
                this.unsplashFull = ''; this.unsplashDownloadLocation = ''; this.unsplashAlt = '';
                this.librarySelected = null; this.libraryThumb = ''; this.libraryMediaId = ''; this.libraryAlt = '';
            },

            async search() {
                if (!this.query.trim()) return;
                this.loading = true; this.error = ''; this.results = [];
                try {
                    const r = await fetch(`${searchUrl}?q=${encodeURIComponent(this.query)}`, { headers: { 'Accept': 'application/json' } });
                    if (!r.ok) { this.error = r.status === 501 ? 'Integracja z Unsplash nie jest skonfigurowana.' : 'Nie udało się pobrać wyników.'; return; }
                    const data = await r.json();
                    this.results = data;
                    if (!data.length) this.error = 'Brak wyników dla tego zapytania.';
                } catch { this.error = 'Błąd połączenia z Unsplash.'; }
                finally { this.loading = false; }
            },

            pickUnsplash(photo) {
                this.unsplashSelected = photo;
                this.localPreview = null; this.fileName = '';
                const fi = this.$el.querySelector('#image');
                if (fi) fi.value = '';
                this.librarySelected = null;
            },

            confirmUnsplash() {
                if (!this.unsplashSelected) return;
                this.unsplashThumb = this.unsplashSelected.thumb_url;
                this.unsplashAuthor = this.unsplashSelected.author_name;
                this.unsplashFull = this.unsplashSelected.full_url;
                this.unsplashDownloadLocation = this.unsplashSelected.download_location;
                this.unsplashAlt = this.unsplashSelected.alt || '';
                this.librarySelected = null; this.libraryThumb = ''; this.libraryMediaId = ''; this.libraryAlt = '';
                this.close();
            },

            async loadLibrary() {
                if (this.libraryLoaded) return;
                this.libraryLoading = true;
                try {
                    const r = await fetch('{{ route('admin.multimedia.images') }}', { headers: { 'Accept': 'application/json' } });
                    this.libraryResults = await r.json();
                    this.libraryLoaded = true;
                } catch {}
                this.libraryLoading = false;
            },

            pickLibrary(img) {
                this.librarySelected = img;
            },

            confirmLibrary() {
                if (!this.librarySelected) return;
                this.libraryThumb = this.librarySelected.url;
                this.libraryMediaId = this.librarySelected.id;
                this.libraryAlt = this.librarySelected.alt || '';
                // Wyczyść inne tryby wyboru
                this.localPreview = null; this.fileName = ''; this.fileDimensions = '';
                const fi = this.$el.querySelector('#image');
                if (fi) fi.value = '';
                this.unsplashSelected = null; this.unsplashThumb = ''; this.unsplashFull = '';
                this.unsplashDownloadLocation = ''; this.unsplashAlt = ''; this.unsplashAuthor = '';
                this.close();
            },
        });
    </script>
@endsection
