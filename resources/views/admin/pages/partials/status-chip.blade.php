{{--
    Skrócony status strony: kolor + ikona + tekst (nigdy sam kolor — WCAG 1.4.1).
    Zmienna: $page. Parametr $compact = true → tylko ikona z tekstem dla czytników (drzewo).
--}}
@php
    $compact ??= false;
    // Wyłączona przez stronę nadrzędną: $inherited przekazuje widok zbiorczy (mapa z jednego zapytania),
    // pojedyncze strony sprawdzają przodków same.
    $inherited ??= $page->isDisabledByAncestor();
    $isLive = $page->is_published && ($page->publish_at === null || $page->publish_at->isPast());
    [$chipLabel, $chipIcon, $chipClass] = match (true) {
        (bool) $page->is_disabled => ['Wyłączona', 'fa-ban', 'bg-red-100 text-red-700'],
        $inherited => ['Wyłączona (nadrzędna)', 'fa-ban', 'bg-red-50 text-red-700 ring-1 ring-red-200'],
        $isLive => ['Opublikowana', 'fa-circle-check', 'bg-green-100 text-green-700'],
        $page->is_published => ['Zaplanowana', 'fa-clock', 'bg-blue-100 text-blue-700'],
        default => ['Szkic', 'fa-pen-ruler', 'bg-gray-100 text-gray-600'],
    };
@endphp
@if ($compact)
    <span class="inline-flex h-4 w-4 flex-none items-center justify-center rounded-full text-[9px] {{ $chipClass }}" title="{{ $chipLabel }}">
        <i class="fa-solid {{ $chipIcon }}" aria-hidden="true"></i><span class="sr-only">{{ $chipLabel }}</span>
    </span>
@else
    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-bold {{ $chipClass }}">
        <i class="fa-solid {{ $chipIcon }}" aria-hidden="true"></i>{{ $chipLabel }}
    </span>
@endif
