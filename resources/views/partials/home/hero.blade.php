@php
    $wideMission = $siteSettings->header_layout === 'wide_mission';
    $sidebarStyle = $siteSettings->wide_mission_sidebar_style ?? 'colored';
    $sidebarEnabled = $wideMission && ($siteSettings->wide_mission_sidebar ?? false);

    $missionText = null;
    if ($sidebarEnabled && $sidebarStyle === 'mission') {
        $missionText = \App\Models\Page::where('type', 'about')->value('about_motto') ?: $siteSettings->tagline;
    }

    $sidebarLinks = ($sidebarEnabled && $sidebarStyle !== 'mission' && isset($quickLinks) && $quickLinks->isNotEmpty())
        ? $quickLinks->take(4)
        : collect();

    $hasSidebar = $missionText || $sidebarLinks->isNotEmpty();
@endphp

@if ($siteSettings->isModuleEnabled('hero') && $slides->isNotEmpty())
@if ($hasSidebar)<div class="flex h-[260px] md:h-[360px]">@endif

<section
    class="{{ $hasSidebar ? 'relative flex-1 min-w-0 overflow-hidden bg-ink' : 'relative overflow-hidden bg-ink' }}"
    data-hero-slider
    role="region"
    aria-roledescription="karuzela"
    aria-label="Wyróżnione treści">

    <div class="{{ $hasSidebar ? 'relative h-full' : 'relative h-[320px] md:h-[440px]' }}">
        @foreach ($slides as $index => $slide)
            <div
                class="absolute inset-0 flex items-end bg-cover bg-center transition-opacity duration-700 motion-reduce:transition-none {{ $index === 0 ? 'opacity-100' : 'opacity-0 pointer-events-none' }}"
                style="background-image: linear-gradient(0deg, rgba(0,0,0,.65), rgba(0,0,0,.15)), url('{{ $slide->image_url }}')"
                data-hero-slide
                @if ($index !== 0) aria-hidden="true" @endif
            >
                <div class="mx-auto w-full {{ $hasSidebar ? '' : 'max-w-6xl' }} px-4 py-6 text-white">
                    <h1 class="{{ $hasSidebar ? 'text-xl md:text-2xl' : 'max-w-xl text-2xl md:text-4xl' }} font-bold leading-tight">{{ $slide->title }}</h1>
                    <p class="mt-1.5 {{ $hasSidebar ? 'text-xs md:text-sm' : 'max-w-lg text-sm md:text-base' }} text-white/85">{{ $slide->text }}</p>
                    @if ($slide->button_label && $slide->button_url)
                        <a href="{{ $slide->button_url }}" data-hero-cta {{ $index !== 0 ? 'tabindex=-1' : '' }}
                            class="mt-4 inline-block rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                            {{ $slide->button_label }}
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <button type="button" data-hero-prev class="absolute left-3 top-1/2 flex min-h-[36px] min-w-[36px] -translate-y-1/2 items-center justify-center rounded-full bg-black/30 text-white hover:bg-black/50" aria-label="Poprzedni slajd">
        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
    </button>
    <button type="button" data-hero-next class="absolute right-3 top-1/2 flex min-h-[36px] min-w-[36px] -translate-y-1/2 items-center justify-center rounded-full bg-black/30 text-white hover:bg-black/50" aria-label="Następny slajd">
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
    </button>

    <div class="absolute bottom-3 right-4 flex items-center gap-2 rounded-full bg-black/30 px-3 py-1 text-xs text-white">
        <button type="button" data-hero-toggle class="flex min-h-6 min-w-6 items-center justify-center" aria-label="Wstrzymaj automatyczną zmianę slajdów" aria-pressed="false">
            <i class="fa-solid fa-pause" data-hero-toggle-icon aria-hidden="true"></i>
        </button>
        <span aria-live="polite"><span data-hero-counter>1</span>/{{ count($slides) }}</span>
    </div>
</section>

@if ($hasSidebar)
@if ($missionText)
{{-- Styl: misja organizacji --}}
<aside aria-label="Misja organizacji" class="hidden w-56 flex-none flex-col items-center justify-center gap-4 bg-white px-6 py-8 md:flex lg:w-72">
    @if ($siteSettings->logoUrl())
        <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->site_name }}" class="h-12 w-auto max-w-[8rem] object-contain opacity-80">
    @endif
    <p class="text-center text-sm font-medium leading-relaxed text-gray-700">{{ $missionText }}</p>
    @php $wmCtaLabel = trim($siteSettings->wide_mission_cta_label ?? ''); $wmCtaUrl = trim($siteSettings->wide_mission_cta_url ?? ''); @endphp
    @if ($wmCtaLabel && $wmCtaUrl)
        <a href="{{ $wmCtaUrl }}" class="mt-1 inline-flex items-center gap-2 rounded-full bg-brand px-4 py-2 text-xs font-bold text-white hover:bg-brand-dark">
            {{ $wmCtaLabel }}
        </a>
    @endif
</aside>
@elseif ($sidebarStyle === 'cards')
{{-- Styl: białe karty w siatce 2-kolumnowej --}}
<nav aria-label="Na skróty" class="hidden w-64 flex-col md:flex lg:w-80">
    <div class="grid h-full grid-cols-2">
        @foreach ($sidebarLinks as $link)
            @php
                $tileHex = \App\Support\Color::isValid($link->color ?? '') ? $link->color : '#f59e0b';
                $qa = \App\Support\Color::button($tileHex);
            @endphp
            <a href="{{ $link->url }}"
                class="flex flex-1 flex-col items-center justify-center gap-2 border border-gray-200 bg-white px-3 py-4 text-center text-ink transition hover:border-brand hover:shadow-inner focus-visible:outline-2 focus-visible:outline-brand">
                @if ($link->icon)
                    <span class="flex h-12 w-12 flex-none items-center justify-center rounded-full text-2xl"
                        style="background-color: {{ $tileHex }}; color: {{ $qa['text'] }};">
                        <i class="bi {{ $link->icon }}" aria-hidden="true"></i>
                    </span>
                @endif
                <span class="text-[11px] font-bold uppercase leading-snug tracking-wide">{{ $link->label }}</span>
            </a>
        @endforeach
    </div>
</nav>
@else
{{-- Styl: kolorowe kafle pełnej wysokości (domyślny) --}}
<nav aria-label="Na skróty" class="hidden w-48 flex-col md:flex lg:w-56">
    @foreach ($sidebarLinks as $link)
        @php
            $tileHex = \App\Support\Color::isValid($link->color ?? '') ? $link->color : '#374151';
            $qa = \App\Support\Color::button($tileHex);
        @endphp
        <a href="{{ $link->url }}"
            class="flex flex-1 flex-col items-center justify-center gap-2 px-3 py-3 text-center transition-[filter] duration-150 hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-[-2px]"
            style="background-color: {{ $tileHex }}; color: {{ $qa['text'] }}; outline-color: {{ $qa['text'] }};">
            @if ($link->icon)
                <i class="bi {{ $link->icon }} text-xl" aria-hidden="true"></i>
            @endif
            <span class="text-xs font-bold uppercase leading-snug tracking-wide">{{ $link->label }}</span>
        </a>
    @endforeach
</nav>
@endif
</div>
@endif

@endif
