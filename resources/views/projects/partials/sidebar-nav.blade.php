{{--
    Menu boczne strony projektu (zamiast paska zakładek): „O projekcie", własne sekcje i podstrony-zakładki
    z zagnieżdżonymi gałęziami. Stan: tab, node, openIds we wspólnym x-data strony projektu.
--}}
<nav aria-label="Sekcje projektu" class="relative bg-gray-100 p-6 lg:sticky lg:top-6">
    <span class="absolute -left-2 -top-2 h-6 w-6 bg-brand" aria-hidden="true"></span>
    <p class="mb-4 border-b border-gray-900 pb-3 text-lg font-bold text-ink">{{ $project->title }}</p>
    <ul role="list" class="text-ink">
        <li class="border-b border-gray-300">
            <button type="button" @click="tab = 'opis'; node = null" :aria-current="tab === 'opis' ? 'page' : null"
                class="flex w-full items-center py-3 text-left font-bold hover:text-brand focus-visible:outline-2 focus-visible:outline-brand"
                :class="tab === 'opis' ? 'text-brand' : ''">O projekcie</button>
        </li>
        @foreach ($sectionTabs as $i => $section)
            <li class="border-b border-gray-300">
                <button type="button" @click="tab = 'sekcja-{{ $i }}'; node = null" :aria-current="tab === 'sekcja-{{ $i }}' ? 'page' : null"
                    class="flex w-full items-center py-3 text-left font-bold hover:text-brand focus-visible:outline-2 focus-visible:outline-brand"
                    :class="tab === 'sekcja-{{ $i }}' ? 'text-brand' : ''">{{ $section['title'] ?: 'Sekcja '.($i + 1) }}</button>
            </li>
        @endforeach
        @foreach ($tabPages as $i => $subpage)
            @include('projects.partials.tab-page-nav', ['item' => $subpage, 'depth' => 0, 'tabId' => 'podstrona-'.$i])
        @endforeach
    </ul>
</nav>
