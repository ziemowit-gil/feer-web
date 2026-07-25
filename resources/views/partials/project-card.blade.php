@php $ngoAccent = ($project->audience ?? 'brand') === 'ngo' ? $siteSettings->audienceColor('ngo') : null; @endphp
<a href="{{ route('projects.show', $project) }}" class="group block overflow-hidden rounded-lg border border-gray-200"
    @if ($ngoAccent) style="border-top: 4px solid {{ $ngoAccent }};" @endif>
    @if ($project->image_url)
        <div class="h-36 overflow-hidden bg-gray-100">
            <img src="{{ $project->image_url }}" alt="" class="h-full w-full object-cover transition group-hover:scale-105">
        </div>
    @endif
    <div class="p-4">
        <h3 class="font-bold text-ink group-hover:text-brand">{{ $project->title }}</h3>
        @if ($project->excerpt)
            <p class="mt-1 text-sm text-muted">{{ $project->excerpt }}</p>
        @endif
    </div>
</a>
