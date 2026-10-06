{{-- Kafel projektu (lista projektów): duże zdjęcie 4:3, pasek koloru akcentu, kategoria, tytuł, zajawka i odnośnik. Bez ikon i gradientów. --}}
@php
    $tileAccent = \App\Support\Color::isValid($project->accent_color ?? null)
        ? $project->accent_color
        : (($project->audience ?? 'brand') === 'ngo' ? $siteSettings->audienceColor('ngo') : null);
    $tileAccent = $tileAccent ?: 'var(--color-brand)';
@endphp
<a href="{{ route('projects.show', $project) }}"
   class="group flex h-full flex-col overflow-hidden rounded-lg border border-gray-200 bg-white transition duration-200 hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2"
   style="border-top: 4px solid {{ $tileAccent }}">
    @if ($project->image_url)
        <span class="block overflow-hidden bg-gray-100">
            <img src="{{ $project->image_url }}" alt="{{ $project->image_alt ?? '' }}" loading="lazy"
                 class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-[1.03]">
        </span>
    @else
        <span class="block aspect-[4/3] w-full" style="background: color-mix(in srgb, {{ $tileAccent }} 12%, #fff)" aria-hidden="true"></span>
    @endif
    <span class="flex flex-1 flex-col p-6">
        <span class="text-xs font-bold uppercase tracking-widest text-muted">{{ $project->category->name ?? ($categoryName ?? 'Projekt') }}</span>
        <span class="mt-2 text-xl font-bold leading-snug text-ink group-hover:text-brand-dark">{{ $project->title }}</span>
        @if ($project->excerpt)
            <span class="mt-2 line-clamp-3 text-sm leading-relaxed text-muted">{{ $project->excerpt }}</span>
        @endif
        <span class="mt-auto pt-4 text-sm font-bold text-brand-dark">Zobacz projekt <span aria-hidden="true" class="inline-block transition group-hover:translate-x-1">→</span></span>
    </span>
</a>
