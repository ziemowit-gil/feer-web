{{--
    Wariant „Wizytówka" strony kontaktowej: na wierzchu trzy najważniejsze dane
    (biuro, telefon, e-mail), pod spodem formularz i pozostałe sekcje. Gdy wgrano
    zdjęcie biura, ląduje ono w tle sekcji nagłówkowej.

    WCAG: zdjęcie w tle przykrywa przyciemnienie 65% czerni — nawet dla całkiem
    białego zdjęcia biały tekst ma wtedy ok. 7:1 kontrastu (1.4.3 wymaga 4.5:1).
    Zdjęcie jest dekoracyjne (opisana wersja stoi w danych biura niżej), a w trybach
    wysokiego kontrastu chowamy je regułą .contact-hero-photo w app.css.
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
        $officePhoto = $siteSettings->contactHeroPhotoUrl();
        $hasOffice   = $siteSettings->officeDiffersFromRegistered();
        $addressLine = $hasOffice ? $siteSettings->officeAddressLine() : $siteSettings->registeredAddressLine();

        $cardBase = $officePhoto
            ? 'bg-white/10 ring-1 ring-white/40 text-white'
            : 'bg-white ring-1 ring-gray-200 text-ink';
        $cardLabel = $officePhoto ? 'text-white/80' : 'text-muted';
        $cardIcon  = $officePhoto ? 'bg-white/15 text-white' : 'bg-brand-light text-brand';
    @endphp

    <section class="relative {{ $officePhoto ? 'contact-hero-photo' : 'bg-brand-light/40' }}"
        @if ($officePhoto) style="background-image: url('{{ $officePhoto }}');" @endif>

        @if ($officePhoto)
            {{-- Przyciemnienie pod tekstem — bez niego kontrast zależałby od zdjęcia. --}}
            <div class="absolute inset-0 bg-black/65" aria-hidden="true"></div>
        @endif

        <div class="relative mx-auto max-w-6xl px-4 py-14">
            <h1 class="text-3xl font-bold sm:text-4xl {{ $officePhoto ? 'text-white' : 'text-ink' }}">Kontakt</h1>

            @if ($siteSettings->contact_intro)
                <div class="prose mt-3 max-w-2xl {{ $officePhoto ? 'text-white/90 prose-invert' : 'text-muted' }}">
                    {!! $siteSettings->contact_intro !!}
                </div>
            @endif

            <ul class="mt-8 grid gap-4 sm:grid-cols-3">
                {{-- Biuro / adres --}}
                <li>
                    <a href="https://www.google.com/maps?q={{ urlencode($addressLine) }}" target="_blank" rel="noopener"
                       aria-label="{{ $hasOffice ? 'Biuro' : 'Adres' }}: {{ trim($siteSettings->contact_office_building.' '.$addressLine) }} (otwiera mapę w nowej karcie)"
                       class="flex h-full items-start gap-4 rounded-2xl p-5 transition hover:ring-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current {{ $cardBase }}">
                        <span class="flex h-11 w-11 flex-none items-center justify-center rounded-full {{ $cardIcon }}" aria-hidden="true">
                            <i class="fa-solid fa-location-dot"></i>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-xs font-bold uppercase tracking-wide {{ $cardLabel }}">
                                {{ $hasOffice ? 'Biuro / korespondencja' : 'Adres' }}
                            </span>
                            @if (filled($siteSettings->contact_office_building) && $hasOffice)
                                <span class="block font-bold">{{ $siteSettings->contact_office_building }}</span>
                            @endif
                            <span class="block break-words font-medium">{{ $addressLine }}</span>
                        </span>
                    </a>
                </li>

                {{-- Telefon --}}
                @if (filled($siteSettings->contact_phone))
                    <li>
                        <a href="tel:{{ preg_replace('/\s+/', '', $siteSettings->contact_phone) }}"
                           class="flex h-full items-start gap-4 rounded-2xl p-5 transition hover:ring-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current {{ $cardBase }}">
                            <span class="flex h-11 w-11 flex-none items-center justify-center rounded-full {{ $cardIcon }}" aria-hidden="true">
                                <i class="fa-solid fa-phone"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-xs font-bold uppercase tracking-wide {{ $cardLabel }}">Telefon</span>
                                <span class="block break-words text-lg font-bold">{{ $siteSettings->contact_phone }}</span>
                                @if (filled($siteSettings->contact_office_hours))
                                    <span class="block text-sm {{ $cardLabel }}">{{ $siteSettings->contact_office_hours }}</span>
                                @endif
                            </span>
                        </a>
                    </li>
                @endif

                {{-- E-mail --}}
                @if (filled($siteSettings->contact_email))
                    <li>
                        <a href="mailto:{{ $siteSettings->contact_email }}"
                           class="flex h-full items-start gap-4 rounded-2xl p-5 transition hover:ring-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current {{ $cardBase }}">
                            <span class="flex h-11 w-11 flex-none items-center justify-center rounded-full {{ $cardIcon }}" aria-hidden="true">
                                <i class="fa-solid fa-envelope"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-xs font-bold uppercase tracking-wide {{ $cardLabel }}">E-mail</span>
                                <span class="block break-all font-bold">{{ $siteSettings->contact_email }}</span>
                            </span>
                        </a>
                    </li>
                @endif
            </ul>

            @if ($hasOffice && filled($siteSettings->contact_office_note))
                <p class="mt-4 max-w-2xl text-sm {{ $officePhoto ? 'text-white/90' : 'text-muted' }}">
                    <i class="fa-solid fa-circle-info mr-1" aria-hidden="true"></i>
                    {!! nl2br(e($siteSettings->contact_office_note)) !!}
                </p>
            @endif
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-12">

        @include('partials.correspondence-note')

        <div class="mx-auto max-w-3xl rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="mb-1 text-2xl font-bold text-ink">Napisz do nas</h2>
            <p class="mb-6 text-sm text-muted">Odpowiadamy zwykle w ciągu jednego dnia roboczego.</p>

            @include('contact.partials.form')
        </div>

        {{-- Uporządkowany dół strony: spotkania na całą szerokość, niżej siatka
             kart z przesyłkami, rachunkami i danymi organizacji. --}}
        <div class="mt-12 space-y-6">
            @if ($showMeetings)
                @include('contact.partials.meetings', ['sectionStyle' => 'card'])
            @endif

            <div class="grid gap-6 md:grid-cols-2">
                @if ($showShipping)
                    @include('contact.partials.shipping', ['sectionStyle' => 'card'])
                @endif

                @if (! empty($siteSettings->contact_bank_accounts))
                    @include('contact.partials.bank-accounts', ['sectionStyle' => 'card'])
                @endif

                <div class="h-full rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-bold text-ink">Dane organizacji</h2>
                    @include('contact.partials.details', ['withOfficePhoto' => ! $officePhoto])
                    @include('contact.partials.registry')
                </div>
            </div>
        </div>
    </section>

    @include('contact.partials.copy-script')
@endsection
