{{-- Węzeł drzewa podstron działania (rekurencyjny, dowolna głębokość). Zmienne: $node (Page), $project, $depth. --}}
@php $kids = $node->tree_children ?? collect(); @endphp
<li>
    <div class="flex flex-wrap items-center gap-2 px-3 py-2 rounded-md bg-white">
        <i class="fa-solid {{ $kids->isNotEmpty() ? 'fa-folder-tree' : 'fa-file-lines' }} w-4 text-center text-gray-500" aria-hidden="true"></i>
        <a href="{{ route('admin.podstrony.edit', $node) }}" class="min-w-0 flex-1 truncate text-sm font-bold text-ink hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $node->title }}</a>
        @if (! $node->is_published)<span class="text-xs font-bold text-ink"><i class="fa-solid fa-pen-to-square mr-1 text-muted" aria-hidden="true"></i>Szkic</span>@endif
        @if ($depth === 0)<span class="text-xs font-bold text-muted">{{ \App\Models\Page::PROJECT_DISPLAYS[$node->project_display ?? 'link'] ?? '' }}</span>@endif
        <a href="{{ route('admin.podstrony.create', ['parent_id' => $node->id, 'project_id' => $project->id, 'project_display' => 'tab']) }}"
           class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-bold text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark bg-gray-100 hover:bg-brand-light">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Podstrona<span class="sr-only"> w „{{ $node->title }}”</span>
        </a>
    </div>
    @if ($kids->isNotEmpty())
        <ul class="ml-5 mt-1.5 space-y-1.5 border-l-2 border-gray-200 pl-3" role="list">
            @foreach ($kids as $child)
                @include('admin.projects.partials.page-tree-node', ['node' => $child, 'project' => $project, 'depth' => $depth + 1])
            @endforeach
        </ul>
    @endif
</li>
