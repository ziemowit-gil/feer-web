{{--
    Menu boczne strony działania (zamiast paska zakładek): „O projekcie", własne sekcje i podstrony-zakładki
    z zagnieżdżonymi gałęziami. Stan: tab, node, openIds we wspólnym x-data strony projektu.
--}}
<nav aria-label="Sekcje działania" class="proj-nav rounded-md bg-gray-50 p-6">
    <p class="mb-2 text-lg font-bold text-ink">{{ $project->title }}</p>
    <ul role="list" class="text-ink">
        <li>
            <button type="button" @click="tab = 'opis'; node = null" :aria-current="tab === 'opis' ? 'page' : null"
                class="flex w-full items-center py-3 text-left text-base hover:text-brand-dark hover:underline hover:underline-offset-4 focus-visible:outline-2 focus-visible:outline-brand-dark"
                :class="tab === 'opis' ? 'font-bold text-brand-dark' : ''">O projekcie</button>
        </li>
        @foreach ($sectionTabs as $i => $section)
            <li>
                <button type="button" @click="tab = 'sekcja-{{ $i }}'; node = null" :aria-current="tab === 'sekcja-{{ $i }}' ? 'page' : null"
                    class="flex w-full items-center py-3 text-left text-base hover:text-brand-dark hover:underline hover:underline-offset-4 focus-visible:outline-2 focus-visible:outline-brand-dark"
                    :class="tab === 'sekcja-{{ $i }}' ? 'font-bold text-brand-dark' : ''">{{ $section['title'] ?: 'Sekcja '.($i + 1) }}</button>
            </li>
        @endforeach
        @foreach ($tabPages as $i => $subpage)
            @include('projects.partials.tab-page-nav', ['item' => $subpage, 'depth' => 0, 'tabId' => 'podstrona-'.$i])
        @endforeach
    </ul>
    @include('projects.partials.sidebar-extras', ['project' => $project])
</nav>
