@if ($projects->isNotEmpty())
<section class="border-t border-gray-100 py-16" aria-labelledby="federation-projects-heading">
    <div class="mx-auto max-w-[1400px] px-4">
        <h2 id="federation-projects-heading" class="mb-12 text-center text-3xl font-extrabold tracking-tight text-ink">
            Nasze aktualne projekty
        </h2>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($projects as $i => $project)
                @php $accent = $project->accent_color ?: $siteSettings->brandColorN(($i % 4) + 1); @endphp
                <article class="flex flex-col gap-3 rounded-lg border border-gray-100 p-6">
                    <span class="inline-flex h-8 w-8 flex-none items-center justify-center rounded text-sm font-extrabold" style="background:{{ $accent }}1a; color:{{ $accent }}" aria-hidden="true">
                        {{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}
                    </span>
                    <h3 class="text-lg font-bold leading-snug text-ink">{{ $project->title }}</h3>
                    @if ($project->excerpt)
                        <p class="flex-1 text-sm leading-relaxed text-muted">{{ \Illuminate\Support\Str::limit($project->excerpt, 150) }}</p>
                    @endif
                    <a href="{{ route('projects.show', $project) }}"
                        class="mt-2 inline-flex w-fit items-center gap-1.5 text-sm font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                        style="color:{{ $accent }}; --tw-ring-color:{{ $accent }}">
                        Dowiedz się więcej
                        <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                    </a>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif
