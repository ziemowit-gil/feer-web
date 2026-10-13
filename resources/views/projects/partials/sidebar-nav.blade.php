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
<div class="proj-nav space-y-4">
<nav aria-label="Sekcje działania" class="rounded-md bg-gray-50 p-5">
    @php $navHeading = $project->nav_title ?: $project->title; @endphp
    {{-- Krótka nazwa z pola „Krótka nazwa (menu boczne)”; długi pełny tytuł mniejszą czcionką. --}}
    <p @class(['font-bold leading-snug text-ink', 'text-base' => mb_strlen($navHeading) <= 40, 'text-[0.9375rem]' => mb_strlen($navHeading) > 40])>{{ $navHeading }}</p>
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

    {{-- Powrót: do kategorii i do wszystkich działań. --}}
    <ul role="list" class="mt-5 space-y-1 text-sm">
        @if ($project->category)
            <li><a href="{{ route('categories.show', $project->category) }}" class="inline-flex min-h-9 items-center gap-2 font-bold text-ink hover:text-brand-dark hover:underline hover:underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"><span aria-hidden="true">←</span> {{ $project->category->name }}</a></li>
        @endif
        <li><a href="{{ route('projects.index') }}" class="inline-flex min-h-9 items-center gap-2 font-bold text-ink hover:text-brand-dark hover:underline hover:underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"><span aria-hidden="true">←</span> Wszystkie działania</a></li>
    </ul>
</nav>

    {{-- Kontakt z koordynatorem — osobny blok pod menu (poza jego szarym polem): zdjęcie (jeśli jest), imię, e-mail i telefon. --}}
    @if (! $project->is_completed && $project->showsCoordinator())
        <section class="rounded-md bg-gray-50 p-5" aria-labelledby="proj-contact-h">
            <h2 id="proj-contact-h" class="text-base font-bold text-ink">Masz pytania? Chętnie odpowiemy</h2>
            @php $coordPhoto = $project->coordinatorPhoto(); @endphp
            {{-- Zdjęcie tylko, gdy jest (a gdy się nie wczyta — znika); bez zdjęcia sam tekst. --}}
            @if ($coordPhoto || $project->coordinator_name)
                <div class="mt-3 flex items-center gap-3">
                    @if ($coordPhoto)
                        <img src="{{ $coordPhoto }}" alt="" loading="lazy" class="h-16 w-16 flex-none rounded-md object-cover" onerror="this.remove()">
                    @endif
                    @if ($project->coordinator_name)<p class="text-base font-bold text-ink">{{ $project->coordinator_name }}</p>@endif
                </div>
            @endif
            <dl class="mt-3 space-y-1 text-sm text-ink">
                <div><dt class="inline font-bold">E-mail:</dt> <dd class="inline"><a href="mailto:{{ $project->contactEmail() }}" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $project->contactEmail() }}</a></dd></div>
                @if ($project->coordinator_phone)
                    <div><dt class="inline font-bold">Tel.:</dt> <dd class="inline"><a href="tel:{{ preg_replace('/[^0-9+]/', '', $project->coordinator_phone) }}" class="whitespace-nowrap font-bold text-brand-dark underline underline-offset-2 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $project->coordinator_phone }}</a></dd></div>
                    @if (filled($project->coordinator_phone_hours))<p class="text-xs">Telefon czynny {{ $project->coordinator_phone_hours }}</p>@endif
                @endif
            </dl>
            <p class="mt-4 text-sm text-ink">Najwygodniej napisz przez formularz: wiadomość trafi prosto do koordynatora tego działania.</p>
            <a href="{{ route('projects.ask', ['project' => $project->slug]) }}" class="mt-2 flex min-h-12 w-full items-center justify-center gap-2 rounded-md bg-brand-dark px-4 text-base font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Zadaj pytanie przez formularz</a>
        </section>
    @endif
</div>
