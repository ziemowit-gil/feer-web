{{--
    Szablon FEER — „Nasze działania": kafle w 3 kolumnach (2 na tablecie, 1 na telefonie), inne niż karty na liście projektów.
    Każdy kafel: numer, kategoria, tytuł, zajawka, a na dole forma udziału i strzałka. Cały kafel jest linkiem.
    Płasko, bez ramek: tła na zmianę jasnoszary / brand-light / ink (sekcja bywa biała). Na ink cały tekst biały (bez opacity), na jasnych ciemny — kontrast ≥ 4,5:1.
--}}
@if ($projects->isNotEmpty())
<section class="bg-gray-50 py-12" aria-labelledby="ngo-projects-heading">
    <div class="mx-auto max-w-6xl px-4">
        <div class="mb-8 flex items-end justify-between gap-4">
            <h2 id="ngo-projects-heading" class="text-2xl font-bold text-ink md:text-3xl">Nasze działania</h2>
            <div class="flex shrink-0 flex-wrap items-center justify-end gap-x-5 gap-y-1">
                @include('partials.admin-manage-link', ['route' => 'admin.projekty.index', 'label' => 'Zarządzaj działaniami'])
                <a href="{{ route('projects.index') }}" class="shrink-0 text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                   aria-label="Wszystkie działania">Wszystkie działania →</a>
            </div>
        </div>

        @php
            // Tła kafli na zmianę: [tło + hover, kolor tekstu, kolor numeru/strzałki, kolor zajawki, kolor ikony w tle].
            // Ikona w tle to ozdobnik (aria-hidden) w pełnym, jasnym kolorze — bez przezroczystości, nie zasłania tekstu.
            $tileThemes = [
                ['bg-gray-100 hover:bg-brand-light', 'text-ink', 'text-brand-dark', 'text-muted', 'text-gray-200 group-hover:text-white'],
                ['bg-brand-light hover:bg-white', 'text-ink', 'text-brand-dark', 'text-ink', 'text-white group-hover:text-brand-light'],
                ['bg-ink hover:bg-brand-dark', 'text-white', 'text-white', 'text-white', 'text-[#33332f] group-hover:text-[#2f63c9]'],
            ];
        @endphp
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" role="list">
            @foreach ($projects as $project)
                @php [$tileBg, $tileText, $tileAccent, $tileTeaser, $tileIcon] = $tileThemes[$loop->index % 3]; @endphp
                <li class="flex">
                    <a href="{{ route('projects.show', $project) }}"
                       class="group relative isolate flex w-full flex-col overflow-hidden rounded-md p-6 transition-colors motion-reduce:transition-none {{ $tileBg }} {{ $tileText }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                        <span class="pointer-events-none absolute -bottom-6 -right-4 -z-10 select-none text-[8.5rem] leading-none transition-colors motion-reduce:transition-none {{ $tileIcon }}" aria-hidden="true">{!! icon_html($project->category?->iconValue() ?? 'bi-grid') !!}</span>
                        <span class="text-3xl font-bold leading-none {{ $tileAccent }}" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="mt-5 block text-xs font-bold uppercase tracking-widest">{{ $project->category->name ?? 'Działanie' }}</span>
                        <span class="mt-1 block text-xl font-bold leading-snug group-hover:underline group-hover:underline-offset-4">{{ $project->title }}</span>
                        @if ($teaser = $project->teaser())
                            <span class="mt-3 line-clamp-3 block text-sm leading-relaxed {{ $tileTeaser }}">{{ $teaser }}</span>
                        @endif
                        <span class="mt-auto flex items-center justify-between gap-3 pt-6">
                            @include('projects.partials.form-badge', ['project' => $project])
                            <span class="text-xl {{ $tileAccent }} transition group-hover:translate-x-1 motion-reduce:transition-none" aria-hidden="true">→</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
