{{-- Kafel działania (lista działań): płaskie szare pole (bez ramek, cieni i paska koloru), zdjęcie 4:3 (gdy jest), kategoria, tytuł,
     forma udziału zwykłym tekstem, zajawka i odnośnik. Kontrast: ink/muted na gray-50 i gray-100 (≥ 4,5:1), odnośnik brand-dark. --}}
<a href="{{ route('projects.show', $project) }}"
   class="group flex h-full flex-col overflow-hidden rounded-md bg-gray-50 transition-colors hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
    @if ($project->image_url)
        <img src="{{ $project->image_url }}" alt="{{ $project->image_alt ?? '' }}" loading="lazy" class="aspect-[4/3] w-full object-cover">
    @endif
    <span class="flex flex-1 flex-col p-6">
        <span class="text-xs font-bold uppercase tracking-widest text-muted">{{ $project->category->name ?? ($categoryName ?? 'Działanie') }}</span>
        <span class="mt-2 text-xl font-bold leading-snug text-ink group-hover:text-brand-dark group-hover:underline group-hover:underline-offset-4">{{ $project->title }}</span>
        <span class="block text-ink">@include('projects.partials.form-badge', ['project' => $project])</span>
        @if ($project->excerpt)
            <span class="mt-2 line-clamp-3 text-sm leading-relaxed text-muted">{{ $project->excerpt }}</span>
        @endif
        <span class="mt-auto pt-4 text-sm font-bold text-brand-dark">Zobacz działanie <span aria-hidden="true" class="inline-block transition group-hover:translate-x-1 motion-reduce:transition-none">→</span></span>
    </span>
</a>
