{{--
    Hamburger menu mobilnego — wspólny dla wszystkich układów nagłówka.
    Wymaga rodzica z x-data="siteMobileNav()" (resources/js/app.js).

    Zmienne (opcjonalne): $panelId (domyślnie main-nav-panel), $onBrand (białe
    ikony na tle marki), $hideAt ('lg' | 'md' — od jakiej szerokości chowany).

    WCAG: cel 44×44 px (2.5.8), etykieta zmienia się z „Otwórz" na „Zamknij"
    (4.1.2), aria-controls wskazuje panel, widoczny fokus (2.4.7).
--}}
@php
    $panelId = $panelId ?? 'main-nav-panel';
    $onBrand = $onBrand ?? false;
    $hideAt  = ($hideAt ?? 'lg') === 'md' ? 'md:hidden' : 'lg:hidden';
@endphp
<button type="button" x-ref="menuToggle" @click="toggleMenu()"
        class="flex h-11 w-11 flex-none items-center justify-center rounded-lg border text-xl transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 {{ $hideAt }} {{ $onBrand
            ? 'border-white/30 text-white hover:bg-white/15 focus-visible:ring-white focus-visible:ring-offset-brand'
            : 'border-gray-200 text-ink hover:border-brand hover:text-brand focus-visible:ring-brand' }}"
        aria-controls="{{ $panelId }}"
        :aria-expanded="mobileOpen.toString()"
        :aria-label="mobileOpen ? 'Zamknij menu' : 'Otwórz menu'">
    <i class="fa-solid" :class="mobileOpen ? 'fa-xmark' : 'fa-bars'" aria-hidden="true"></i>
</button>
