@php
    $canInlineEdit = auth()->check() && auth()->user()->isAdmin();
@endphp
<section class="bg-white" aria-labelledby="federation-hero-heading"
    @if ($canInlineEdit) x-data="inlineContentEditor('site_setting', {{ $siteSettings->id }}, '{{ route('admin.inline-edit.update') }}')" @endif>
    @if ($canInlineEdit)
        @include('partials.inline-edit-bar')
    @endif

    <div class="mx-auto grid max-w-[1400px] gap-12 px-4 py-16 lg:grid-cols-[1.1fr_0.9fr] lg:items-center lg:py-24">
        <div>
            <p class="mb-3 text-sm font-extrabold uppercase tracking-widest text-brand">O nas</p>
            @if ($canInlineEdit)
                <h1 id="federation-hero-heading" :contenteditable="editMode ? 'true' : 'false'"
                    @blur="if (editMode) saveField('federation_hero_heading', $el.innerText.trim())"
                    :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-brand rounded' : ''"
                    class="mb-6 text-4xl font-extrabold leading-tight tracking-tight text-ink sm:text-5xl">{{ $siteSettings->federationHeroHeading() }}</h1>
            @else
                <h1 id="federation-hero-heading" class="mb-6 text-4xl font-extrabold leading-tight tracking-tight text-ink sm:text-5xl">
                    {{ $siteSettings->federationHeroHeading() }}
                </h1>
            @endif

            @if ($canInlineEdit)
                <div :contenteditable="editMode ? 'true' : 'false'"
                    @blur="if (editMode) saveField('federation_hero_intro', $el.innerHTML.trim())"
                    :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-brand rounded' : ''"
                    class="max-w-2xl space-y-4 text-base leading-relaxed text-muted">{!! $siteSettings->federationHeroIntro() !!}</div>
            @else
                <div class="max-w-2xl space-y-4 text-base leading-relaxed text-muted">{!! $siteSettings->federationHeroIntro() !!}</div>
            @endif
            <a href="{{ url('/zespol') }}"
                class="mt-4 inline-flex items-center gap-1.5 text-sm font-bold text-brand transition hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                Poznaj nasz zespół
                <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
            </a>
        </div>

        {{-- Ilustracja zastępcza: mozaika kafelków w kolorach marki (zamiast jednej dużej karty) — edytowalna w panelu (Ustawienia → Strona główna). --}}
        <div class="mx-auto grid w-full max-w-sm grid-cols-2 gap-3" role="img" aria-label="Organizacje pozarządowe działające razem dla Krakowa">
            @foreach ($siteSettings->federationHeroTiles() as $tile)
                @php $tileColor = $siteSettings->brandColorN((int) ($tile['color'] ?? 1)); @endphp
                <div class="flex items-center gap-3 rounded-lg p-6 text-white {{ ($tile['wide'] ?? false) ? 'col-span-2' : 'flex-col items-start' }}" style="background:{{ $tileColor }}">
                    @if (filled($tile['value'] ?? null))
                        <span class="text-4xl font-extrabold leading-none">{{ $tile['value'] }}</span>
                    @elseif (filled($tile['icon'] ?? null))
                        <i class="{{ $tile['icon'] }} text-2xl" aria-hidden="true"></i>
                    @endif
                    <span class="text-sm font-semibold leading-snug {{ filled($tile['value'] ?? null) ? 'text-white/90' : '' }}">{{ $tile['title'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>
