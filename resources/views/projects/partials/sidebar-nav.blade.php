{{--
    Menu boczne strony działania (zamiast paska zakładek): „O projekcie", własne sekcje i podstrony-zakładki
    z zagnieżdżonymi gałęziami. Stan: tab, node, openIds we wspólnym x-data strony projektu.
    Wygląd jak menu boczne stron (partials/page-local-nav): kreska marki pod tytułem, pozycje na całą
    szerokość z szewronem, bieżąca tłem marki i pogrubieniem.
--}}
@php
    $projNavItem = 'flex min-h-11 w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-left text-base transition '
        .'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    $projNavIdle = 'text-ink hover:bg-white hover:text-brand-dark hover:underline hover:underline-offset-4';
    $projNavCurrent = 'bg-brand-light font-bold text-brand-dark';
@endphp
<nav aria-label="Sekcje działania" class="proj-nav rounded-md bg-gray-50 p-5">
    <p class="text-lg font-bold leading-snug text-ink">{{ $project->title }}</p>
    <span class="mb-4 mt-2 block h-1 w-14 bg-brand" aria-hidden="true"></span>
    <ul role="list" class="space-y-1">
        <li>
            <button type="button" @click="tab = 'opis'; node = null" :aria-current="tab === 'opis' ? 'page' : null"
                class="{{ $projNavItem }}" :class="tab === 'opis' ? @js($projNavCurrent) : @js($projNavIdle)">
                <span class="min-w-0">O projekcie</span>
                <i class="fa-solid fa-chevron-right flex-none text-xs" aria-hidden="true"></i>
            </button>
        </li>
        @foreach ($sectionTabs as $i => $section)
            <li>
                <button type="button" @click="tab = 'sekcja-{{ $i }}'; node = null" :aria-current="tab === 'sekcja-{{ $i }}' ? 'page' : null"
                    class="{{ $projNavItem }}" :class="tab === 'sekcja-{{ $i }}' ? @js($projNavCurrent) : @js($projNavIdle)">
                    <span class="min-w-0">{{ $section['title'] ?: 'Sekcja '.($i + 1) }}</span>
                    <i class="fa-solid fa-chevron-right flex-none text-xs" aria-hidden="true"></i>
                </button>
            </li>
        @endforeach
        @foreach ($tabPages as $i => $subpage)
            @include('projects.partials.tab-page-nav', ['item' => $subpage, 'depth' => 0, 'tabId' => 'podstrona-'.$i])
        @endforeach
    </ul>
    @include('projects.partials.sidebar-extras', ['project' => $project])
</nav>
