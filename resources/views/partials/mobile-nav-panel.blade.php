{{--
    Panel menu mobilnego — wspólny dla wszystkich układów nagłówka.
    Wymaga rodzica z x-data="siteMobileNav()" (resources/js/app.js) i
    przycisku z partials/mobile-nav-toggle (x-ref="menuToggle").

    Zmienne (opcjonalne):
      $panelId     id panelu (domyślnie main-nav-panel),
      $hideAt      'lg' | 'md' — od jakiej szerokości panel znika,
      $showSearch  wyszukiwarka na górze panelu,
      $cta         ['label' => …, 'url' => …] — przycisk pod menu,
      $showSupport numer konta + „Wesprzyj" (partials/wide-support-line),
      $socials     lista [url, klasa ikony, etykieta] (socialLinks()).

    WCAG: <nav> z nazwą, fokus trafia do panelu po otwarciu i wraca na
    hamburger po zamknięciu (2.4.3), Escape i kliknięcie obok zamykają,
    cele ≥ 44 px, linki zewnętrzne zapowiadają nową kartę.
--}}
@php
    $panelId     = $panelId ?? 'main-nav-panel';
    $hideAt      = ($hideAt ?? 'lg') === 'md' ? 'md:hidden' : 'lg:hidden';
    $showSearch  = $showSearch ?? false;
    $cta         = $cta ?? null;
    $showSupport = $showSupport ?? true;
    $socials     = $socials ?? [];
    $hasSupport  = $showSupport && (filled($siteSettings->bank_account_number) || \Illuminate\Support\Facades\Route::has('support.show'));
@endphp
<nav id="{{ $panelId }}" x-ref="mobilePanel" x-show="mobileOpen" x-cloak
     aria-label="Menu główne"
     @click.outside="if (! $refs.menuToggle.contains($event.target)) closeMenu(false)"
     class="{{ $hideAt }} border-t border-gray-200 bg-white text-ink shadow-lg">

    @if ($showSearch)
        <form action="{{ route('search') }}" method="GET" role="search" aria-label="Wyszukiwarka serwisu" class="border-b border-gray-100 px-4 py-3">
            <label for="{{ $panelId }}-search" class="sr-only">Wyszukaj w serwisie</label>
            <div class="flex overflow-hidden rounded-md border border-gray-300 focus-within:border-brand focus-within:ring-2 focus-within:ring-brand">
                <input id="{{ $panelId }}-search" type="search" name="q" value="{{ request('q') }}" placeholder="Wyszukaj w serwisie" autocomplete="off"
                       class="min-h-11 flex-1 border-0 px-3 text-sm placeholder:text-gray-600 focus:outline-none focus:ring-0">
                <button type="submit" class="flex h-11 w-11 flex-none items-center justify-center text-ink hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand" aria-label="Szukaj">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                </button>
            </div>
        </form>
    @endif

    <div class="px-4 pb-4">
        @include('partials.main-nav-items', ['mobile' => true])
    </div>

    @if ($cta || $hasSupport)
        <div class="flex flex-col gap-3 border-t border-gray-100 px-4 py-4">
            @if ($cta)
                <a href="{{ $cta['url'] }}" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-full bg-brand px-5 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">{{ $cta['label'] }}</a>
            @endif
            @if ($hasSupport)
                @include('partials.wide-support-line')
            @endif
        </div>
    @endif

    @if ($socials)
        <div class="border-t border-gray-100 px-4 py-3">
            <ul class="flex flex-wrap gap-1" aria-label="Media społecznościowe">
                @foreach ($socials as [$socialUrl, $socialIcon, $socialLabel])
                    <li>
                        <a href="{{ $socialUrl }}" target="_blank" rel="noopener"
                           class="flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 text-lg text-muted transition hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2"
                           aria-label="{{ $socialLabel }} — otwiera się w nowej karcie">
                            <i class="{{ $socialIcon }}" aria-hidden="true"></i>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</nav>
