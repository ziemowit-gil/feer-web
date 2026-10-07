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

                @include('contact.partials.details', ['wideLayout' => false])
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
