{{-- Szablon FEER — „Nasze projekty": te same karty co na liście projektów (partial projects._tile). --}}
@if ($projects->isNotEmpty())
<section class="bg-gray-50 py-12" aria-labelledby="ngo-projects-heading">
    <div class="mx-auto max-w-6xl px-4">
        <div class="mb-8 flex items-end justify-between gap-4">
            <h2 id="ngo-projects-heading" class="text-2xl font-bold text-ink md:text-3xl">Nasze projekty</h2>
            <a href="{{ route('projects.index') }}" class="shrink-0 text-sm font-bold text-brand-dark underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
               aria-label="Wszystkie projekty">Wszystkie projekty →</a>
        </div>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($projects as $project)
                @include('projects._tile', ['project' => $project])
            @endforeach
        </div>
    </div>
</section>
@endif
