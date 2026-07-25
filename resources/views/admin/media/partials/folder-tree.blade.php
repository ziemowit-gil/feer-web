{{--
    Renders one level of the media folder tree and recurses into children.

    Expects:
      $nodes        — collection of MediaFolder at this level (with media_count)
      $tree         — all folders grouped by parent_id (App\...\MediaFolder::groupBy)
      $currentId    — id of the folder currently open (null at root)
      $showArchived — whether the archive view is active (kept in nav links)
--}}
<ul class="space-y-0.5">
    @foreach ($nodes as $node)
        @php $children = $tree->get($node->id, collect()); @endphp
        <li>
            <a href="{{ route('admin.multimedia.index', ['folder' => $node->id, 'archived' => $showArchived ? 1 : null]) }}"
                class="flex items-center gap-2 rounded px-2 py-1 text-sm {{ $currentId === $node->id ? 'bg-brand-light font-bold text-brand' : 'text-ink hover:bg-gray-100' }}">
                <i class="fa-solid {{ $currentId === $node->id ? 'fa-folder-open' : 'fa-folder' }} w-4 text-brand" aria-hidden="true"></i>
                <span class="min-w-0 flex-1 truncate" title="{{ $node->name }}">{{ $node->name }}</span>
                @if ($node->media_count > 0)
                    <span class="text-xs text-muted">{{ $node->media_count }}</span>
                @endif
            </a>

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
