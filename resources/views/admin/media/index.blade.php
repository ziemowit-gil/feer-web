@extends('admin.layout')

@section('title', 'Multimedia')

@section('content')
    @php
        $imageItems = $media->filter(fn ($i) => $i['is_image'])->values()->toArray();
    @endphp
    <div x-data="{
            view: localStorage.getItem('media-view') || 'list',
            lightbox: null,
            lightboxIdx: -1,
            lightboxLoaded: false,
            images: @js($imageItems),
            openLightbox(url) {
                const idx = this.images.findIndex(i => i.url === url);
                this.lightboxIdx = idx;
                this.lightboxLoaded = false;
                this.lightbox = idx >= 0 ? this.images[idx] : { url, alt: '', caption: url };
            },
            lightboxPrev() { if (this.lightboxIdx > 0) { this.lightboxLoaded = false; this.lightboxIdx--; this.lightbox = this.images[this.lightboxIdx]; } },
            lightboxNext() { if (this.lightboxIdx < this.images.length - 1) { this.lightboxLoaded = false; this.lightboxIdx++; this.lightbox = this.images[this.lightboxIdx]; } },
            selected: [],
            copiedId: null,
            pageIds: @js($media->pluck('id')->values()),
            toggle(id) { const i = this.selected.indexOf(id); if (i === -1) { this.selected.push(id); } else { this.selected.splice(i, 1); } },
            isSelected(id) { return this.selected.includes(id); },
            get allSelected() { return this.pageIds.length > 0 && this.pageIds.every(id => this.selected.includes(id)); },
            toggleAll() { if (this.allSelected) { this.selected = this.selected.filter(id => !this.pageIds.includes(id)); } else { this.pageIds.forEach(id => { if (!this.selected.includes(id)) this.selected.push(id); }); } },
            clearSelection() { this.selected = []; },
            async copy(url, id) { try { await navigator.clipboard.writeText(url); } catch (e) { window.prompt('Skopiuj adres URL:', url); return; } this.copiedId = id; setTimeout(() => { if (this.copiedId === id) this.copiedId = null; }, 2000); }
        }"
        x-init="$watch('view', value => localStorage.setItem('media-view', value))"
        @keydown.arrow-left.window="if (lightbox) lightboxPrev()"
        @keydown.arrow-right.window="if (lightbox) lightboxNext()">

        <div class="flex flex-col gap-6 lg:flex-row lg:items-start">

            {{-- ── Folder tree ─────────────────────────────────────────────── --}}
            <aside class="w-full flex-none lg:w-56">
                <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
                    <p class="mb-2 px-2 text-[10px] font-bold uppercase tracking-widest text-muted">Foldery</p>

                    <a href="{{ route('admin.multimedia.index', ['archived' => $showArchived ? 1 : null]) }}"
                        class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm {{ ! $folder ? 'bg-brand-light font-bold text-brand' : 'text-ink hover:bg-gray-100' }}">
                        <i class="fa-solid fa-photo-film w-4 text-brand" aria-hidden="true"></i>
                        <span class="flex-1">Wszystkie multimedia</span>
                    </a>

                    @include('admin.media.partials.folder-tree', [
                        'nodes' => $folderTree->get('', collect()),
                        'tree' => $folderTree,
                        'currentId' => $folder?->id,
                        'showArchived' => $showArchived,
                    ])
                </div>

                {{-- Tag filter sidebar --}}
                @if ($allTags->isNotEmpty())
                    <div class="mt-4 rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
                        <p class="mb-2 px-2 text-[10px] font-bold uppercase tracking-widest text-muted">Filtruj po tagu</p>
                        <div class="space-y-0.5">
                            <a href="{{ route('admin.multimedia.index', array_filter(['folder' => $folder?->id, 'archived' => $showArchived ? 1 : null, 'q' => $currentSearch])) }}"
                                class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm {{ ! $currentTag ? 'bg-brand-light font-bold text-brand' : 'text-ink hover:bg-gray-100' }}">
                                <i class="fa-solid fa-tags w-4 text-xs text-muted" aria-hidden="true"></i>
                                <span class="flex-1">Wszystkie tagi</span>
                            </a>
                            @foreach ($allTags as $t)
                                <a href="{{ route('admin.multimedia.index', array_filter(['folder' => $folder?->id, 'archived' => $showArchived ? 1 : null, 'q' => $currentSearch, 'tag' => $t])) }}"
                                    class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm {{ $currentTag === $t ? 'bg-brand-light font-bold text-brand' : 'text-ink hover:bg-gray-100' }}">
                                    <i class="fa-solid fa-tag w-4 text-xs text-muted" aria-hidden="true"></i>
                                    <span class="flex-1 truncate">{{ $t }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </aside>

            {{-- ── Main column ──────────────────────────────────────────────── --}}
            <div class="min-w-0 flex-1">

                {{-- Breadcrumbs --}}
                <nav aria-label="Ścieżka folderów" class="mb-4 flex flex-wrap items-center gap-1 text-sm text-muted">
                    <a href="{{ route('admin.multimedia.index', ['archived' => $showArchived ? 1 : null]) }}"
                        class="hover:text-brand {{ ! $folder ? 'font-bold text-ink' : '' }}">
                        <i class="fa-solid fa-folder-open" aria-hidden="true"></i> Multimedia
                    </a>
                    @foreach ($breadcrumbs as $crumb)
                        <span aria-hidden="true">/</span>
                        <a href="{{ route('admin.multimedia.index', ['folder' => $crumb->id, 'archived' => $showArchived ? 1 : null]) }}"
                            class="hover:text-brand {{ $loop->last ? 'font-bold text-ink' : '' }}">
                            {{ $crumb->name }}
                        </a>
                    @endforeach
                </nav>

                {{-- ── Toolbar ─────────────────────────────────────────────── --}}
                <div class="mb-4 rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
                    <div class="flex flex-wrap items-center gap-3">

                        {{-- New folder --}}
                        <form method="POST" action="{{ route('admin.multimedia.foldery.store') }}" class="flex items-center gap-2">
                            @csrf
                            <input type="hidden" name="parent_id" value="{{ $folder?->id }}">
                            <label class="sr-only" for="new-folder-name">Nazwa nowego folderu</label>
                            <input type="text" id="new-folder-name" name="name" placeholder="Nowy folder…" required
                                class="rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            <button type="submit"
                                class="flex items-center gap-1.5 rounded-lg border border-brand px-3 py-2 text-xs font-bold text-brand hover:bg-brand-light">
                                <i class="fa-solid fa-folder-plus" aria-hidden="true"></i> Utwórz
                            </button>
                        </form>

                        <div class="flex-1"></div>

                        {{-- View switch --}}
                        <div class="flex items-center divide-x divide-gray-300 overflow-hidden rounded-lg border border-gray-300"
                            role="group" aria-label="Widok multimediów">
                            <button type="button" @click="view = 'grid'"
                                :class="view === 'grid' ? 'bg-brand-light text-brand' : 'text-muted hover:bg-gray-100'"
                                class="px-3 py-2 text-xs" aria-label="Widok kafelków">
                                <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i>
                            </button>
                            <button type="button" @click="view = 'list'"
                                :class="view === 'list' ? 'bg-brand-light text-brand' : 'text-muted hover:bg-gray-100'"
                                class="px-3 py-2 text-xs" aria-label="Widok listy">
                                <i class="fa-solid fa-list" aria-hidden="true"></i>
                            </button>
                        </div>

                        <div class="h-5 w-px bg-gray-200" aria-hidden="true"></div>

                        {{-- Archive toggle --}}
                        <a href="{{ route('admin.multimedia.index', array_filter(['folder' => $folder?->id, 'archived' => $showArchived ? null : 1, 'q' => $currentSearch, 'tag' => $currentTag])) }}"
                            class="flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-bold {{ $showArchived ? 'border-brand bg-brand-light text-brand' : 'border-gray-300 text-muted hover:bg-gray-100' }}">
                            <i class="fa-solid fa-box-archive" aria-hidden="true"></i>
                            {{ $showArchived ? 'Aktywne pliki' : 'Archiwum' }}
                        </a>

                        {{-- Alt audit --}}
                        <a href="{{ route('admin.multimedia.alt-audit') }}"
                            class="flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-2 text-xs font-bold text-muted hover:bg-gray-100"
                            title="Audyt dostępności — pliki bez alt-text">
                            <i class="fa-solid fa-universal-access" aria-hidden="true"></i>
                            <span class="hidden sm:inline">Audyt alt</span>
                        </a>

                        <div class="h-5 w-px bg-gray-200" aria-hidden="true"></div>

                        {{-- Export --}}
                        <a href="{{ route('admin.multimedia.export', ['folder' => $folder?->id]) }}"
                            class="flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-2 text-xs font-bold text-muted hover:bg-gray-100"
                            title="{{ $folder ? 'Eksportuj folder jako ZIP' : 'Eksportuj wszystko jako ZIP' }}">
                            <i class="fa-solid fa-file-zipper" aria-hidden="true"></i>
                            <span class="hidden sm:inline">{{ $folder ? 'Eksportuj folder' : 'Eksportuj' }}</span>
                        </a>

                        {{-- Import --}}
                        <form method="POST" action="{{ route('admin.multimedia.import') }}" enctype="multipart/form-data"
                            x-data class="flex items-center">
                            @csrf
                            <input type="hidden" name="folder_id" value="{{ $folder?->id }}">
                            <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-2 text-xs font-bold text-muted hover:bg-gray-100"
                                title="Importuj archiwum ZIP z plikami">
                                <i class="fa-solid fa-file-import" aria-hidden="true"></i>
                                <span class="hidden sm:inline">Importuj ZIP</span>
                                <input type="file" name="archive" accept=".zip" required
                                    class="sr-only" @change="$el.form.requestSubmit()">
                            </label>
                        </form>

                    </div>
                </div>

                {{-- ── Search + filters ────────────────────────────────────── --}}
                <form method="GET" action="{{ route('admin.multimedia.index') }}" class="mb-4"
                    role="search" aria-label="Wyszukiwarka multimediów">
                    @if ($folder) <input type="hidden" name="folder" value="{{ $folder->id }}"> @endif

                    <div x-data="{ expanded: {{ ($currentDateFrom || $currentDateTo || $currentAuthor || $withArchived) ? 'true' : 'false' }} }"
                        class="rounded-xl border border-gray-200 bg-white shadow-sm">

                        {{-- Main row --}}
                        <div class="flex flex-wrap items-center gap-2 px-4 py-3">
                            <div class="relative min-w-[200px] flex-1">
                                <label for="media-search" class="sr-only">Szukaj po nazwie pliku</label>
                                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-muted" aria-hidden="true"></i>
                                <input type="text" id="media-search" name="q" value="{{ $currentSearch }}"
                                    placeholder="Szukaj po nazwie pliku…"
                                    class="w-full rounded-lg border-gray-300 py-2 pl-8 pr-3 text-sm focus:border-brand focus:ring-brand">
                            </div>

                            <button type="submit"
                                class="rounded-lg bg-brand px-4 py-2 text-xs font-bold text-white hover:bg-brand-dark">
                                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                <span class="ml-1 hidden sm:inline">Szukaj</span>
                            </button>

                            <button type="button" @click="expanded = !expanded"
                                class="flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-2 text-xs font-bold text-muted hover:bg-gray-100"
                                :aria-expanded="expanded" aria-controls="search-filters">
                                <i class="fa-solid fa-sliders text-[11px]" aria-hidden="true"></i>
                                <span>Filtry</span>
                                @if ($currentDateFrom || $currentDateTo || $currentAuthor || $withArchived)
                                    <span class="flex h-4 w-4 items-center justify-center rounded-full bg-brand text-[9px] font-bold text-white"
                                        aria-label="Aktywne filtry">
                                        {{ (int)($currentDateFrom || $currentDateTo) + (int)($currentAuthor > 0) + (int)$withArchived }}
                                    </span>
                                @endif
                            </button>

                            @if ($currentSearch || $currentTag || $currentDateFrom || $currentDateTo || $currentAuthor || $withArchived)
                                <a href="{{ route('admin.multimedia.index', array_filter(['folder' => $folder?->id])) }}"
                                    class="rounded-lg px-3 py-2 text-xs font-bold text-muted hover:text-ink">
                                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                    <span class="hidden sm:inline">Wyczyść filtry</span>
                                </a>
                            @endif

                            @if ($currentTag)
                                <span class="flex items-center gap-1.5 rounded-full bg-brand-light px-3 py-1 text-xs font-bold text-brand">
                                    <i class="fa-solid fa-tag text-[10px]" aria-hidden="true"></i>
                                    {{ $currentTag }}
                                    <a href="{{ route('admin.multimedia.index', array_filter(['folder' => $folder?->id, 'q' => $currentSearch])) }}"
                                        class="ml-0.5 rounded-full hover:text-brand-dark"
                                        aria-label="Usuń filtr tagu: {{ $currentTag }}">
                                        <i class="fa-solid fa-xmark text-[10px]" aria-hidden="true"></i>
                                    </a>
                                </span>
                            @endif
                        </div>

                        {{-- Expanded filters --}}
                        <div id="search-filters" x-show="expanded" x-cloak
                            x-transition:enter="transition duration-150 ease-out"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="border-t border-gray-100 px-4 pb-4 pt-3">

                            <fieldset class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <legend class="sr-only">Filtry zaawansowane</legend>

                                {{-- Date from --}}
                                <div>
                                    <label for="filter-date-from" class="mb-1 block text-xs font-bold text-muted">
                                        Data dodania — od
                                    </label>
                                    <input type="date" id="filter-date-from" name="date_from"
                                        value="{{ $currentDateFrom }}"
                                        class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>

                                {{-- Date to --}}
                                <div>
                                    <label for="filter-date-to" class="mb-1 block text-xs font-bold text-muted">
                                        Data dodania — do
                                    </label>
                                    <input type="date" id="filter-date-to" name="date_to"
                                        value="{{ $currentDateTo }}"
                                        class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>

                                {{-- Author --}}
                                <div>
                                    <label for="filter-author" class="mb-1 block text-xs font-bold text-muted">
                                        Autor / twórca
                                    </label>
                                    <input type="text" id="filter-author" name="author"
                                        value="{{ $currentAuthor }}"
                                        placeholder="np. Jan Kowalski"
                                        class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>

                                {{-- Archive checkbox --}}
                                <div class="flex items-end">
                                    <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-ink hover:bg-gray-50 w-full">
                                        <input type="checkbox" name="with_archived" value="1"
                                            {{ $withArchived ? 'checked' : '' }}
                                            class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                                        <span>Szukaj też w archiwum</span>
                                    </label>
                                </div>
                            </fieldset>

                            <div class="mt-3 flex justify-end">
                                <button type="submit"
                                    class="rounded-lg bg-brand px-4 py-2 text-xs font-bold text-white hover:bg-brand-dark">
                                    Zastosuj filtry
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                {{-- ── Upload zone ──────────────────────────────────────────── --}}
                @unless ($showArchived)
                    <form method="POST" action="{{ route('admin.multimedia.store') }}" enctype="multipart/form-data"
                        x-data="{
                            files: [],
                            meta: [],
                            croppedFiles: {},
                            cropIdx: null,
                            cropperInst: null,
                            drag: false,
                            setFiles(list) {
                                this.files = Array.from(list);
                                this.croppedFiles = {};
                                this.meta = this.files.map(f => ({
                                    author: '',
                                    alt: f.name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ')
                                }));
                            },
                            onDrop(event) {
                                this.drag = false;
                                this.$refs.input.files = event.dataTransfer.files;
                                this.setFiles(event.dataTransfer.files);
                            },
                            clear() { this.$refs.input.value = ''; this.files = []; this.meta = []; this.croppedFiles = {}; },
                            isImage(f) { return f.type.startsWith('image/'); },
                            openCrop(idx) {
                                this.cropIdx = idx;
                                const src = this.croppedFiles[idx]
                                    ? URL.createObjectURL(this.croppedFiles[idx])
                                    : URL.createObjectURL(this.files[idx]);
                                this.$nextTick(() => {
                                    const img = this.$refs.cropImg;
                                    img.src = src;
                                    if (this.cropperInst) this.cropperInst.destroy();
                                    this.cropperInst = new Cropper(img, { viewMode: 1, autoCropArea: 0.9 });
                                });
                            },
                            confirmCrop() {
                                const idx = this.cropIdx;
                                const mime = this.files[idx]?.type || 'image/jpeg';
                                this.cropperInst.getCroppedCanvas({ maxWidth: 4096 }).toBlob(blob => {
                                    this.croppedFiles[idx] = blob;
                                    this.cancelCrop();
                                }, mime, 0.92);
                            },
                            cancelCrop() {
                                this.cropIdx = null;
                                if (this.cropperInst) { this.cropperInst.destroy(); this.cropperInst = null; }
                            },
                            prepareAndSubmit(form) {
                                if (Object.keys(this.croppedFiles).length === 0) { form.submit(); return; }
                                const dt = new DataTransfer();
                                this.files.forEach((f, i) => {
                                    dt.items.add(this.croppedFiles[i]
                                        ? new File([this.croppedFiles[i]], f.name, { type: f.type })
                                        : f);
                                });
                                this.$refs.input.files = dt.files;
                                form.submit();
                            }
                        }"
                        @dragover.prevent="drag = true" @dragleave.prevent="drag = false" @drop.prevent="onDrop($event)"
                        @submit.prevent="prepareAndSubmit($el)"
                        class="mb-4">
                        @csrf
                        <input type="hidden" name="folder_id" value="{{ $folder?->id }}">

                        <input id="media-upload" x-ref="input" type="file" name="files[]" multiple required
                            class="peer sr-only" @change="setFiles($event.target.files)">
                        <label for="media-upload"
                            class="flex cursor-pointer items-center gap-4 rounded-xl border-2 border-dashed px-6 py-5 transition peer-focus-visible:ring-2 peer-focus-visible:ring-brand"
                            :class="drag ? 'border-brand bg-brand-light' : 'border-gray-300 bg-white hover:border-brand hover:bg-gray-50'">
                            <i class="fa-solid fa-cloud-arrow-up flex-none text-3xl text-brand" aria-hidden="true"></i>
                            <div>
                                <p class="text-sm font-bold text-ink">Przeciągnij pliki tutaj lub kliknij, aby wybrać</p>
                                <p class="text-xs text-muted">Wiele plików naraz &middot; maks. 10 MB każdy</p>
                            </div>
                        </label>

                        <template x-if="files.length">
                            <div class="mt-3 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                                <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-4 py-2.5">
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-muted">
                                        Wybrane pliki (<span x-text="files.length"></span>)
                                    </p>
                                    <p class="text-[10px] text-muted">Wypełnij autora i opis — pomagają przy wyszukiwaniu i dostępności</p>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="border-b border-gray-100 bg-gray-50 text-[10px] font-bold uppercase tracking-widest text-muted">
                                            <tr>
                                                <th scope="col" class="px-4 py-2 text-left">Plik</th>
                                                <th scope="col" class="px-4 py-2 text-left">
                                                    Autor / twórca
                                                    <span class="font-normal normal-case tracking-normal">(opcjonalnie)</span>
                                                </th>
                                                <th scope="col" class="px-4 py-2 text-left">
                                                    Opis alternatywny
                                                    <span class="font-normal normal-case tracking-normal">(zalecany dla obrazów)</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <template x-for="(file, i) in files" :key="i">
                                                <tr>
                                                    <td class="px-4 py-2">
                                                        <div class="flex items-center gap-2">
                                                            <i class="fa-solid fa-file flex-none text-muted" aria-hidden="true"></i>
                                                            <span class="max-w-[10rem] truncate text-sm" x-text="file.name"></span>
                                                            <template x-if="isImage(file)">
                                                                <button type="button" @click="openCrop(i)"
                                                                    class="flex-none rounded border border-gray-300 px-1.5 py-0.5 text-[10px] font-bold text-muted hover:border-brand hover:text-brand"
                                                                    :aria-label="'Kadruj plik ' + file.name">
                                                                    <i class="fa-solid fa-crop-simple" aria-hidden="true"></i>
                                                                    <span x-text="croppedFiles[i] ? 'Kadrowano' : 'Kadruj'"></span>
                                                                </button>
                                                            </template>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-2">
                                                        <label :for="'upl-author-' + i" class="sr-only"
                                                            x-text="'Autor pliku ' + file.name"></label>
                                                        <input :id="'upl-author-' + i" type="text"
                                                            :name="'authors[' + i + ']'"
                                                            x-model="meta[i].author"
                                                            placeholder="np. Jan Kowalski"
                                                            class="w-full min-w-[10rem] rounded-lg border-gray-300 py-1.5 text-xs focus:border-brand focus:ring-brand">
                                                    </td>
                                                    <td class="px-4 py-2">
                                                        <label :for="'upl-alt-' + i" class="sr-only"
                                                            x-text="'Opis alternatywny pliku ' + file.name"></label>
                                                        <input :id="'upl-alt-' + i" type="text"
                                                            :name="'alts[' + i + ']'"
                                                            x-model="meta[i].alt"
                                                            placeholder="Co przedstawia ten plik?"
                                                            class="w-full min-w-[14rem] rounded-lg border-gray-300 py-1.5 text-xs focus:border-brand focus:ring-brand">
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="flex items-center gap-3 border-t border-gray-100 px-4 py-3">
                                    <button type="submit"
                                        class="rounded-lg bg-brand px-4 py-2 text-xs font-bold text-white hover:bg-brand-dark">
                                        <i class="fa-solid fa-upload" aria-hidden="true"></i>
                                        Prześlij (<span x-text="files.length"></span>)
                                    </button>
                                    <button type="button" @click="clear()"
                                        class="text-xs font-bold text-muted hover:text-ink">Wyczyść</button>
                                </div>
                            </div>
                        </template>

                        {{-- Modal kadrowania --}}
                        <div x-show="cropIdx !== null" x-cloak
                            @keydown.escape.window="cancelCrop()"
                            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
                            role="dialog" aria-modal="true" aria-label="Kadruj obraz"
                            x-transition.opacity>
                            <div class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl" @click.stop>
                                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                                    <h2 class="text-sm font-bold text-ink">Kadrowanie obrazu</h2>
                                    <button type="button" @click="cancelCrop()"
                                        class="rounded-lg p-1.5 text-muted hover:bg-gray-100 hover:text-ink"
                                        aria-label="Zamknij">
                                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div class="flex-1 overflow-hidden bg-gray-100 p-2">
                                    <img x-ref="cropImg" src="" alt="Podgląd do kadrowania"
                                        class="block max-h-[60vh] max-w-full mx-auto">
                                </div>
                                <div class="flex items-center gap-3 border-t border-gray-100 px-5 py-4">
                                    <button type="button" @click="confirmCrop()"
                                        class="rounded-lg bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                                        <i class="fa-solid fa-check" aria-hidden="true"></i>
                                        Zastosuj kadrowanie
                                    </button>
                                    <button type="button" @click="cancelCrop()"
                                        class="text-sm font-bold text-muted hover:text-ink">Anuluj</button>
                                    <p class="ml-auto text-xs text-muted">Przeciągnij zaznaczenie &middot; Kółko myszy = zoom</p>
                                </div>
                            </div>
                        </div>
                    </form>
                @endunless

                @error('files') <p class="mb-4 text-sm text-red-600">{{ $message }}</p> @enderror
                @error('files.*') <p class="mb-4 text-sm text-red-600">{{ $message }}</p> @enderror
                @error('archive') <p class="mb-4 text-sm text-red-600">{{ $message }}</p> @enderror
                @error('name') <p class="mb-4 text-sm text-red-600">{{ $message }}</p> @enderror

                @if ($showArchived)
                    <div class="mb-6 flex flex-wrap items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        <i class="fa-solid fa-box-archive flex-none text-amber-500" aria-hidden="true"></i>
                        <span class="flex-1">Przeglądasz archiwum — schowane pliki nie pojawiają się w bibliotece ani edytorze, ale nie zostały usunięte.</span>
                        <form method="POST" action="{{ route('admin.multimedia.empty-archive', array_filter(['folder' => $folder?->id])) }}"
                            onsubmit="return confirm('Czy na pewno chcesz trwale usunąć wszystkie pliki w archiwum? Tej operacji nie można cofnąć.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="flex items-center gap-1.5 rounded-lg border border-amber-400 bg-white px-3 py-1.5 text-xs font-bold text-amber-700 hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-500">
                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                Opróżnij kosz
                            </button>
                        </form>
                    </div>
                @endif

                {{-- ── Bulk action bar ──────────────────────────────────────── --}}
                <div x-show="selected.length" x-cloak x-transition
                    class="sticky top-2 z-30 mb-4 flex flex-wrap items-center gap-3 rounded-xl border border-brand bg-brand-light px-4 py-3 shadow-md"
                    role="region" aria-label="Akcje dla zaznaczonych plików">

                    <p class="text-sm font-bold text-brand" aria-live="polite">
                        Zaznaczono: <span x-text="selected.length"></span>
                    </p>

                    <div class="flex flex-1 flex-wrap items-center justify-end gap-2">
                        <form method="POST" action="{{ route('admin.multimedia.export-selected') }}">
                            @csrf
                            <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                            <button type="submit"
                                class="flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-bold text-muted hover:bg-gray-100">
                                <i class="fa-solid fa-file-zipper" aria-hidden="true"></i> Eksportuj ZIP
                            </button>
                        </form>

                        @if (! $showArchived && $allFolders->isNotEmpty())
                            <form method="POST" action="{{ route('admin.multimedia.bulk') }}" class="flex items-center gap-1.5">
                                @csrf
                                <input type="hidden" name="action" value="move">
                                <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                                <label class="sr-only" for="bulk-folder">Przenieś zaznaczone do folderu</label>
                                <select id="bulk-folder" name="folder_id"
                                    class="rounded-lg border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                    <option value="">— główny katalog —</option>
                                    @foreach ($allFolders as $option)
                                        <option value="{{ $option->id }}">{{ $option->fullPath() }}</option>
                                    @endforeach
                                </select>
                                <button type="submit"
                                    class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-bold text-muted hover:bg-gray-100">
                                    Przenieś
                                </button>
                            </form>
                        @endif

                        @if ($showArchived)
                            <form method="POST" action="{{ route('admin.multimedia.bulk') }}">
                                @csrf
                                <input type="hidden" name="action" value="restore">
                                <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                                <button type="submit"
                                    class="flex items-center gap-1.5 rounded-lg border border-brand bg-white px-3 py-1.5 text-xs font-bold text-brand hover:bg-brand-light">
                                    <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Przywróć
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.multimedia.bulk') }}">
                                @csrf
                                <input type="hidden" name="action" value="archive">
                                <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                                <button type="submit"
                                    class="flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-bold text-muted hover:bg-gray-100">
                                    <i class="fa-solid fa-box-archive" aria-hidden="true"></i> Schowaj
                                </button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('admin.multimedia.bulk') }}"
                            @submit="if (! confirm('Usunąć zaznaczone pliki (' + selected.length + ')? Jeśli któryś jest używany, zniknie też z miejsca, w którym się wyświetlał.')) $event.preventDefault();">
                            @csrf
                            <input type="hidden" name="action" value="delete">
                            <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                            <button type="submit"
                                class="flex items-center gap-1.5 rounded-lg border border-red-300 bg-white px-3 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50">
                                <i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń
                            </button>
                        </form>

                        <button type="button" @click="clearSelection()"
                            class="rounded-lg px-3 py-1.5 text-xs font-bold text-muted hover:text-ink">
                            Odznacz
                        </button>
                    </div>
                </div>

                @if ($media->isEmpty())
                    <div class="flex flex-col items-center gap-3 py-16 text-center text-muted">
                        <i class="fa-solid fa-photo-film text-5xl opacity-20" aria-hidden="true"></i>
                        <p class="text-sm">
                            @if ($currentSearch || $currentTag)
                                Brak wyników dla podanych filtrów.
                            @elseif ($showArchived)
                                Brak schowanych plików w tym folderze.
                            @else
                                Brak plików w tym folderze.
                            @endif
                        </p>
                    </div>
                @else
                    <div class="mb-3 flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm text-muted">
                            <input type="checkbox" id="select-all" :checked="allSelected" @change="toggleAll()"
                                class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                            Zaznacz wszystkie na tej stronie
                        </label>
                        <p class="text-xs text-muted">{{ $media->total() }} {{ $media->total() === 1 ? 'plik' : ($media->total() < 5 ? 'pliki' : 'plików') }}</p>
                    </div>

                    {{-- ── Grid view ───────────────────────────────────────── --}}
                    <div x-show="view === 'grid'" x-cloak
                        class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                        @foreach ($media as $item)
                            <div class="group relative overflow-visible rounded-xl border bg-white shadow-sm transition-shadow hover:shadow-md {{ $item['archived'] ? 'opacity-60' : '' }}"
                                :class="isSelected({{ $item['id'] }}) ? 'border-brand ring-2 ring-brand' : 'border-gray-200'">

                                {{-- Thumbnail --}}
                                <div class="relative h-40 overflow-hidden rounded-t-xl bg-gray-100">
                                    <div class="absolute left-2 top-2 z-10">
                                        <input type="checkbox" id="sel-{{ $item['id'] }}"
                                            :checked="isSelected({{ $item['id'] }})"
                                            @change="toggle({{ $item['id'] }})"
                                            class="h-5 w-5 rounded border-gray-300 bg-white text-brand shadow focus:ring-brand">
                                        <label for="sel-{{ $item['id'] }}" class="sr-only">Zaznacz plik {{ $item['file_name'] }}</label>
                                    </div>

                                    @if ($item['archived'])
                                        <span class="absolute right-2 top-2 z-10 rounded-full bg-gray-900/70 px-2 py-0.5 text-[10px] font-bold text-white">Archiwum</span>
                                    @endif

                                    @if ($item['is_image'])
                                        <button type="button" class="block h-full w-full cursor-zoom-in"
                                            @click="openLightbox(@js($item['url']))"
                                            aria-label="Powiększ obraz: {{ $item['alt'] }}">
                                            <img src="{{ $item['thumb_url'] }}" alt="{{ $item['alt'] }}" class="h-full w-full object-cover" loading="lazy">
                                        </button>
                                    @else
                                        <div class="flex h-full w-full flex-col items-center justify-center gap-2">
                                            <i class="fa-solid fa-file text-5xl text-gray-300" aria-hidden="true"></i>
                                            <span class="text-xs font-bold uppercase text-gray-400">{{ pathinfo($item['file_name'], PATHINFO_EXTENSION) }}</span>
                                        </div>
                                    @endif

                                    {{-- Hover: copy URL overlay --}}
                                    <div class="pointer-events-none absolute inset-0 flex items-end justify-end bg-gradient-to-t from-black/40 to-transparent p-2 opacity-0 transition-opacity group-hover:pointer-events-auto group-hover:opacity-100">
                                        <button type="button"
                                            @click.stop="copy(@js($item['url']), {{ $item['id'] }})"
                                            class="flex items-center gap-1.5 rounded-full bg-black/60 px-2.5 py-1.5 text-xs font-bold text-white backdrop-blur hover:bg-black/80"
                                            :aria-label="copiedId === {{ $item['id'] }} ? 'Skopiowano URL' : 'Kopiuj URL'">
                                            <i class="fa-solid"
                                                :class="copiedId === {{ $item['id'] }} ? 'fa-check' : 'fa-link'"
                                                aria-hidden="true"></i>
                                            <span x-text="copiedId === {{ $item['id'] }} ? 'Skopiowano' : 'Kopiuj URL'"></span>
                                        </button>
                                    </div>
                                </div>

                                {{-- Card footer --}}
                                <div class="p-3" x-data="{ open: false, tags: @js($item['tags']), tagInput: '', addingTag: false }">

                                    {{-- Filename + format badge --}}
                                    <div class="mb-0.5 flex items-start gap-1">
                                        <p class="flex-1 truncate text-xs font-bold leading-snug text-ink"
                                            title="{{ $item['file_name'] }}">{{ $item['file_name'] }}</p>
                                        @if ($item['is_image'])
                                            @if ($item['is_webp'])
                                                <span class="flex-none rounded bg-green-100 px-1 py-0.5 text-[10px] font-bold text-green-700">WebP</span>
                                            @else
                                                <span class="flex-none rounded border border-amber-200 bg-amber-50 px-1 py-0.5 text-[10px] font-bold text-amber-700">{{ strtoupper(pathinfo($item['file_name'], PATHINFO_EXTENSION)) }}</span>
                                            @endif
                                        @endif
                                    </div>

                                    <p class="text-[11px] text-muted">{{ $item['size'] }} &middot; {{ $item['created_at']->format('d.m.Y') }}</p>
                                    <p class="text-[11px] text-muted">
                                        <i class="fa-solid fa-user text-[9px]" aria-hidden="true"></i>
                                        {{ $item['author'] ?? 'System' }}
                                    </p>

                                    {{-- Usage (owner) link --}}
                                    @if ($item['owner']['url'])
                                        <a href="{{ $item['owner']['url'] }}"
                                            class="mt-1 flex items-center gap-1 text-[11px] font-bold text-brand hover:text-brand-dark">
                                            <i class="fa-solid fa-arrow-up-right-from-square text-[9px]" aria-hidden="true"></i>
                                            {{ $item['owner']['label'] }}
                                        </a>
                                    @else
                                        <p class="mt-1 text-[11px] text-muted">
                                            {{ ($item['owner']['standalone'] ?? false) ? 'Plik w bibliotece' : 'Osierocony plik' }}
                                        </p>
                                    @endif

                                    {{-- Tags --}}
                                    <div class="mt-2">
                                        <form x-ref="tagForm-{{ $item['id'] }}"
                                            method="POST" action="{{ route('admin.multimedia.tags', $item['id']) }}">
                                            @csrf
                                            @method('PUT')
                                            <template x-for="tag in tags" :key="tag">
                                                <input type="hidden" name="tags[]" :value="tag">
                                            </template>
                                        </form>

                                        <div class="flex flex-wrap items-center gap-1">
                                            <template x-for="tag in tags" :key="tag">
                                                <span class="flex items-center gap-0.5 rounded-full bg-gray-100 pl-2 pr-1 py-0.5 text-[10px] font-bold text-gray-600">
                                                    <span x-text="tag"></span>
                                                    <button type="button"
                                                        @click="tags = tags.filter(t => t !== tag); $refs['tagForm-{{ $item['id'] }}'].requestSubmit()"
                                                        class="ml-0.5 rounded-full hover:text-red-500" aria-label="Usuń tag">
                                                        <i class="fa-solid fa-xmark text-[9px]" aria-hidden="true"></i>
                                                    </button>
                                                </span>
                                            </template>

                                            <button type="button" @click="addingTag = !addingTag"
                                                class="rounded-full border border-dashed border-gray-300 px-2 py-0.5 text-[10px] text-muted hover:border-brand hover:text-brand"
                                                aria-label="Dodaj tag">
                                                <i class="fa-solid fa-plus text-[8px]" aria-hidden="true"></i> Tag
                                            </button>
                                        </div>

                                        <div x-show="addingTag" x-cloak class="mt-1.5">
                                            <input type="text" x-model="tagInput"
                                                placeholder="Nowy tag…"
                                                class="w-full rounded-lg border-gray-300 py-1 pl-2 pr-2 text-xs focus:border-brand focus:ring-brand"
                                                @keydown.enter.prevent="
                                                    const t = tagInput.trim();
                                                    if (t && !tags.includes(t)) { tags.push(t); $refs['tagForm-{{ $item['id'] }}'].requestSubmit(); }
                                                    tagInput = ''; addingTag = false;
                                                "
                                                @keydown.escape="addingTag = false; tagInput = ''"
                                                x-init="$nextTick(() => { if (addingTag) $el.focus(); })"
                                                x-effect="if (addingTag) $nextTick(() => $el.focus())">
                                        </div>
                                    </div>

                                    {{-- Three-dot actions --}}
                                    <div class="mt-2 flex items-center justify-between">
                                        <span class="text-[11px] text-muted">{{ $item['collection'] }}</span>

                                        <div class="relative flex-none" @click.outside="open = false">
                                            <button type="button" @click="open = !open"
                                                class="rounded-md p-1 text-muted hover:bg-gray-100 hover:text-ink"
                                                aria-label="Więcej akcji">
                                                <i class="fa-solid fa-ellipsis-vertical text-xs" aria-hidden="true"></i>
                                            </button>

                                            <div x-show="open" x-cloak
                                                x-transition:enter="transition duration-100 ease-out"
                                                x-transition:enter-start="opacity-0 scale-95"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition duration-75 ease-in"
                                                x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-95"
                                                class="absolute bottom-full right-0 z-20 mb-1 w-52 origin-bottom-right rounded-xl border border-gray-200 bg-white py-1 shadow-lg">

                                                @if ($allFolders->isNotEmpty())
                                                    <div class="border-b border-gray-100 px-3 py-2">
                                                        <p class="mb-1 text-[10px] font-bold uppercase tracking-widest text-muted">Przenieś do folderu</p>
                                                        <form method="POST" action="{{ route('admin.multimedia.move', $item['id']) }}">
                                                            @csrf
                                                            @method('PUT')
                                                            <label class="sr-only" for="folder-g-{{ $item['id'] }}">Wybierz folder</label>
                                                            <select id="folder-g-{{ $item['id'] }}" name="folder_id"
                                                                onchange="this.form.submit()"
                                                                class="w-full rounded-lg border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                                                <option value="" {{ ! $folder ? 'selected' : '' }}>— główny katalog —</option>
                                                                @foreach ($allFolders as $option)
                                                                    <option value="{{ $option->id }}" {{ $folder?->id === $option->id ? 'selected' : '' }}>{{ $option->fullPath() }}</option>
                                                                @endforeach
                                                            </select>
                                                        </form>
                                                    </div>
                                                @endif

                                                @if ($item['archived'])
                                                    <form method="POST" action="{{ route('admin.multimedia.restore', $item['id']) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-xs text-brand hover:bg-brand-light">
                                                            <i class="fa-solid fa-rotate-left w-4 text-center" aria-hidden="true"></i>
                                                            Przywróć z archiwum
                                                        </button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('admin.multimedia.archive', $item['id']) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-xs text-ink hover:bg-gray-50">
                                                            <i class="fa-solid fa-box-archive w-4 text-center text-muted" aria-hidden="true"></i>
                                                            Schowaj / archiwizuj
                                                        </button>
                                                    </form>
                                                @endif

                                                <div class="my-1 border-t border-gray-100"></div>

                                                <form method="POST" action="{{ route('admin.multimedia.destroy', $item['id']) }}"
                                                    onsubmit="return confirm('Usunąć plik &quot;{{ $item['file_name'] }}&quot;? Jeśli jest używany, zniknie też z miejsca, w którym się wyświetlał.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-xs text-red-600 hover:bg-red-50">
                                                        <i class="fa-solid fa-trash w-4 text-center" aria-hidden="true"></i>
                                                        Usuń plik
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- ── List view ───────────────────────────────────────── --}}
                    <div x-show="view === 'list'" x-cloak class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-widest text-muted">
                                <tr>
                                    <th scope="col" class="px-4 py-3">
                                        <input type="checkbox" :checked="allSelected" @change="toggleAll()"
                                            class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand"
                                            aria-label="Zaznacz wszystkie na tej stronie">
                                    </th>
                                    <th scope="col" class="px-4 py-3"><span class="sr-only">Podgląd</span></th>
                                    <th scope="col" class="px-4 py-3">Plik</th>
                                    <th scope="col" class="px-4 py-3">Użycie</th>
                                    <th scope="col" class="px-4 py-3">Tagi</th>
                                    <th scope="col" class="px-4 py-3">URL</th>
                                    <th scope="col" class="px-4 py-3">Autor</th>
                                    <th scope="col" class="px-4 py-3">Rozmiar&nbsp;/ data</th>
                                    @if ($allFolders->isNotEmpty())
                                        <th scope="col" class="px-4 py-3">Folder</th>
                                    @endif
                                    <th scope="col" class="px-4 py-3 text-right">Akcje</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($media as $item)
                                    <tr class="{{ $item['archived'] ? 'opacity-60' : '' }}"
                                        :class="isSelected({{ $item['id'] }}) ? 'bg-brand-light' : 'hover:bg-gray-50'">

                                        {{-- Checkbox --}}
                                        <td class="px-4 py-3">
                                            <input type="checkbox" id="sel-list-{{ $item['id'] }}"
                                                :checked="isSelected({{ $item['id'] }})"
                                                @change="toggle({{ $item['id'] }})"
                                                class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                                            <label for="sel-list-{{ $item['id'] }}" class="sr-only">Zaznacz plik {{ $item['file_name'] }}</label>
                                        </td>

                                        {{-- Thumbnail --}}
                                        <td class="px-4 py-3">
                                            <div class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-lg bg-gray-100 flex-none">
                                                @if ($item['is_image'])
                                                    <button type="button" class="block h-full w-full cursor-zoom-in"
                                                        @click="openLightbox(@js($item['url']))"
                                                        aria-label="Powiększ obraz: {{ $item['alt'] }}">
                                                        <img src="{{ $item['thumb_url'] }}" alt="{{ $item['alt'] }}" class="h-full w-full object-cover" loading="lazy">
                                                    </button>
                                                @else
                                                    <i class="fa-solid fa-file text-xl text-gray-300" aria-hidden="true"></i>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- Filename + meta --}}
                                        <td class="max-w-[14rem] px-4 py-3"
                                            x-data="{ renaming: false, nameVal: @js($item['display_name_custom'] ?: $item['file_name_real']) }">

                                            <template x-if="!renaming">
                                                <div>
                                                    <div class="group/name flex items-center gap-1">
                                                        <p class="min-w-0 flex-1 truncate font-bold text-ink" title="{{ $item['file_name'] }}">{{ $item['file_name'] }}</p>
                                                        <button type="button" @click="renaming = true"
                                                            class="flex-none px-0.5 py-0.5 text-[11px] text-muted opacity-0 transition-opacity group-hover/name:opacity-100 hover:text-brand"
                                                            aria-label="Zmień nazwę pliku {{ $item['file_name'] }}">
                                                            <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                                        </button>
                                                    </div>
                                                    <p class="mt-0.5 text-xs text-muted">{{ $item['mime_type'] }}</p>
                                                    @if ($item['archived'])
                                                        <span class="mt-0.5 inline-block rounded bg-gray-200 px-1.5 py-0.5 text-[10px] font-bold text-gray-600">W archiwum</span>
                                                    @endif
                                                    @if ($item['is_image'])
                                                        @if ($item['is_webp'])
                                                            <span class="mt-0.5 inline-flex items-center gap-0.5 rounded bg-green-100 px-1.5 py-0.5 text-[10px] font-bold text-green-700">
                                                                <i class="fa-solid fa-check" aria-hidden="true"></i> WebP
                                                            </span>
                                                        @else
                                                            <span class="mt-0.5 inline-block rounded border border-amber-200 bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold text-amber-700">
                                                                {{ strtoupper(pathinfo($item['file_name_real'], PATHINFO_EXTENSION)) }}
                                                            </span>
                                                        @endif
                                                    @endif
                                                </div>
                                            </template>

                                            <template x-if="renaming">
                                                <form method="POST" action="{{ route('admin.multimedia.rename', $item['id']) }}"
                                                    class="flex items-center gap-1"
                                                    x-init="$nextTick(() => $refs['rename-{{ $item['id'] }}'].focus())">
                                                    @csrf
                                                    @method('PUT')
                                                    <label class="sr-only" for="rename-{{ $item['id'] }}">Nowa nazwa pliku</label>
                                                    <input type="text" id="rename-{{ $item['id'] }}" x-ref="rename-{{ $item['id'] }}"
                                                        name="name" x-model="nameVal" required
                                                        @keydown.escape="renaming = false"
                                                        class="w-full min-w-0 rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                                    <button type="submit" class="flex-none text-brand hover:text-brand-dark" aria-label="Zapisz"><i class="fa-solid fa-check text-xs" aria-hidden="true"></i></button>
                                                    <button type="button" @click="renaming = false" class="flex-none text-muted" aria-label="Anuluj"><i class="fa-solid fa-xmark text-xs" aria-hidden="true"></i></button>
                                                </form>
                                            </template>
                                        </td>

                                        {{-- Usage / owner --}}
                                        <td class="px-4 py-3">
                                            @if ($item['owner']['url'])
                                                <a href="{{ $item['owner']['url'] }}"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-brand/30 bg-brand-light px-2.5 py-1.5 text-xs font-bold text-brand hover:bg-brand hover:text-white transition-colors">
                                                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px]" aria-hidden="true"></i>
                                                    {{ $item['owner']['label'] }}
                                                </a>
                                            @elseif ($item['owner']['standalone'] ?? false)
                                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-2.5 py-1.5 text-xs text-muted">
                                                    <i class="fa-solid fa-layer-group text-[10px]" aria-hidden="true"></i>
                                                    Biblioteka plików
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs text-amber-700">
                                                    <i class="fa-solid fa-triangle-exclamation text-[10px]" aria-hidden="true"></i>
                                                    Osierocony plik
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Tags --}}
                                        <td class="px-4 py-3"
                                            x-data="{ tags: @js($item['tags']), tagInput: '', addingTag: false }">
                                            <form x-ref="tagFormList-{{ $item['id'] }}"
                                                method="POST" action="{{ route('admin.multimedia.tags', $item['id']) }}">
                                                @csrf
                                                @method('PUT')
                                                <template x-for="tag in tags" :key="tag">
                                                    <input type="hidden" name="tags[]" :value="tag">
                                                </template>
                                            </form>

                                            <div class="flex flex-wrap items-center gap-1">
                                                <template x-for="tag in tags" :key="tag">
                                                    <span class="flex items-center gap-0.5 rounded-full bg-gray-100 pl-2 pr-1 py-0.5 text-[10px] font-bold text-gray-600">
                                                        <span x-text="tag"></span>
                                                        <button type="button"
                                                            @click="tags = tags.filter(t => t !== tag); $refs['tagFormList-{{ $item['id'] }}'].requestSubmit()"
                                                            class="ml-0.5 rounded-full hover:text-red-500" aria-label="Usuń tag">
                                                            <i class="fa-solid fa-xmark text-[9px]" aria-hidden="true"></i>
                                                        </button>
                                                    </span>
                                                </template>
                                                <button type="button" @click="addingTag = !addingTag"
                                                    class="rounded-full border border-dashed border-gray-300 px-2 py-0.5 text-[10px] text-muted hover:border-brand hover:text-brand"
                                                    aria-label="Dodaj tag">
                                                    <i class="fa-solid fa-plus text-[8px]" aria-hidden="true"></i>
                                                </button>
                                            </div>

                                            <div x-show="addingTag" x-cloak class="mt-1">
                                                <input type="text" x-model="tagInput"
                                                    placeholder="Nowy tag…"
                                                    class="w-32 rounded-lg border-gray-300 py-1 px-2 text-xs focus:border-brand focus:ring-brand"
                                                    @keydown.enter.prevent="
                                                        const t = tagInput.trim();
                                                        if (t && !tags.includes(t)) { tags.push(t); $refs['tagFormList-{{ $item['id'] }}'].requestSubmit(); }
                                                        tagInput = ''; addingTag = false;
                                                    "
                                                    @keydown.escape="addingTag = false; tagInput = ''"
                                                    x-effect="if (addingTag) $nextTick(() => $el.focus())">
                                            </div>
                                        </td>

                                        {{-- URL --}}
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-1.5">
                                                <label class="sr-only" for="url-list-{{ $item['id'] }}">Adres URL pliku {{ $item['file_name'] }}</label>
                                                <input type="text" id="url-list-{{ $item['id'] }}" readonly value="{{ $item['url'] }}"
                                                    @focus="$event.target.select()"
                                                    class="w-32 rounded-lg border-gray-200 bg-gray-50 px-2 py-1.5 text-xs text-muted focus:border-brand focus:ring-brand">
                                                <button type="button"
                                                    @click="copy(@js($item['url']), {{ $item['id'] }})"
                                                    class="flex-none rounded-lg border border-gray-300 px-2 py-1.5 hover:bg-gray-100"
                                                    :aria-label="copiedId === {{ $item['id'] }} ? 'Skopiowano adres URL' : 'Kopiuj adres URL'">
                                                    <i class="fa-solid"
                                                        :class="copiedId === {{ $item['id'] }} ? 'fa-check text-green-600' : 'fa-copy text-muted'"
                                                        aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </td>

                                        {{-- Author (inline edit) --}}
                                        <td class="px-4 py-3"
                                            x-data="{ editingAuthor: false, authorVal: @js($item['author_editable']) }">
                                            <template x-if="!editingAuthor">
                                                <div class="group/author flex items-center gap-1">
                                                    <span class="whitespace-nowrap text-sm text-muted" x-text="authorVal || @js($item['author'])"></span>
                                                    <button type="button" @click="editingAuthor = true"
                                                        class="flex-none px-0.5 text-[11px] text-muted opacity-0 transition-opacity group-hover/author:opacity-100 hover:text-brand"
                                                        aria-label="Edytuj autora">
                                                        <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            </template>
                                            <template x-if="editingAuthor">
                                                <form method="POST" action="{{ route('admin.multimedia.author', $item['id']) }}"
                                                    class="flex items-center gap-1"
                                                    x-init="$nextTick(() => $refs['author-{{ $item['id'] }}'].focus())">
                                                    @csrf
                                                    @method('PUT')
                                                    <label class="sr-only" for="author-{{ $item['id'] }}">Autor pliku</label>
                                                    <input type="text" id="author-{{ $item['id'] }}" x-ref="author-{{ $item['id'] }}"
                                                        name="author" x-model="authorVal"
                                                        placeholder="np. Jan Kowalski"
                                                        @keydown.escape="editingAuthor = false"
                                                        class="w-32 rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                                    <button type="submit" class="flex-none text-brand" aria-label="Zapisz"><i class="fa-solid fa-check text-xs" aria-hidden="true"></i></button>
                                                    <button type="button" @click="editingAuthor = false" class="flex-none text-muted" aria-label="Anuluj"><i class="fa-solid fa-xmark text-xs" aria-hidden="true"></i></button>
                                                </form>
                                            </template>
                                        </td>

                                        {{-- Size + date --}}
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-muted">
                                            <p>{{ $item['size'] }}</p>
                                            <p class="text-xs">{{ $item['created_at']->format('d.m.Y') }}</p>
                                        </td>

                                        {{-- Folder --}}
                                        @if ($allFolders->isNotEmpty())
                                            <td class="px-4 py-3">
                                                <form method="POST" action="{{ route('admin.multimedia.move', $item['id']) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <label class="sr-only" for="folder-list-{{ $item['id'] }}">Przenieś do folderu</label>
                                                    <select id="folder-list-{{ $item['id'] }}" name="folder_id"
                                                        onchange="this.form.submit()"
                                                        class="w-full min-w-[9rem] rounded-lg border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                                        <option value="" {{ ! $folder ? 'selected' : '' }}>— główny katalog —</option>
                                                        @foreach ($allFolders as $option)
                                                            <option value="{{ $option->id }}" {{ $folder?->id === $option->id ? 'selected' : '' }}>{{ $option->fullPath() }}</option>
                                                        @endforeach
                                                    </select>
                                                </form>
                                            </td>
                                        @endif

                                        {{-- Actions --}}
                                        <td class="px-4 py-3"
                                            x-data="{ editingAlt: false, altVal: @js($item['alt_editable']) }">
                                            <template x-if="editingAlt">
                                                <form method="POST" action="{{ route('admin.multimedia.alt', $item['id']) }}"
                                                    class="mb-2 flex items-center gap-1"
                                                    x-init="$nextTick(() => $refs['alt-{{ $item['id'] }}'].focus())">
                                                    @csrf
                                                    @method('PUT')
                                                    <label class="sr-only" for="alt-{{ $item['id'] }}">Opis alternatywny</label>
                                                    <input type="text" id="alt-{{ $item['id'] }}" x-ref="alt-{{ $item['id'] }}"
                                                        name="alt" x-model="altVal"
                                                        placeholder="Co przedstawia obraz?"
                                                        @keydown.escape="editingAlt = false"
                                                        class="w-36 rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                                    <button type="submit" class="flex-none text-brand" aria-label="Zapisz alt"><i class="fa-solid fa-check text-xs" aria-hidden="true"></i></button>
                                                    <button type="button" @click="editingAlt = false" class="flex-none text-muted" aria-label="Anuluj"><i class="fa-solid fa-xmark text-xs" aria-hidden="true"></i></button>
                                                </form>
                                            </template>
                                            <div class="flex items-center justify-end gap-1">
                                                @if ($item['is_image'])
                                                    <button type="button" @click="editingAlt = !editingAlt"
                                                        class="rounded-lg p-1.5 text-muted hover:bg-gray-100 hover:text-ink"
                                                        :title="altVal ? 'Edytuj opis alt: ' + altVal : 'Dodaj opis alt'"
                                                        :aria-label="altVal ? 'Edytuj opis alt' : 'Dodaj opis alt (brak)'">
                                                        <i class="fa-solid" :class="altVal ? 'fa-image text-green-600' : 'fa-image text-amber-500'" aria-hidden="true"></i>
                                                    </button>
                                                @endif
                                                @if ($item['archived'])
                                                    <form method="POST" action="{{ route('admin.multimedia.restore', $item['id']) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit"
                                                            class="rounded-lg p-1.5 text-brand hover:bg-brand-light"
                                                            title="Przywróć z archiwum" aria-label="Przywróć z archiwum">
                                                            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('admin.multimedia.archive', $item['id']) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit"
                                                            class="rounded-lg p-1.5 text-muted hover:bg-gray-100 hover:text-ink"
                                                            title="Schowaj / archiwizuj" aria-label="Schowaj / archiwizuj">
                                                            <i class="fa-solid fa-box-archive" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif

                                                <form method="POST" action="{{ route('admin.multimedia.destroy', $item['id']) }}"
                                                    onsubmit="return confirm('Usunąć plik &quot;{{ $item['file_name'] }}&quot;? Jeśli jest używany, zniknie też z miejsca, w którym się wyświetlał.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="rounded-lg p-1.5 text-muted hover:bg-red-50 hover:text-red-600"
                                                        title="Usuń plik" aria-label="Usuń plik">
                                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="mt-6">
                    {{ $media->links() }}
                </div>
            </div>
        </div>

        {{-- ── Image lightbox ───────────────────────────────────────────────── --}}
        <div x-show="lightbox" x-cloak
            @keydown.escape.window="lightbox = null"
            @click="lightbox = null"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/85 p-4"
            role="dialog" aria-modal="true" aria-label="Podgląd obrazu"
            x-transition.opacity>

            {{-- Close --}}
            <button type="button" @click="lightbox = null"
                class="absolute right-4 top-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/25 focus:outline-none focus:ring-2 focus:ring-white/50"
                aria-label="Zamknij podgląd (Esc)">
                <i class="fa-solid fa-xmark text-xl" aria-hidden="true"></i>
            </button>

            {{-- Prev arrow --}}
            <button type="button" @click.stop="lightboxPrev()"
                x-show="lightboxIdx > 0"
                class="absolute left-4 top-1/2 z-10 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/25 focus:outline-none focus:ring-2 focus:ring-white/50"
                aria-label="Poprzedni obraz (strzałka w lewo)">
                <i class="fa-solid fa-chevron-left text-lg" aria-hidden="true"></i>
            </button>

            {{-- Next arrow --}}
            <button type="button" @click.stop="lightboxNext()"
                x-show="lightboxIdx < images.length - 1"
                class="absolute right-4 top-1/2 z-10 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/25 focus:outline-none focus:ring-2 focus:ring-white/50"
                aria-label="Następny obraz (strzałka w prawo)">
                <i class="fa-solid fa-chevron-right text-lg" aria-hidden="true"></i>
            </button>

            {{-- Image + caption --}}
            <figure class="flex max-h-full max-w-4xl flex-col items-center" @click.stop>

                {{-- Loading spinner --}}
                <div x-show="!lightboxLoaded" class="flex h-[40vh] w-full items-center justify-center">
                    <i class="fa-solid fa-spinner fa-spin text-4xl text-white/40" aria-hidden="true"></i>
                </div>

                <img :src="lightbox?.url" :alt="lightbox?.alt"
                    class="mx-auto max-h-[80vh] w-auto rounded-xl object-contain shadow-2xl"
                    :class="lightboxLoaded ? 'block' : 'hidden'"
                    @load="lightboxLoaded = true">

                <figcaption class="mt-3 w-full space-y-1 text-center" x-show="lightboxLoaded">
                    <p class="text-sm font-bold text-white" x-text="lightbox?.file_name"></p>
                    <p class="text-xs text-white/60"
                        x-text="[lightbox?.size, lightbox?.mime_type].filter(Boolean).join(' · ')"></p>
                    <template x-if="lightbox?.author && lightbox.author !== 'System'">
                        <p class="text-xs text-white/50">
                            <i class="fa-solid fa-user text-[10px]" aria-hidden="true"></i>
                            <span x-text="lightbox?.author"></span>
                        </p>
                    </template>
                    <template x-if="lightboxIdx >= 0 && images.length > 1">
                        <p class="text-xs text-white/40" x-text="(lightboxIdx + 1) + ' / ' + images.length"></p>
                    </template>
                </figcaption>
            </figure>
        </div>

        <div class="sr-only" aria-live="polite" x-text="copiedId ? 'Adres URL skopiowany do schowka.' : ''"></div>
    </div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css"
    integrity="sha512-UtLOu9C7NuThQhuXXrGwx9Jb/z9zPQJcM85ND7HF1GGZc0H4eiL4o91A/GBUDqmAzgme6oAnq35AggAVb9fA==" crossorigin="anonymous">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"
    integrity="sha512-JyCZjCOZoyeQZSd5+YEAcFgz2fowJ1F1hyJOXgtKu4llIa0KneLcidn5bwfutiehqlCPSRQLzKqeHkgykc0SQ==" crossorigin="anonymous" defer></script>
@endpush
