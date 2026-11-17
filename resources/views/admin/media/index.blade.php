@extends('admin.layout')

@section('title', 'Pliki')

@section('content')
    @php
        $q = fn (array $extra = []) => array_filter(array_merge([
            'folder' => $folder?->id, 'archived' => $showArchived ? 1 : null, 'q' => $currentSearch,
            'tag' => $currentTag, 'kind' => $currentKind,
        ], $extra), fn ($v) => $v !== null && $v !== '');
        $navItem = 'flex items-center gap-2 rounded-md px-2 py-1.5 text-sm';
        $navOn = 'bg-brand-light font-bold text-brand-dark';
        $navOff = 'text-ink hover:bg-gray-100';
        $btn = 'inline-flex min-h-10 items-center gap-2 rounded-md px-4 text-sm font-bold';
        $usedPct = $storageQuota ? min(100, (int) round($storageUsed / max(1, $storageQuota) * 100)) : null;
        $usageConfirm = session('usage_confirm');
        // Szuflada otwarta po zapisie w niej (flash) albo z odnośnika ?plik={id} (np. z audytu dokumentów).
        $openPanelId = (int) (session('media_panel') ?: request()->integer('plik'));
        $kindIcon = ['image' => 'fa-image', 'document' => 'fa-file-lines', 'other' => 'fa-file'];
        $a11yText = ['ok' => 'dostępny', 'warn' => 'drobne uwagi', 'fail' => 'wymaga poprawy', 'error' => 'nie sprawdzono', 'unchecked' => 'nie sprawdzono'];
        $showFolderTiles = ! $isFiltered && $media->currentPage() === 1 && ($subfolders->isNotEmpty() || $folder);
        $parentCrumb = $folder ? collect($breadcrumbs)->slice(0, -1)->last() : null;
        // Pliki tej strony dla podglądu z przewijaniem (←/→) i edytora obrazu.
        $previewItems = $media->getCollection()->map(fn ($i) => [
            'id' => $i['id'],
            'name' => $i['file_name'],
            'url' => $i['url'],
            'ext' => $i['ext'],
            'size' => $i['size'],
            'alt' => $i['is_image'] ? (string) $i['alt'] : '',
            'altNote' => ! $i['is_image'] ? null : ($i['has_alt'] ? 'Opis alternatywny: '.$i['alt'] : ($i['owner']['standalone'] ? 'Brak opisu alternatywnego — dodasz go w szczegółach pliku.' : null)),
            'kind' => match (true) {
                $i['is_image'] => 'image',
                str_starts_with((string) $i['mime_type'], 'video/') => 'video',
                str_starts_with((string) $i['mime_type'], 'audio/') => 'audio',
                $i['mime_type'] === 'application/pdf' => 'pdf',
                default => 'other',
            },
            'edit' => $i['edit'],
        ])->values();
        $libraryConfig = [
            'uploadOpen' => $errors->has('files') || $errors->has('files.*'),
            'pageIds' => $media->pluck('id')->values(),
            'items' => $previewItems,
            'canUpload' => ! $showArchived,
            'openPanelId' => $openPanelId ?: null,
            'panelUrl' => route('admin.multimedia.panel', '__ID__'),
        ];
        $menuItem = 'flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-ink hover:bg-brand-light focus-visible:bg-brand-light focus-visible:outline-none';
    @endphp

    {{-- Logika listy: Alpine.data('mediaLibrary') w @push('scripts') na dole widoku. --}}
    <div x-data="mediaLibrary(@js($libraryConfig))" @keydown.escape.window="onEscape()" @drop.window="dropActive = false" @dragend.window="dropActive = false">
        <p class="sr-only" aria-live="polite" x-text="announce"></p>

        {{-- ── Nagłówek: tytuł, miejsce, wgrywanie ───────────────────────── --}}
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-2xl font-bold text-ink">
                    {{ $folder?->name ?? ($showArchived ? 'Archiwum plików' : 'Pliki') }}
                </h1>
                <nav aria-label="Ścieżka folderów" class="mt-1 flex flex-wrap items-center gap-1 text-sm text-muted">
                    <a href="{{ route('admin.multimedia.index', array_filter(['archived' => $showArchived ? 1 : null])) }}" class="hover:text-brand-dark hover:underline">Wszystkie pliki</a>
                    @foreach ($breadcrumbs as $crumb)
                        <span aria-hidden="true">/</span>
                        @if ($loop->last)
                            <span aria-current="page" class="font-bold text-ink">{{ $crumb->name }}</span>
                        @else
                            <a href="{{ route('admin.multimedia.index', array_filter(['folder' => $crumb->id, 'archived' => $showArchived ? 1 : null])) }}" class="hover:text-brand-dark hover:underline">{{ $crumb->name }}</a>
                        @endif
                    @endforeach
                </nav>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <div class="min-w-[12rem]">
                    <p class="text-sm text-ink">
                        Zajęte miejsce: <strong>{{ \Illuminate\Support\Number::fileSize($storageUsed, 1) }}</strong>
                        @if ($storageQuota) z {{ \Illuminate\Support\Number::fileSize($storageQuota, 0) }} ({{ $usedPct }}%) @endif
                    </p>
                    @if ($storageQuota)
                        <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-200" aria-hidden="true">
                            <div class="h-full {{ $usedPct >= 90 ? 'bg-ink' : 'bg-brand-dark' }}" style="width: {{ $usedPct }}%"></div>
                        </div>
                    @endif
                    <a href="{{ route('admin.multimedia.cleanup') }}" class="text-xs font-bold text-brand-dark underline underline-offset-2 hover:no-underline">Zrób porządki</a>
                </div>
                @unless ($showArchived)
                    <button type="button" @click="uploadOpen = !uploadOpen" :aria-expanded="uploadOpen" aria-controls="upload-panel"
                        class="{{ $btn }} bg-brand-dark text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                        <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i> Wgraj pliki
                    </button>
                @endunless
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[15rem_minmax(0,1fr)] lg:items-start">

            {{-- ── Nawigacja ─────────────────────────────────────────────────── --}}
            <aside class="lg:sticky lg:top-4" aria-label="Foldery i filtry" x-data="{ navOpen: false }">
                {{-- Na telefonie nawigacja jest zwinięta, żeby pliki były od razu widoczne. --}}
                <button type="button" @click="navOpen = !navOpen" :aria-expanded="navOpen" aria-controls="media-nav"
                    class="{{ $btn }} w-full justify-between bg-white text-ink hover:bg-brand-light lg:hidden">
                    <span><i class="fa-solid fa-folder-tree mr-2 text-brand-dark" aria-hidden="true"></i>Foldery i filtry{{ $folder ? ': '.$folder->name : '' }}</span>
                    <i class="fa-solid" :class="navOpen ? 'fa-chevron-up' : 'fa-chevron-down'" aria-hidden="true"></i>
                </button>
                <div id="media-nav" x-show="navOpen" x-cloak class="mt-3 space-y-4 lg:mt-0 lg:block!">
                <nav class="rounded-md bg-white p-3" aria-labelledby="nav-folders">
                    <h2 id="nav-folders" class="mb-2 px-2 text-xs font-bold uppercase tracking-widest text-muted">Foldery</h2>
                    <a href="{{ route('admin.multimedia.index', array_filter(['archived' => $showArchived ? 1 : null])) }}"
                        @if (! $folder && ! $isFiltered) aria-current="page" @endif
                        class="{{ $navItem }} {{ ! $folder && ! $isFiltered ? $navOn : $navOff }}">
                        <i class="fa-solid fa-layer-group w-4 text-brand-dark" aria-hidden="true"></i> Wszystkie pliki
                    </a>
                    @include('admin.media.partials.folder-tree', [
                        'nodes' => $folderTree->get('', collect()),
                        'tree' => $folderTree,
                        'currentId' => $folder?->id,
                        'showArchived' => $showArchived,
                    ])
                    <form method="POST" action="{{ route('admin.multimedia.foldery.store') }}" class="mt-3 flex gap-1 px-1">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ $folder?->id }}">
                        <label class="sr-only" for="new-folder-name">Nazwa nowego folderu{{ $folder ? ' w folderze '.$folder->name : '' }}</label>
                        <input type="text" id="new-folder-name" name="name" placeholder="{{ $folder ? 'Podfolder…' : 'Nowy folder…' }}" required
                            class="min-w-0 flex-1 rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                        <button type="submit" class="flex-none rounded-md bg-gray-100 px-3 text-sm font-bold text-ink hover:bg-brand-light" aria-label="Utwórz folder">
                            <i class="fa-solid fa-folder-plus" aria-hidden="true"></i>
                        </button>
                    </form>
                </nav>

                <nav class="rounded-md bg-white p-3" aria-labelledby="nav-kinds">
                    <h2 id="nav-kinds" class="mb-2 px-2 text-xs font-bold uppercase tracking-widest text-muted">Rodzaj</h2>
                    @foreach (\App\Http\Controllers\Admin\MediaLibraryController::KINDS as $k => $label)
                        <a href="{{ route('admin.multimedia.index', $q(['kind' => $currentKind === $k ? null : $k])) }}"
                            @if ($currentKind === $k) aria-current="true" @endif
                            class="{{ $navItem }} {{ $currentKind === $k ? $navOn : $navOff }}">
                            <i class="fa-solid {{ $kindIcon[$k] }} w-4 text-brand-dark" aria-hidden="true"></i> {{ $label }}
                        </a>
                    @endforeach
                </nav>

                @if ($allTags->isNotEmpty())
                    <nav class="rounded-md bg-white p-3" aria-labelledby="nav-tags">
                        <h2 id="nav-tags" class="mb-2 px-2 text-xs font-bold uppercase tracking-widest text-muted">Tagi</h2>
                        <ul class="space-y-0.5" role="list">
                            @foreach ($allTags as $t)
                                <li>
                                    <a href="{{ route('admin.multimedia.index', $q(['tag' => $currentTag === $t ? null : $t])) }}"
                                        @if ($currentTag === $t) aria-current="true" @endif
                                        class="{{ $navItem }} {{ $currentTag === $t ? $navOn : $navOff }}">
                                        <span class="text-muted" aria-hidden="true">#</span><span class="truncate">{{ $t }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                @endif

                <nav class="rounded-md bg-white p-3" aria-labelledby="nav-tools">
                    <h2 id="nav-tools" class="mb-2 px-2 text-xs font-bold uppercase tracking-widest text-muted">Narzędzia</h2>
                    <a href="{{ route('admin.multimedia.cleanup') }}" class="{{ $navItem }} {{ $navOff }}"><i class="fa-solid fa-broom w-4 text-brand-dark" aria-hidden="true"></i> Porządki</a>
                    <a href="{{ route('admin.multimedia.documents') }}" class="{{ $navItem }} {{ $navOff }}"><i class="fa-solid fa-file-circle-check w-4 text-brand-dark" aria-hidden="true"></i> Dostępność dokumentów</a>
                    <a href="{{ route('admin.multimedia.alt-audit') }}" class="{{ $navItem }} {{ $navOff }}"><i class="fa-solid fa-universal-access w-4 text-brand-dark" aria-hidden="true"></i> Opisy alternatywne</a>
                    <a href="{{ route('admin.multimedia.index', array_filter(['folder' => $folder?->id, 'archived' => $showArchived ? null : 1])) }}"
                        class="{{ $navItem }} {{ $showArchived ? $navOn : $navOff }}">
                        <i class="fa-solid fa-box-archive w-4 text-brand-dark" aria-hidden="true"></i> {{ $showArchived ? 'Wróć do plików' : 'Archiwum' }}
                    </a>
                    <a href="{{ route('admin.multimedia.export', array_filter(['folder' => $folder?->id])) }}" class="{{ $navItem }} {{ $navOff }}">
                        <i class="fa-solid fa-file-zipper w-4 text-brand-dark" aria-hidden="true"></i> {{ $folder ? 'Pobierz folder (ZIP)' : 'Pobierz wszystko (ZIP)' }}
                    </a>
                    <form method="POST" action="{{ route('admin.multimedia.import') }}" enctype="multipart/form-data" x-data>
                        @csrf
                        <input type="hidden" name="folder_id" value="{{ $folder?->id }}">
                        <label class="{{ $navItem }} {{ $navOff }} cursor-pointer focus-within:ring-2 focus-within:ring-brand-dark">
                            <i class="fa-solid fa-file-import w-4 text-brand-dark" aria-hidden="true"></i> Importuj ZIP
                            <input type="file" name="archive" accept=".zip" required class="sr-only" @change="$el.form.requestSubmit()">
                        </label>
                    </form>
                </nav>

                {{-- Zalecane wymiary grafik (przeniesione z pulpitu): zwijany akapit przy narzędziach plików. --}}
                <section class="rounded-md bg-white p-3" x-data="{ open: false }" aria-labelledby="nav-sizes">
                    <h2 id="nav-sizes">
                        <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-controls="media-sizes"
                            class="flex w-full items-center justify-between gap-2 rounded-md px-2 py-1.5 text-left text-xs font-bold uppercase tracking-widest text-muted hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                            Zalecane wymiary grafik
                            <i class="fa-solid fa-chevron-down text-[0.65rem] transition-transform" :class="open ? 'rotate-180' : ''" aria-hidden="true"></i>
                        </button>
                    </h2>
                    <div id="media-sizes" x-show="open" x-cloak class="px-2 pb-1 pt-2">
                        <p class="mb-3 text-xs text-muted">Zdjęcia są przycinane, więc inne proporcje też zadziałają — poniższe dają najostrzejszy wygląd.</p>
                        <ul role="list" class="space-y-2 text-sm">
                            <li class="flex justify-between gap-2"><span class="font-medium text-ink">Logo</span><span class="text-right text-muted">400×400 px, PNG/SVG</span></li>
                            <li class="flex justify-between gap-2"><span class="font-medium text-ink">Slajder hero</span><span class="text-right text-muted">1600×600 px</span></li>
                            <li class="flex justify-between gap-2"><span class="font-medium text-ink">OG / udostępnianie</span><span class="text-right text-muted">1200×630 px</span></li>
                            <li class="flex justify-between gap-2"><span class="font-medium text-ink">Miniatury</span><span class="text-right text-muted">800×600 px</span></li>
                            <li class="flex justify-between gap-2"><span class="font-medium text-ink">Zdjęcie działania</span><span class="text-right text-muted">1200×500 px</span></li>
                            <li class="flex justify-between gap-2"><span class="font-medium text-ink">Galeria</span><span class="text-right text-muted">min. 600×450 px</span></li>
                            <li class="flex justify-between gap-2"><span class="font-medium text-ink">Logo partnera</span><span class="text-right text-muted">do 300 px, PNG</span></li>
                        </ul>
                    </div>
                </section>
                </div>
            </aside>

            {{-- ── Główna kolumna (upuszczenie plików gdziekolwiek tutaj otwiera wgrywanie do bieżącego folderu) ── --}}
            <div class="relative min-w-0 space-y-4" @dragover="onDragOver($event)" @dragleave="onDragLeave($event, $el)" @drop="onDropFiles($event)">
                @unless ($showArchived)
                    <div x-show="dropActive" x-cloak class="pointer-events-none absolute inset-0 z-40 flex items-start justify-center rounded-md bg-brand-light/95 outline-2 outline-dashed outline-brand-dark" aria-hidden="true">
                        <p class="sticky top-1/3 mt-24 flex items-center gap-3 rounded-md bg-white px-6 py-4 text-base font-bold text-ink shadow-lg">
                            <i class="fa-solid fa-cloud-arrow-up text-2xl text-brand-dark"></i>
                            Upuść pliki, aby wgrać je {{ $folder ? 'do folderu „'.$folder->name.'”' : 'do katalogu głównego' }}
                        </p>
                    </div>
                @endunless

                {{-- Uprawnienia bieżącego folderu (administrator) --}}
                @if ($folder && auth()->user()->isAdmin())
                    @php $folderGroups = $folder->userGroups()->pluck('user_groups.id')->all(); @endphp
                    <details class="rounded-md bg-white p-4" @if ($errors->has('groups')) open @endif>
                        <summary class="cursor-pointer text-sm font-bold text-ink">
                            <i class="fa-solid {{ $folderGroups ? 'fa-lock' : 'fa-lock-open' }} mr-1 text-brand-dark" aria-hidden="true"></i>
                            Kto widzi ten folder:
                            <span class="font-normal">{{ $folderGroups ? 'administratorzy i wybrane grupy' : 'wszyscy z dostępem do plików' }}</span>
                        </summary>
                        <form method="POST" action="{{ route('admin.multimedia.foldery.permissions', $folder) }}" class="mt-3">
                            @csrf
                            @method('PUT')
                            @if ($userGroups->isEmpty())
                                <p class="text-sm text-ink">Nie ma jeszcze grup użytkowników — utwórz je w Użytkownicy → Grupy.</p>
                            @else
                                <fieldset>
                                    <legend class="mb-2 text-sm text-ink">Zaznacz grupy, które mają widzieć folder „{{ $folder->name }}” i jego podfoldery. Bez zaznaczenia folder widzą wszyscy.</legend>
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        @foreach ($userGroups as $group)
                                            <label class="flex items-center gap-2 rounded-md bg-gray-50 px-3 py-2 text-sm text-ink">
                                                <input type="checkbox" name="groups[]" value="{{ $group->id }}" @checked(in_array($group->id, $folderGroups, true))
                                                    class="h-4 w-4 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                                                {{ $group->name }}
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                                <button type="submit" class="{{ $btn }} mt-3 bg-brand-dark text-white hover:bg-ink">Zapisz dostęp</button>
                            @endif
                        </form>
                    </details>
                @endif

                {{-- Wgrywanie --}}
                @unless ($showArchived)
                    <div id="upload-panel" x-show="uploadOpen" x-cloak>
                        @include('admin.media.partials.upload', ['folder' => $folder])
                    </div>
                @endunless

                @foreach (['files', 'files.*', 'archive', 'name', 'file'] as $field)
                    @error($field) <p class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $message }}</p> @enderror
                @endforeach

                {{-- Wyszukiwarka i filtry --}}
                <form method="GET" action="{{ route('admin.multimedia.index') }}" role="search" aria-label="Szukaj plików"
                    x-data="{ more: {{ ($currentDateFrom || $currentDateTo || $currentAuthor || $withArchived) ? 'true' : 'false' }} }"
                    class="rounded-md bg-white p-3">
                    @if ($folder) <input type="hidden" name="folder" value="{{ $folder->id }}"> @endif
                    @if ($currentKind) <input type="hidden" name="kind" value="{{ $currentKind }}"> @endif
                    @if ($currentTag) <input type="hidden" name="tag" value="{{ $currentTag }}"> @endif
                    @if ($showArchived) <input type="hidden" name="archived" value="1"> @endif
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative min-w-[14rem] flex-1">
                            <label for="media-search" class="sr-only">Szukaj po nazwie pliku</label>
                            <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted" aria-hidden="true"></i>
                            <input type="search" id="media-search" name="q" value="{{ $currentSearch }}" placeholder="Szukaj w całej bibliotece…"
                                class="w-full rounded-md border-gray-500 py-2 pl-9 pr-3 text-sm focus:border-brand-dark focus:ring-brand-dark">
                        </div>
                        <button type="submit" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink">Szukaj</button>
                        <button type="button" @click="more = !more" :aria-expanded="more" aria-controls="search-more"
                            class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light">
                            <i class="fa-solid fa-sliders" aria-hidden="true"></i> Więcej filtrów
                        </button>
                        {{-- Sortowanie (zapamiętane w sesji) --}}
                        <div class="relative ml-auto" x-data="{ sortOpen: false }" @click.outside="sortOpen = false" @keydown.escape.stop="if (sortOpen) { sortOpen = false; $refs.sortBtn.focus() }">
                            <button type="button" x-ref="sortBtn" @click="sortOpen = ! sortOpen" :aria-expanded="sortOpen.toString()" aria-controls="media-sort-list"
                                class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light">
                                <i class="fa-solid fa-arrow-down-wide-short" aria-hidden="true"></i>
                                <span><span class="sr-only">Sortowanie: </span>{{ \App\Http\Controllers\Admin\MediaLibraryController::SORTS[$currentSort] }}</span>
                                <i class="fa-solid fa-chevron-down text-[0.65rem]" aria-hidden="true"></i>
                            </button>
                            <ul id="media-sort-list" x-show="sortOpen" x-cloak role="list" class="absolute right-0 z-30 mt-1 w-48 overflow-hidden rounded-md bg-white py-1 shadow-lg">
                                @foreach (\App\Http\Controllers\Admin\MediaLibraryController::SORTS as $sortKey => $sortLabel)
                                    <li>
                                        <a href="{{ route('admin.multimedia.index', array_merge(request()->except(['sort', 'page', 'plik']), ['sort' => $sortKey])) }}"
                                            @if ($currentSort === $sortKey) aria-current="true" @endif
                                            class="{{ $menuItem }} {{ $currentSort === $sortKey ? 'font-bold' : '' }}">
                                            <i class="fa-solid fa-check w-4 {{ $currentSort === $sortKey ? 'text-brand-dark' : 'invisible' }}" aria-hidden="true"></i> {{ $sortLabel }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        {{-- Rozmiar miniatur (jak lupa −/+ w Joomli), tylko w kafelkach --}}
                        <div x-show="view === 'grid'" class="flex overflow-hidden rounded-md bg-gray-100" role="group" aria-label="Rozmiar miniatur">
                            <button type="button" @click="zoomBy(-1)" :disabled="zoom === 0" class="px-3 py-2 text-sm text-ink hover:bg-brand-light disabled:cursor-not-allowed disabled:text-muted disabled:hover:bg-gray-100">
                                <i class="fa-solid fa-magnifying-glass-minus" aria-hidden="true"></i><span class="sr-only">Mniejsze miniatury</span>
                            </button>
                            <button type="button" @click="zoomBy(1)" :disabled="zoom === zoomSizes.length - 1" class="px-3 py-2 text-sm text-ink hover:bg-brand-light disabled:cursor-not-allowed disabled:text-muted disabled:hover:bg-gray-100">
                                <i class="fa-solid fa-magnifying-glass-plus" aria-hidden="true"></i><span class="sr-only">Większe miniatury</span>
                            </button>
                        </div>
                        <div class="flex overflow-hidden rounded-md bg-gray-100" role="group" aria-label="Układ listy">
                            <button type="button" @click="view = 'grid'" :aria-pressed="view === 'grid'"
                                :class="view === 'grid' ? 'bg-brand-dark text-white' : 'text-ink hover:bg-brand-light'" class="px-3 py-2 text-sm">
                                <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i><span class="sr-only">Kafelki</span>
                            </button>
                            <button type="button" @click="view = 'list'" :aria-pressed="view === 'list'"
                                :class="view === 'list' ? 'bg-brand-dark text-white' : 'text-ink hover:bg-brand-light'" class="px-3 py-2 text-sm">
                                <i class="fa-solid fa-list" aria-hidden="true"></i><span class="sr-only">Lista</span>
                            </button>
                        </div>
                    </div>
                    <div id="search-more" x-show="more" x-cloak class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label for="filter-date-from" class="mb-1 block text-xs font-bold text-ink">Dodano od</label>
                            <input type="date" id="filter-date-from" name="date_from" value="{{ $currentDateFrom }}" class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                        </div>
                        <div>
                            <label for="filter-date-to" class="mb-1 block text-xs font-bold text-ink">Dodano do</label>
                            <input type="date" id="filter-date-to" name="date_to" value="{{ $currentDateTo }}" class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                        </div>
                        <div>
                            <label for="filter-author" class="mb-1 block text-xs font-bold text-ink">Autor / twórca</label>
                            <input type="text" id="filter-author" name="author" value="{{ $currentAuthor }}" placeholder="np. Jan Kowalski" class="w-full rounded-md border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                        </div>
                        <label class="flex items-center gap-2 self-end rounded-md bg-gray-50 px-3 py-2.5 text-sm text-ink">
                            <input type="checkbox" name="with_archived" value="1" @checked($withArchived) class="h-4 w-4 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                            Także w archiwum
                        </label>
                    </div>
                </form>

                {{-- Aktywne filtry --}}
                @if ($isFiltered || $withArchived)
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-ink">
                        <span>Szukasz w całej bibliotece:</span>
                        @if ($currentSearch)<span>„<strong>{{ $currentSearch }}</strong>”</span>@endif
                        @if ($currentKind)<span>rodzaj: <strong>{{ \App\Http\Controllers\Admin\MediaLibraryController::KINDS[$currentKind] }}</strong></span>@endif
                        @if ($currentTag)<span>tag: <strong>#{{ $currentTag }}</strong></span>@endif
                        @if ($currentAuthor)<span>autor: <strong>{{ $currentAuthor }}</strong></span>@endif
                        @if ($currentDateFrom || $currentDateTo)<span>dodano: <strong>{{ $currentDateFrom ?: '…' }} – {{ $currentDateTo ?: '…' }}</strong></span>@endif
                        <a href="{{ route('admin.multimedia.index', array_filter(['folder' => $folder?->id, 'archived' => $showArchived ? 1 : null])) }}"
                            class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline">Wyczyść filtry</a>
                    </div>
                @endif

                @if ($showArchived)
                    <div class="flex flex-wrap items-center gap-3 rounded-md bg-gray-100 px-4 py-3 text-sm text-ink">
                        <i class="fa-solid fa-box-archive flex-none text-brand-dark" aria-hidden="true"></i>
                        <span class="flex-1">Archiwum: schowane pliki nie pojawiają się w wyborze obrazów, ale nadal działają pod swoimi adresami.</span>
                        <form method="POST" action="{{ route('admin.multimedia.empty-archive', array_filter(['folder' => $folder?->id])) }}"
                            onsubmit="return confirm('Usunąć na stałe wszystkie pliki z archiwum{{ $folder ? ' w tym folderze' : '' }}? Tego nie da się cofnąć.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="{{ $btn }} bg-white text-red-800 hover:bg-red-50"><i class="fa-solid fa-trash-can" aria-hidden="true"></i> Opróżnij archiwum</button>
                        </form>
                    </div>
                @endif

                {{-- Pasek akcji dla zaznaczonych --}}
                <div x-show="selected.length" x-cloak class="sticky top-2 z-30 flex flex-wrap items-center gap-2 rounded-md bg-ink px-4 py-3 text-white shadow-lg"
                    role="region" aria-label="Akcje dla zaznaczonych plików">
                    <p class="mr-auto text-sm font-bold" aria-live="polite">Zaznaczono: <span x-text="selected.length"></span></p>
                    <form method="POST" action="{{ route('admin.multimedia.export-selected') }}">
                        @csrf
                        <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                        <button type="submit" class="{{ $btn }} bg-white/10 hover:bg-white/20"><i class="fa-solid fa-file-zipper" aria-hidden="true"></i> Pobierz ZIP</button>
                    </form>
                    @if (! $showArchived)
                        <form method="POST" action="{{ route('admin.multimedia.bulk') }}" class="flex items-center gap-1">
                            @csrf
                            <input type="hidden" name="action" value="move">
                            <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                            <label class="sr-only" for="bulk-folder">Folder docelowy</label>
                            <select id="bulk-folder" name="folder_id" class="rounded-md border-0 bg-white text-sm text-ink focus:ring-2 focus:ring-white">
                                <option value="">— katalog główny —</option>
                                @foreach ($allFolders as $option)
                                    <option value="{{ $option->id }}">{{ $option->fullPath() }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="{{ $btn }} bg-white/10 hover:bg-white/20">Przenieś</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.multimedia.bulk') }}">
                        @csrf
                        <input type="hidden" name="action" value="{{ $showArchived ? 'restore' : 'archive' }}">
                        <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                        <button type="submit" class="{{ $btn }} bg-white/10 hover:bg-white/20">
                            <i class="fa-solid {{ $showArchived ? 'fa-rotate-left' : 'fa-box-archive' }}" aria-hidden="true"></i> {{ $showArchived ? 'Przywróć' : 'Do archiwum' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.multimedia.bulk') }}"
                        @submit="if (! confirm('Usunąć zaznaczone pliki (' + selected.length + ') na stałe?')) $event.preventDefault()">
                        @csrf
                        <input type="hidden" name="action" value="delete">
                        <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                        <button type="submit" class="{{ $btn }} bg-white text-red-800 hover:bg-red-50"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                    </form>
                    <button type="button" @click="selected = []" class="{{ $btn }} hover:bg-white/10">Odznacz</button>
                </div>

                {{-- Foldery jako kafle nad plikami (jak w menedżerze mediów Joomli) --}}
                @if ($showFolderTiles)
                    @include('admin.media.partials.folder-tiles')
                @endif

                @if ($media->isEmpty() && $subfolders->isNotEmpty() && ! $isFiltered && ! $showArchived)
                    <p class="rounded-md bg-white px-4 py-3 text-sm text-ink">W tym miejscu nie ma plików — są tylko foldery powyżej.</p>
                @elseif ($media->isEmpty())
                    <div class="rounded-md bg-white px-6 py-16 text-center">
                        <i class="fa-solid fa-folder-open mb-3 text-4xl text-brand-dark" aria-hidden="true"></i>
                        <p class="font-bold text-ink">
                            @if ($isFiltered) Nic nie pasuje do filtrów.
                            @elseif ($showArchived) Archiwum jest puste.
                            @else Ten folder jest pusty.
                            @endif
                        </p>
                        @if (! $showArchived && ! $isFiltered)
                            <button type="button" @click="uploadOpen = true; $nextTick(() => document.getElementById('upload-panel')?.scrollIntoView({ behavior: 'smooth' }))"
                                class="{{ $btn }} mt-4 bg-brand-dark text-white hover:bg-ink">Wgraj pierwsze pliki</button>
                        @endif
                    </div>
                @else
                    <div class="flex items-center justify-between gap-3 text-sm text-ink">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" :checked="allSelected" @change="toggleAll()" class="h-4 w-4 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                            Zaznacz wszystkie na tej stronie
                        </label>
                        <p>{{ $media->total() }} {{ trans_choice('plik|pliki|plików', $media->total()) }}</p>
                    </div>

                    {{-- Kafelki --}}
                    <ul x-show="view === 'grid'" x-cloak role="list" class="grid gap-3" :style="gridStyle" aria-label="Pliki">
                        @foreach ($media as $item)
                            <li class="group relative rounded-md p-2 transition"
                                :class="isSelected({{ $item['id'] }}) ? 'bg-brand-light' : 'bg-white hover:bg-gray-50'">
                                <input type="checkbox" id="sel-{{ $item['id'] }}" :checked="isSelected({{ $item['id'] }})" @change="toggle({{ $item['id'] }})"
                                    class="absolute left-4 top-4 z-10 h-5 w-5 rounded border-gray-500 text-brand-dark shadow focus:ring-brand-dark">
                                <label for="sel-{{ $item['id'] }}" class="sr-only">Zaznacz {{ $item['file_name'] }}</label>
                                @include('admin.media.partials.item-actions', ['item' => $item])

                                <button type="button" @click="openPanel({{ $item['id'] }})" class="block w-full text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark rounded-md"
                                    aria-haspopup="dialog">
                                    <span class="flex aspect-[4/3] items-center justify-center overflow-hidden rounded bg-gray-100">
                                        @if ($item['is_image'])
                                            <img src="{{ $item['thumb_url'] }}" alt="" class="h-full w-full object-cover {{ $item['archived'] ? 'grayscale' : '' }}" loading="lazy">
                                        @else
                                            <span class="flex flex-col items-center gap-1 text-brand-dark">
                                                <i class="fa-solid {{ $item['ext'] === 'PDF' ? 'fa-file-pdf' : ($kindIcon[$item['kind']] ?? 'fa-file') }} text-4xl" aria-hidden="true"></i>
                                                <span class="text-xs font-bold text-ink">{{ $item['ext'] }}</span>
                                            </span>
                                        @endif
                                    </span>
                                    <span class="mt-2 flex items-baseline gap-1 text-sm" title="{{ $item['file_name'] }}">
                                        <span class="min-w-0 truncate font-bold text-ink">{{ $item['file_name'] }}</span>
                                        @if ($item['ext'] !== '')<span class="flex-none text-ink">({{ $item['ext'] }})</span>@endif
                                    </span>
                                    <span class="block text-xs text-muted">{{ $item['size'] }}</span>
                                    <span class="block text-xs {{ $item['usage'] ? 'text-ink' : 'text-muted' }}">
                                        {{ $item['usage'] ? 'Użyty: '.$item['usage'] : 'Nieużywany' }}@if ($item['versions']) · wersji: {{ $item['versions'] + 1 }}@endif
                                        @if ($item['is_image'] && ! $item['has_alt'] && $item['owner']['standalone']) · brak opisu alt @endif
                                        @if ($item['a11y'] && $item['a11y'] !== 'ok') · {{ $a11yText[$item['a11y']] }} @endif
                                        @if ($item['archived']) · w archiwum @endif
                                    </span>
                                </button>
                                <a href="{{ $item['url'] }}" download
                                    class="mt-1 inline-flex min-h-11 items-center gap-1.5 rounded-md px-1 text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                    <i class="fa-solid fa-arrow-down text-xs" aria-hidden="true"></i> Pobierz<span class="sr-only"> plik: {{ $item['file_name'] }}@if ($item['ext'] !== '') ({{ $item['ext'] }}, {{ $item['size'] }})@endif</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    {{-- Lista --}}
                    <div x-show="view === 'list'" x-cloak class="overflow-x-auto rounded-md bg-white">
                        <table class="w-full text-left text-sm">
                            <caption class="sr-only">Pliki</caption>
                            <thead class="bg-gray-50 text-xs font-bold uppercase tracking-widest text-muted">
                                <tr>
                                    <th scope="col" class="w-10 px-4 py-3"><span class="sr-only">Zaznacz</span></th>
                                    <th scope="col" class="px-2 py-3">Plik</th>
                                    <th scope="col" class="px-4 py-3">Użycie</th>
                                    <th scope="col" class="px-4 py-3">Rozmiar</th>
                                    <th scope="col" class="px-4 py-3">Dodano</th>
                                    <th scope="col" class="px-4 py-3">Autor</th>
                                    <th scope="col" class="px-4 py-3"><span class="sr-only">Akcje</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($media as $item)
                                    <tr class="transition" :class="isSelected({{ $item['id'] }}) ? 'bg-brand-light' : 'hover:bg-gray-50'">
                                        <td class="px-4 py-2">
                                            <input type="checkbox" id="sel-list-{{ $item['id'] }}" :checked="isSelected({{ $item['id'] }})" @change="toggle({{ $item['id'] }})"
                                                class="h-4 w-4 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                                            <label for="sel-list-{{ $item['id'] }}" class="sr-only">Zaznacz {{ $item['file_name'] }}</label>
                                        </td>
                                        <td class="px-2 py-2">
                                            <button type="button" @click="openPanel({{ $item['id'] }})" aria-haspopup="dialog"
                                                class="flex items-center gap-3 rounded-md text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                                <span class="flex h-12 w-12 flex-none items-center justify-center overflow-hidden rounded bg-gray-100">
                                                    @if ($item['is_image'])
                                                        <img src="{{ $item['thumb_url'] }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                                    @else
                                                        <i class="fa-solid {{ $item['ext'] === 'PDF' ? 'fa-file-pdf' : ($kindIcon[$item['kind']] ?? 'fa-file') }} text-xl text-brand-dark" aria-hidden="true"></i>
                                                    @endif
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="flex max-w-[26rem] items-baseline gap-1">
                                                        <span class="min-w-0 truncate font-bold text-ink hover:underline">{{ $item['file_name'] }}</span>
                                                        @if ($item['ext'] !== '')<span class="flex-none text-ink">({{ $item['ext'] }})</span>@endif
                                                    </span>
                                                    @if ($item['versions'] || $item['a11y'] || $item['archived'])
                                                        <span class="block text-xs text-muted">
                                                            @php $notes = array_filter([$item['versions'] ? 'wersji: '.($item['versions'] + 1) : null, $item['a11y'] ? 'dostępność: '.$a11yText[$item['a11y']] : null, $item['archived'] ? 'w archiwum' : null]); @endphp
                                                            {{ implode(' · ', $notes) }}
                                                        </span>
                                                    @endif
                                                </span>
                                            </button>
                                        </td>
                                        <td class="px-4 py-2 {{ $item['usage'] ? 'text-ink' : 'text-muted' }}">{{ $item['usage'] ? $item['usage'].' '.trans_choice('miejsce|miejsca|miejsc', $item['usage']) : 'nieużywany' }}</td>
                                        <td class="whitespace-nowrap px-4 py-2 text-ink">{{ $item['size'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-2 text-ink">{{ $item['created_at']->format('d.m.Y') }}</td>
                                        <td class="px-4 py-2 text-ink">{{ $item['author'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-2">
                                            <button type="button" @click="openPreview({{ $item['id'] }})" aria-haspopup="dialog"
                                                class="inline-flex min-h-11 items-center gap-1.5 rounded-md px-1 font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                                <i class="fa-solid fa-eye text-xs" aria-hidden="true"></i> Podgląd<span class="sr-only">: {{ $item['file_name'] }}</span>
                                            </button>
                                            @if ($item['edit'])
                                                <button type="button" @click="$dispatch('media-edit', @js($item['edit']))" aria-haspopup="dialog"
                                                    class="ml-2 inline-flex min-h-11 items-center gap-1.5 rounded-md px-1 font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                                    <i class="fa-solid fa-crop-simple text-xs" aria-hidden="true"></i> Edytuj<span class="sr-only"> obraz: {{ $item['file_name'] }}</span>
                                                </button>
                                            @endif
                                            <a href="{{ $item['url'] }}" download
                                                class="ml-2 inline-flex min-h-11 items-center gap-1.5 rounded-md px-1 font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                                <i class="fa-solid fa-arrow-down text-xs" aria-hidden="true"></i> Pobierz<span class="sr-only"> plik: {{ $item['file_name'] }}@if ($item['ext'] !== '') ({{ $item['ext'] }}, {{ $item['size'] }})@endif</span>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div>{{ $media->links() }}</div>
            </div>
        </div>

        {{-- ── Szuflada szczegółów pliku ─────────────────────────────────────── --}}
        <div x-show="panelId" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="media-panel-heading">
            <div class="absolute inset-0 bg-ink/50" @click="closePanel()" aria-hidden="true"></div>
            <div class="absolute inset-y-0 right-0 flex w-full max-w-lg flex-col bg-white shadow-2xl"
                x-show="panelId" x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="translate-x-8 opacity-0" x-transition:enter-end="translate-x-0 opacity-100"
                @keydown.tab="
                    const f = [...$el.querySelectorAll('a[href],button:not([disabled]),input:not([type=hidden]):not([disabled]),select,textarea,[tabindex]:not([tabindex=&quot;-1&quot;])')].filter(e => e.offsetParent !== null);
                    if (!f.length) return;
                    if ($event.shiftKey && document.activeElement === f[0]) { $event.preventDefault(); f[f.length - 1].focus(); }
                    else if (!$event.shiftKey && document.activeElement === f[f.length - 1]) { $event.preventDefault(); f[0].focus(); }">
                <div class="flex items-center justify-between gap-3 bg-gray-50 px-5 py-3">
                    <p id="media-panel-heading" x-ref="panelHeading" tabindex="-1" class="text-sm font-bold uppercase tracking-widest text-muted focus:outline-none">Szczegóły pliku</p>
                    <button type="button" @click="closePanel()" class="rounded-md p-2 text-ink hover:bg-gray-200" aria-label="Zamknij szczegóły (Esc)">
                        <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-5 py-5">
                    <p x-show="panelLoading" class="text-sm text-ink" role="status"><i class="fa-solid fa-spinner fa-spin mr-2" aria-hidden="true"></i>Wczytywanie…</p>
                    <div x-html="panelHtml"></div>
                </div>
            </div>
        </div>

        {{-- ── Podgląd pliku z przewijaniem ────────────────────────────────────── --}}
        @include('admin.media.partials.preview')

        {{-- ── Potwierdzenie usunięcia plików w użyciu ───────────────────────── --}}
        @if ($usageConfirm)
            <div x-data="{ open: true }" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-ink/60 p-4"
                role="alertdialog" aria-modal="true" aria-labelledby="usage-confirm-title" aria-describedby="usage-confirm-desc"
                x-init="$nextTick(() => $refs.cancel.focus())" @keydown.escape.window="open = false">
                <div class="max-h-[85vh] w-full max-w-xl overflow-y-auto rounded-md bg-white p-6 shadow-2xl">
                    <h2 id="usage-confirm-title" class="text-lg font-bold text-ink">Te pliki są w użyciu</h2>
                    <p id="usage-confirm-desc" class="mt-1 text-sm text-ink">Po usunięciu znikną z poniższych miejsc — obrazy przestaną się wyświetlać, a odnośniki do dokumentów przestaną działać. Rozważ podmianę pliku albo schowanie go do archiwum.</p>
                    <ul class="mt-4 space-y-3" role="list">
                        @foreach ($usageConfirm['files'] as $file)
                            <li class="rounded-md bg-gray-50 p-3 text-sm">
                                <p class="font-bold text-ink">{{ $file['name'] }}</p>
                                <ul class="mt-1 space-y-0.5" role="list">
                                    @foreach ($file['places'] as $place)
                                        <li class="text-ink">
                                            <span class="text-muted">{{ $place['label'] }}:</span>
                                            @if ($place['url'])
                                                <a href="{{ $place['url'] }}" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline">{{ $place['title'] }}</a>
                                            @else {{ $place['title'] }} @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-5 flex flex-wrap justify-end gap-2">
                        <button type="button" x-ref="cancel" @click="open = false" class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light">Nie usuwaj</button>
                        <form method="POST" action="{{ route('admin.multimedia.bulk') }}">
                            @csrf
                            <input type="hidden" name="action" value="archive">
                            @foreach ($usageConfirm['ids'] as $id)<input type="hidden" name="ids[]" value="{{ $id }}">@endforeach
                            <button type="submit" class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light">Schowaj do archiwum</button>
                        </form>
                        <form method="POST" action="{{ route('admin.multimedia.bulk') }}">
                            @csrf
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="force" value="1">
                            @foreach ($usageConfirm['ids'] as $id)<input type="hidden" name="ids[]" value="{{ $id }}">@endforeach
                            <button type="submit" class="{{ $btn }} bg-red-800 text-white hover:bg-red-900">Usuń mimo to</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- ── Edytor obrazu (kadr, obrót, rozmiar) ──────────────────────────── --}}
    @include('admin.media.partials.image-editor')
@endsection

@push('scripts')
<script>
    /*
     * Biblioteka plików (admin.media.index): zaznaczanie, szuflada szczegółów, menu akcji kafla,
     * podgląd z przewijaniem, rozmiar miniatur, upuszczanie plików na listę. Wzorowane na menedżerze mediów Joomli.
     */
    document.addEventListener('alpine:init', () => {
        const stored = (key, fallback) => { try { return localStorage.getItem(key) ?? fallback; } catch (e) { return fallback; } };
        const store = (key, value) => { try { localStorage.setItem(key, value); } catch (e) {} };

        Alpine.data('mediaLibrary', (cfg) => ({
            view: stored('media-view', 'grid'),
            zoomSizes: [7.5, 9.5, 12, 15, 19],
            zoom: (() => { const z = parseInt(stored('media-zoom', '2'), 10); return z >= 0 && z <= 4 ? z : 2; })(),
            uploadOpen: !! cfg.uploadOpen,
            selected: [],
            pageIds: cfg.pageIds || [],
            items: cfg.items || [],
            panelId: null, panelHtml: '', panelLoading: false, returnFocus: null,
            menuId: null, copiedId: null, announce: '',
            previewIdx: null,
            dropActive: false,

            init() {
                this.$watch('view', v => store('media-view', v));
                if (cfg.openPanelId) this.openPanel(cfg.openPanelId);
            },

            // Zaznaczanie
            toggle(id) { const i = this.selected.indexOf(id); i === -1 ? this.selected.push(id) : this.selected.splice(i, 1); },
            isSelected(id) { return this.selected.includes(id); },
            get allSelected() { return this.pageIds.length > 0 && this.pageIds.every(id => this.selected.includes(id)); },
            toggleAll() { this.allSelected ? this.selected = this.selected.filter(id => ! this.pageIds.includes(id)) : this.pageIds.forEach(id => { if (! this.selected.includes(id)) this.selected.push(id); }); },

            // Szuflada szczegółów
            async openPanel(id) {
                this.returnFocus = document.activeElement;
                this.panelId = id; this.panelLoading = true; this.panelHtml = '';
                try {
                    const r = await fetch(cfg.panelUrl.replace('__ID__', id), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    this.panelHtml = r.ok ? await r.text() : '<p class="text-sm text-ink">Nie udało się wczytać szczegółów pliku.</p>';
                } catch (e) { this.panelHtml = '<p class="text-sm text-ink">Brak połączenia — spróbuj ponownie.</p>'; }
                this.panelLoading = false;
                this.$nextTick(() => this.$refs.panelHeading?.focus());
            },
            closePanel() { this.panelId = null; this.panelHtml = ''; this.$nextTick(() => this.returnFocus?.focus?.()); },

            // Menu akcji kafla
            toggleMenu(id) { this.menuId = this.menuId === id ? null : id; },
            async copyUrl(id, url) {
                try {
                    await navigator.clipboard.writeText(url);
                    this.copiedId = id; this.announce = 'Skopiowano adres pliku.';
                    setTimeout(() => { if (this.copiedId === id) this.copiedId = null; }, 2000);
                } catch (e) { this.announce = 'Nie udało się skopiować adresu.'; }
            },

            // Podgląd z przewijaniem
            get previewItem() { return this.previewIdx === null ? null : (this.items[this.previewIdx] || null); },
            openPreview(id) {
                const idx = this.items.findIndex(i => i.id === id);
                if (idx === -1) return;
                this.returnFocus = document.activeElement;
                this.menuId = null;
                this.previewIdx = idx;
                this.$nextTick(() => document.getElementById('media-preview-title')?.focus());
            },
            closePreview(restoreFocus = true) {
                this.previewIdx = null;
                if (restoreFocus) this.$nextTick(() => this.returnFocus?.focus?.());
            },
            step(delta) {
                const n = this.items.length;
                if (! n || this.previewIdx === null) return;
                this.previewIdx = (this.previewIdx + delta + n) % n;
                this.announce = 'Plik ' + (this.previewIdx + 1) + ' z ' + n + ': ' + this.items[this.previewIdx].name;
            },
            previewKey(event, delta) {
                if (['VIDEO', 'AUDIO', 'INPUT', 'SELECT', 'TEXTAREA', 'IFRAME'].includes(event.target.tagName)) return;
                event.preventDefault();
                this.step(delta);
            },
            detailsFromPreview() {
                const id = this.previewItem?.id, back = this.returnFocus;
                this.closePreview(false);
                back?.focus?.();
                if (id) this.openPanel(id);
            },
            editFromPreview() {
                const edit = this.previewItem?.edit, back = this.returnFocus;
                this.closePreview(false);
                back?.focus?.();
                if (edit) window.dispatchEvent(new CustomEvent('media-edit', { detail: edit }));
            },
            trapIn(event, el) {
                const f = [...el.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),select,textarea,video,audio,iframe,[tabindex]:not([tabindex="-1"])')].filter(e => e.offsetParent !== null);
                if (! f.length) return;
                if (event.shiftKey && document.activeElement === f[0]) { event.preventDefault(); f[f.length - 1].focus(); }
                else if (! event.shiftKey && document.activeElement === f[f.length - 1]) { event.preventDefault(); f[0].focus(); }
            },

            // Rozmiar miniatur
            get gridStyle() { return 'grid-template-columns: repeat(auto-fill, minmax(min(' + this.zoomSizes[this.zoom] + 'rem, 100%), 1fr))'; },
            zoomBy(delta) {
                this.zoom = Math.min(this.zoomSizes.length - 1, Math.max(0, this.zoom + delta));
                store('media-zoom', this.zoom);
            },

            // Upuszczanie plików na listę → panel wgrywania (z polami autora i opisu alternatywnego)
            hasFiles(event) { return !! event.dataTransfer && [...(event.dataTransfer.types || [])].includes('Files'); },
            inUpload(event) { return !! event.target?.closest?.('#upload-panel'); },
            onDragOver(event) {
                if (! cfg.canUpload || ! this.hasFiles(event)) return;
                if (this.inUpload(event) && this.uploadOpen) { this.dropActive = false; return; }
                event.preventDefault();
                event.dataTransfer.dropEffect = 'copy';
                this.dropActive = true;
            },
            onDragLeave(event, el) { if (! el.contains(event.relatedTarget)) this.dropActive = false; },
            onDropFiles(event) {
                this.dropActive = false;
                if (! cfg.canUpload || ! this.hasFiles(event) || (this.inUpload(event) && this.uploadOpen)) return;
                event.preventDefault();
                const files = event.dataTransfer.files;
                if (! files.length) return;
                this.uploadOpen = true;
                this.$nextTick(() => {
                    window.dispatchEvent(new CustomEvent('media-files-dropped', { detail: { files } }));
                    document.getElementById('upload-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    this.announce = 'Dodano pliki do wgrania: ' + files.length + '. Uzupełnij opisy i wyślij przyciskiem w panelu wgrywania.';
                });
            },

            // Esc: najpierw podgląd, potem menu akcji, na końcu szuflada (edytor obrazu obsługuje Esc sam)
            onEscape() {
                if (this.previewIdx !== null) return this.closePreview();
                if (this.menuId !== null) {
                    const id = this.menuId;
                    this.menuId = null;
                    document.getElementById('menu-btn-' + id)?.focus();
                    return;
                }
                if (this.panelId) this.closePanel();
            },
        }));
    });
</script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css"
    integrity="sha512-UtLOu9C7NuThQhuXXrGwx9Jb/z9zPQJcM85ND7HF1GGZc0H4eiL4o91A/GBUDqmAzgme6oAnq35AggAVb9fA==" crossorigin="anonymous">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"
    integrity="sha512-JyCZjCOZoyeQZSd5+YEAcFgz2fowJ1F1hyJOXgtKu4llIa0KneLcidn5bwfutiehqlCPSRQLzKqeHkgykc0SQ==" crossorigin="anonymous" defer></script>
@endpush
