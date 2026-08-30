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

    <div x-data="{
            tab: @js($tabIds[0] ?? 'dane'),
            tabs: @js($tabIds),
            move(step) {
                const index = this.tabs.indexOf(this.tab);
                this.tab = this.tabs[(index + step + this.tabs.length) % this.tabs.length];
                this.focusActive();
            },
            jump(id) { this.tab = id; this.focusActive(); },
            focusActive() { this.$nextTick(() => document.getElementById('tab-' + this.tab)?.focus()); },
        }">

        {{-- Ciemny pas z tytułem; ze zdjęciem biura w tle, jeśli je wgrano.
             Przyciemnienie 65% czerni trzyma kontrast białego tekstu na min. 7:1
             nawet przy całkiem jasnym zdjęciu (WCAG 1.4.3), a klasa
             .contact-hero-photo chowa zdjęcie w trybach wysokiego kontrastu. --}}
        @php $tabsHeroPhoto = $siteSettings->officePhotoUrl(); @endphp
        <section class="relative bg-ink {{ $tabsHeroPhoto ? 'contact-hero-photo' : '' }}"
            @if ($tabsHeroPhoto) style="background-image: url('{{ $tabsHeroPhoto }}');" @endif>

            @if ($tabsHeroPhoto)
                <div class="absolute inset-0 bg-black/65" aria-hidden="true"></div>
            @endif

            <div class="relative mx-auto max-w-6xl px-4 {{ $tabsHeroPhoto ? 'py-16 sm:py-20' : 'py-10' }}">
                <h1 class="text-3xl font-bold uppercase tracking-wide text-white sm:text-4xl">Kontakt</h1>

                @if ($tabsHeroPhoto && filled($siteSettings->contact_office_building))
                    <p class="mt-2 text-sm text-white/90">{{ $siteSettings->contact_office_building }}</p>
                @endif
            </div>
        </section>

        {{-- Pasek zakładek --}}
        <div class="border-t border-white/10 bg-ink">
            <div class="mx-auto max-w-6xl px-4">
                <div role="tablist" aria-label="Sekcje strony kontaktowej" class="flex flex-wrap">
                    @foreach ($contactTabs as $tab)
                        <button type="button" role="tab"
                            id="tab-{{ $tab['id'] }}"
                            aria-controls="panel-{{ $tab['id'] }}"
                            :aria-selected="tab === '{{ $tab['id'] }}' ? 'true' : 'false'"
                            :tabindex="tab === '{{ $tab['id'] }}' ? 0 : -1"
                            @click="tab = '{{ $tab['id'] }}'"
                            @keydown.arrow-right.prevent="move(1)"
                            @keydown.arrow-left.prevent="move(-1)"
                            @keydown.home.prevent="jump(tabs[0])"
                            @keydown.end.prevent="jump(tabs[tabs.length - 1])"
                            class="px-5 py-4 text-sm font-bold uppercase tracking-wide transition focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-white"
                            :class="tab === '{{ $tab['id'] }}' ? 'bg-white text-ink' : 'text-white/80 hover:bg-white/10 hover:text-white'">
                            {{ $tab['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-6xl px-4 py-10">

            {{-- Zakładka: dane teleadresowe (w miejscu mapy) --}}
            <div id="panel-dane" role="tabpanel" aria-labelledby="tab-dane" tabindex="0"
                 x-show="tab === 'dane'" class="focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand">
                <div class="grid gap-10 md:grid-cols-[1fr_340px]">
                    <div>
                        <h2 class="text-2xl font-bold text-ink">Zapraszamy do kontaktu!</h2>

                        @if ($siteSettings->contact_intro)
                            <div class="prose mt-3 max-w-2xl text-muted">{!! $siteSettings->contact_intro !!}</div>
                        @endif

                        <div class="mt-6">
                            @include('partials.correspondence-note')
                        </div>

                        {{-- Zdjęcia biura tu nie powtarzamy — jest już w tle nagłówka. --}}

                        <p class="mt-6 text-sm text-muted">
                            Wolisz napisać?
                            <button type="button" @click="jump('formularz')" class="font-bold text-brand underline hover:no-underline">
                                Przejdź do formularza kontaktowego
                            </button>.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-6">
                        @include('contact.partials.details', ['withOfficePhoto' => false])
                        @include('contact.partials.registry')
                    </div>
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
