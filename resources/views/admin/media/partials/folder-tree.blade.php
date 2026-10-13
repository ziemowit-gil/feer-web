{{--
    Jeden poziom drzewa folderów (rekurencyjnie). Płasko: aktywny folder na tle marki,
    kłódka przy folderach z ograniczonym dostępem; zmiana nazwy i usuwanie przy najechaniu / fokusie.

    Expects:
      $nodes        — collection of MediaFolder at this level (with media_count, userGroups)
      $tree         — all folders grouped by parent_id
      $currentId    — id of the folder currently open (null at root)
      $showArchived — whether the archive view is active (kept in nav links)
--}}
<ul class="space-y-0.5" role="list">
    @foreach ($nodes as $node)
        @php
            $children = $tree->get($node->id, collect());
            $active = $currentId === $node->id;
            $restricted = $node->userGroups->isNotEmpty();
        @endphp
        <li x-data="{ renaming: false }">
            <div x-show="!renaming" class="group flex items-center rounded-md {{ $active ? 'bg-brand-light' : 'hover:bg-gray-100' }}">
                <a href="{{ route('admin.multimedia.index', array_filter(['folder' => $node->id, 'archived' => $showArchived ? 1 : null])) }}"
                    @if ($active) aria-current="page" @endif
                    class="flex min-w-0 flex-1 items-center gap-2 px-2 py-1.5 text-sm {{ $active ? 'font-bold text-brand-dark' : 'text-ink' }}">
                    <i class="fa-solid {{ $active ? 'fa-folder-open' : 'fa-folder' }} w-4 flex-none text-brand-dark" aria-hidden="true"></i>
                    <span class="min-w-0 flex-1 truncate" title="{{ $node->name }}">{{ $node->name }}</span>
                    @if ($restricted)
                        <i class="fa-solid fa-lock flex-none text-xs text-muted" aria-hidden="true" title="Dostęp: {{ $node->userGroups->pluck('name')->implode(', ') }}"></i>
                        <span class="sr-only">(dostęp ograniczony: {{ $node->userGroups->pluck('name')->implode(', ') }})</span>
                    @endif
                    @if ($node->media_count > 0)
                        <span class="flex-none text-xs text-muted">{{ $node->media_count }}<span class="sr-only"> plików</span></span>
                    @endif
                </a>
                <span class="flex flex-none items-center pr-1 opacity-0 transition group-hover:opacity-100 group-focus-within:opacity-100">
                    <button type="button" @click="renaming = true" class="px-1 py-1 text-xs text-muted hover:text-brand-dark"
                        aria-label="Zmień nazwę folderu {{ $node->name }}">
                        <i class="fa-solid fa-pen" aria-hidden="true"></i>
                    </button>
                    <form method="POST" action="{{ route('admin.multimedia.foldery.destroy', $node->id) }}"
                        onsubmit="return confirm(@js('Usunąć folder „'.$node->name.'”? Jego pliki i podfoldery zostaną przeniesione do folderu nadrzędnego.'))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-1 py-1 text-xs text-muted hover:text-red-700" aria-label="Usuń folder {{ $node->name }}">
                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
                        </button>
                    </form>
                </span>
            </div>

            <form x-show="renaming" x-cloak method="POST" action="{{ route('admin.multimedia.foldery.update', $node->id) }}"
                class="flex items-center gap-1 px-2 py-1" x-effect="if (renaming) $nextTick(() => $refs.name.focus())">
                @csrf
                @method('PUT')
                <label class="sr-only" for="rename-folder-{{ $node->id }}">Nowa nazwa folderu {{ $node->name }}</label>
                <input type="text" id="rename-folder-{{ $node->id }}" x-ref="name" name="name" value="{{ $node->name }}" required
                    @keydown.escape="renaming = false"
                    class="w-full rounded-md border-gray-500 text-xs focus:border-brand-dark focus:ring-brand-dark">
                <button type="submit" class="flex-none px-1 text-brand-dark hover:text-ink" aria-label="Zapisz nazwę"><i class="fa-solid fa-check" aria-hidden="true"></i></button>
                <button type="button" @click="renaming = false" class="flex-none px-1 text-muted" aria-label="Anuluj zmianę nazwy"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
            </form>

            @if ($children->isNotEmpty())
                <div class="ml-4">
                    @include('admin.media.partials.folder-tree', [
                        'nodes' => $children,
                        'tree' => $tree,
                        'currentId' => $currentId,
                        'showArchived' => $showArchived,
                    ])
                </div>
            @endif
        </li>
    @endforeach
</ul>
