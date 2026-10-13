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
    @endphp

    <div x-data="{
            view: (() => { try { return localStorage.getItem('media-view') || 'grid' } catch (e) { return 'grid' } })(),
            uploadOpen: {{ $errors->has('files') || $errors->has('files.*') ? 'true' : 'false' }},
            selected: [],
            pageIds: @js($media->pluck('id')->values()),
            toggle(id) { const i = this.selected.indexOf(id); i === -1 ? this.selected.push(id) : this.selected.splice(i, 1); },
            isSelected(id) { return this.selected.includes(id); },
            get allSelected() { return this.pageIds.length > 0 && this.pageIds.every(id => this.selected.includes(id)); },
            toggleAll() { this.allSelected ? this.selected = this.selected.filter(id => !this.pageIds.includes(id)) : this.pageIds.forEach(id => { if (!this.selected.includes(id)) this.selected.push(id) }); },
            panelId: null, panelHtml: '', panelLoading: false, returnFocus: null,
            async openPanel(id) {
                this.returnFocus = document.activeElement;
                this.panelId = id; this.panelLoading = true; this.panelHtml = '';
                try {
                    const r = await fetch(@js(route('admin.multimedia.panel', '__ID__')).replace('__ID__', id), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    this.panelHtml = r.ok ? await r.text() : '<p class=&quot;text-sm text-ink&quot;>Nie udało się wczytać szczegółów pliku.</p>';
                } catch (e) { this.panelHtml = '<p class=&quot;text-sm text-ink&quot;>Brak połączenia — spróbuj ponownie.</p>'; }
                this.panelLoading = false;
                this.$nextTick(() => this.$refs.panelHeading?.focus());
            },
            closePanel() { this.panelId = null; this.panelHtml = ''; this.$nextTick(() => this.returnFocus?.focus?.()); }
        }"
        x-init="$watch('view', v => { try { localStorage.setItem('media-view', v) } catch (e) {} }); @if ($openPanelId) openPanel({{ $openPanelId }}) @endif"
        @keydown.escape.window="if (panelId) closePanel()">

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
                </div>
            </aside>

            {{-- ── Główna kolumna ────────────────────────────────────────────── --}}
            <div class="min-w-0 space-y-4">

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
                        <div class="ml-auto flex overflow-hidden rounded-md bg-gray-100" role="group" aria-label="Układ listy">
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

                @if ($media->isEmpty())
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
                    <ul x-show="view === 'grid'" x-cloak role="list" class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                        @foreach ($media as $item)
                            <li class="group relative rounded-md p-2 transition"
                                :class="isSelected({{ $item['id'] }}) ? 'bg-brand-light' : 'bg-white hover:bg-gray-50'">
                                <input type="checkbox" id="sel-{{ $item['id'] }}" :checked="isSelected({{ $item['id'] }})" @change="toggle({{ $item['id'] }})"
                                    class="absolute left-4 top-4 z-10 h-5 w-5 rounded border-gray-500 text-brand-dark shadow focus:ring-brand-dark">
                                <label for="sel-{{ $item['id'] }}" class="sr-only">Zaznacz {{ $item['file_name'] }}</label>

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
                                    <span class="mt-2 block truncate text-sm font-bold text-ink" title="{{ $item['file_name'] }}">{{ $item['file_name'] }}</span>
                                    <span class="block text-xs text-muted">{{ $item['ext'] }} · {{ $item['size'] }}</span>
                                    <span class="block text-xs {{ $item['usage'] ? 'text-ink' : 'text-muted' }}">
                                        {{ $item['usage'] ? 'Użyty: '.$item['usage'] : 'Nieużywany' }}@if ($item['versions']) · wersji: {{ $item['versions'] + 1 }}@endif
                                        @if ($item['is_image'] && ! $item['has_alt'] && $item['owner']['standalone']) · brak opisu alt @endif
                                        @if ($item['a11y'] && $item['a11y'] !== 'ok') · {{ $a11yText[$item['a11y']] }} @endif
                                        @if ($item['archived']) · w archiwum @endif
                                    </span>
                                </button>
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
                                                    <span class="block max-w-[22rem] truncate font-bold text-ink hover:underline">{{ $item['file_name'] }}</span>
                                                    <span class="block text-xs text-muted">
                                                        {{ $item['ext'] }}@if ($item['versions']) · wersji: {{ $item['versions'] + 1 }}@endif
                                                        @if ($item['a11y']) · dostępność: {{ $a11yText[$item['a11y']] }} @endif
                                                        @if ($item['archived']) · w archiwum @endif
                                                    </span>
                                                </span>
                                            </button>
                                        </td>
                                        <td class="px-4 py-2 {{ $item['usage'] ? 'text-ink' : 'text-muted' }}">{{ $item['usage'] ? $item['usage'].' '.trans_choice('miejsce|miejsca|miejsc', $item['usage']) : 'nieużywany' }}</td>
                                        <td class="whitespace-nowrap px-4 py-2 text-ink">{{ $item['size'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-2 text-ink">{{ $item['created_at']->format('d.m.Y') }}</td>
                                        <td class="px-4 py-2 text-ink">{{ $item['author'] }}</td>
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
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css"
    integrity="sha512-UtLOu9C7NuThQhuXXrGwx9Jb/z9zPQJcM85ND7HF1GGZc0H4eiL4o91A/GBUDqmAzgme6oAnq35AggAVb9fA==" crossorigin="anonymous">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"
    integrity="sha512-JyCZjCOZoyeQZSd5+YEAcFgz2fowJ1F1hyJOXgtKu4llIa0KneLcidn5bwfutiehqlCPSRQLzKqeHkgykc0SQ==" crossorigin="anonymous" defer></script>
@endpush
