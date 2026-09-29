{{--
    Belka szablonu "vm": logo (+ opcjonalne logo partnera/programu), menu
    główne i przycisk „Wpłać". Renderowana wewnątrz <header x-data="siteMobileNav()">
    z layouts/site.blade.php — hamburger i panel mobilny to wspólne partiale.
--}}
@php
    $donateUrl = $siteSettings->isModuleEnabled('support') ? route('support.show') : null;
@endphp
<div class="border-b border-gray-100 bg-white">
    <div class="mx-auto flex max-w-[1400px] items-center gap-6 px-4 py-3">

        <div class="flex flex-none items-center gap-6">
            <a href="{{ site_route('home') }}"
               class="flex flex-none items-center gap-3 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2"
               aria-label="{{ $siteSettings->site_name }} — strona główna">
                @if ($siteSettings->logoUrl())
                    <img src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->logoAltText() }}"
                         class="h-12 w-auto max-w-[200px] object-contain lg:h-14">
                @else
                    <span class="vm-display grid h-12 w-12 flex-none place-items-center rounded-2xl bg-brand text-xl text-white" aria-hidden="true">
                        {{ mb_substr($siteSettings->site_name, 0, 1) }}
                    </span>
                    @unless ($siteSettings->showLogoOnly())
                        <span class="vm-display max-w-[14rem] text-base leading-tight text-ink">{{ $siteSettings->site_name }}</span>
                    @endunless
                @endif
            </a>

            @if ($siteSettings->vmHeaderBadgeUrl())
                <img src="{{ $siteSettings->vmHeaderBadgeUrl() }}" alt="{{ $siteSettings->vm_header_badge_alt ?? '' }}"
                     class="hidden h-10 w-auto max-w-[180px] object-contain sm:block lg:h-12">
            @endif
        </div>

        <nav class="vm-nav ml-auto hidden lg:block" aria-label="Nawigacja główna">
            @include('partials.main-nav-items', ['navItems' => $navItems ?? collect(), 'mobile' => false, 'navStyle' => 'underline'])
        </nav>

        <div class="ml-auto flex flex-none items-center gap-3 lg:ml-2">
            @if ($donateUrl)
                <a href="{{ $donateUrl }}"
                   class="vm-display hidden min-h-11 items-center gap-2 rounded-2xl bg-brand px-5 text-sm text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 sm:inline-flex">
                    Wpłać
                    <span class="border-l border-white/60 pl-2" aria-hidden="true"><i class="fa-solid fa-angles-right"></i></span>
                </a>
            @endif
            @include('partials.mobile-nav-toggle', ['panelId' => 'main-nav-panel', 'onBrand' => false, 'hideAt' => 'lg'])
        </div>
    </div>
</div>

@include('partials.mobile-nav-panel', [
    'panelId' => 'main-nav-panel',
    'hideAt' => 'lg',
    'showSearch' => true,
    'cta' => $donateUrl ? ['label' => 'Wpłać', 'url' => $donateUrl] : null,
    'showSupport' => false,
    'socials' => $siteSettings->socialLinks(),
])
