@if ($projects->isNotEmpty())
<section class="bg-gray-50 py-14" aria-labelledby="ngo-projects-heading">
    <div class="mx-auto max-w-[1400px] px-4">

        <div class="mb-8 flex items-end justify-between gap-4">
            <h2 id="ngo-projects-heading" class="text-2xl font-extrabold text-gray-900 md:text-3xl">
                Nasze projekty
            </h2>
            <a href="{{ route('projects.index') }}"
                class="shrink-0 text-sm font-semibold text-brand hover:underline"
                aria-label="Wszystkie projekty">
                Wszystkie projekty &rarr;
            </a>
        </div>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($projects as $project)
            <article class="group relative flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 transition hover:shadow-md">

                {{-- Coloured stripe / image --}}
                @if ($project->image_url)
                    <a href="{{ route('projects.show', $project) }}" tabindex="-1" aria-hidden="true">
                        <div class="aspect-[4/3] overflow-hidden">
                            <img src="{{ $project->image_url }}" alt="{{ $project->image_alt ?? $project->title }}"
                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                        </div>
                    </a>
                @else
                    <div class="h-2 w-full" style="background-color: {{ $project->accent_color ?: 'var(--color-brand)' }}"></div>
                @endif

                <div class="flex flex-1 flex-col gap-3 p-5">
                    <h3 class="text-base font-extrabold leading-snug text-gray-900 group-hover:text-brand">
                        <a href="{{ route('projects.show', $project) }}" class="stretched-link">{{ $project->title }}</a>
                    </h3>
                    @if ($project->excerpt)
                        <p class="text-sm leading-relaxed text-gray-500 line-clamp-3">{{ $project->excerpt }}</p>
                    @endif
                    @if ($project->for_whom)
                        <p class="mt-auto flex items-center gap-1.5 text-xs text-gray-400">
                            <i class="fa-solid fa-users" aria-hidden="true"></i>
                            {{ $project->for_whom }}
                        </p>
                    @endif
                </div>

            </article>
            @endforeach
        </div>

    </div>
</section>
@endif
