{{-- Kafel projektu (lista projektów): zdjęcie lub jednolity kolor akcentu, kategoria, tytuł, zajawka i odnośnik. --}}
@php
    $tileAccent = \App\Support\Color::isValid($project->accent_color ?? null)
        ? $project->accent_color
        : (($project->audience ?? 'brand') === 'ngo' ? $siteSettings->audienceColor('ngo') : null);
    $tileAccent = $tileAccent ?: 'var(--color-brand)';
@endphp
<a href="{{ route('projects.show', $project) }}"
   class="group flex h-full flex-col overflow-hidden rounded-xl border border-gray-200 bg-white transition hover:border-brand hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
    @if ($project->image_url)
        <img src="{{ $project->image_url }}" alt="{{ $project->image_alt ?? '' }}" loading="lazy" class="aspect-[16/10] w-full object-cover">
    @else
        <span class="block aspect-[16/10] w-full" style="background: color-mix(in srgb, {{ $tileAccent }} 14%, #fff); border-bottom: 4px solid {{ $tileAccent }}" aria-hidden="true"></span>
    @endif
    <span class="flex flex-1 flex-col p-4">
        <span class="text-[11px] font-bold uppercase tracking-widest text-brand">{{ $project->category->name ?? ($categoryName ?? 'Projekt') }}</span>
        <span class="mt-1.5 text-base font-bold leading-snug text-ink group-hover:text-brand">{{ $project->title }}</span>
        @if ($project->excerpt)
            <span class="mt-1.5 line-clamp-2 text-sm leading-snug text-muted">{{ $project->excerpt }}</span>
        @endif
        <span class="mt-auto pt-3 text-sm font-bold text-brand">Zobacz projekt <span aria-hidden="true">→</span></span>
    </span>
</a>
