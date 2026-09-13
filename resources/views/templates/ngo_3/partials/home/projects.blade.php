{{--
    Nasze projekty — ten sam układ co Aktualności (pierwsza pozycja wyróżniona,
    reszta mniejsza), żeby sekcje na stronie ngo_3 wyglądały spójnie.
    Kontroler (HomeController::ngo3Home()) filtruje tylko aktywne projekty
    (is_completed = false).
--}}
@if ($projects->isNotEmpty())
<section class="py-14" aria-labelledby="ngo3-projects-heading">
    <div class="mx-auto max-w-[1400px] px-4">

        <div class="mb-8 flex items-end justify-between gap-4">
            <h2 id="ngo3-projects-heading" class="text-2xl font-extrabold text-gray-900 md:text-3xl">
                Nasze projekty
            </h2>
            <a href="{{ route('projects.index') }}"
                class="shrink-0 text-sm font-semibold text-brand hover:underline"
                aria-label="Wszystkie projekty">
                Wszystkie projekty &rarr;
            </a>
        </div>

        @php $first = $projects->first(); $rest = $projects->skip(1); @endphp

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">

            {{-- Pierwsza (wyróżniona) pozycja --}}
            @if ($first)
            <article class="group relative flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 transition hover:shadow-md md:col-span-2 lg:col-span-1">
                @if ($first->image_url)
                    <a href="{{ route('projects.show', $first) }}" tabindex="-1" aria-hidden="true">
                        <div class="aspect-[21/9] overflow-hidden">
                            <img src="{{ $first->image_url }}" alt="{{ $first->image_alt ?? $first->title }}"
                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                        </div>
                    </a>
                @else
                    <div class="h-2 w-full" style="background-color: {{ $first->accent_color ?: 'var(--color-brand)' }}"></div>
                @endif
                <div class="flex flex-1 flex-col gap-2 p-5">
                    @if ($first->category)
                        <span class="text-xs font-semibold uppercase tracking-wide text-brand">{{ $first->category->name }}</span>
                    @endif
                    <h3 class="text-base font-extrabold leading-snug text-gray-900 group-hover:text-brand">
                        <a href="{{ route('projects.show', $first) }}" class="stretched-link">{{ $first->title }}</a>
                    </h3>
                    @if ($first->for_whom)
                        <p class="mt-auto flex items-center gap-1.5 text-xs text-muted">
                            <i class="fa-solid fa-users" aria-hidden="true"></i> {{ $first->for_whom }}
                        </p>
                    @endif
                </div>
            </article>
            @endif

            {{-- Pozostałe pozycje --}}
            @foreach ($rest as $project)
            <article class="group relative flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 transition hover:shadow-md">
                @if ($project->image_url)
                    <a href="{{ route('projects.show', $project) }}" tabindex="-1" aria-hidden="true">
                        <div class="aspect-[21/9] overflow-hidden">
                            <img src="{{ $project->image_url }}" alt="{{ $project->image_alt ?? $project->title }}"
                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                        </div>
                    </a>
                @else
                    <div class="h-2 w-full" style="background-color: {{ $project->accent_color ?: 'var(--color-brand)' }}"></div>
                @endif
                <div class="flex flex-1 flex-col gap-2 p-5">
                    @if ($project->category)
                        <span class="text-xs font-semibold uppercase tracking-wide text-brand">{{ $project->category->name }}</span>
                    @endif
                    <h3 class="text-sm font-bold leading-snug text-gray-900 group-hover:text-brand">
                        <a href="{{ route('projects.show', $project) }}" class="stretched-link">{{ $project->title }}</a>
                    </h3>
                    @if ($project->for_whom)
                        <p class="mt-auto flex items-center gap-1.5 text-xs text-muted">
                            <i class="fa-solid fa-users" aria-hidden="true"></i> {{ $project->for_whom }}
                        </p>
                    @endif
                </div>
            </article>
            @endforeach
        </div>
    </div>
</section>
@endif
