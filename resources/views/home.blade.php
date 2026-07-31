@extends('layouts.site')

@section('title', $siteSettings->site_name . ' — Strona główna')

@section('content')

    @foreach ($sectionOrder as $section)
        @include('partials.home.'.$section)
    @endforeach

    {{-- MAPA + KONTAKT --}}
    <section id="kontakt" class="mx-auto max-w-6xl px-4">
        <div class="grid overflow-hidden rounded-2xl shadow-sm ring-1 ring-black/5 md:grid-cols-2">
            <div class="h-64 w-full bg-gray-200 md:h-auto">
                <iframe
                    title="Mapa dojazdu do siedziby fundacji"
                    class="h-full w-full"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    src="https://www.google.com/maps?q={{ urlencode($siteSettings->contact_address.', '.$siteSettings->contact_city) }}&output=embed"
                ></iframe>
            </div>

            <div class="relative flex flex-col justify-center gap-6 bg-brand p-8 text-white sm:p-10">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-white/70">Skontaktuj się z nami</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ $siteSettings->site_name }}</h2>
                </div>

                <ul class="space-y-4">
                    <li>
                        <a href="https://www.google.com/maps?q={{ urlencode($siteSettings->contact_address.', '.$siteSettings->contact_city) }}" target="_blank" rel="noopener"
                            class="group flex items-start gap-3">
                            <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-white/15 transition group-hover:bg-white/25" aria-hidden="true">
                                <i class="fa-solid fa-location-dot"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-xs font-bold uppercase tracking-wide text-white/70">Adres</span>
                                <span class="font-medium group-hover:underline">{{ $siteSettings->contact_address }}<br>{{ $siteSettings->contact_city }}</span>
                            </span>
                        </a>
                    </li>

                    @if ($siteSettings->contact_phone)
                        <li>
                            <a href="tel:{{ preg_replace('/\s+/', '', $siteSettings->contact_phone) }}" class="group flex items-start gap-3">
                                <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-white/15 transition group-hover:bg-white/25" aria-hidden="true">
                                    <i class="fa-solid fa-phone"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-xs font-bold uppercase tracking-wide text-white/70">Telefon</span>
                                    <span class="font-medium group-hover:underline">{{ $siteSettings->contact_phone }}</span>
                                </span>
                            </a>
                        </li>
                    @endif

                    <li>
                        <a href="mailto:{{ $siteSettings->contact_email }}" class="group flex items-start gap-3">
                            <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-white/15 transition group-hover:bg-white/25" aria-hidden="true">
                                <i class="fa-solid fa-envelope"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-xs font-bold uppercase tracking-wide text-white/70">E-mail</span>
                                <span class="block break-all font-medium group-hover:underline">{{ $siteSettings->contact_email }}</span>
                            </span>
                        </a>
                    </li>
                </ul>

                <a href="{{ route('contact.show') }}" class="inline-flex w-fit items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-bold text-brand transition hover:bg-white/90">
                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Napisz do nas
                </a>
            </div>
        </div>
    </section>

@endsection
