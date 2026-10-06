{{--
    Zagnieżdżona lista stron z polami wyboru w modalu „Filtry i operacje zbiorcze”.
    Zmienne: $nodes, $byParent, $inheritedDisabled. Strony systemowe są wyłączone z zaznaczania
    (tak samo jak w liście tabelarycznej), a przyczyna jest zapowiadana czytnikom ekranu.
--}}
<ul role="list" class="space-y-0.5 {{ ($depth ?? 0) > 0 ? 'ml-5 border-l border-gray-200 pl-2' : '' }}">
    @foreach ($nodes as $node)
        @php $kids = $byParent->get($node->id, collect()); @endphp
        <li data-bulk-item data-title="{{ \Illuminate\Support\Str::lower($node->title) }}">
            <label class="flex min-h-9 cursor-pointer items-center gap-2 rounded px-2 py-1 text-sm hover:bg-gray-50 {{ $node->is_system ? 'cursor-not-allowed opacity-60' : '' }}">
                <input type="checkbox" name="ids[]" value="{{ $node->id }}" id="bulk-page-{{ $node->id }}" @change="update()" @disabled($node->is_system)
                    class="h-4 w-4 flex-none rounded border-gray-300 text-brand focus:ring-brand">
                <span class="min-w-0 flex-1 truncate">{{ $node->title }}@if ($node->is_system) <span class="sr-only">(strona systemowa — nie można zmieniać zbiorczo)</span>@endif</span>
                <span class="flex-none">@include('admin.pages.partials.status-chip', ['page' => $node, 'compact' => true, 'inherited' => isset($inheritedDisabled[$node->id])])</span>
            </label>
            @if ($kids->isNotEmpty())
                @include('admin.pages.partials.bulk-node', ['nodes' => $kids, 'depth' => ($depth ?? 0) + 1])
            @endif
        </li>
    @endforeach
</ul>
