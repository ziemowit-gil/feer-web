{{--
    Automatyczna podstrona kontaktu w szablonie FEER (/kontakt/{section}): jedna sekcja ustawień (spotkania, przesyłki,
    rachunki, oddziały, dane rejestrowe) z menu bocznym jak na stronie Kontakt. Dane z ContactController::viewData.
--}}
@extends('layouts.site')

@section('title', $sectionLabel . ' — Kontakt — ' . $siteSettings->site_name)
@section('meta_description', $sectionLabel . ': ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Kontakt', 'url' => ($metkaPage ?? null)?->publicUrl() ?? route('contact.show')],
        ['label' => $sectionLabel],
    ]])
@endsection

@section('content')
    @php $navHtml = trim(view('contact.partials.feer-nav', ['current' => $section, 'showMeetings' => $showMeetings, 'showShipping' => $showShipping, 'metkaPage' => $metkaPage ?? null])->render()); @endphp

    <section class="bg-gray-50">
        <div class="mx-auto max-w-6xl px-4 py-8 md:py-10">
            <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-5xl">{{ $sectionLabel }}</h1>
            <span class="mt-4 block h-1 w-14 bg-brand" aria-hidden="true"></span>
        </div>
    </section>

    <div class="contact-feer mx-auto max-w-6xl px-4 py-8 md:py-10">
        <div @class(['grid items-start gap-10', 'lg:grid-cols-[minmax(0,1fr)_20rem]' => $navHtml !== ''])>
            <div class="min-w-0 {{ $navHtml !== '' ? 'lg:col-start-1 lg:row-start-1' : '' }}">
                @switch($section)
                    @case('spotkania') @include('contact.partials.meetings', ['sectionStyle' => 'bare']) @break
                    @case('przesylki') @include('contact.partials.shipping', ['sectionStyle' => 'bare']) @break
                    @case('rachunki') @include('contact.partials.bank-accounts', ['sectionStyle' => 'bare']) @break
                    @case('oddzialy') @include('contact.partials.branches', ['sectionStyle' => 'bare']) @break
                    @case('dane-rejestrowe') @include('contact.partials.registry', ['wideLayout' => false, 'showAccounts' => empty($siteSettings->contact_bank_accounts)]) @break
                @endswitch
            </div>
            @if ($navHtml !== '')
                <aside class="order-first lg:order-none lg:col-start-2 lg:row-start-1 lg:sticky lg:top-6">{!! $navHtml !!}</aside>
            @endif
        </div>
    </div>

    @include('contact.partials.copy-script')
@endsection
