{{--
    Wariant „FEER" (dedykowany szablonowi FEER): jasny nagłówek, a pod nim dwie kolumny — po lewej dane
    kontaktowe i pozostałe sekcje (spotkania, przesyłki, rachunki), po prawej formularz w przyklejonej karcie.
    Bez zakładek, linii dzielących i ramek (karta formularza to jasnoszare tło); sekcje oddzielają odstępy. Na telefonie formularz trafia pod dane.
    Dostępność: nagłówki h1/h2 w kolejności, formularz ma landmark przez aria-labelledby, cele dotyku ≥ 44 px.
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
    @php $hasBank = ! empty($siteSettings->contact_bank_accounts) || filled($siteSettings->contact_bank_accounts_note); @endphp

    <section class="bg-gray-50">
        <div class="mx-auto max-w-6xl px-4 py-12 md:py-16">
            <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-5xl">Kontakt</h1>
            <div class="mt-4 max-w-2xl text-lg leading-relaxed text-ink">
                @if ($siteSettings->contact_intro)
                    <div class="prose max-w-none text-ink">{!! $siteSettings->contact_intro !!}</div>
                @else
                    <p>Napisz, zadzwoń albo odwiedź nas — odpowiadamy zwykle w ciągu jednego dnia roboczego.</p>
                @endif
            </div>
        </div>
    </section>

    <div class="contact-feer mx-auto max-w-6xl px-4 py-12">
        <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_26rem] lg:items-start">

            <div class="min-w-0 space-y-12">
                @include('partials.correspondence-note')

                {{-- Dane kontaktowe: wyraźne, płaskie kafelki z dużym tekstem (kontrast ink na #F3F4F6 ≥ 14:1, linki brand-dark) --}}
                @php
                    $hasOffice = $siteSettings->officeDiffersFromRegistered();
                    $tile = 'rounded-md bg-gray-50 p-5';
                    $dt = 'text-xs font-bold uppercase tracking-widest text-muted';
                    $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand';
                    $actionLink = 'inline-flex min-h-11 items-center gap-2 text-xl font-bold text-brand-dark hover:underline hover:underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand';
                @endphp
                <section aria-labelledby="dane-heading">
                    <h2 id="dane-heading" class="mb-5 text-2xl font-bold text-ink">{{ $siteSettings->site_name }}</h2>
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div class="{{ $tile }}">
                            <dt class="{{ $dt }}">E-mail</dt>
                            <dd class="mt-2 text-lg"><a href="mailto:{{ $siteSettings->contact_email }}" class="{{ $actionLink }}"><i class="fa-solid fa-envelope shrink-0 text-base" aria-hidden="true"></i><span class="min-w-0 break-words">{{ $siteSettings->contact_email }}</span></a></dd>
                        </div>
                        @if ($siteSettings->contact_phone)
                            <div class="{{ $tile }}">
                                <dt class="{{ $dt }}">Telefon</dt>
                                <dd class="mt-2 text-lg"><a href="tel:{{ preg_replace('/\s+/', '', $siteSettings->contact_phone) }}" class="{{ $actionLink }}"><i class="fa-solid fa-phone shrink-0 text-base" aria-hidden="true"></i><span>{{ $siteSettings->contact_phone }}</span></a></dd>
                            </div>
                        @endif
                        <div class="{{ $tile }}">
                            <dt class="{{ $dt }}">{{ $hasOffice ? 'Adres rejestrowy' : 'Adres' }}</dt>
                            <dd class="mt-2 text-lg leading-snug text-ink">
                                <a href="https://www.google.com/maps?q={{ urlencode($siteSettings->registeredAddressLine()) }}" target="_blank" rel="noopener" class="{{ $link }}">{{ $siteSettings->contact_address }}<br>{{ $siteSettings->contact_city }}<span class="sr-only"> (otwiera mapę w nowej karcie)</span></a>
                            </dd>
                        </div>
                        @if ($hasOffice)
                            <div class="{{ $tile }}">
                                <dt class="{{ $dt }}">Biuro / korespondencja</dt>
                                <dd class="mt-2 text-lg leading-snug text-ink">
                                    @if (filled($siteSettings->contact_office_building))<span class="block font-bold">{{ $siteSettings->contact_office_building }}</span>@endif
                                    <a href="https://www.google.com/maps?q={{ urlencode($siteSettings->officeAddressLine()) }}" target="_blank" rel="noopener" class="{{ $link }}">{{ $siteSettings->contact_office_address }}<br>{{ $siteSettings->contact_office_city }}<span class="sr-only"> (otwiera mapę w nowej karcie)</span></a>
                                    @if (filled($siteSettings->contact_office_note))<span class="mt-2 block text-sm text-muted">{!! nl2br(e($siteSettings->contact_office_note)) !!}</span>@endif
                                </dd>
                            </div>
                        @endif
                        @if ($siteSettings->contact_office_hours)
                            <div class="{{ $tile }}">
                                <dt class="{{ $dt }}">Godziny pracy</dt>
                                <dd class="mt-2 text-lg font-bold leading-snug text-ink">{{ $siteSettings->contact_office_hours }}</dd>
                            </div>
                        @endif
                        @if ($siteSettings->contact_edelivery_address)
                            <div class="{{ $tile }}">
                                <dt class="{{ $dt }}">Adres do e-Doręczeń</dt>
                                <dd class="mt-2 break-all font-mono text-base font-bold text-ink">{{ $siteSettings->contact_edelivery_address }}</dd>
                            </div>
                        @endif
                    </dl>

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
                @include('contact.partials.registry', ['wideLayout' => false, 'showAccounts' => empty($siteSettings->contact_bank_accounts)])

                @if ($showMeetings)
                    <div id="spotkania" class="scroll-mt-24">@include('contact.partials.meetings', ['sectionStyle' => 'bare'])</div>
                @endif
                @if ($showShipping)
                    <div id="przesylki" class="scroll-mt-24">@include('contact.partials.shipping', ['sectionStyle' => 'bare'])</div>
                @endif
                @if ($hasBank)
                    <div id="rachunki" class="scroll-mt-24">@include('contact.partials.bank-accounts', ['sectionStyle' => 'bare'])</div>
                @endif
            </div>

            <section id="formularz" aria-labelledby="formularz-heading"
                     class="scroll-mt-24 rounded-lg bg-gray-50 p-6 sm:p-8 lg:sticky lg:top-6">
                <h2 id="formularz-heading" class="mb-1 text-2xl font-bold text-ink">Napisz do nas</h2>
                <p class="mb-6 text-sm text-muted">Odpowiadamy zwykle w ciągu jednego dnia roboczego.</p>
                @include('contact.partials.form')
            </section>
        </div>

        @include('contact.partials.locations-map', ['sectionStyle' => 'bare'])
    </div>

    @include('contact.partials.copy-script')
@endsection
