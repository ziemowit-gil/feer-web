{{--
    Czarny pasek górny szablonu "vm": KRS + 1,5% (lewa), rozmiar czcionki,
    kontrast, telefon, e-mail i wyszukiwarka (prawa). Renderowany wewnątrz
    <header> z layouts/site.blade.php.

    WCAG: tekst biały / jasnoszary na #111 (> 12:1), cele ≥ 24 px (2.5.8),
    przyciski mają nazwy i stan aria-pressed, widoczny fokus (2.4.7).
--}}
@php
    $vmFocus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#111]';
@endphp
<div class="bg-[#111] text-xs text-white" role="region" aria-label="Pasek informacyjny i ustawienia dostępności">
    <div class="mx-auto flex max-w-[1400px] flex-wrap items-center gap-x-5 gap-y-1 px-4 py-1.5">

        @if ($siteSettings->krs_number)
            <p class="flex shrink-0 items-center gap-2 font-bold">
                <span><span class="text-brand-light">KRS:</span> {{ $siteSettings->krs_number }}</span>
                @if ($siteSettings->isModuleEnabled('support'))
                    <a href="{{ route('support.show') }}"
                       class="rounded border border-white/70 px-1.5 py-0.5 leading-none transition hover:bg-white hover:text-[#111] {{ $vmFocus }}"
                       aria-label="Przekaż 1,5% podatku — jak nas wesprzeć">1,5%</a>
                @endif
            </p>
        @endif

        <div class="ml-auto flex flex-wrap items-center gap-x-4 gap-y-1">
            <div class="flex items-center gap-1" role="group" aria-label="Rozmiar czcionki">
                <button type="button" data-a11y-font="reset" class="flex h-6 min-w-6 items-center justify-center rounded px-1 text-[11px] font-bold hover:text-brand-light {{ $vmFocus }}" aria-label="Domyślny rozmiar czcionki">A</button>
                <button type="button" data-a11y-font="up" class="flex h-6 min-w-6 items-center justify-center rounded px-1 text-sm font-bold hover:text-brand-light {{ $vmFocus }}" aria-label="Zwiększ czcionkę">A<sup aria-hidden="true">+</sup></button>
                <button type="button" data-a11y-font="down" class="flex h-6 min-w-6 items-center justify-center rounded px-1 text-sm font-bold hover:text-brand-light {{ $vmFocus }}" aria-label="Zmniejsz czcionkę">A<sup aria-hidden="true">−</sup></button>
            </div>

            <div class="flex items-center gap-1.5" role="group" aria-label="Tryb kontrastowy">
                <button type="button" data-a11y-contrast="contrast-bw"
                    class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-white bg-white text-[11px] font-black text-[#111] hover:opacity-80 {{ $vmFocus }}"
                    aria-pressed="false" aria-label="Kontrast: czarno-żółty">A</button>
                <button type="button" data-a11y-contrast="contrast"
                    class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-white text-[11px] font-black text-white hover:bg-white/15 {{ $vmFocus }}"
                    aria-pressed="false" aria-label="Kontrast: odwrócone kolory">A</button>
            </div>

            @if ($siteSettings->contact_phone)
                <a href="tel:{{ preg_replace('/\s+/', '', $siteSettings->contact_phone) }}"
                   class="hidden items-center gap-1.5 rounded font-bold hover:text-brand-light md:flex {{ $vmFocus }}">
                    <i class="fa-solid fa-phone text-brand-light" aria-hidden="true"></i>
                    <span><span class="sr-only">Telefon: </span>{{ $siteSettings->contact_phone }}</span>
                </a>
            @endif
            @if ($siteSettings->contact_email)
                <a href="mailto:{{ $siteSettings->contact_email }}"
                   class="hidden items-center gap-1.5 rounded font-bold hover:text-brand-light md:flex {{ $vmFocus }}">
                    <i class="fa-solid fa-envelope text-brand-light" aria-hidden="true"></i>
                    <span><span class="sr-only">E-mail: </span>{{ $siteSettings->contact_email }}</span>
                </a>
            @endif

            <form action="{{ route('search') }}" method="GET" role="search" aria-label="Wyszukiwarka serwisu"
                  class="hidden overflow-hidden rounded bg-white text-ink focus-within:ring-2 focus-within:ring-white focus-within:ring-offset-2 focus-within:ring-offset-[#111] sm:flex">
                <label for="vm-top-search" class="sr-only">Wyszukaj w serwisie</label>
                <input id="vm-top-search" type="search" name="q" value="{{ request('q') }}" placeholder="Wyszukaj…" autocomplete="off"
                       class="h-7 w-36 border-0 bg-transparent px-2 text-xs placeholder:text-gray-600 focus:outline-none focus:ring-0 lg:w-44">
                <button type="submit" class="flex h-7 w-7 items-center justify-center hover:text-brand" aria-label="Szukaj">
                    <i class="fa-solid fa-magnifying-glass text-xs" aria-hidden="true"></i>
                </button>
            </form>
        </div>
    </div>
</div>
