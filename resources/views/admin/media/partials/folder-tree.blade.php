{{--
    Renders one level of the media folder tree and recurses into children.
    The tree is the single place folders are listed and managed — each node
    links to its contents and carries inline rename / delete controls.

    Expects:
      $nodes        — collection of MediaFolder at this level (with media_count)
      $tree         — all folders grouped by parent_id (App\...\MediaFolder::groupBy)
      $currentId    — id of the folder currently open (null at root)
      $showArchived — whether the archive view is active (kept in nav links)
--}}
<ul class="space-y-0.5">
    @foreach ($nodes as $node)
        @php $children = $tree->get($node->id, collect()); @endphp
        <li x-data="{ renaming: false }">
            <template x-if="!renaming">
                <div class="group flex items-center gap-1 rounded {{ $currentId === $node->id ? 'bg-brand-light' : 'hover:bg-gray-100' }}">
                    <a href="{{ route('admin.multimedia.index', ['folder' => $node->id, 'archived' => $showArchived ? 1 : null]) }}"
                        class="flex min-w-0 flex-1 items-center gap-2 px-2 py-1 text-sm {{ $currentId === $node->id ? 'font-bold text-brand' : 'text-ink' }}">
                        <i class="fa-solid {{ $currentId === $node->id ? 'fa-folder-open' : 'fa-folder' }} w-4 text-brand" aria-hidden="true"></i>
                        <span class="min-w-0 flex-1 truncate" title="{{ $node->name }}">{{ $node->name }}</span>
                        @if ($node->media_count > 0)
                            <span class="text-xs text-muted">{{ $node->media_count }}</span>
                        @endif
                    </a>

                    <span class="flex flex-none items-center pr-1">
                        <button type="button" @click="renaming = true"
                            class="px-1 py-1 text-xs text-muted hover:text-brand"
                            aria-label="Zmień nazwę folderu {{ $node->name }}">
                            <i class="fa-solid fa-pen" aria-hidden="true"></i>
                        </button>

                        <form method="POST" action="{{ route('admin.multimedia.foldery.destroy', $node->id) }}"
                            onsubmit="return confirm('Usunąć folder „{{ $node->name }}"? Jego pliki i podfoldery zostaną przeniesione do folderu nadrzędnego.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-1 py-1 text-xs text-muted hover:text-red-600"
                                aria-label="Usuń folder {{ $node->name }}">
                                <i class="fa-solid fa-trash" aria-hidden="true"></i>
                            </button>
                        </form>
                    </span>
                </div>
            </template>

            <template x-if="renaming">
                <form method="POST" action="{{ route('admin.multimedia.foldery.update', $node->id) }}"
                    class="flex items-center gap-1 px-2 py-1" x-init="$nextTick(() => $refs.name.focus())">
                    @csrf
                    @method('PUT')
                    <label class="sr-only" for="rename-{{ $node->id }}">Nowa nazwa folderu {{ $node->name }}</label>
                    <input type="text" id="rename-{{ $node->id }}" x-ref="name" name="name" value="{{ $node->name }}" required
                        @keydown.escape="renaming = false"
                        class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                    <button type="submit" class="flex-none px-1 text-brand hover:text-brand-dark" aria-label="Zapisz nazwę"><i class="fa-solid fa-check" aria-hidden="true"></i></button>
                    <button type="button" @click="renaming = false" class="flex-none px-1 text-muted" aria-label="Anuluj zmianę nazwy"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                </form>
            </template>

            @if ($children->isNotEmpty())
                <div class="ml-3 border-l border-gray-200 pl-2">
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
