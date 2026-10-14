{{-- Widżet portalu: działania kategorii jako płaskie wiersze (jak lista kategorii w szablonie FEER), miniatura tylko gdy jest zdjęcie. $cols: klasy siatki (domyślnie dwie kolumny). --}}
@if ($projects->isNotEmpty())
    <section aria-labelledby="portal-w-projects">
        <h2 id="portal-w-projects" class="text-xl font-bold leading-tight text-ink md:text-2xl">Działania</h2>
        <span class="mt-2 block h-1 w-12 bg-brand" aria-hidden="true"></span>
        <ul role="list" class="mt-6 grid gap-2 {{ $cols ?? 'md:grid-cols-2' }}">
            @foreach ($projects as $project)
                <li>
                    <a href="{{ route('projects.show', $project) }}" class="group flex h-full items-center gap-4 rounded-md bg-gray-50 px-4 py-4 transition-colors hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                        @if ($project->image_url)
                            <img src="{{ $project->image_url }}" alt="" class="h-16 w-16 flex-none rounded-md object-cover" loading="lazy">
                        @endif
                        <span class="min-w-0 flex-1">
                            <span class="block text-lg font-bold leading-snug text-ink group-hover:text-brand-dark group-hover:underline group-hover:underline-offset-4">{{ $project->title }}</span>
                            <span class="block text-ink">@include('projects.partials.form-badge', ['project' => $project])</span>
                            @if ($teaser = $project->teaser())
                                <span class="mt-1 line-clamp-2 block text-sm leading-relaxed text-ink">{{ $teaser }}</span>
                            @endif
                        </span>
                        <span class="flex-none text-lg text-brand-dark transition group-hover:translate-x-1 motion-reduce:transition-none" aria-hidden="true">→</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif
