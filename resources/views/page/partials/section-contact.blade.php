{{--
    Kontakt opiekuna działu dziedziczony w głąb drzewa (Page::inheritedSetting('contact')): ustawiony na stronie
    działu pokazuje się pod treścią jej podstron, dopóki podstrona nie poda własnego albo go nie wyłączy.
    Płasko: jasne tło, bez ramki; linki e-mail i telefon z kontrastem ≥ 4,5:1.
--}}
@php $sectionContact = $page->inheritedSetting('contact'); @endphp
@if ($sectionContact)
    @php $sc = $sectionContact['values']; @endphp
    <section class="mt-10 rounded-md bg-gray-50 p-5" aria-labelledby="page-contact-h" data-section-contact>
        <h2 id="page-contact-h" class="text-lg font-bold text-ink">Kontakt w sprawie tej strony</h2>
        <p class="mt-2 text-base text-ink"><span class="font-bold">{{ $sc['contact_name'] }}</span>@if ($sc['contact_role'])<span class="text-muted">, {{ $sc['contact_role'] }}</span>@endif</p>
        @if ($sc['contact_email'] || $sc['contact_phone'])
            <ul role="list" class="mt-2 flex flex-wrap gap-x-6 gap-y-2 text-base">
                @if ($sc['contact_email'])
                    <li><i class="fa-solid fa-envelope mr-1.5 text-brand-dark" aria-hidden="true"></i><span class="sr-only">E-mail: </span><a href="mailto:{{ $sc['contact_email'] }}" class="font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $sc['contact_email'] }}</a></li>
                @endif
                @if ($sc['contact_phone'])
                    <li><i class="fa-solid fa-phone mr-1.5 text-brand-dark" aria-hidden="true"></i><span class="sr-only">Telefon: </span><a href="tel:{{ preg_replace('/[^0-9+]/', '', $sc['contact_phone']) }}" class="font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $sc['contact_phone'] }}</a></li>
                @endif
            </ul>
        @endif
    </section>
@endif
