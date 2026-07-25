@extends('admin.layout')

@section('title', 'Multimedia')

@section('content')
    <div x-data="{ lightbox: null, view: localStorage.getItem('media-view') || 'grid' }"
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

                <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-lg border border-gray-200 bg-white p-4">
                    <form method="POST" action="{{ route('admin.multimedia.foldery.store') }}" class="flex items-center gap-2">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ $folder?->id }}">
                        <input type="text" name="name" placeholder="Nazwa nowego folderu" required
                            class="rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        <button type="submit" class="rounded border border-brand px-3 py-2 text-xs font-bold text-brand hover:bg-brand-light">
                            <i class="fa-solid fa-folder-plus" aria-hidden="true"></i> Nowy folder
                        </button>
                    </form>

                    <div class="flex items-center gap-3">
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

                        <form method="POST" action="{{ route('admin.multimedia.store') }}" enctype="multipart/form-data" class="flex items-center gap-2">
                            @csrf
                            <input type="hidden" name="folder_id" value="{{ $folder?->id }}">
                            <input type="file" name="file" required class="cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-bold file:text-ink hover:file:bg-gray-200">
                            <button type="submit" class="rounded bg-brand px-3 py-2 text-xs font-bold text-white hover:bg-brand-dark">
                                <i class="fa-solid fa-upload" aria-hidden="true"></i> Prześlij plik
                            </button>
                        </form>
                    </div>
                </div>
                @error('file') <p class="mb-4 text-sm text-red-600">{{ $message }}</p> @enderror
                @error('name') <p class="mb-4 text-sm text-red-600">{{ $message }}</p> @enderror

                @if ($showArchived)
                    <div class="mb-6 flex items-center gap-2 rounded border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-muted">
                        <i class="fa-solid fa-box-archive" aria-hidden="true"></i>
                        Przeglądasz archiwum. Schowane pliki nie pojawiają się w bibliotece ani w wyborze zdjęć w edytorze, ale nie zostały usunięte.
                    </div>
                @endif

                @if ($subfolders->isNotEmpty())
                    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                        @foreach ($subfolders as $sub)
                            <div class="rounded-lg border border-gray-200 bg-white p-4 text-center" x-data="{ renaming: false }">
                                <template x-if="!renaming">
                                    <div>
                                        <a href="{{ route('admin.multimedia.index', ['folder' => $sub->id, 'archived' => $showArchived ? 1 : null]) }}" class="block">
                                            <i class="fa-solid fa-folder text-3xl text-brand" aria-hidden="true"></i>
                                            <p class="mt-2 truncate text-sm font-bold" title="{{ $sub->name }}">{{ $sub->name }}</p>
                                        </a>

                                        <div class="mt-2 flex items-center justify-center gap-3 text-xs">
                                            <button type="button" @click="renaming = true" class="text-muted hover:text-brand">Zmień nazwę</button>

                                            <form method="POST" action="{{ route('admin.multimedia.foldery.destroy', $sub) }}"
                                                onsubmit="return confirm('Usunąć folder „{{ $sub->name }}”? Jego pliki i podfoldery zostaną przeniesione do folderu nadrzędnego.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-muted hover:text-red-600">Usuń</button>
                                            </form>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="renaming">
                                    <form method="POST" action="{{ route('admin.multimedia.foldery.update', $sub) }}" class="mt-2 flex items-center gap-1">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="name" value="{{ $sub->name }}" required
                                            class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                        <button type="submit" class="text-brand hover:text-brand-dark" aria-label="Zapisz nazwę"><i class="fa-solid fa-check"></i></button>
                                        <button type="button" @click="renaming = false" class="text-muted" aria-label="Anuluj"><i class="fa-solid fa-xmark"></i></button>
                                    </form>
                                </template>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($media->isEmpty())
                    @if ($subfolders->isEmpty())
                        <p class="py-6 text-center text-muted">
                            {{ $showArchived ? 'Brak schowanych plików w tym folderze.' : 'Brak plików w tym folderze.' }}
                        </p>
                    @endif
                @else
                    {{-- Grid view --}}
                    <div x-show="view === 'grid'" x-cloak class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                        @foreach ($media as $item)
                            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white {{ $item['archived'] ? 'opacity-75' : '' }}">
                                <div class="relative flex h-32 items-center justify-center bg-gray-50">
                                    @if ($item['archived'])
                                        <span class="absolute left-2 top-2 z-10 rounded bg-gray-800/80 px-2 py-0.5 text-xs font-bold text-white">
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
                                    <th scope="col" class="p-3"><span class="sr-only">Podgląd</span></th>
                                    <th scope="col" class="p-3">Nazwa</th>
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
                                    <tr class="border-b border-gray-100 last:border-0 {{ $item['archived'] ? 'opacity-75' : '' }}">
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
    </div>
@endsection
