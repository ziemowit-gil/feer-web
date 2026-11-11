{{--
    Kontakt w szablonie „bu2”: menu boczne, tytuł (tytuł strony typu „Kontakt” albo „Kontakt”), „Dane teleadresowe”
    z ustawień (adres biura, a bez niego rejestrowy; telefony, e-mail, WWW), potem treść strony — np. tabela numerów
    zrobiona blokiem tabeli w edytorze — i formularz, gdy jest włączony (Komunikacja → Kontakt).
--}}
@extends('templates.bu2.layout')

@php
    $bu2Page = isset($metkaPage) && $metkaPage instanceof \App\Models\Page ? $metkaPage : null;
    $bu2Title = $bu2Page?->title ?: 'Kontakt';
    $bu2Lines = $siteSettings->hasOfficeAddress()
        ? [$siteSettings->contact_office_address, $siteSettings->contact_office_city, $siteSettings->contact_office_building]
        : [$siteSettings->contact_address, $siteSettings->contact_city];
    $bu2Lines = array_values(array_filter(array_map(fn ($l) => trim((string) $l), $bu2Lines), 'strlen'));
    $bu2Phones = collect(preg_split('/\s*[;,\n]\s*/u', (string) $siteSettings->contact_phone) ?: [])->map(fn ($p) => trim($p))->filter()->values();
    $bu2Www = trim((string) $siteSettings->site_url);
    $bu2Content = $bu2Page && filled($bu2Page->content) ? \App\Support\ShortcodeParser::render($bu2Page->content) : '';
@endphp

@section('title', ($bu2Page?->meta_title ?: $bu2Title) . ' — ' . $siteSettings->site_name)
@section('meta_description', $bu2Page?->meta_description ?: 'Skontaktuj się z ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [['label' => $bu2Title, 'url' => null]]])
@endsection

@section('bu2_main')
    <article class="bu2-contact">
        <h1 class="bu2-title">{{ $bu2Title }}</h1>

        @include('contact.partials.closure-notice')

        <div class="bu2-body">
            @if ($bu2Lines !== [] || $bu2Phones->isNotEmpty() || filled($siteSettings->contact_email))
                <h2>Dane teleadresowe</h2>
                <address>
                    @foreach ($bu2Lines as $line){{ $line }}<br>@endforeach
                    @if ($bu2Phones->isNotEmpty())
                        Telefon:
                        @foreach ($bu2Phones as $phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">{{ $phone }}</a>@if (! $loop->last); @endif @endforeach
                        <br>
                    @endif
                    @if (filled($siteSettings->contact_email))
                        E-mail: <a href="mailto:{{ $siteSettings->contact_email }}">{{ $siteSettings->contact_email }}</a><br>
                    @endif
                    @if ($bu2Www !== '')
                        WWW: <a href="{{ $bu2Www }}">{{ $bu2Www }}</a>
                    @endif
                </address>
            @endif

            @if ($bu2Content !== '')
                <div class="prose bu2-body">{!! $bu2Content !!}</div>
            @endif
        </div>

        @if ($contactOptions->formEnabled())
            <section class="bu2-body" aria-labelledby="bu2-form-h" style="margin-top:2rem">
                <h2 id="bu2-form-h">Napisz do nas</h2>
                @include('contact.partials.form')
            </section>
        @endif

        @include('templates.bu2.partials.tools')
    </article>
    @include('contact.partials.copy-script')
@endsection
