{{--
    Skrócony status strony: ikona + pogrubiony tekst (płasko, bez kolorowej pigułki; nigdy sam kolor — WCAG 1.4.1).
    Zmienna: $page. Parametr $compact = true → tylko ikona z tekstem dla czytników (drzewo).
--}}
@php
    $compact ??= false;
    // Wyłączona przez stronę nadrzędną: $inherited przekazuje widok zbiorczy (mapa z jednego zapytania),
    // pojedyncze strony sprawdzają przodków same.
    $inherited ??= $page->isDisabledByAncestor();
    $isLive = $page->is_published && ($page->publish_at === null || $page->publish_at->isPast());
    // [etykieta, ikona, kolor ikony]
    [$chipLabel, $chipIcon, $chipClass] = match (true) {
        (bool) $page->is_disabled => ['Wyłączona', 'fa-ban', 'text-red-700'],
        $inherited => ['Wyłączona (nadrzędna)', 'fa-ban', 'text-red-700'],
        $isLive => ['Opublikowana', 'fa-circle-check', 'text-brand-dark'],
        $page->is_published => ['Zaplanowana', 'fa-clock', 'text-brand-dark'],
        default => ['Szkic', 'fa-pen-ruler', 'text-muted'],
    };
@endphp
@if ($compact)
    <span class="inline-flex h-4 w-4 flex-none items-center justify-center text-[11px] {{ $chipClass }}" title="{{ $chipLabel }}">
        <i class="fa-solid {{ $chipIcon }}" aria-hidden="true"></i><span class="sr-only">{{ $chipLabel }}</span>
    </span>
@else
    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-ink">
        <i class="fa-solid {{ $chipIcon }} {{ $chipClass }}" aria-hidden="true"></i>{{ $chipLabel }}
    </span>
@endif
