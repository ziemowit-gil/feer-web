{{--
    Wariant „FEER" (dedykowany szablonowi FEER). Kolejność wg tego, czego szukają odwiedzający:
    1) nagłówek ze skrótami (formularz, rachunki, spotkania, przesyłki),
    2) trzy kafle „jak się skontaktować”: telefon (numer, godziny, książka), e-mail, adres i godziny pracy,
    3) formularz jako główna akcja (szeroka kolumna) i obok dane dodatkowe (rejestrowe, oddziały, spotkania, przesyłki),
    4) rachunki bankowe na całą szerokość, na końcu mapa.
    Płasko: jasnoszare tła, bez ramek i kresek (sekcje oddzielają odstępy). Dane kontaktowe to zwykły tekst (bez linków tel:).
    Dostępność: h1/h2 w kolejności, landmarki przez aria-labelledby, cele dotyku ≥ 44 px, kontrast ink na #F3F4F6 ≥ 14:1.
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
        $hasBank = ! empty($siteSettings->contact_bank_accounts) || filled($siteSettings->contact_bank_accounts_note);
        $hasOffice = $siteSettings->officeDiffersFromRegistered();
        $tile = 'rounded-lg bg-gray-50 p-5 sm:p-6';
        $dt = 'text-xs font-bold uppercase tracking-widest text-muted';
        $ico = 'flex size-11 shrink-0 items-center justify-center rounded-full bg-gray-100 text-lg text-brand-dark';
        $jump = 'inline-flex min-h-11 items-center gap-2 rounded-md bg-gray-100 px-4 text-sm font-bold text-ink hover:bg-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand';
        $hours = $siteSettings->contact_office_hours ? preg_replace('/\s+([-–])\s+/u', "\u{00A0}$1\u{00A0}", $siteSettings->contact_office_hours) : null;
    @endphp

    <section class="bg-gray-50">
        <div class="mx-auto max-w-6xl px-4 py-8 md:py-10">
            <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-5xl">Kontakt</h1>
            <div class="mt-4 max-w-2xl text-lg leading-relaxed text-ink">
                @if ($siteSettings->contact_intro)
                    <div class="prose max-w-none text-ink">{!! $siteSettings->contact_intro !!}</div>
                @else
                    <p>Napisz, zadzwoń albo odwiedź nas.</p>
                @endif
            </div>
            <nav class="mt-6 flex flex-wrap gap-3" aria-label="Skróty na stronie kontaktu">
                <a href="#formularz" class="inline-flex min-h-11 items-center gap-2 rounded-md bg-ink px-5 text-sm font-bold text-white hover:bg-black focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i>Napisz wiadomość</a>
                @if ($showMeetings)<a href="#spotkania" class="{{ $jump }}"><i class="fa-regular fa-calendar" aria-hidden="true"></i>Umów spotkanie</a>@endif
                @if ($showShipping)<a href="#przesylki" class="{{ $jump }}"><i class="fa-solid fa-box" aria-hidden="true"></i>Wyślij przesyłkę</a>@endif
                @if ($hasBank)<a href="#rachunki" class="{{ $jump }}"><i class="fa-solid fa-wallet" aria-hidden="true"></i>Numery rachunków</a>@endif
            </nav>
        </div>
    </section>

    <div class="contact-feer mx-auto max-w-6xl px-4 py-8 md:py-10">
        <div class="space-y-10 md:space-y-12">
            @include('partials.correspondence-note')

            <section aria-labelledby="dane-heading">
                <h2 id="dane-heading" class="mb-5 text-2xl font-bold text-ink">{{ $siteSettings->site_name }}</h2>
                <div class="grid gap-4 lg:grid-cols-3">
                    {{-- 1. Telefon: numer kontaktowy, godziny, wyjaśnienie i książka telefoniczna --}}
                    @include('contact.partials.phone-book-note', ['class' => 'h-full'])

                    {{-- 2. E-mail i e-Doręczenia --}}
                    <div class="{{ $tile }}">
                        <div class="flex items-start gap-4">
                            <span class="{{ $ico }}" aria-hidden="true"><i class="fa-solid fa-envelope"></i></span>
                            <dl class="min-w-0">
                                <dt class="{{ $dt }}">E-mail</dt>
                                <dd class="mt-1 break-words text-xl font-bold text-ink">{{ $siteSettings->contact_email }}</dd>
                                @if ($siteSettings->contact_edelivery_address)
                                    <dt class="{{ $dt }} mt-5">Adres do e-Doręczeń</dt>
                                    <dd class="mt-1 break-all font-mono text-base font-bold text-ink">{{ $siteSettings->contact_edelivery_address }}</dd>
                                @endif
                            </dl>
                        </div>
                    </div>

                    {{-- 3. Adres i godziny pracy --}}
                    <div class="{{ $tile }}">
                        <div class="flex items-start gap-4">
                            <span class="{{ $ico }}" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></span>
                            <dl class="min-w-0">
                                <dt class="{{ $dt }}">{{ $hasOffice ? 'Adres rejestrowy' : 'Adres' }}</dt>
                                <dd class="mt-1 text-lg font-bold leading-snug text-ink">{{ $siteSettings->contact_address }}, {{ $siteSettings->contact_city }}</dd>
                                @if ($hasOffice)
                                    <dt class="{{ $dt }} mt-5">Biuro i korespondencja</dt>
                                    <dd class="mt-1 text-lg leading-snug text-ink">
                                        @if (filled($siteSettings->contact_office_building))<span class="block font-bold">{{ $siteSettings->contact_office_building }}</span>@endif
                                        <span class="font-bold">{{ $siteSettings->contact_office_address }}, {{ $siteSettings->contact_office_city }}</span>
                                        @if (filled($siteSettings->contact_office_note))<span class="mt-1 block text-sm text-muted">{!! nl2br(e($siteSettings->contact_office_note)) !!}</span>@endif
                                    </dd>
                                @endif
                                @if ($hours)
                                    <dt class="{{ $dt }} mt-5">Godziny pracy</dt>
                                    <dd class="mt-1 text-lg font-bold leading-snug text-ink">{{ $hours }}</dd>
                                @endif
                            </dl>
                        </div>
                    </div>
                </div>

                @if ($hasOffice && ($photo = $siteSettings->officePhotoUrl()))
                    <img src="{{ $photo }}" loading="lazy" alt="{{ $siteSettings->contact_office_photo_alt }}" class="mt-4 w-full max-w-sm rounded-md object-cover">
                @endif

                @if ($siteSettings->contactBoxIsVisible())
                    <div class="mt-4 rounded-md bg-brand-dark p-5 text-white">
                        @if ($siteSettings->contact_box_text)<p class="text-base">{{ $siteSettings->contact_box_text }}</p>@endif
                        @if ($siteSettings->contact_box_link_url && $siteSettings->contact_box_link_label)
                            @php $boxExternal = \Illuminate\Support\Str::startsWith($siteSettings->contact_box_link_url, ['http://', 'https://']); @endphp
                            <a href="{{ $siteSettings->contact_box_link_url }}" @if ($boxExternal) target="_blank" rel="noopener" @endif
                               class="{{ $siteSettings->contact_box_text ? 'mt-3 ' : '' }}inline-flex min-h-11 items-center rounded-md bg-white px-5 text-sm font-bold text-ink hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-brand-dark">{{ $siteSettings->contact_box_link_label }}@if ($boxExternal)<span class="sr-only"> (otwiera się w nowej karcie)</span>@endif</a>
                        @endif
                    </div>
                @endif
            </section>

            {{-- Formularz (główna akcja) i dane dodatkowe obok --}}
            <div class="grid gap-10 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)] lg:items-start">
                <section id="formularz" aria-labelledby="formularz-heading" class="scroll-mt-24 rounded-lg bg-gray-50 p-6 sm:p-8">
                    <h2 id="formularz-heading" class="mb-1 text-2xl font-bold text-ink">Napisz do nas</h2>
                    <p class="mb-6 text-sm text-muted">Odpowiadamy zwykle w ciągu jednego dnia roboczego.</p>
                    @include('contact.partials.form')
                </section>

                <div class="min-w-0 space-y-10">
                    @include('contact.partials.registry', ['wideLayout' => false, 'showAccounts' => empty($siteSettings->contact_bank_accounts)])
                    @include('contact.partials.branches', ['sectionStyle' => 'bare'])
                    @if ($showMeetings)
                        <div id="spotkania" class="scroll-mt-24">@include('contact.partials.meetings', ['sectionStyle' => 'bare'])</div>
                    @endif
                    @if ($showShipping)
                        <div id="przesylki" class="scroll-mt-24">@include('contact.partials.shipping', ['sectionStyle' => 'bare'])</div>
                    @endif
                </div>
            </div>

            @if ($hasBank)
                <div id="rachunki" class="scroll-mt-24">@include('contact.partials.bank-accounts', ['sectionStyle' => 'bare'])</div>
            @endif
        </div>

        @include('contact.partials.locations-map', ['sectionStyle' => 'bare'])
    </div>

    @include('contact.partials.copy-script')
@endsection
