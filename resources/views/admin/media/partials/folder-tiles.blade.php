{{--
    Foldery bieżącego miejsca jako kafle nad plikami (jak w menedżerze mediów Joomli):
    „W górę” do folderu nadrzędnego i podfoldery z liczbą plików i podfolderów.
    W kafelkach ta sama siatka (i rozmiar miniatur) co pliki, w liście — zwarte wiersze.

    Expects: $folder, $subfolders, $folderTree, $parentCrumb, $showArchived (z admin.media.index).
--}}
@php
    $folderUrl = fn ($id) => route('admin.multimedia.index', array_filter(['folder' => $id, 'archived' => $showArchived ? 1 : null]));
    $upLabel = $parentCrumb?->name ?? 'Wszystkie pliki';
    $folderMeta = function ($node) use ($folderTree) {
        $files = (int) $node->media_count;
        $subs = $folderTree->get($node->id, collect())->count();

        return collect([
            $files.' '.trans_choice('plik|pliki|plików', $files),
            $subs ? $subs.' '.trans_choice('podfolder|podfoldery|podfolderów', $subs) : null,
        ])->filter()->implode(' · ');
    };
@endphp

{{-- Kafelki --}}
<ul x-show="view === 'grid'" x-cloak role="list" class="grid gap-3" :style="gridStyle" aria-label="Foldery">
    @if ($folder)
        <li>
            <a href="{{ $folderUrl($folder->parent_id) }}" class="group block rounded-md bg-white p-2 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                <span class="flex aspect-[4/3] items-center justify-center rounded bg-gray-100 text-brand-dark">
                    <i class="fa-solid fa-arrow-turn-up text-4xl" aria-hidden="true"></i>
                </span>
                <span class="mt-2 block text-sm font-bold text-ink">W górę</span>
                <span class="block truncate text-xs text-muted">do: {{ $upLabel }}</span>
            </a>
        </li>
    @endif
    @foreach ($subfolders as $sub)
        @php $restricted = $sub->userGroups->isNotEmpty(); @endphp
        <li>
            <a href="{{ $folderUrl($sub->id) }}" class="group block rounded-md bg-white p-2 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                <span class="relative flex aspect-[4/3] items-center justify-center rounded bg-brand-light text-brand-dark">
                    <i class="fa-solid fa-folder text-5xl" aria-hidden="true"></i>
                    @if ($restricted)
                        <i class="fa-solid fa-lock absolute bottom-2 right-2 text-sm text-ink" aria-hidden="true"></i>
                    @endif
                </span>
                <span class="mt-2 block truncate text-sm font-bold text-ink" title="{{ $sub->name }}"><span class="sr-only">Folder: </span>{{ $sub->name }}</span>
                <span class="block text-xs text-muted">
                    {{ $folderMeta($sub) }}
                    @if ($restricted)<span class="sr-only"> · dostęp ograniczony</span>@endif
                </span>
            </a>
        </li>
    @endforeach
</ul>

{{-- Lista --}}
<ul x-show="view === 'list'" x-cloak role="list" class="overflow-hidden rounded-md bg-white" aria-label="Foldery">
    @if ($folder)
        <li>
            <a href="{{ $folderUrl($folder->parent_id) }}" class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-dark">
                <span class="flex h-8 w-8 flex-none items-center justify-center rounded bg-gray-100 text-brand-dark"><i class="fa-solid fa-arrow-turn-up" aria-hidden="true"></i></span>
                <span class="font-bold text-ink">W górę</span>
                <span class="truncate text-muted">do: {{ $upLabel }}</span>
            </a>
        </li>
    @endif
    @foreach ($subfolders as $sub)
        <li>
            <a href="{{ $folderUrl($sub->id) }}" class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-dark">
                <span class="flex h-8 w-8 flex-none items-center justify-center rounded bg-brand-light text-brand-dark"><i class="fa-solid fa-folder" aria-hidden="true"></i></span>
                <span class="min-w-0 truncate font-bold text-ink"><span class="sr-only">Folder: </span>{{ $sub->name }}</span>
                @if ($sub->userGroups->isNotEmpty())
                    <i class="fa-solid fa-lock flex-none text-xs text-muted" aria-hidden="true"></i><span class="sr-only">(dostęp ograniczony)</span>
                @endif
                <span class="ml-auto flex-none text-xs text-muted">{{ $folderMeta($sub) }}</span>
            </a>
        </li>
    @endforeach
</ul>
