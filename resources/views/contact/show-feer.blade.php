{{--
    Wariant „FEER" (dedykowany szablonowi FEER), uproszczony: jedna kolumna treści i, jak na stronie działania,
    menu boczne „Na tej stronie” (odnośniki do sekcji, gdy jest ich co najmniej trzy, oraz opublikowane podstrony strony Kontakt).
    Kolejność: dane kontaktowe (telefon z wyjaśnieniem, e-mail, adres, godziny), formularz, potem sekcje dodatkowe
    (spotkania, przesyłki, rachunki, oddziały, dane rejestrowe). Płasko: jasnoszare tła, bez ramek i kresek.
    Dane kontaktowe to zwykły tekst (bez linków tel:). Dostępność: h1/h2 w kolejności, <nav> z nazwą, aria-current
    na bieżącej sekcji (IntersectionObserver), cele dotyku ≥ 44 px, kontrast ink na #F3F4F6 ≥ 14:1.
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
        $hasRegistry = filled($siteSettings->krs_number) || filled($siteSettings->nip_number) || filled($siteSettings->regon_number);
        $hasBranches = (bool) $siteSettings->contactBranches();
        $dt = 'text-xs font-bold uppercase tracking-widest text-muted';
        $hours = $siteSettings->contact_office_hours ? preg_replace('/\s+([-–])\s+/u', "\u{00A0}$1\u{00A0}", $siteSettings->contact_office_hours) : null;

        // Menu boczne: tylko sekcje, które na stronie naprawdę są.
        $navItems = array_values(array_filter([
            ['id' => 'dane-heading', 'label' => 'Dane kontaktowe'],
            ['id' => 'formularz-heading', 'label' => 'Napisz do nas'],
            $showMeetings ? ['id' => 'spotkania', 'label' => 'Spotkania'] : null,
            $showShipping ? ['id' => 'przesylki', 'label' => 'Przesyłki'] : null,
            $hasBank ? ['id' => 'rachunki', 'label' => 'Numery rachunków'] : null,
            $hasBranches ? ['id' => 'oddzialy', 'label' => $siteSettings->contact_branches_title ?: 'Oddziały'] : null,
            $hasRegistry ? ['id' => 'dane-rejestrowe', 'label' => 'Dane rejestrowe'] : null,
        ]));
        // Opublikowane podstrony strony „Kontakt” (gdy redakcja je dodała) — odnośniki pod sekcjami; z nimi menu ma sens zawsze.
        $subpages = ($metkaPage ?? null)?->publishedChildren()->get() ?? collect();
        $showNav = count($navItems) >= 3 || $subpages->isNotEmpty();
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
        </div>
    </section>

    <div class="contact-feer mx-auto max-w-6xl px-4 py-8 md:py-10">
        <div @class(['grid items-start gap-10', 'lg:grid-cols-[minmax(0,1fr)_20rem]' => $showNav])>

            <div class="min-w-0 space-y-12 {{ $showNav ? 'lg:col-start-1 lg:row-start-1' : '' }}">
                @include('partials.correspondence-note')

                <section id="dane" aria-labelledby="dane-heading" class="scroll-mt-24">
                    <h2 id="dane-heading" class="mb-5 text-2xl font-bold text-ink">{{ $siteSettings->site_name }}</h2>
                    @include('contact.partials.phone-book-note', ['class' => 'mb-6'])

                    <dl class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="{{ $dt }}">E-mail</dt>
                            <dd class="mt-1 break-words text-lg font-bold text-ink">{{ $siteSettings->contact_email }}</dd>
                        </div>
                        <div>
                            <dt class="{{ $dt }}">{{ $hasOffice ? 'Adres rejestrowy' : 'Adres' }}</dt>
                            <dd class="mt-1 text-lg font-bold leading-snug text-ink">{{ $siteSettings->contact_address }}, {{ $siteSettings->contact_city }}</dd>
                        </div>
                        @if ($hasOffice)
                            <div>
                                <dt class="{{ $dt }}">Biuro i korespondencja</dt>
                                <dd class="mt-1 text-lg leading-snug text-ink">
                                    @if (filled($siteSettings->contact_office_building))<span class="block font-bold">{{ $siteSettings->contact_office_building }}</span>@endif
                                    <span class="font-bold">{{ $siteSettings->contact_office_address }}, {{ $siteSettings->contact_office_city }}</span>
                                    @if (filled($siteSettings->contact_office_note))<span class="mt-1 block text-sm text-muted">{!! nl2br(e($siteSettings->contact_office_note)) !!}</span>@endif
                                </dd>
                            </div>
                        @endif
                        @if ($hours)
                            <div>
                                <dt class="{{ $dt }}">Godziny pracy biura</dt>
                                <dd class="mt-1 text-lg font-bold leading-snug text-ink">{{ $hours }}</dd>
                            </div>
                        @endif
                        @if ($siteSettings->contact_edelivery_address)
                            <div>
                                <dt class="{{ $dt }}">Adres do e-Doręczeń</dt>
                                <dd class="mt-1 break-all font-mono text-base font-bold text-ink">{{ $siteSettings->contact_edelivery_address }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if ($hasOffice && ($photo = $siteSettings->officePhotoUrl()))
                        <img src="{{ $photo }}" loading="lazy" alt="{{ $siteSettings->contact_office_photo_alt }}" class="mt-6 w-full max-w-sm rounded-md object-cover">
                    @endif

                    @if ($siteSettings->contactBoxIsVisible())
                        <div class="mt-6 rounded-md bg-brand-dark p-5 text-white">
                            @if ($siteSettings->contact_box_text)<p class="text-base">{{ $siteSettings->contact_box_text }}</p>@endif
                            @if ($siteSettings->contact_box_link_url && $siteSettings->contact_box_link_label)
                                @php $boxExternal = \Illuminate\Support\Str::startsWith($siteSettings->contact_box_link_url, ['http://', 'https://']); @endphp
                                <a href="{{ $siteSettings->contact_box_link_url }}" @if ($boxExternal) target="_blank" rel="noopener" @endif
                                   class="{{ $siteSettings->contact_box_text ? 'mt-3 ' : '' }}inline-flex min-h-11 items-center rounded-md bg-white px-5 text-sm font-bold text-ink hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-brand-dark">{{ $siteSettings->contact_box_link_label }}@if ($boxExternal)<span class="sr-only"> (otwiera się w nowej karcie)</span>@endif</a>
                            @endif
                        </div>
                    @endif
                </section>

                <section id="formularz" aria-labelledby="formularz-heading" class="scroll-mt-24 rounded-lg bg-gray-50 p-6 sm:p-8">
                    <h2 id="formularz-heading" class="mb-1 text-2xl font-bold text-ink">Napisz do nas</h2>
                    <p class="mb-6 text-sm text-muted">Odpowiadamy zwykle w ciągu jednego dnia roboczego.</p>
                    @include('contact.partials.form')
                </section>

                {{-- Sekcje dodatkowe; każdy partial ma własny id (spotkania, przesylki, rachunki, oddzialy). --}}
                @include('contact.partials.meetings', ['sectionStyle' => 'bare'])
                @if ($showShipping)
                    @include('contact.partials.shipping', ['sectionStyle' => 'bare'])
                @endif
                @if ($hasBank)
                    @include('contact.partials.bank-accounts', ['sectionStyle' => 'bare'])
                @endif
                @include('contact.partials.branches', ['sectionStyle' => 'bare'])
                @if ($hasRegistry)
                    <div id="dane-rejestrowe" class="scroll-mt-24">@include('contact.partials.registry', ['wideLayout' => false, 'showAccounts' => empty($siteSettings->contact_bank_accounts)])</div>
                @endif

                @include('contact.partials.locations-map', ['sectionStyle' => 'bare'])
            </div>

            @if ($showNav)
                {{-- Menu boczne jak na stronie działania: szare pole, kreska marki pod tytułem, bieżąca pozycja na białym. Na telefonie nad treścią. --}}
                <aside class="order-first lg:order-none lg:col-start-2 lg:row-start-1 lg:sticky lg:top-6">
                    <nav aria-label="Na tej stronie" class="rounded-md bg-gray-50 p-5" x-data="{ current: null }"
                         x-init="const ids = @js(array_column($navItems, 'id')); const obs = new IntersectionObserver((es) => { es.forEach(e => { if (e.isIntersecting) current = e.target.id }) }, { rootMargin: '-20% 0px -70% 0px' }); ids.forEach(id => { const el = document.getElementById(id); if (el) obs.observe(el) })">
                        <p class="text-base font-bold leading-snug text-ink">Na tej stronie</p>
                        <span class="mb-3 mt-2 block h-1 w-10 bg-brand" aria-hidden="true"></span>
                        <ul role="list" class="space-y-0.5">
                            @foreach ($navItems as $item)
                                <li>
                                    <a href="#{{ $item['id'] }}" @click="current = '{{ $item['id'] }}'" :aria-current="current === '{{ $item['id'] }}' ? 'location' : null"
                                       class="flex min-h-11 w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-[0.9375rem] text-ink transition hover:bg-white hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                                       :class="current === '{{ $item['id'] }}' ? 'bg-white font-bold text-brand-dark' : ''">
                                        <span class="min-w-0">{{ $item['label'] }}</span>
                                        <i class="fa-solid fa-chevron-right flex-none text-xs" aria-hidden="true"></i>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        @if ($subpages->isNotEmpty())
                            <p class="mt-5 text-sm font-bold uppercase tracking-widest text-muted">Podstrony</p>
                            <ul role="list" class="mt-2 space-y-0.5">
                                @foreach ($subpages as $sub)
                                    <li>
                                        <a href="{{ $sub->publicUrl() }}"
                                           class="flex min-h-11 w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-[0.9375rem] text-ink transition hover:bg-white hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                            <span class="min-w-0">{{ $sub->title }}</span>
                                            <i class="fa-solid fa-chevron-right flex-none text-xs" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </nav>
                </aside>
            @endif
        </div>
    </div>

    @include('contact.partials.copy-script')
@endsection
