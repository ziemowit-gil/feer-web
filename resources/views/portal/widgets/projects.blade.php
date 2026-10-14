{{-- Widżet portalu: działania kategorii jako kafelki (ten sam kafel co na liście działań). --}}
@if ($projects->isNotEmpty())
    <section aria-labelledby="portal-w-projects">
        <h2 id="portal-w-projects" class="text-xl font-bold leading-tight text-ink md:text-2xl">Działania</h2>
        <span class="mt-2 block h-1 w-12 bg-brand" aria-hidden="true"></span>
        <div class="mt-6 grid gap-6 {{ $cols ?? 'sm:grid-cols-2 lg:grid-cols-3' }}">
            @foreach ($projects as $project)
                @include('projects._tile', ['project' => $project, 'categoryName' => $category->name])
            @endforeach
        </div>
    </section>
@endif
