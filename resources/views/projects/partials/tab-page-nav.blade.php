{{-- Pozycja menu bocznego zakładki (rekurencyjna). Zmienne: $item (Page), $depth. --}}
@php $kids = $item->tree_children; @endphp
<li class="border-b border-gray-300 last:border-0">
    <button type="button" @click="@if (! empty($tabId)) tab = '{{ $tabId }}'; @endif node = {{ $item->id }}; @if ($kids->isNotEmpty()) openIds.includes({{ $item->id }}) ? openIds = openIds.filter(i => i !== {{ $item->id }}) : openIds.push({{ $item->id }}) @endif"
        :aria-current="node === {{ $item->id }} ? 'page' : null"
        @if ($kids->isNotEmpty()) :aria-expanded="openIds.includes({{ $item->id }}).toString()" @endif
        class="flex w-full items-center justify-between gap-2 py-3.5 text-left hover:text-brand focus-visible:outline-2 focus-visible:outline-brand {{ $depth === 0 ? 'text-lg font-bold' : 'text-base font-semibold' }}"
        :class="node === {{ $item->id }} ? 'text-brand' : ''" style="padding-left: {{ $depth * 0.75 }}rem">
        <span>{{ $item->title }}</span>
        @if ($kids->isNotEmpty())
            <i class="fa-solid fa-chevron-right text-xs transition-transform" :class="openIds.includes({{ $item->id }}) ? 'rotate-90' : ''" aria-hidden="true"></i>
        @endif
    </button>
    @if ($kids->isNotEmpty())
        <ul role="list" x-show="openIds.includes({{ $item->id }})" x-cloak class="border-t border-gray-300">
            @foreach ($kids as $kid)
                @include('projects.partials.tab-page-nav', ['item' => $kid, 'depth' => $depth + 1, 'tabId' => $tabId ?? null])
            @endforeach
        </ul>
    @endif
</li>
