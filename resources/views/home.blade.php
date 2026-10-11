@extends('layouts.site')

@section('title', $siteSettings->site_name . ' — Strona główna')

@php
    // Sprawdzamy rolę po stronie serwera — Alpine dostaje tylko URL do zapisu,
    // nie żadnych uprawnień. Faktyczna autoryzacja jest w HomepageLayoutController.
    $isAdmin = auth()->check() && auth()->user()->isAdmin();
@endphp

@section('content')

{{--
    Wrapper x-data renderowany wyłącznie dla administratorów.
    Dla zwykłych użytkowników sekcje renderują się bez żadnego Alpine overhead.
--}}
@if ($isAdmin)
<div x-data="homepageEditor(@js($sectionOrder), '{{ route('admin.homepage.section-order') }}')">
@endif

    {{-- Kontener sekcji: w trybie edycji staje się flex-col, by CSS `order` działało --}}
    <div @if ($isAdmin) :class="editMode ? 'flex flex-col' : ''" @endif>

        @foreach ($sectionOrder as $section)
        <div
            class="relative"
            @if ($isAdmin)
                {{-- CSS order kontroluje wizualną kolejność bez zmiany DOM --}}
                :style="editMode ? { order: sectionIndex('{{ $section }}') } : {}"

                {{-- Drag and Drop (HTML5 API, działa na desktopie) --}}
                :draggable="editMode ? 'true' : 'false'"
                @dragstart="startDrag('{{ $section }}')"
                @dragover.prevent="enterDrop('{{ $section }}')"
                @dragleave.self="leaveDrop('{{ $section }}')"
                @drop.prevent="onDrop('{{ $section }}')"
                @dragend="dragging = null; dragOver = null"

                :class="{
                    'ring-2 ring-brand ring-offset-4 rounded-2xl overflow-hidden transition-all':
                        editMode && dragOver === '{{ $section }}' && dragging !== '{{ $section }}',
                    'opacity-50 scale-[0.99]':
                        editMode && dragging === '{{ $section }}',
                    'cursor-grab active:cursor-grabbing': editMode,
                }"
            @endif
        >

            @if ($isAdmin)
                {{-- Baner z nazwą sekcji + ikonką uchwytu, widoczny tylko na desktopie w trybie edycji --}}
                <div
                    x-show="editMode"
                    x-cloak
                    class="absolute inset-x-0 top-0 z-20 hidden justify-center md:flex"
                    aria-hidden="true"
                >
                    <div class="flex select-none items-center gap-2 rounded-b-xl bg-brand px-4 py-1.5 text-xs font-bold text-white shadow-md">
                        <i class="fa-solid fa-grip-dots-vertical"></i>
                        <span x-text="sectionLabel('{{ $section }}')"></span>
                    </div>
                </div>

                {{-- Przyciski góra / dół — dla klawiatury i urządzeń dotykowych --}}
                <div
                    x-show="editMode"
                    x-cloak
                    class="absolute right-3 top-1/2 z-20 flex -translate-y-1/2 flex-col gap-1 md:hidden"
                >
                    <button
                        type="button"
                        @click="moveUp('{{ $section }}')"
                        :disabled="sectionIndex('{{ $section }}') === 0"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-white shadow ring-1 ring-black/10 transition hover:bg-gray-50 disabled:opacity-30"
                        :aria-label="'Przesuń wyżej: ' + sectionLabel('{{ $section }}')"
                    >
                        <i class="fa-solid fa-chevron-up text-xs text-brand" aria-hidden="true"></i>
                    </button>
                    <button
                        type="button"
                        @click="moveDown('{{ $section }}')"
                        :disabled="sectionIndex('{{ $section }}') === sections.length - 1"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-white shadow ring-1 ring-black/10 transition hover:bg-gray-50 disabled:opacity-30"
                        :aria-label="'Przesuń niżej: ' + sectionLabel('{{ $section }}')"
                    >
                        <i class="fa-solid fa-chevron-down text-xs text-brand" aria-hidden="true"></i>
                    </button>
                </div>
            @endif

            @include('partials.home.'.$section)

            {{-- Kończące się nabory i zapytania ofertowe — tuż pod slajderem (albo pod pierwszą sekcją, gdy slajder wyłączony). --}}
            @if ($section === 'hero' || ($loop->first && ! in_array('hero', $sectionOrder, true)))
                @include('partials.home.closing-recruitments')
            @endif

        </div>
        @endforeach

    </div>

    {{-- MAPA + KONTAKT — zawsze na końcu, poza kolejnością drag-and-drop. Płasko: bez cienia i obwódki;
         biały tekst na brand-dark (≥ 4,5:1), bez przezroczystości. --}}
    <section id="kontakt" class="mx-auto max-w-6xl px-4" aria-labelledby="home-contact-heading">
        <div class="grid overflow-hidden rounded-md md:grid-cols-2">
            <div class="h-64 w-full bg-gray-200 md:h-auto">
                <iframe
                    title="Mapa dojazdu do siedziby fundacji"
                    class="h-full w-full"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    src="https://www.google.com/maps?q={{ urlencode($siteSettings->contact_address.', '.$siteSettings->contact_city) }}&output=embed"
                ></iframe>
            </div>

            <div class="relative flex flex-col justify-center gap-6 bg-brand-dark p-8 text-white sm:p-10">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-white">Skontaktuj się z nami</p>
                    <h2 id="home-contact-heading" class="mt-1 text-2xl font-bold">{{ $siteSettings->site_name }}</h2>
                </div>

                <ul class="space-y-4">
                    <li>
                        <a href="https://www.google.com/maps?q={{ urlencode($siteSettings->contact_address.', '.$siteSettings->contact_city) }}" target="_blank" rel="noopener"
                            class="group flex items-start gap-3 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                            <span class="flex h-6 w-6 flex-none items-center justify-center pt-1" aria-hidden="true">
                                <i class="fa-solid fa-location-dot"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-xs font-bold uppercase tracking-wide text-white">Adres</span>
                                <span class="font-medium group-hover:underline">{{ $siteSettings->contact_address }}<br>{{ $siteSettings->contact_city }}</span><span class="sr-only"> (mapa otwiera się w nowej karcie)</span>
                            </span>
                        </a>
                    </li>

                    @if ($siteSettings->contact_phone)
                        <li>
                            <a href="tel:{{ preg_replace('/\s+/', '', $siteSettings->contact_phone) }}" class="group flex items-start gap-3 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                                <span class="flex h-6 w-6 flex-none items-center justify-center pt-1" aria-hidden="true">
                                    <i class="fa-solid fa-phone"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-xs font-bold uppercase tracking-wide text-white">Telefon</span>
                                    <span class="font-medium group-hover:underline">{{ $siteSettings->contact_phone }}</span>
                                </span>
                            </a>
                        </li>
                    @endif

                    <li>
                        <a href="mailto:{{ $siteSettings->contact_email }}" class="group flex items-start gap-3 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                            <span class="flex h-6 w-6 flex-none items-center justify-center pt-1" aria-hidden="true">
                                <i class="fa-solid fa-envelope"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-xs font-bold uppercase tracking-wide text-white">E-mail</span>
                                <span class="block break-all font-medium group-hover:underline">{{ $siteSettings->contact_email }}</span>
                            </span>
                        </a>
                    </li>
                </ul>

                <a href="{{ route('contact.show') }}" class="inline-flex min-h-11 w-fit items-center gap-2 rounded-md bg-white px-5 text-sm font-bold text-ink transition-colors hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-brand-dark">
                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Napisz do nas
                </a>
            </div>
        </div>
    </section>

@if ($isAdmin)
    @include('partials.home-editor-bar')
</div>{{-- /x-data="homepageEditor" --}}
@endif

@endsection
