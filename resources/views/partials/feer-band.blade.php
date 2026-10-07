{{--
    Pasek z modułu „FEER Paski": tytuł, opcjonalny tekst i do dwóch przycisków. Style (kontrast tekstu ≥ 4,5:1):
      • brand — biały na #1456CC (6,5:1), • dark — biały na #1D1D1A (16,9:1), • light — ink na #E8F0FF (≥ 14:1).
    Zmienne: $band (FeerBand), $inContent (opcjonalnie) — wstawiony skrótem w treści (zaokrąglony blok zamiast pasa na całą szerokość).
--}}
@php
    $inContent ??= false;
    $style = $band->style ?? 'brand';
    $wrap = match ($style) {
        'dark' => 'bg-ink text-white',
        'light' => 'bg-brand-light text-ink',
        default => 'bg-brand-dark text-white',
    };
    $primary = $style === 'light'
        ? 'bg-brand text-white hover:bg-brand-dark focus-visible:ring-brand focus-visible:ring-offset-brand-light'
        : 'bg-white text-ink hover:bg-gray-100 focus-visible:ring-white '.($style === 'dark' ? 'focus-visible:ring-offset-ink' : 'focus-visible:ring-offset-brand-dark');
    $secondary = $style === 'light'
        ? 'border-2 border-brand-dark text-brand-dark hover:bg-white focus-visible:ring-brand focus-visible:ring-offset-brand-light'
        : 'border-2 border-white text-white hover:bg-white hover:text-ink focus-visible:ring-white '.($style === 'dark' ? 'focus-visible:ring-offset-ink' : 'focus-visible:ring-offset-brand-dark');
    $btn = 'inline-flex min-h-11 items-center gap-2 rounded-md px-6 text-sm font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2';
    $isExternal = fn ($url) => \Illuminate\Support\Str::startsWith((string) $url, ['http://', 'https://']) && ! \Illuminate\Support\Str::contains((string) $url, request()->getHost());
@endphp
<section class="{{ $wrap }} {{ $inContent ? 'not-prose my-8 rounded-lg' : '' }}" aria-labelledby="feer-band-{{ $band->id }}">
    <div class="{{ $inContent ? 'px-6 py-8' : 'mx-auto max-w-6xl px-4 py-12' }} flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
        <div class="min-w-0">
            <h2 id="feer-band-{{ $band->id }}" class="text-2xl font-extrabold leading-tight md:text-3xl">{{ $band->title }}</h2>
            @if (filled($band->text))<p class="mt-2 max-w-2xl text-base leading-relaxed md:text-lg">{{ $band->text }}</p>@endif
        </div>
        @if ($band->hasButton() || $band->hasButton2())
            <div class="flex flex-none flex-wrap gap-3">
                @if ($band->hasButton())
                    <a href="{{ $band->button_url }}" @if ($isExternal($band->button_url)) target="_blank" rel="noopener" @endif class="{{ $btn }} {{ $primary }}">{{ $band->button_label }}@if ($isExternal($band->button_url))<span class="sr-only"> (otwiera się w nowej karcie)</span>@endif</a>
                @endif
                @if ($band->hasButton2())
                    <a href="{{ $band->button2_url }}" @if ($isExternal($band->button2_url)) target="_blank" rel="noopener" @endif class="{{ $btn }} {{ $secondary }}">{{ $band->button2_label }}@if ($isExternal($band->button2_url))<span class="sr-only"> (otwiera się w nowej karcie)</span>@endif</a>
                @endif
            </div>
        @endif
        @if ($band->image_url)
            <img src="{{ $band->image_url }}" alt="{{ $band->image_alt ?? '' }}" loading="lazy" class="aspect-[4/3] w-full flex-none rounded-md object-cover md:order-last md:w-72">
        @endif
    </div>
</section>
