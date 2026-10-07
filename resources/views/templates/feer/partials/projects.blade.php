{{--
    Szablon FEER — „Nasze projekty": widok listy (wiersze), inny niż karty na liście projektów.
    Każdy wiersz: kolor akcentu po lewej, kategoria, tytuł, zajawka i strzałka; miniatura zdjęcia, gdy jest.
    Cały wiersz jest linkiem; bez ramek i kresek — białe wiersze na jasnoszarym tle sekcji; tekst ciemny (kontrast ≥ 4,5:1).
--}}
@if ($projects->isNotEmpty())
<section class="bg-gray-50 py-12" aria-labelledby="ngo-projects-heading">
    <div class="mx-auto max-w-6xl px-4">
        <div class="mb-8 flex items-end justify-between gap-4">
            <h2 id="ngo-projects-heading" class="text-2xl font-bold text-ink md:text-3xl">Nasze projekty</h2>
            <a href="{{ route('projects.index') }}" class="shrink-0 text-sm font-bold text-brand-dark underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
               aria-label="Wszystkie projekty">Wszystkie projekty →</a>
        </div>

        <ul class="space-y-2" role="list">
            @foreach ($projects as $project)
                @php
                    $rowAccent = \App\Support\Color::isValid($project->accent_color ?? null)
                        ? $project->accent_color
                        : (($project->audience ?? 'brand') === 'ngo' ? $siteSettings->audienceColor('ngo') : 'var(--color-brand)');
                @endphp
                <li>
                    <a href="{{ route('projects.show', $project) }}"
                       class="group flex items-center gap-4 rounded-md bg-white px-5 py-4 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                       style="border-left: 4px solid {{ $rowAccent }}">
                        @if ($project->image_url)
                            <img src="{{ $project->image_url }}" alt="" loading="lazy" class="hidden h-16 w-24 flex-none rounded-md object-cover sm:block">
                        @endif
                        <span class="min-w-0 flex-1">
                            <span class="block text-xs font-bold uppercase tracking-widest text-muted">{{ $project->category->name ?? 'Projekt' }}</span>
                            <span class="mt-0.5 block text-lg font-bold leading-snug text-ink group-hover:text-brand-dark">{{ $project->title }}</span>
                            @if ($project->excerpt)
                                <span class="mt-1 line-clamp-2 block text-sm leading-relaxed text-muted">{{ $project->excerpt }}</span>
                            @endif
                        </span>
                        <span class="flex-none text-lg text-brand-dark transition group-hover:translate-x-1" aria-hidden="true">→</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
