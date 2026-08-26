{{--
    Wariant „Kafelkowy" strony kontaktowej: nagłówek z kaflami szybkiego kontaktu,
    formularz w karcie i przyklejony panel z danymi teleadresowymi. Treść sekcji
    jest ta sama co w wariancie klasycznym — korzystamy z tych samych partiali.
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
        // Kafle szybkiego kontaktu: adres kierujemy na biuro, jeśli jest inne niż
        // rejestrowe — tam realnie się przychodzi i wysyła pocztę.
        $tileAddress = $siteSettings->hasOfficeAddress()
            ? $siteSettings->officeAddressLine()
            : $siteSettings->registeredAddressLine();

        $quickTiles = collect([
            [
                'show'  => filled($siteSettings->contact_email),
                'icon'  => 'fa-solid fa-envelope',
                'label' => 'Napisz e-mail',
                'value' => $siteSettings->contact_email,
                'href'  => 'mailto:'.$siteSettings->contact_email,
            ],
            [
                'show'  => filled($siteSettings->contact_phone),
                'icon'  => 'fa-solid fa-phone',
                'label' => 'Zadzwoń',
                'value' => $siteSettings->contact_phone,
                'href'  => 'tel:'.$siteSettings->contact_phone,
            ],
            [
                'show'  => filled($tileAddress),
                'icon'  => 'fa-solid fa-location-dot',
                'label' => $siteSettings->hasOfficeAddress() ? 'Odwiedź biuro' : 'Odwiedź nas',
                'value' => trim($siteSettings->contact_office_building.' · '.$tileAddress, ' ·'),
                'href'  => 'https://www.google.com/maps?q='.urlencode($tileAddress),
                'external' => true,
            ],
            [
                'show'  => filled($siteSettings->contact_office_hours),
                'icon'  => 'fa-regular fa-clock',
                'label' => 'Godziny pracy',
                'value' => $siteSettings->contact_office_hours,
            ],
            [
                'show'  => filled($siteSettings->contact_edelivery_address),
                'icon'  => 'fa-solid fa-envelope-circle-check',
                'label' => 'e-Doręczenia',
                'value' => $siteSettings->contact_edelivery_address,
            ],
            [
                'show'  => filled($siteSettings->bank_account_number),
                'icon'  => 'fa-solid fa-building-columns',
                'label' => 'Numer konta',
                'value' => $siteSettings->bank_account_number,
            ],
        ])->filter(fn ($tile) => $tile['show'])->values();
    @endphp
    @endphp

    {{-- Nagłówek z kaflami szybkiego kontaktu --}}
    <section class="border-b border-gray-100 bg-brand-light/40">
        <div class="mx-auto max-w-6xl px-4 py-12">
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">Kontakt</h1>

            @if ($siteSettings->contact_intro)
                <div class="prose mt-3 max-w-2xl text-muted">{!! $siteSettings->contact_intro !!}</div>
            @endif

            @if ($quickTiles->isNotEmpty())
                <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($quickTiles as $tile)
                        <li>
                            @if (! empty($tile['href']))
                                <a href="{{ $tile['href'] }}"
                                   @if (! empty($tile['external'])) target="_blank" rel="noopener" @endif
                                   class="group flex h-full items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 transition hover:border-brand hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                                   @if (! empty($tile['external'])) aria-label="{{ $tile['label'] }}: {{ $tile['value'] }} (otwiera mapę w nowej karcie)" @endif>
                                    <span class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-brand-light text-brand transition group-hover:bg-brand group-hover:text-white" aria-hidden="true">
                                        <i class="{{ $tile['icon'] }} text-lg"></i>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-xs font-bold uppercase tracking-wide text-muted">{{ $tile['label'] }}</span>
                                        <span class="block break-words font-bold text-ink group-hover:text-brand">{{ $tile['value'] }}</span>
                                    </span>
                                </a>
                            @else
                                <div class="flex h-full items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5">
                                    <span class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                                        <i class="{{ $tile['icon'] }} text-lg"></i>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-xs font-bold uppercase tracking-wide text-muted">{{ $tile['label'] }}</span>
                                        <span class="block break-words font-bold text-ink">{{ $tile['value'] }}</span>
                                    </span>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-12">

        @include('partials.correspondence-note')

        {{-- Formularz w karcie + przyklejony panel z danymi --}}
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div id="formularz" class="scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="mb-1 text-2xl font-bold text-ink">Napisz do nas</h2>
                <p class="mb-6 text-sm text-muted">Odpowiadamy zwykle w ciągu jednego dnia roboczego.</p>

                @include('contact.partials.form')
            </div>

            <div class="lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-6">
                    @include('contact.partials.details')
                    @include('contact.partials.registry')
                </div>
            </div>
        </div>

        @include('contact.partials.meetings')
        @include('contact.partials.shipping')
        @include('contact.partials.bank-accounts')
    </section>

    @include('contact.partials.copy-script')
@endsection
