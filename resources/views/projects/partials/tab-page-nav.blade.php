{{-- Pozycja menu bocznego zakładki (rekurencyjna). Zmienne: $item (Page), $depth. Wygląd jak partials/page-local-nav. --}}
@php
    $kids = $item->tree_children;
    $navClass = $depth === 0
        ? 'flex min-h-11 w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-left text-base transition'
        : 'flex min-h-10 w-full items-center justify-between gap-3 rounded-md px-3 py-1.5 text-left text-[0.9375rem] transition';
@endphp
<li>
    <button type="button" @click="@if (! empty($tabId)) tab = '{{ $tabId }}'; @endif node = {{ $item->id }}; @if ($kids->isNotEmpty()) openIds.includes({{ $item->id }}) ? openIds = openIds.filter(i => i !== {{ $item->id }}) : openIds.push({{ $item->id }}) @endif"
        :aria-current="node === {{ $item->id }} ? 'page' : null"
        @if ($kids->isNotEmpty()) :aria-expanded="openIds.includes({{ $item->id }}).toString()" @endif
        class="{{ $navClass }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
        :class="node === {{ $item->id }} ? 'bg-brand-light font-bold text-brand-dark' : 'text-ink hover:bg-white hover:text-brand-dark hover:underline hover:underline-offset-4'">
        <span class="min-w-0">{{ $item->title }}</span>
        <i class="fa-solid fa-chevron-right flex-none {{ $depth === 0 ? 'text-xs' : 'text-[0.625rem]' }} transition-transform motion-reduce:transition-none"
            @if ($kids->isNotEmpty()) :class="openIds.includes({{ $item->id }}) ? 'rotate-90' : ''" @endif aria-hidden="true"></i>
    </button>
    @if ($kids->isNotEmpty())
        <ul role="list" class="mt-1 space-y-1 pl-4" x-show="openIds.includes({{ $item->id }})" x-cloak>
            @foreach ($kids as $kid)
                @include('projects.partials.tab-page-nav', ['item' => $kid, 'depth' => $depth + 1, 'tabId' => $tabId ?? null])
            @endforeach
        </ul>
    @endif
</li>
