{{--
    Baner sekcji dziedziczony w głąb drzewa (Page::inheritedSetting('banner')): ustawiony na stronie działu
    pokazuje się na jej podstronach, dopóki podstrona nie ma własnego albo nie wyłączy go („Bez banera”).
    Płasko: sam obraz, bez ramki i podpisu; bez opisu alt = ozdobnik (alt="").
--}}
@php $sectionBanner = $page->inheritedSetting('banner'); @endphp
@if ($sectionBanner)
    <div class="mb-8 print:hidden" data-section-banner @if ($sectionBanner['inherited']) data-inherited-from="{{ $sectionBanner['source']->id }}" @endif>
        <img src="{{ $sectionBanner['values']['banner_image'] }}" alt="{{ $sectionBanner['values']['banner_alt'] ?? '' }}"
            class="h-40 w-full rounded-md object-cover sm:h-56 md:h-64" loading="eager" decoding="async">
    </div>
@endif
