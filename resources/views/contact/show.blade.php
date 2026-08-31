@extends('layouts.site')

@section('title', 'Kontakt — ' . $siteSettings->site_name)
@section('meta_description', 'Skontaktuj się z ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Kontakt'],
    ]])
@endsection

@section('content')
    @php $isFederationTemplate = ($siteSettings->site_template ?? 'default') === 'federation'; @endphp
    <section class="mx-auto max-w-5xl px-4 py-12">
        @if ($isFederationTemplate)
            <p class="mb-3 text-sm font-extrabold uppercase tracking-widest text-brand">Kontakt</p>
            <h1 class="mb-6 text-3xl font-extrabold leading-tight tracking-tight text-ink sm:text-4xl">Napisz albo zadzwoń</h1>
        @else
            <h1 class="mb-6 text-3xl font-bold text-ink">Kontakt</h1>
        @endif

        @if ($siteSettings->contact_intro)
            <div class="prose mb-8 max-w-2xl text-muted">{!! $siteSettings->contact_intro !!}</div>
        @endif

        @include('partials.correspondence-note')

        @php
            $contactSections = collect([
                ['id' => 'formularz', 'label' => 'Napisz do nas'],
                ['id' => 'spotkania', 'label' => $meetingTitle,        'show' => $showMeetings],
                ['id' => 'przesylki', 'label' => 'Wyślij przesyłkę',   'show' => $showShipping],
                ['id' => 'rachunki',  'label' => 'Rachunki bankowe',   'show' => ! empty($siteSettings->contact_bank_accounts)],
            ])->filter(fn ($s) => $s['show'] ?? true)->values();
        @endphp

        @if ($contactSections->count() > 1)
            <nav aria-label="Przejdź do sekcji" class="mb-8 flex flex-wrap gap-2">
                @foreach ($contactSections as $sec)
                    <a href="#{{ $sec['id'] }}"
                       class="rounded-full border border-brand/30 bg-brand-light/50 px-3 py-1 text-sm font-bold text-brand hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                        {{ $sec['label'] }}
                    </a>
                @endforeach
            </nav>
        @endif

        {{-- Formularz kontaktowy + dane teleadresowe --}}
        <div id="formularz" class="scroll-mt-24 grid gap-6 md:grid-cols-[1fr_300px] {{ $isFederationTemplate ? 'items-start' : 'gap-10' }}">
            <div class="{{ $isFederationTemplate ? 'rounded-lg border border-gray-100 bg-white p-6 shadow-sm sm:p-8' : '' }}">
                @include('contact.partials.form')
            </div>

            <div>
                @include('contact.partials.details')
            </div>
        </div>

        @include('contact.partials.meetings')
        @include('contact.partials.shipping')
        @include('contact.partials.bank-accounts')
        @include('contact.partials.locations-map')
    </section>

    @include('contact.partials.copy-script')
@endsection
