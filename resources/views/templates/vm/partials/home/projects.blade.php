{{-- Projekty szablonu "vm": trzy kafle ze zdjęciem i ciemną etykietą tytułu (biały tekst na #262626 — 15:1). --}}
@if ($projects->isNotEmpty())
<section class="py-14" aria-labelledby="vm-projects-heading">
    <div class="mx-auto max-w-[1200px] px-4">
        <h2 id="vm-projects-heading" class="vm-display vm-section-title mb-10 text-2xl text-ink sm:text-3xl lg:text-4xl">Projekty</h2>

        <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" role="list">
            @foreach ($projects as $project)
                <li>
                    <a href="{{ route('projects.show', $project) }}"
                       class="group relative block aspect-[4/3] overflow-hidden bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                        @if ($project->image_url)
                            <img src="{{ $project->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                        @elseif ($siteSettings->logoUrl())
                            <img src="{{ $siteSettings->logoUrl() }}" alt="" loading="lazy" class="h-full w-full object-contain p-10">
                        @endif
                        <span class="vm-display absolute bottom-0 left-0 right-6 bg-[#262626]/95 px-4 py-3 text-sm leading-snug text-white transition group-hover:bg-brand">
                            {{ $project->title }}
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="mt-10 text-center">
            @include('templates.vm.partials.home.more-button', ['url' => route('projects.index'), 'label' => 'Czytaj więcej', 'context' => 'projekty'])
        </div>
    </div>
</section>
@endif
