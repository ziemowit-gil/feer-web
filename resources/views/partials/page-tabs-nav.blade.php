{{--
    Zakładki podstron działu — poziomy odpowiednik bocznego drzewa
    (partials/page-local-nav), włączany na stronie polem `side_nav_style = tabs`.

    Pierwsza zakładka to strona nadrzędna (lub działanie), kolejne to podstrony
    tego samego poziomu. Podstrony rodzeństwa nie są tu rozwijane — zakładki
    pokazują jeden poziom, żeby pasek pozostał czytelny.

    WCAG: to nawigacja (linki), nie widżet „tabs" ARIA — bieżąca strona ma
    aria-current="page" (4.1.2), cele mają ≥ 44 px wysokości (2.5.8), pasek
    przewija się poziomo na wąskich ekranach bez ucinania (1.4.10).
--}}
@php
    $tabsUp = null;
    $tabsHeading = $page->title;

    if ($page->project) {
        $tabsHeading = $page->project->title;
        $tabsUp = ['label' => $page->project->title, 'url' => route('projects.show', $page->project), 'current' => false];
    } elseif ($page->parent) {
        $tabsHeading = $page->parent->title;
        $tabsUp = ['label' => $page->parent->title, 'url' => route('page.show', $page->parent), 'current' => $page->parent->is($page)];
    } else {
        // Strona główna działu: sama jest pierwszą zakładką.
        $tabsUp = ['label' => $page->title, 'url' => $page->publicUrl(), 'current' => true];
    }

    // Zakładki dziedziczą substyl paska menu głównego (Ustawienia → Nagłówek):
    // „pigułki" wypełniają aktywną zakładkę ciemnym kolorem marki, pozostałe
    // style oznaczają ją pogrubionym tekstem w kolorze marki i kreską pod spodem
    // (kształt, nie tylko kolor). Płasko: szare pole zamiast linii.
    $tabsPills = ($siteSettings->wide_mission_nav_style ?? 'brand_bar') === 'pills';
    $tabClass = $tabsPills
        ? fn (bool $current) => 'inline-flex min-h-11 items-center whitespace-nowrap rounded-md px-4 text-sm font-bold transition '
            . 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2 '
            . ($current ? 'bg-brand-dark text-white' : 'text-ink hover:bg-white hover:text-brand-dark')
        : fn (bool $current) => 'relative inline-flex min-h-11 items-center whitespace-nowrap rounded-md px-4 text-sm font-bold transition '
            . 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-dark '
            . ($current ? 'bg-white text-brand-dark' : 'text-ink hover:bg-white hover:text-brand-dark hover:underline hover:underline-offset-4');
@endphp

<nav aria-label="Podstrony w tym dziale" class="mb-8 rounded-md bg-gray-50 p-1.5">
    <p class="sr-only">{{ $tabsHeading }}</p>
    <ul role="list" class="flex gap-1 overflow-x-auto">
        @if ($tabsUp)
            <li class="flex-none">
                <a href="{{ $tabsUp['url'] }}" @if ($tabsUp['current']) aria-current="page" @endif class="{{ $tabClass($tabsUp['current']) }}">
                    {{ $tabsUp['label'] }}
                    @if (! $tabsPills && $tabsUp['current'])<span class="absolute inset-x-4 bottom-1 block h-1 bg-brand" aria-hidden="true"></span>@endif
                </a>
            </li>
        @endif
        @foreach ($menuSiblings as $sibling)
            @php $tabCurrent = $sibling->is($page) || $sibling->publishedChildren->contains(fn ($c) => $c->is($page)); @endphp
            <li class="flex-none">
                <a href="{{ $sibling->publicUrl() }}" @if ($sibling->is($page)) aria-current="page" @endif class="{{ $tabClass($tabCurrent) }}">
                    {{ $sibling->title }}
                    @if (! $tabsPills && $tabCurrent)<span class="absolute inset-x-4 bottom-1 block h-1 bg-brand" aria-hidden="true"></span>@endif
                </a>
            </li>
        @endforeach
    </ul>
</nav>
