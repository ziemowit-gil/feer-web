{{--
    Kontakt w szablonie „kreatywni” (wizytówka): kremowy pas z tytułem (tytuł strony typu „Kontakt” albo „Kontakt”)
    i jednym zdaniem, pod nim dwie kolumny — po lewej telefon, e-mail, obszar działania (miasto z ustawień, adres tylko
    gdy jest wpisany) i profile social media, po prawej formularz (gdy włączony w Komunikacja → Kontakt). Treść strony
    typu „Kontakt” (jeśli jest) pod spodem. Bez notek i danych domyślnych innych szablonów.
--}}
@extends('layouts.site')

@php
    $kcPage = isset($metkaPage) && $metkaPage instanceof \App\Models\Page ? $metkaPage : null;
    $kcTitle = $kcPage?->title ?: 'Kontakt';
    $kcTexts = \App\Support\KreatywniTexts::for($siteSettings);
    $kcPhones = collect(preg_split('/\s*[;,\n]\s*/u', (string) $siteSettings->contact_phone) ?: [])->map(fn ($p) => trim($p))->filter()->values();
    $kcEmail = trim((string) $siteSettings->contact_email);
    $kcAddress = trim((string) $siteSettings->contact_address);
    $kcCity = trim((string) $siteSettings->contact_city);
    $kcSocial = $siteSettings->socialLinks();
    $kcContent = $kcPage && filled($kcPage->content) ? \App\Support\ShortcodeParser::render($kcPage->content) : '';
@endphp

@section('title', ($kcPage?->meta_title ?: $kcTitle) . ' — ' . $siteSettings->site_name)
@section('meta_description', $kcPage?->meta_description ?: 'Skontaktuj się z ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [['label' => $kcTitle, 'url' => null]]])
@endsection

@section('content')
    @include('templates.kreatywni.partials.base-css')
    <div class="kc">
        <section class="kc-page-head" aria-labelledby="kc-contact-h1">
            <div class="kc-wrap">
                <p class="kc-eyebrow kc-hand">Porozmawiajmy</p>
                <h1 id="kc-contact-h1">{{ $kcTitle }}</h1>
                @foreach ($kcTexts->paragraphs('contact_text') as $p)
                    <p class="kc-lead">{{ $p }}</p>
                @endforeach
            </div>
        </section>

        <div class="kc-wrap">
            @include('contact.partials.closure-notice')
        </div>

        <section class="kc-sec" aria-label="Dane kontaktowe i formularz">
            <div class="kc-wrap kc-split">
                <div>
                    <ul class="kc-list kc-contact-cards">
                        @foreach ($kcPhones as $phone)
                            <li>
                                <span class="kc-list-icon" aria-hidden="true"><i class="fa-solid fa-phone"></i></span>
                                <div><h2>Telefon</h2><p><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">{{ $phone }}</a></p></div>
                            </li>
                        @endforeach
                        @if ($kcEmail !== '')
                            <li>
                                <span class="kc-list-icon" aria-hidden="true"><i class="fa-solid fa-envelope"></i></span>
                                <div><h2>E-mail</h2><p><a href="mailto:{{ $kcEmail }}">{{ $kcEmail }}</a></p></div>
                            </li>
                        @endif
                        @if ($kcAddress !== '' || $kcCity !== '')
                            <li>
                                <span class="kc-list-icon" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></span>
                                <div><h2>{{ $kcAddress !== '' ? 'Adres' : 'Gdzie działamy' }}</h2><p>@if ($kcAddress !== ''){{ $kcAddress }}<br>@endif{{ $kcCity }}</p></div>
                            </li>
                        @endif
                    </ul>

                    @if ($kcSocial !== [])
                        <h2 class="kc-small-h">Znajdziesz nas też tutaj</h2>
                        <ul class="kc-social kc-social-compact">
                            @foreach ($kcSocial as [$sUrl, $sIcon, $sLabel])
                                <li>
                                    <a href="{{ $sUrl }}" target="_blank" rel="noopener">
                                        <span class="kc-social-icon" aria-hidden="true"><i class="{{ $sIcon }}"></i></span>
                                        <span class="kc-social-name">{{ $sLabel }}<span class="kc-sr"> (otwiera się w nowej karcie)</span></span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                @if ($contactOptions->formEnabled())
                    <div class="kc-form-card" aria-labelledby="kc-form-h" role="region">
                        <h2 id="kc-form-h">Napisz do nas</h2>
                        @include('contact.partials.form')
                    </div>
                @endif
            </div>

            @if ($kcContent !== '')
                <div class="kc-wrap" style="margin-top:3rem">
                    <div class="prose max-w-none">{!! $kcContent !!}</div>
                </div>
            @endif
        </section>
    </div>
    @include('contact.partials.copy-script')
@endsection
