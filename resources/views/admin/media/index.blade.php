@extends('admin.layout')

@section('title', 'Multimedia')

@section('content')
    <div x-data="{
            view: localStorage.getItem('media-view') || 'grid',
            lightbox: null,
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
        x-init="$watch('view', value => localStorage.setItem('media-view', value))">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
            {{-- Folder tree --}}
            <aside class="w-full flex-none lg:w-60">
                <div class="rounded-lg border border-gray-200 bg-white p-3">
                    <p class="mb-2 px-2 text-xs font-bold uppercase tracking-wide text-muted">Foldery</p>

                    <a href="{{ route('admin.multimedia.index', ['archived' => $showArchived ? 1 : null]) }}"
                        class="flex items-center gap-2 rounded px-2 py-1 text-sm {{ ! $folder ? 'bg-brand-light font-bold text-brand' : 'text-ink hover:bg-gray-100' }}">
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
            </aside>

            {{-- Main column --}}
            <div class="min-w-0 flex-1">
                <nav aria-label="Ścieżka folderów" class="mb-4 flex flex-wrap items-center gap-1 text-sm text-muted">
                    <a href="{{ route('admin.multimedia.index', ['archived' => $showArchived ? 1 : null]) }}" class="hover:text-brand {{ ! $folder ? 'font-bold text-ink' : '' }}">
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

                {{-- Toolbar: folder creation + view / archive / export-all / import --}}
                <div class="mb-4 flex flex-wrap items-center justify-between gap-4 rounded-lg border border-gray-200 bg-white p-4">
                    <form method="POST" action="{{ route('admin.multimedia.foldery.store') }}" class="flex items-center gap-2">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ $folder?->id }}">
                        <label class="sr-only" for="new-folder-name">Nazwa nowego folderu</label>
                        <input type="text" id="new-folder-name" name="name" placeholder="Nazwa nowego folderu" required
                            class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        <button type="submit" class="rounded border border-brand px-3 py-2 text-xs font-bold text-brand hover:bg-brand-light">
                            <i class="fa-solid fa-folder-plus" aria-hidden="true"></i> Nowy folder
                        </button>
                    </form>

                    <div class="flex flex-wrap items-center gap-3">
                        {{-- Grid / list view switch --}}
                        <div class="flex items-center rounded border border-gray-300" role="group" aria-label="Widok multimediów">
                            <button type="button" @click="view = 'grid'"
                                :class="view === 'grid' ? 'bg-brand-light text-brand' : 'text-muted hover:bg-gray-100'"
                                class="rounded-l px-3 py-2 text-xs font-bold" aria-label="Widok kafelków">
                                <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i>
                            </button>
                            <button type="button" @click="view = 'list'"
                                :class="view === 'list' ? 'bg-brand-light text-brand' : 'text-muted hover:bg-gray-100'"
                                class="rounded-r border-l border-gray-300 px-3 py-2 text-xs font-bold" aria-label="Widok listy">
                                <i class="fa-solid fa-list" aria-hidden="true"></i>
                            </button>
                        </div>

                        <a href="{{ route('admin.multimedia.index', ['folder' => $folder?->id, 'archived' => $showArchived ? null : 1]) }}"
                            class="rounded border px-3 py-2 text-xs font-bold {{ $showArchived ? 'border-brand bg-brand-light text-brand' : 'border-gray-300 text-muted hover:bg-gray-100' }}">
                            <i class="fa-solid fa-box-archive" aria-hidden="true"></i>
                            {{ $showArchived ? 'Pokaż aktywne pliki' : 'Pokaż archiwum' }}
                        </a>

                        <a href="{{ route('admin.multimedia.alt-audit') }}"
                            class="rounded border border-gray-300 px-3 py-2 text-xs font-bold text-muted hover:bg-gray-100">
                            <i class="fa-solid fa-universal-access" aria-hidden="true"></i>
                            Audyt alt-text
                        </a>

                        <a href="{{ route('admin.multimedia.export', ['folder' => $folder?->id]) }}"
                            class="rounded border border-gray-300 px-3 py-2 text-xs font-bold text-muted hover:bg-gray-100">
                            <i class="fa-solid fa-file-zipper" aria-hidden="true"></i>
                            {{ $folder ? 'Eksportuj folder' : 'Eksportuj wszystko' }}
                        </a>

                        <form method="POST" action="{{ route('admin.multimedia.import') }}" enctype="multipart/form-data"
                            x-data class="flex items-center gap-2">
                            @csrf
                            <input type="hidden" name="folder_id" value="{{ $folder?->id }}">
                            <label class="sr-only" for="media-import">Zaimportuj archiwum ZIP z plikami</label>
                            <input type="file" name="archive" id="media-import" accept=".zip" required
                                @change="$el.form.requestSubmit()"
                                class="cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-bold file:text-ink hover:file:bg-gray-200">
                            <button type="submit" class="rounded border border-gray-300 px-3 py-2 text-xs font-bold text-muted hover:bg-gray-100">
                                <i class="fa-solid fa-file-import" aria-hidden="true"></i> Importuj ZIP
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Multi-file upload with drag & drop --}}
                @unless ($showArchived)
                    <form method="POST" action="{{ route('admin.multimedia.store') }}" enctype="multipart/form-data"
                        x-data="{
                            files: [],
                            drag: false,
                            setFiles(list) { this.files = Array.from(list).map(f => f.name); },
                            onDrop(event) { this.drag = false; this.$refs.input.files = event.dataTransfer.files; this.setFiles(event.dataTransfer.files); },
                            clear() { this.$refs.input.value = ''; this.files = []; }
                        }"
                        @dragover.prevent="drag = true" @dragleave.prevent="drag = false"
                        @drop.prevent="onDrop($event)"
                        class="mb-4">
                        @csrf
                        <input type="hidden" name="folder_id" value="{{ $folder?->id }}">

                        <input id="media-upload" x-ref="input" type="file" name="files[]" multiple required
                            class="peer sr-only" @change="setFiles($event.target.files)">
                        <label for="media-upload"
                            class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed p-6 text-center transition peer-focus-visible:ring-2 peer-focus-visible:ring-brand"
                            :class="drag ? 'border-brand bg-brand-light' : 'border-gray-300 bg-white hover:border-brand hover:bg-gray-50'">
                            <i class="fa-solid fa-cloud-arrow-up text-3xl text-brand" aria-hidden="true"></i>
                            <span class="text-sm font-bold text-ink">Przeciągnij pliki tutaj lub kliknij, aby wybrać</span>
                            <span class="text-xs text-muted">Możesz dodać wiele plików naraz (do 10 MB każdy).</span>
                        </label>

                        <template x-if="files.length">
                            <div class="mt-3 rounded-lg border border-gray-200 bg-white p-4">
                                <p class="mb-2 text-xs font-bold uppercase tracking-wide text-muted">
                                    Wybrane pliki (<span x-text="files.length"></span>)
                                </p>
                                <ul class="mb-3 max-h-40 space-y-1 overflow-y-auto text-sm text-ink">
                                    <template x-for="(name, i) in files" :key="i">
                                        <li class="flex items-center gap-2">
                                            <i class="fa-solid fa-file text-muted" aria-hidden="true"></i>
                                            <span class="truncate" x-text="name"></span>
                                        </li>
                                    </template>
                                </ul>
                                <div class="flex items-center gap-3">
                                    <button type="submit"
                                        class="rounded bg-brand px-4 py-2 text-xs font-bold text-white hover:bg-brand-dark">
                                        <i class="fa-solid fa-upload" aria-hidden="true"></i>
                                        Prześlij pliki (<span x-text="files.length"></span>)
                                    </button>
                                    <button type="button" @click="clear()"
                                        class="text-xs font-bold text-muted hover:text-ink">Wyczyść</button>
                                </div>
                            </div>
                        </template>
                    </form>
                @endunless

                @error('files') <p class="mb-4 text-sm text-red-600">{{ $message }}</p> @enderror
                @error('files.*') <p class="mb-4 text-sm text-red-600">{{ $message }}</p> @enderror
                @error('archive') <p class="mb-4 text-sm text-red-600">{{ $message }}</p> @enderror
                @error('name') <p class="mb-4 text-sm text-red-600">{{ $message }}</p> @enderror

                @if ($showArchived)
                    <div class="mb-6 flex items-center gap-2 rounded border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-muted">
                        <i class="fa-solid fa-box-archive" aria-hidden="true"></i>
                        Przeglądasz archiwum. Schowane pliki nie pojawiają się w bibliotece ani w wyborze zdjęć w edytorze, ale nie zostały usunięte.
                    </div>
                @endif

                {{-- Bulk action bar: appears once files are selected --}}
                <div x-show="selected.length" x-cloak x-transition
                    class="sticky top-2 z-30 mb-4 flex flex-wrap items-center gap-3 rounded-lg border border-brand bg-brand-light p-3 shadow"
                    role="region" aria-label="Akcje dla zaznaczonych plików">
                    <p class="text-sm font-bold text-brand" aria-live="polite">
                        Zaznaczono: <span x-text="selected.length"></span>
                    </p>

                    <div class="flex flex-1 flex-wrap items-center justify-end gap-2">
                        {{-- Export selected as ZIP --}}
                        <form method="POST" action="{{ route('admin.multimedia.export-selected') }}">
                            @csrf
                            <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                            <button type="submit" class="rounded border border-gray-300 bg-white px-3 py-2 text-xs font-bold text-muted hover:bg-gray-100">
                                <i class="fa-solid fa-file-zipper" aria-hidden="true"></i> Eksportuj ZIP
                            </button>
                        </form>

                        @if (! $showArchived && $allFolders->isNotEmpty())
                            {{-- Move selected to a folder --}}
                            <form method="POST" action="{{ route('admin.multimedia.bulk') }}" class="flex items-center gap-1">
                                @csrf
                                <input type="hidden" name="action" value="move">
                                <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                                <label class="sr-only" for="bulk-folder">Przenieś zaznaczone do folderu</label>
                                <select id="bulk-folder" name="folder_id"
                                    class="rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                    <option value="">— główny katalog —</option>
                                    @foreach ($allFolders as $option)
                                        <option value="{{ $option->id }}">{{ $option->fullPath() }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="rounded border border-gray-300 bg-white px-3 py-2 text-xs font-bold text-muted hover:bg-gray-100">
                                    Przenieś
                                </button>
                            </form>
                        @endif

                        @if ($showArchived)
                            {{-- Restore selected --}}
                            <form method="POST" action="{{ route('admin.multimedia.bulk') }}">
                                @csrf
                                <input type="hidden" name="action" value="restore">
                                <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                                <button type="submit" class="rounded border border-brand bg-white px-3 py-2 text-xs font-bold text-brand hover:bg-brand-light">
                                    <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Przywróć
                                </button>
                            </form>
                        @else
                            {{-- Archive selected --}}
                            <form method="POST" action="{{ route('admin.multimedia.bulk') }}">
                                @csrf
                                <input type="hidden" name="action" value="archive">
                                <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                                <button type="submit" class="rounded border border-gray-300 bg-white px-3 py-2 text-xs font-bold text-muted hover:bg-gray-100">
                                    <i class="fa-solid fa-box-archive" aria-hidden="true"></i> Schowaj
                                </button>
                            </form>
                        @endif

                        {{-- Delete selected --}}
                        <form method="POST" action="{{ route('admin.multimedia.bulk') }}"
                            @submit="if (! confirm('Usunąć zaznaczone pliki (' + selected.length + ')? Jeśli któryś jest używany, zniknie też z miejsca, w którym się wyświetlał.')) $event.preventDefault();">
                            @csrf
                            <input type="hidden" name="action" value="delete">
                            <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                            <button type="submit" class="rounded border border-red-300 bg-white px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-50">
                                <i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń
                            </button>
                        </form>

                        <button type="button" @click="clearSelection()"
                            class="rounded px-3 py-2 text-xs font-bold text-muted hover:text-ink">
                            Odznacz wszystkie
                        </button>
                    </div>
                </div>

                @if ($media->isEmpty())
                    <p class="py-6 text-center text-muted">
                        {{ $showArchived ? 'Brak schowanych plików w tym folderze.' : 'Brak plików w tym folderze.' }}
                    </p>
                @else
                    {{-- Select-all-on-page --}}
                    <div class="mb-3 flex items-center gap-2">
                        <input type="checkbox" id="select-all" :checked="allSelected" @change="toggleAll()"
                            class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                        <label for="select-all" class="text-sm text-muted">Zaznacz wszystkie na tej stronie</label>
                    </div>

                    {{-- Grid view --}}
                    <div x-show="view === 'grid'" x-cloak class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                        @foreach ($media as $item)
                            <div class="overflow-hidden rounded-lg border bg-white transition {{ $item['archived'] ? 'opacity-75' : '' }}"
                                :class="isSelected({{ $item['id'] }}) ? 'border-brand ring-2 ring-brand' : 'border-gray-200'">
                                <div class="relative flex h-32 items-center justify-center bg-gray-50">
                                    <div class="absolute left-2 top-2 z-10">
                                        <input type="checkbox" id="sel-{{ $item['id'] }}"
                                            :checked="isSelected({{ $item['id'] }})" @change="toggle({{ $item['id'] }})"
                                            class="h-5 w-5 rounded border-gray-300 bg-white text-brand shadow focus:ring-brand">
                                        <label for="sel-{{ $item['id'] }}" class="sr-only">Zaznacz plik {{ $item['file_name'] }}</label>
                                    </div>

                                    @if ($item['archived'])
                                        <span class="absolute right-2 top-2 z-10 rounded bg-gray-800/80 px-2 py-0.5 text-xs font-bold text-white">
                                            W archiwum
                                        </span>
                                    @endif

                                    @if ($item['is_image'])
                                        <button type="button" class="block h-full w-full cursor-zoom-in"
                                            @click="lightbox = { url: @js($item['url']), alt: @js($item['alt']), caption: @js($item['file_name']) }"
                                            aria-label="Powiększ obraz: {{ $item['alt'] }}">
                                            <img src="{{ $item['url'] }}" alt="{{ $item['alt'] }}" class="h-full w-full object-cover">
                                        </button>
                                    @else
                                        <i class="fa-solid fa-file text-3xl text-muted" aria-hidden="true"></i>
                                    @endif
                                </div>
                                <div class="space-y-1 p-3">
                                    <p class="truncate text-xs font-bold" title="{{ $item['file_name'] }}">{{ $item['file_name'] }}</p>
                                    <p class="text-xs text-muted">{{ $item['owner']['label'] }} &middot; {{ $item['collection'] }}</p>
                                    <p class="text-xs text-muted">{{ $item['size'] }} &middot; {{ $item['created_at']->format('d.m.Y') }}</p>

                                    {{-- File URL + copy --}}
                                    <div class="flex items-center gap-1 pt-1">
                                        <label class="sr-only" for="url-{{ $item['id'] }}">Adres URL pliku {{ $item['file_name'] }}</label>
                                        <input type="text" id="url-{{ $item['id'] }}" readonly value="{{ $item['url'] }}"
                                            @focus="$event.target.select()"
                                            class="min-w-0 flex-1 truncate rounded border-gray-200 bg-gray-50 px-2 py-1 text-xs text-muted focus:border-brand focus:ring-brand">
                                        <button type="button" @click="copy(@js($item['url']), {{ $item['id'] }})"
                                            class="flex-none rounded border border-gray-300 px-2 py-1 hover:bg-gray-100"
                                            :aria-label="copiedId === {{ $item['id'] }} ? 'Skopiowano adres URL' : 'Kopiuj adres URL'">
                                            <i class="fa-solid" :class="copiedId === {{ $item['id'] }} ? 'fa-check text-green-600' : 'fa-copy text-muted'" aria-hidden="true"></i>
                                        </button>
                                    </div>

                                    @if ($allFolders->isNotEmpty())
                                        <form method="POST" action="{{ route('admin.multimedia.move', $item['id']) }}" class="pt-1">
                                            @csrf
                                            @method('PUT')
                                            <label class="sr-only" for="folder-{{ $item['id'] }}">Przenieś do folderu</label>
                                            <select id="folder-{{ $item['id'] }}" name="folder_id" onchange="this.form.submit()"
                                                class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                                <option value="" {{ ! $folder ? 'selected' : '' }}>— główny katalog —</option>
                                                @foreach ($allFolders as $option)
                                                    <option value="{{ $option->id }}" {{ $folder?->id === $option->id ? 'selected' : '' }}>{{ $option->fullPath() }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @endif

                                    <div class="pt-1">
                                        @if ($item['archived'])
                                            <form method="POST" action="{{ route('admin.multimedia.restore', $item['id']) }}">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="flex w-full items-center justify-center gap-1 rounded border border-brand px-2 py-1.5 text-xs font-bold text-brand hover:bg-brand-light">
                                                    <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Przywróć z archiwum
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.multimedia.archive', $item['id']) }}">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="flex w-full items-center justify-center gap-1 rounded border border-gray-300 px-2 py-1.5 text-xs font-bold text-muted hover:bg-gray-100 hover:text-ink">
                                                    <i class="fa-solid fa-box-archive" aria-hidden="true"></i> Schowaj / archiwizuj
                                                </button>
                                            </form>
                                        @endif
                                    </div>

                                    @if ($item['is_image'])
                                        <div class="pt-1">
                                            @if ($item['is_webp'])
                                                <span class="inline-flex items-center gap-1 rounded bg-green-100 px-1.5 py-0.5 text-xs font-bold text-green-700">
                                                    <i class="fa-solid fa-check" aria-hidden="true"></i> WebP
                                                </span>
                                            @else
                                                <span class="inline-block rounded border border-amber-200 bg-amber-50 px-1.5 py-0.5 text-xs font-bold text-amber-700">
                                                    {{ strtoupper(pathinfo($item['file_name'], PATHINFO_EXTENSION)) }}
                                                </span>
                                                @if ($item['has_webp_conversion'])
                                                    <form method="POST" action="{{ route('admin.multimedia.konwertuj-webp', $item['id']) }}" class="mt-1">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit"
                                                            onclick="return confirm('Zastąpić oryginalny plik wersją WebP? Operacja jest nieodwracalna.')"
                                                            class="flex w-full items-center justify-center gap-1 rounded border border-green-300 px-2 py-1.5 text-xs font-bold text-green-700 hover:bg-green-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-500">
                                                            <i class="fa-solid fa-rotate" aria-hidden="true"></i> Zastąp WebP
                                                        </button>
                                                    </form>
                                                @endif
                                            @endif
                                        </div>
                                    @endif

                                    <div class="flex items-center justify-between pt-2">
                                        @if ($item['owner']['url'])
                                            <a href="{{ $item['owner']['url'] }}" class="text-xs font-bold text-brand hover:text-brand-dark">Otwórz</a>
                                        @elseif ($item['owner']['standalone'] ?? false)
                                            <span class="text-xs text-muted">Plik w bibliotece</span>
                                        @else
                                            <span class="text-xs text-muted">Osierocony plik</span>
                                        @endif

                                        <form method="POST" action="{{ route('admin.multimedia.destroy', $item['id']) }}"
                                            onsubmit="return confirm('Usunąć plik &quot;{{ $item['file_name'] }}&quot;? Jeśli jest używany, zniknie też z miejsca, w którym się wyświetlał.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-muted hover:text-red-600" title="Usuń"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- List view --}}
                    <div x-show="view === 'list'" x-cloak class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-gray-200 text-xs uppercase tracking-wide text-muted">
                                <tr>
                                    <th scope="col" class="p-3">
                                        <input type="checkbox" :checked="allSelected" @change="toggleAll()"
                                            class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand"
                                            aria-label="Zaznacz wszystkie na tej stronie">
                                    </th>
                                    <th scope="col" class="p-3"><span class="sr-only">Podgląd</span></th>
                                    <th scope="col" class="p-3">Nazwa</th>
                                    <th scope="col" class="p-3">Adres URL</th>
                                    <th scope="col" class="p-3">Rozmiar</th>
                                    <th scope="col" class="p-3">Dodano</th>
                                    @if ($allFolders->isNotEmpty())
                                        <th scope="col" class="p-3">Folder</th>
                                    @endif
                                    <th scope="col" class="p-3 text-right">Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($media as $item)
                                    <tr class="border-b border-gray-100 last:border-0 {{ $item['archived'] ? 'opacity-75' : '' }}"
                                        :class="isSelected({{ $item['id'] }}) ? 'bg-brand-light' : ''">
                                        <td class="p-3">
                                            <input type="checkbox" id="sel-list-{{ $item['id'] }}"
                                                :checked="isSelected({{ $item['id'] }})" @change="toggle({{ $item['id'] }})"
                                                class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                                            <label for="sel-list-{{ $item['id'] }}" class="sr-only">Zaznacz plik {{ $item['file_name'] }}</label>
                                        </td>
                                        <td class="p-3">
                                            <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded bg-gray-50">
                                                @if ($item['is_image'])
                                                    <button type="button" class="block h-full w-full cursor-zoom-in"
                                                        @click="lightbox = { url: @js($item['url']), alt: @js($item['alt']), caption: @js($item['file_name']) }"
                                                        aria-label="Powiększ obraz: {{ $item['alt'] }}">
                                                        <img src="{{ $item['url'] }}" alt="{{ $item['alt'] }}" class="h-full w-full object-cover">
                                                    </button>
                                                @else
                                                    <i class="fa-solid fa-file text-muted" aria-hidden="true"></i>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="max-w-xs p-3">
                                            <p class="truncate font-bold" title="{{ $item['file_name'] }}">{{ $item['file_name'] }}</p>
                                            <p class="text-xs text-muted">
                                                {{ $item['owner']['label'] }} &middot; {{ $item['collection'] }}
                                                @if ($item['archived'])
                                                    &middot; <span class="font-bold text-ink">W archiwum</span>
                                                @endif
                                            </p>
                                            @if ($item['is_image'])
                                                @if ($item['is_webp'])
                                                    <span class="mt-0.5 inline-flex items-center gap-0.5 rounded bg-green-100 px-1.5 py-0.5 text-xs font-bold text-green-700">
                                                        <i class="fa-solid fa-check" aria-hidden="true"></i> WebP
                                                    </span>
                                                @else
                                                    <span class="mt-0.5 inline-block rounded border border-amber-200 bg-amber-50 px-1.5 py-0.5 text-xs font-bold text-amber-700">
                                                        {{ strtoupper(pathinfo($item['file_name'], PATHINFO_EXTENSION)) }}
                                                    </span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="p-3">
                                            <div class="flex items-center gap-1">
                                                <label class="sr-only" for="url-list-{{ $item['id'] }}">Adres URL pliku {{ $item['file_name'] }}</label>
                                                <input type="text" id="url-list-{{ $item['id'] }}" readonly value="{{ $item['url'] }}"
                                                    @focus="$event.target.select()"
                                                    class="w-40 truncate rounded border-gray-200 bg-gray-50 px-2 py-1 text-xs text-muted focus:border-brand focus:ring-brand">
                                                <button type="button" @click="copy(@js($item['url']), {{ $item['id'] }})"
                                                    class="flex-none rounded border border-gray-300 px-2 py-1 hover:bg-gray-100"
                                                    :aria-label="copiedId === {{ $item['id'] }} ? 'Skopiowano adres URL' : 'Kopiuj adres URL'">
                                                    <i class="fa-solid" :class="copiedId === {{ $item['id'] }} ? 'fa-check text-green-600' : 'fa-copy text-muted'" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap p-3 text-muted">{{ $item['size'] }}</td>
                                        <td class="whitespace-nowrap p-3 text-muted">{{ $item['created_at']->format('d.m.Y') }}</td>
                                        @if ($allFolders->isNotEmpty())
                                            <td class="p-3">
                                                <form method="POST" action="{{ route('admin.multimedia.move', $item['id']) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <label class="sr-only" for="folder-list-{{ $item['id'] }}">Przenieś do folderu</label>
                                                    <select id="folder-list-{{ $item['id'] }}" name="folder_id" onchange="this.form.submit()"
                                                        class="w-full min-w-[10rem] rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                                        <option value="" {{ ! $folder ? 'selected' : '' }}>— główny katalog —</option>
                                                        @foreach ($allFolders as $option)
                                                            <option value="{{ $option->id }}" {{ $folder?->id === $option->id ? 'selected' : '' }}>{{ $option->fullPath() }}</option>
                                                        @endforeach
                                                    </select>
                                                </form>
                                            </td>
                                        @endif
                                        <td class="p-3">
                                            <div class="flex items-center justify-end gap-3">
                                                @if ($item['owner']['url'])
                                                    <a href="{{ $item['owner']['url'] }}" class="text-xs font-bold text-brand hover:text-brand-dark">Otwórz</a>
                                                @endif

                                                @if ($item['has_webp_conversion'])
                                                    <form method="POST" action="{{ route('admin.multimedia.konwertuj-webp', $item['id']) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit"
                                                            onclick="return confirm('Zastąpić oryginalny plik wersją WebP? Operacja jest nieodwracalna.')"
                                                            class="text-green-600 hover:text-green-800" title="Zastąp WebP" aria-label="Zastąp plik wersją WebP">
                                                            <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @endif

                                                @if ($item['archived'])
                                                    <form method="POST" action="{{ route('admin.multimedia.restore', $item['id']) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="text-brand hover:text-brand-dark" title="Przywróć z archiwum" aria-label="Przywróć z archiwum">
                                                            <i class="fa-solid fa-rotate-left"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('admin.multimedia.archive', $item['id']) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="text-muted hover:text-ink" title="Schowaj / archiwizuj" aria-label="Schowaj / archiwizuj">
                                                            <i class="fa-solid fa-box-archive"></i>
                                                        </button>
                                                    </form>
                                                @endif

                                                <form method="POST" action="{{ route('admin.multimedia.destroy', $item['id']) }}"
                                                    onsubmit="return confirm('Usunąć plik &quot;{{ $item['file_name'] }}&quot;? Jeśli jest używany, zniknie też z miejsca, w którym się wyświetlał.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń plik"><i class="fa-solid fa-trash"></i></button>
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

        {{-- Image lightbox --}}
        <div x-show="lightbox" x-cloak
            @keydown.escape.window="lightbox = null"
            @click="lightbox = null"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
            role="dialog" aria-modal="true" aria-label="Podgląd obrazu"
            x-transition.opacity>
            <button type="button" @click="lightbox = null"
                class="absolute right-4 top-4 text-3xl text-white/80 hover:text-white" aria-label="Zamknij podgląd">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <figure class="max-w-4xl" @click.stop>
                <img :src="lightbox?.url" :alt="lightbox?.alt" class="mx-auto max-h-[80vh] w-auto rounded object-contain">
                <figcaption class="mt-3 text-center text-sm text-white/80" x-text="lightbox?.caption"></figcaption>
            </figure>
        </div>

        {{-- Screen-reader announcement for copy-to-clipboard --}}
        <div class="sr-only" aria-live="polite" x-text="copiedId ? 'Adres URL skopiowany do schowka.' : ''"></div>
    </div>
@endsection
