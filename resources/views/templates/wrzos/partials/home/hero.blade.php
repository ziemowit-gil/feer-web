{{-- Zdjęcie na całą szerokość + asymetryczne, dekoracyjne kształty w kolorze marki (sygnatura wizualna WRZOS). --}}
<section class="relative overflow-hidden bg-gray-100" aria-hidden="true">
    <div class="relative h-[220px] sm:h-[320px] lg:h-[420px]">
        @if ($siteSettings->wrzosHeroImageUrl())
            <img src="{{ $siteSettings->wrzosHeroImageUrl() }}" alt="" class="h-full w-full object-cover">
        @else
            <div class="h-full w-full bg-gradient-to-br from-gray-200 to-gray-300"></div>
        @endif

        {{-- Kształty dekoracyjne: górny lewy róg + dolny prawy róg. --}}
        <svg class="pointer-events-none absolute -left-6 -top-10 h-40 w-40 text-brand opacity-90 sm:h-56 sm:w-56" viewBox="0 0 200 200" fill="currentColor" aria-hidden="true">
            <path d="M100 0 A100 100 0 0 1 200 100 L100 100 Z" />
            <path d="M0 100 A60 60 0 0 0 60 160 L60 100 Z" opacity="0.85" />
        </svg>
        <svg class="pointer-events-none absolute -bottom-12 -right-6 h-48 w-48 text-brand opacity-90 sm:h-64 sm:w-64" viewBox="0 0 200 200" fill="currentColor" aria-hidden="true">
            <circle cx="100" cy="100" r="100" />
            <path d="M0 100 A100 100 0 0 0 100 200 L100 100 Z" opacity="0" />
        </svg>
        <svg class="pointer-events-none absolute bottom-6 left-1/4 h-16 w-32 text-brand opacity-80 sm:h-20 sm:w-40" viewBox="0 0 200 100" fill="currentColor" aria-hidden="true">
            <path d="M0 100 A100 100 0 0 1 200 100 Z" />
        </svg>
    </div>
</section>
