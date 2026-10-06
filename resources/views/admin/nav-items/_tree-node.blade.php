@php
    /**
     * Pozycja w lewym panelu (drzewo menu). Parametry: $item, $level (1|2), $selected, $location.
     * Markup zachowuje role="tree" / role="group" i data-id, z których korzysta skrypt przeciągania (Sortable).
     */
    $isSel = $selected && $selected->id === $item->id;
    $kids = $level === 1 ? $item->allChildren : collect();
    $typeIcons = ['link' => 'fa-link', 'dropdown' => 'fa-list', 'projects' => 'fa-diagram-project', 'pages' => 'fa-file-lines',
        'volunteering' => 'fa-people-carry-box', 'events' => 'fa-calendar', 'faq' => 'fa-circle-question'];
    $icon = $item->is_button ? 'fa-bullhorn' : ($item->is_column_heading ? 'fa-table-columns' : ($typeIcons[$item->type] ?? 'fa-link'));
@endphp
<li id="nav-item-{{ $item->id }}" data-id="{{ $item->id }}" role="treeitem" aria-level="{{ $level }}" aria-selected="{{ $isSel ? 'true' : 'false' }}" class="list-none">
    <div class="flex items-stretch overflow-hidden rounded-lg border {{ $isSel ? 'border-brand bg-brand-light' : 'border-gray-200 bg-white hover:bg-gray-50' }} {{ $item->is_active ? '' : 'opacity-60' }}">
        <span class="drag-handle flex cursor-grab select-none items-center bg-gray-50/70 px-2 text-gray-300 hover:text-gray-500 active:cursor-grabbing" aria-hidden="true" title="Przeciągnij, aby zmienić kolejność">
            <i class="fa-solid fa-grip-vertical text-xs"></i>
        </span>
        <a href="{{ route('admin.pozycje-menu.index', ['location' => $location, 'pozycja' => $item->id]) }}" data-nav-first-action
            @if ($isSel) aria-current="true" @endif
            class="flex min-w-0 flex-1 items-center gap-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand {{ $isSel ? 'font-bold text-brand' : 'text-ink' }}"
            style="min-height: 2.5rem; padding: .375rem .625rem">
            <i class="fa-solid {{ $icon }} w-4 flex-none text-center text-xs {{ $isSel ? 'text-brand' : 'text-gray-400' }}" aria-hidden="true"></i>
            <span class="min-w-0 flex-1 truncate">{{ $item->label }}</span>
            @unless ($item->is_active)<span class="flex-none rounded-full bg-amber-100 px-1.5 text-[10px] font-bold text-amber-700">ukryta</span>@endunless
            @if ($item->is_mega && $level === 1)<span class="flex-none rounded-full bg-indigo-50 px-1.5 text-[10px] font-bold text-indigo-700" title="Mega menu">mega</span>@endif
            @if ($kids->isNotEmpty())<span class="flex-none rounded-full bg-gray-100 px-1.5 text-[10px] font-bold text-muted"><span class="sr-only">podpozycji: </span>{{ $kids->count() }}</span>@endif
        </a>
    </div>
    @if ($kids->isNotEmpty())
        <ul role="group" class="mt-1 space-y-1 border-l-2 border-gray-200" style="margin-left: .75rem; padding-left: .5rem">
            @foreach ($kids as $child)
                @include('admin.nav-items._tree-node', ['item' => $child, 'level' => 2, 'selected' => $selected, 'location' => $location])
            @endforeach
        </ul>
    @endif
</li>
