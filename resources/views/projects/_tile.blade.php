{{-- Duży kafel projektu (lista projektów): zdjęcie na całą kartę, nakładka, tytuł, zajawka i wezwanie do działania. --}}
@php
    $tileAccent = \App\Support\Color::isValid($project->accent_color ?? null)
        ? $project->accent_color
        : (($project->audience ?? 'brand') === 'ngo' ? $siteSettings->audienceColor('ngo') : null);
    $tileAccent = $tileAccent ?: 'var(--color-brand)';
@endphp
<a href="{{ route('projects.show', $project) }}"
   class="group relative flex min-h-[22rem] flex-col justify-end overflow-hidden rounded-3xl text-white shadow-md ring-1 ring-black/5 transition duration-300 hover:-translate-y-1 hover:shadow-2xl focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand focus-visible:ring-offset-2 {{ $featured ?? false ? 'md:col-span-2 lg:min-h-[26rem]' : '' }}"
   style="background: linear-gradient(135deg, {{ $tileAccent }}, color-mix(in srgb, {{ $tileAccent }} 55%, #000))">
    @if ($project->image_url)
        <img src="{{ $project->image_url }}" alt="{{ $project->image_alt ?? '' }}" loading="lazy"
             class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105">
    @else
        <i class="fa-solid fa-diagram-project pointer-events-none absolute -right-4 -top-4 text-[9rem] opacity-15" aria-hidden="true"></i>
    @endif
    <span class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/40 to-transparent" aria-hidden="true"></span>

    <span class="relative flex flex-col gap-3 p-6 sm:p-7">
        <span class="inline-flex w-fit items-center rounded-full bg-white/15 px-3 py-1 text-[11px] font-bold uppercase tracking-widest backdrop-blur">
            {{ $project->category->name ?? ($categoryName ?? 'Projekt') }}
        </span>
        <span class="text-2xl font-extrabold leading-tight tracking-tight sm:text-3xl">{{ $project->title }}</span>
        @if ($project->excerpt)
            <span class="line-clamp-3 max-w-xl text-base leading-snug text-white/85">{{ $project->excerpt }}</span>
        @endif
        <span class="mt-1 inline-flex w-fit items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-bold text-ink transition group-hover:bg-brand group-hover:text-white">
            Dowiedz się więcej <i class="fa-solid fa-arrow-right transition group-hover:translate-x-1" aria-hidden="true"></i>
        </span>
    </span>
</a>
