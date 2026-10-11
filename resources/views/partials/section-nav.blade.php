{{--
    Menu działu w prawej kolumnie szablonu „Strona działu": tytuł działu
    i podstrony jako pełnej szerokości odnośniki ze strzałką. Płasko: szare
    tło, bez linii; bieżąca strona pogrubiona, na jasnym tle marki,
    z aria-current. Zmienne: $page, $menuSiblings, opcjonalnie $sectionRoot.
--}}
@php
    $sectionRoot ??= ($page->parent ?: $page);
    $navId = 'section-nav-'.$page->id;
@endphp
@if ($menuSiblings->isNotEmpty())
    <nav class="rounded-md bg-gray-50 p-5" aria-labelledby="{{ $navId }}">
        <h2 id="{{ $navId }}" class="text-lg font-bold text-ink">
            @if ($sectionRoot->is($page))
                {{ $sectionRoot->title }}
            @else
                <a href="{{ $sectionRoot->publicUrl() }}" class="hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $sectionRoot->title }}</a>
            @endif
        </h2>
        <span class="mb-4 mt-2 block h-1 w-14 bg-brand" aria-hidden="true"></span>
        <ul class="space-y-1">
            @foreach ($menuSiblings as $item)
                @php $current = $item->is($page); @endphp
                <li>
                    <a href="{{ $item->publicUrl() }}" @if ($current) aria-current="page" @endif
                        class="flex items-center justify-between gap-3 rounded-md px-3 py-2.5 text-ink transition hover:bg-white hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark {{ $current ? 'bg-brand-light font-bold' : 'font-semibold' }}">
                        <span>{{ $item->title }}</span>
                        <i class="fa-solid fa-chevron-right shrink-0 text-xs text-brand-dark" aria-hidden="true"></i>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
