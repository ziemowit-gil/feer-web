{{--
    Wariant „Instytucjonalny": ciemny pas z tytułem, poziomy pasek zakładek
    i treść wybranej zakładki. W miejscu, w którym serwisy urzędowe wstawiają
    mapę, pokazujemy komplet danych teleadresowych.

    Dostępność: zakładki to wzorzec ARIA Tabs — role tablist/tab/tabpanel,
    aria-selected, roving tabindex i obsługa strzałek oraz Home/End (WCAG 2.1.1).
    Bez JavaScriptu (noscript) wszystkie panele są widoczne, więc treść nie ginie.
--}}
@extends('layouts.site')

@section('title', 'Kontakt — ' . $siteSettings->site_name)
@section('meta_description', 'Skontaktuj się z ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Kontakt'],
    ]])
@endsection

@section('content')
    @php
        $contactTabs = collect([
            ['id' => 'dane',      'label' => 'Dane teleadresowe'],
            ['id' => 'formularz', 'label' => 'Napisz do nas'],
            ['id' => 'spotkania', 'label' => $meetingTitle,        'show' => $showMeetings],
            ['id' => 'przesylki', 'label' => 'Przesyłki',          'show' => $showShipping],
            ['id' => 'rachunki',  'label' => 'Rachunki bankowe',   'show' => ! empty($siteSettings->contact_bank_accounts)],
        ])->filter(fn ($tab) => $tab['show'] ?? true)->values();

        $tabIds = $contactTabs->pluck('id')->all();
    @endphp

    <noscript>
        {{-- Bez JS panele nie przełączają się — pokazujemy je wszystkie. --}}
        <style>[x-cloak] { display: block !important; }</style>
    </noscript>

    <div x-data="sectionTabs(@js($tabIds))">

        {{-- Ciemny pas z tytułem; ze zdjęciem biura w tle, jeśli je wgrano.
             Przyciemnienie 65% czerni trzyma kontrast białego tekstu na min. 7:1
             nawet przy całkiem jasnym zdjęciu (WCAG 1.4.3), a klasa
             .contact-hero-photo chowa zdjęcie w trybach wysokiego kontrastu. --}}
        @php $tabsHeroPhoto = $siteSettings->contactHeroPhotoUrl(); @endphp
        <section class="relative bg-ink {{ $tabsHeroPhoto ? 'contact-hero-photo' : '' }}"
            @if ($tabsHeroPhoto) style="background-image: url('{{ $tabsHeroPhoto }}');" @endif>

            @if ($tabsHeroPhoto)
                <div class="absolute inset-0 bg-black/65" aria-hidden="true"></div>
            @endif

            <div class="relative mx-auto max-w-6xl px-4 {{ $tabsHeroPhoto ? 'py-16 sm:py-20' : 'py-10' }}">
                <h1 class="text-3xl font-bold uppercase tracking-wide text-white sm:text-4xl">Kontakt</h1>
            </div>
        </section>

        @include('partials.tab-strip', [
            'tabItems' => $contactTabs->all(),
            'tabsLabel' => 'Sekcje strony kontaktowej',
        ])

        <div class="mx-auto max-w-6xl px-4 py-10">

            {{-- Zakładka: dane teleadresowe (w miejscu mapy) --}}
            <div id="panel-dane" role="tabpanel" aria-labelledby="tab-dane" tabindex="0"
                 x-show="tab === 'dane'" class="focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand">
                {{-- Bez paska bocznego: dane teleadresowe rozkładają się na całą
                     szerokość zakładki, w siatce zamiast wąskiej kolumny. --}}
                <div>
                    <h2 class="text-2xl font-bold text-ink">Zapraszamy do kontaktu!</h2>

                    @if ($siteSettings->contact_intro)
                        <div class="prose mt-3 max-w-3xl text-muted">{!! $siteSettings->contact_intro !!}</div>
                    @endif

                    <div class="mt-6 max-w-3xl">
                        @include('partials.correspondence-note')
                    </div>

                    {{-- Zdjęcia biura tu nie powtarzamy — jest już w tle nagłówka. --}}

                    <div class="mt-8 border-t border-gray-200 pt-8">
                        @include('contact.partials.details', ['withOfficePhoto' => ! $tabsHeroPhoto, 'wideLayout' => true])
                        @include('contact.partials.registry', ['wideLayout' => true])
                    </div>

                    <p class="mt-8 text-sm text-muted">
                        Wolisz napisać?
                        <button type="button" @click="jump('formularz')" class="font-bold text-brand underline hover:no-underline">
                            Przejdź do formularza kontaktowego
                        </button>.
                    </p>
                </div>
            </div>

            {{-- Zakładka: formularz --}}
            <div id="panel-formularz" role="tabpanel" aria-labelledby="tab-formularz" tabindex="0" x-cloak
                 x-show="tab === 'formularz'" class="focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand">
                <div class="max-w-3xl">
                    <h2 class="mb-1 text-2xl font-bold text-ink">Napisz do nas</h2>
                    <p class="mb-6 text-sm text-muted">Odpowiadamy zwykle w ciągu jednego dnia roboczego.</p>

                    @include('contact.partials.form')
                </div>
            </div>

            {{-- Pozostałe zakładki korzystają z tych samych sekcji co inne warianty --}}
            @if ($showMeetings)
                <div id="panel-spotkania" role="tabpanel" aria-labelledby="tab-spotkania" tabindex="0" x-cloak
                     x-show="tab === 'spotkania'" class="focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand">
                    @include('contact.partials.meetings', ['sectionStyle' => 'bare'])
                </div>
            @endif

            @if ($showShipping)
                <div id="panel-przesylki" role="tabpanel" aria-labelledby="tab-przesylki" tabindex="0" x-cloak
                     x-show="tab === 'przesylki'" class="focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand">
                    @include('contact.partials.shipping', ['sectionStyle' => 'bare'])
                </div>
            @endif

            @if (! empty($siteSettings->contact_bank_accounts))
                <div id="panel-rachunki" role="tabpanel" aria-labelledby="tab-rachunki" tabindex="0" x-cloak
                     x-show="tab === 'rachunki'" class="focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand">
                    @include('contact.partials.bank-accounts', ['sectionStyle' => 'bare'])
                </div>
            @endif
        </div>
    </div>

    @include('contact.partials.copy-script')
@endsection
