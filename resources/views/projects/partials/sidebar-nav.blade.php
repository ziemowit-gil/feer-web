{{--
    Menu boczne strony działania (zamiast paska zakładek): „O projekcie", własne sekcje i podstrony-zakładki
    z zagnieżdżonymi gałęziami. Stan: tab, node, openIds we wspólnym x-data strony projektu.
    Wygląd jak menu boczne stron (partials/page-local-nav): kreska marki pod tytułem, pozycje na całą
    szerokość z szewronem, bieżąca tłem marki i pogrubieniem.
--}}
@php
    $projNavItem = 'proj-nav-item flex min-h-10 w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-left text-[0.9375rem] transition '
        .'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    $projNavIdle = 'text-ink hover:bg-white hover:text-brand-dark';
    $projNavCurrent = 'bg-white font-bold text-brand-dark';
@endphp
<nav aria-label="Sekcje działania" class="proj-nav rounded-md bg-gray-50 p-5">
    @php $navHeading = $project->nav_title ?: $project->title; @endphp
    {{-- Krótka nazwa z pola „Krótka nazwa (menu boczne)”; długi pełny tytuł mniejszą czcionką. --}}
    <p class="text-xs font-bold uppercase tracking-widest text-ink">W tym działaniu</p>
    <p @class(['mt-1 font-bold leading-snug text-ink', 'text-base' => mb_strlen($navHeading) <= 40, 'text-[0.9375rem]' => mb_strlen($navHeading) > 40])>{{ $navHeading }}</p>
    <span class="mb-3 mt-2 block h-1 w-10 bg-brand" aria-hidden="true"></span>
    <ul role="list" class="space-y-0.5">
        <li>
            <button type="button" @click="tab = 'opis'; node = null; $event.detail && $el.blur()" :aria-current="tab === 'opis' ? 'page' : null"
                class="{{ $projNavItem }}" :class="tab === 'opis' ? @js($projNavCurrent) : @js($projNavIdle)">
                <span class="min-w-0">O projekcie</span>
                <i class="fa-solid fa-chevron-right flex-none text-xs" aria-hidden="true"></i>
            </button>
        </li>
        @foreach ($sectionTabs as $i => $section)
            <li>
                <button type="button" @click="tab = 'sekcja-{{ $i }}'; node = null; $event.detail && $el.blur()" :aria-current="tab === 'sekcja-{{ $i }}' ? 'page' : null"
                    class="{{ $projNavItem }}" :class="tab === 'sekcja-{{ $i }}' ? @js($projNavCurrent) : @js($projNavIdle)">
                    <span class="min-w-0">{{ $section['title'] ?: 'Sekcja '.($i + 1) }}</span>
                    <i class="fa-solid fa-chevron-right flex-none text-xs" aria-hidden="true"></i>
                </button>
            </li>
        @endforeach
        @foreach ($tabPages as $i => $subpage)
            @include('projects.partials.tab-page-nav', ['item' => $subpage, 'depth' => 0, 'tabId' => 'podstrona-'.$i])
        @endforeach
        @foreach ($tabLinks ?? [] as $linkPage)
            @include('projects.partials.tab-page-nav', ['item' => $linkPage, 'depth' => 0, 'tabId' => null])
        @endforeach
    </ul>
    @include('projects.partials.sidebar-extras', ['project' => $project])
</nav>
