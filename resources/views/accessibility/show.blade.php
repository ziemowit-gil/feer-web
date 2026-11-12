@extends('layouts.site')

@section('title', 'Deklaracja dostępności — ' . $settings->site_name)
@section('meta_description', 'Deklaracja dostępności cyfrowej ' . $settings->accessibilityEntityName() . ' oraz formularz zgłaszania problemów z dostępnością.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Deklaracja dostępności', 'url' => null],
    ]])
@endsection

@php
    $fmt = fn ($date) => $date ? $date->locale('pl')->isoFormat('D MMMM YYYY') : null;
    $email = $settings->accessibilityContactEmail();
    $phone = $settings->accessibilityContactPhone();
@endphp

@section('content')
{{--
    Deklaracja dostępności: na górze tytuł, jedno zdanie o podmiocie i najważniejsze fakty (status, daty) jako płaskie pola,
    pod nimi główna akcja „Zgłoś problem z dostępnością”. Niżej dwie kolumny: spis treści (przyklejony na szerokim ekranie,
    bieżąca sekcja zaznaczona — aria-current) i sekcje deklaracji, na końcu formularz zgłoszenia barier.
    Płasko: grupowanie tłem zamiast ramek, kolory marki, kontrast tekstu ≥ 4,5:1, linki w treści podkreślone.
    Style wbudowane (klasy ad-*), niezależne od zbudowanych klas.
--}}
@php
    $disabledCount = \App\Models\Page::forCurrentSite()->where('is_disabled', true)->count();
    $adStatus = $settings->accessibility_status ?: 'partially';
    $adStatusIcon = ['compliant' => 'fa-circle-check', 'partially' => 'fa-circle-half-stroke', 'none' => 'fa-circle-xmark'][$adStatus] ?? 'fa-circle-half-stroke';
    $adFacts = array_filter([
        'Data publikacji strony' => $fmt($settings->accessibility_page_published_at),
        'Ostatnia istotna aktualizacja' => $fmt($settings->accessibility_page_updated_at),
        'Deklarację sporządzono' => $fmt($settings->accessibility_declaration_date),
    ]);
    $adToc = array_filter([
        'zgodnosc' => 'Status zgodności',
        'przygotowanie' => 'Przygotowanie deklaracji',
        'skroty' => 'Skróty klawiaturowe',
        'kontakt' => 'Informacje zwrotne i kontakt',
        'odwolanie' => 'Postępowanie odwoławcze',
        'architektura' => filled($settings->accessibility_architectural) ? 'Dostępność architektoniczna' : null,
        'wylaczenia' => 'Wyłączenia treści',
        'zglos-bariere' => 'Zgłoś problem z dostępnością',
    ]);
    $adExt = '<span class="sr-only"> (otwiera się w nowej karcie)</span>';
@endphp
<style>
    .ad { color: #1d1d1a; }
    .ad-head { max-width: 72rem; margin: 0 auto; padding: 2.5rem 1rem 1rem; }
    .ad-h1 { margin: 0; font-size: clamp(2rem, 4vw, 2.75rem); line-height: 1.1; font-weight: 800; color: #1d1d1a; }
    .ad-bar { display: block; width: 3.5rem; height: 4px; margin: 1rem 0 1.25rem; background: var(--color-brand-dark); }
    .ad-lead { max-width: 46rem; margin: 0; font-size: 1.125rem; line-height: 1.65; }
    .ad-facts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; margin: 1.75rem 0 0; }
    .ad-facts .ad-fact--status { grid-column: 1 / -1; }
    @media (min-width: 640px) {
        .ad-facts { grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr)); }
        .ad-facts .ad-fact--status { grid-column: auto; }
    }
    .ad-fact { min-width: 0; margin: 0; padding: 1rem 1.125rem; border-radius: .5rem; background: #f3f4f6; overflow-wrap: anywhere; }
    .ad-fact dt { font-size: .875rem; font-weight: 700; color: #4b5563; }
    .ad-fact dd { margin: .3rem 0 0; font-size: 1.125rem; font-weight: 800; line-height: 1.3; }
    .ad-fact--status { background: var(--color-brand-light); }
    .ad-fact--status dd { display: flex; align-items: center; gap: .5rem; }
    .ad-fact--status i { color: var(--color-brand-dark); font-size: 1.25rem; }
    .ad-actions { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1.5rem; margin-top: 1.5rem; }
    .ad-btn { display: inline-flex; min-height: 3rem; align-items: center; gap: .6rem; padding: 0 1.5rem; border: 0; border-radius: .375rem; background: var(--color-brand-dark); color: #fff; font-weight: 800; text-decoration: none; cursor: pointer; }
    .ad-btn:hover { background: #1d1d1a; color: #fff; }
    .ad-btn:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 3px; }
    .ad a.ad-link, .ad-p a, .ad-note a { color: #1d1d1a; font-weight: 700; text-decoration: underline; text-underline-offset: 4px; }
    .ad a.ad-link:hover, .ad-p a:hover, .ad-note a:hover { color: var(--color-brand-dark); }
    .ad a.ad-link:focus-visible, .ad-p a:focus-visible, .ad-note a:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 3px; border-radius: .125rem; }

    .ad-layout { max-width: 72rem; margin: 0 auto; padding: 1.5rem 1rem 4rem; display: grid; gap: 2rem; }
    @media (min-width: 1024px) {
        .ad-layout { grid-template-columns: 15rem minmax(0, 1fr); gap: 3.5rem; }
        .ad-toc { position: sticky; top: 7rem; align-self: start; }
    }
    .ad-toc-title { margin: 0 0 .5rem; padding: 0 .75rem; font-size: .8125rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #4b5563; }
    .ad-toc ol { display: grid; gap: .125rem; margin: 0; padding: 0; list-style: none; }
    .ad-toc a { display: block; padding: .5rem .75rem; border-radius: .375rem; color: #1d1d1a; line-height: 1.35; text-decoration: none; }
    .ad-toc a:hover { background: #f3f4f6; text-decoration: underline; text-underline-offset: 3px; }
    .ad-toc a[aria-current="location"] { background: var(--color-brand-light); font-weight: 700; box-shadow: inset 3px 0 0 var(--color-brand-dark); }
    .ad-toc a:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 2px; }
    @media (max-width: 1023.98px) { .ad-toc { padding: 1rem .25rem; border-radius: .5rem; background: #f9fafb; } }

    .ad-body { min-width: 0; max-width: 48rem; }
    .ad-sec { scroll-margin-top: 6.5rem; }
    .ad-sec + .ad-sec { margin-top: 3rem; }
    .ad-h2 { margin: 0 0 .875rem; font-size: 1.5rem; line-height: 1.25; font-weight: 800; color: #1d1d1a; }
    .ad-p { margin: 0 0 .875rem; line-height: 1.7; }
    .ad-note { margin: 1rem 0 0; padding: 1rem 1.25rem; border-radius: .5rem; background: #f3f4f6; line-height: 1.65; }
    .ad-note--brand { background: var(--color-brand-light); }
    .ad-dl { display: grid; gap: .5rem 1.5rem; margin: 0; }
    @media (min-width: 640px) { .ad-dl { grid-template-columns: max-content 1fr; } }
    .ad-dl dt { font-weight: 700; }
    .ad-dl dd { margin: 0; }
    .ad-keys { display: grid; gap: .5rem; margin: 1rem 0 0; padding: 0; list-style: none; }
    .ad-keys li { display: flex; flex-wrap: wrap; align-items: baseline; gap: .375rem 1rem; padding: .625rem .875rem; border-radius: .375rem; background: #f9fafb; }
    .ad-keys .ad-k { flex: 0 0 12rem; }
    .ad-keys kbd { display: inline-block; min-width: 2rem; padding: .125rem .5rem; border-radius: .25rem; background: #e5e7eb; color: #1d1d1a; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .875rem; font-weight: 700; text-align: center; }
    .ad-cards { display: grid; gap: .75rem; margin: 1rem 0 1.5rem; padding: 0; list-style: none; }
    @media (min-width: 640px) { .ad-cards { grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); } }
    .ad-cards li { padding: 1rem 1.125rem; border-radius: .5rem; background: var(--color-brand-light); }
    .ad-card-l { display: flex; align-items: center; gap: .5rem; font-size: .875rem; font-weight: 700; color: #1d1d1a; }
    .ad-card-l i { color: var(--color-brand-dark); }
    .ad-card-v { display: block; margin-top: .3rem; font-size: 1.0625rem; font-weight: 800; overflow-wrap: anywhere; }
    .ad-card-v a { color: #1d1d1a; text-decoration: underline; text-underline-offset: 4px; }
    .ad-card-v a:hover { color: var(--color-brand-dark); }
    .ad-card-v a:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 3px; border-radius: .125rem; }
    .ad-steps { display: grid; gap: .75rem; margin: 1rem 0 0; padding: 0; list-style: none; }
    @media (min-width: 640px) { .ad-steps { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .ad-steps li { padding: 1rem 1.125rem; border-radius: .5rem; background: #f3f4f6; line-height: 1.55; }
    .ad-steps strong { display: block; margin-bottom: .25rem; font-size: 1.125rem; font-weight: 800; color: #1d1d1a; }

    /* Formularz: jedno jasne pole, kontrolki na białym, zaznaczenie tłem marki i pogrubieniem (stan widać też po polu wyboru). */
    .ad-form { margin-top: 3.5rem; padding: 1.5rem; border-radius: .75rem; background: #f3f4f6; }
    @media (min-width: 640px) { .ad-form { padding: 2rem 2.25rem; } }
    .ad-step-n { flex: none; color: var(--color-brand-dark); font-size: 1.5rem; font-weight: 800; line-height: 1; }
    .ad-choice { display: flex; gap: .75rem; padding: 1rem; border-radius: .5rem; background: #fff; cursor: pointer; transition: background-color .15s; }
    .ad-chip { display: inline-flex; min-height: 2.75rem; align-items: center; gap: .5rem; padding: 0 1rem; border-radius: .375rem; background: #fff; color: #1d1d1a; font-size: .875rem; cursor: pointer; transition: background-color .15s; }
    .ad-choice:hover, .ad-chip:hover, .ad-choice:has(:checked), .ad-chip:has(:checked) { background: var(--color-brand-light); }
    .ad-chip:has(:checked) { font-weight: 700; }
    .ad-choice:has(:focus-visible), .ad-chip:has(:focus-visible) { outline: 3px solid #1d1d1a; outline-offset: 2px; }
    .ad-consent { display: flex; align-items: flex-start; gap: .75rem; padding: 1rem; border-radius: .5rem; background: #fff; cursor: pointer; }
    @media (prefers-reduced-motion: reduce) { .ad-choice, .ad-chip { transition: none; } }
    @media print { .ad-toc, .ad-actions, .ad-form { display: none !important; } .ad-layout { display: block; } }
</style>

<div class="ad">
    <header class="ad-head">
        <h1 class="ad-h1">Deklaracja dostępności</h1>
        <span class="ad-bar" aria-hidden="true"></span>
        <p class="ad-lead">{{ $settings->accessibilityEntityName() }} zobowiązuje się zapewnić dostępność swojej strony internetowej zgodnie z ustawą z dnia 4 kwietnia 2019 r. o dostępności cyfrowej stron internetowych i aplikacji mobilnych podmiotów publicznych.</p>

        <dl class="ad-facts">
            <div class="ad-fact ad-fact--status">
                <dt>Status zgodności z ustawą</dt>
                <dd><i class="fa-solid {{ $adStatusIcon }}" aria-hidden="true"></i>{{ $settings->accessibilityStatusLabel() }}</dd>
            </div>
            @foreach ($adFacts as $adLabel => $adValue)
                <div class="ad-fact">
                    <dt>{{ $adLabel }}</dt>
                    <dd>{{ $adValue }}</dd>
                </div>
            @endforeach
        </dl>

        <div class="ad-actions">
            <a href="#zglos-bariere" class="ad-btn"><i class="fa-solid fa-universal-access" aria-hidden="true"></i>Zgłoś problem z dostępnością</a>
            <a href="#kontakt" class="ad-link">Kontakt w sprawie dostępności</a>
        </div>
    </header>

    <div class="ad-layout">
        <nav class="ad-toc" aria-labelledby="ad-toc-h" data-ad-toc>
            <p class="ad-toc-title" id="ad-toc-h">Na tej stronie</p>
            <ol>
                @foreach ($adToc as $adId => $adLabel)
                    <li><a href="#{{ $adId }}">{{ $adLabel }}</a></li>
                @endforeach
            </ol>
        </nav>

        <div class="ad-body">
            <section class="ad-sec" id="zgodnosc" aria-labelledby="zgodnosc-h">
                <h2 class="ad-h2" id="zgodnosc-h">Status pod względem zgodności z ustawą</h2>
                <p class="ad-p">
                    Strona internetowa jest <strong>{{ mb_strtolower($settings->accessibilityStatusLabel()) }}</strong>
                    z ustawą o dostępności cyfrowej stron internetowych i aplikacji mobilnych podmiotów publicznych.
                </p>
                @if (filled($settings->accessibility_status_note))
                    <div class="ad-note">{!! nl2br(e($settings->accessibility_status_note)) !!}</div>
                @endif
            </section>

            <section class="ad-sec" id="przygotowanie" aria-labelledby="przygotowanie-h">
                <h2 class="ad-h2" id="przygotowanie-h">Przygotowanie deklaracji dostępności</h2>
                <dl class="ad-dl">
                    @if ($fmt($settings->accessibility_declaration_date))
                        <dt>Data sporządzenia</dt>
                        <dd>{{ $fmt($settings->accessibility_declaration_date) }}</dd>
                    @endif
                    <dt>Sposób sporządzenia</dt>
                    <dd>Deklarację sporządzono na podstawie {{ \App\Models\SiteSetting::ACCESSIBILITY_REVIEW_METHODS[$settings->accessibility_review_method] ?? \App\Models\SiteSetting::ACCESSIBILITY_REVIEW_METHODS['self'] }}.</dd>
                </dl>
            </section>

            <section class="ad-sec" id="skroty" aria-labelledby="skroty-h">
                <h2 class="ad-h2" id="skroty-h">Skróty klawiaturowe</h2>
                <p class="ad-p">Na stronie internetowej można korzystać ze standardowych skrótów klawiaturowych przeglądarki. Na początku każdej strony jest odnośnik „Przejdź do treści”, który pojawia się po pierwszym naciśnięciu klawisza Tab.</p>
                <ul class="ad-keys">
                    <li><span class="ad-k"><kbd>Tab</kbd></span><span>przejście do następnego odnośnika, przycisku lub pola</span></li>
                    <li><span class="ad-k"><kbd>Shift</kbd> + <kbd>Tab</kbd></span><span>powrót do poprzedniego elementu</span></li>
                    <li><span class="ad-k"><kbd>Enter</kbd></span><span>otwarcie odnośnika, naciśnięcie przycisku</span></li>
                    <li><span class="ad-k"><kbd>Spacja</kbd></span><span>zaznaczenie pola wyboru, naciśnięcie przycisku, przewinięcie strony</span></li>
                    <li><span class="ad-k"><kbd>Esc</kbd></span><span>zamknięcie rozwiniętego menu lub okna</span></li>
                    <li><span class="ad-k"><kbd>Ctrl</kbd> + <kbd>+</kbd> / <kbd>−</kbd></span><span>powiększenie lub pomniejszenie strony (na Macu <kbd>⌘</kbd> zamiast <kbd>Ctrl</kbd>), <kbd>Ctrl</kbd> + <kbd>0</kbd> przywraca rozmiar</span></li>
                </ul>
            </section>

            <section class="ad-sec" id="kontakt" aria-labelledby="kontakt-h">
                <h2 class="ad-h2" id="kontakt-h">Informacje zwrotne i dane kontaktowe</h2>
                <p class="ad-p">W przypadku problemów z dostępnością strony internetowej prosimy o kontakt.</p>
                @if ($settings->accessibility_contact_name || $email || $phone)
                    <ul class="ad-cards">
                        @if ($settings->accessibility_contact_name)
                            <li><span class="ad-card-l"><i class="fa-solid fa-user" aria-hidden="true"></i>Osoba odpowiedzialna</span><span class="ad-card-v">{{ $settings->accessibility_contact_name }}</span></li>
                        @endif
                        @if ($email)
                            <li><span class="ad-card-l"><i class="fa-solid fa-envelope" aria-hidden="true"></i>Adres e-mail</span><span class="ad-card-v"><a href="mailto:{{ $email }}">{{ $email }}</a></span></li>
                        @endif
                        @if ($phone)
                            <li><span class="ad-card-l"><i class="fa-solid fa-phone" aria-hidden="true"></i>Telefon</span><span class="ad-card-v"><a href="tel:{{ preg_replace('/\s+/', '', $phone) }}">{{ $phone }}</a></span></li>
                        @endif
                    </ul>
                @endif
                <p class="ad-p">Tą samą drogą, lub przez <a href="#zglos-bariere">formularz zgłoszenia</a>, można składać wnioski o udostępnienie informacji niedostępnej oraz żądania zapewnienia dostępności.</p>
                <p class="ad-p">Każdy ma prawo wystąpić z żądaniem zapewnienia dostępności cyfrowej strony internetowej, aplikacji mobilnej lub ich elementu. Tak rozpatrujemy żądanie:</p>
                <ol class="ad-steps">
                    <li><strong>Zgłoszenie</strong>Podajesz swoje dane, wskazujesz, o którą stronę lub element chodzi, i jak się z Tobą skontaktować.</li>
                    <li><strong>Do 7 dni</strong>Zapewniamy dostępność bez zbędnej zwłoki, nie później niż w ciągu 7 dni od dnia wystąpienia z żądaniem.</li>
                    <li><strong>Najdłużej 2 miesiące</strong>Jeżeli 7 dni nie wystarczy, informujemy o tym i podajemy nowy termin, nie dłuższy niż 2 miesiące.</li>
                </ol>
            </section>

            <section class="ad-sec" id="odwolanie" aria-labelledby="odwolanie-h">
                <h2 class="ad-h2" id="odwolanie-h">Postępowanie odwoławcze</h2>
                <p class="ad-p">
                    W przypadku odmowy zapewnienia dostępności można złożyć skargę na sposób rozpatrzenia zgłoszenia. Po wyczerpaniu tej procedury
                    można również złożyć wniosek do <a href="https://www.rpo.gov.pl" target="_blank" rel="noopener">Rzecznika Praw Obywatelskich{!! $adExt !!}</a>.
                </p>
            </section>

            @if (filled($settings->accessibility_architectural))
                <section class="ad-sec" id="architektura" aria-labelledby="architektura-h">
                    <h2 class="ad-h2" id="architektura-h">Dostępność architektoniczna</h2>
                    <div class="ad-p">{!! nl2br(e($settings->accessibility_architectural)) !!}</div>
                </section>
            @endif

            <section class="ad-sec" id="wylaczenia" aria-labelledby="wylaczenia-h">
                <h2 class="ad-h2" id="wylaczenia-h">Wyłączenia treści</h2>
                <p class="ad-note ad-note--brand">{{ $disabledCount === 0 ? 'Obecnie żadna treść nie jest tymczasowo wyłączona.' : 'Obecnie tymczasowo wyłączonych treści: '.$disabledCount.'.' }} Pełna lista z powodami znajduje się na stronie <a href="{{ route('exclusions.index') }}">Wyłączenia treści</a>.</p>
            </section>

            {{-- Formularz zgłaszania barier — trzy kroki, podsumowanie błędów, adres strony podpowiadany z odsyłacza. --}}
            @php
                $arKinds = \App\Http\Controllers\AccessibilityReportController::KINDS;
                $arKindHints = [
                    'bariera' => 'Coś na stronie nie działa albo jest niezrozumiałe.',
                    'zadanie' => 'Chcesz, żebyśmy udostępnili konkretną treść lub element.',
                    'alternatywa' => 'Potrzebujesz treści w innej formie, np. tekst zamiast filmu.',
                ];
                $arBarriers = \App\Http\Controllers\AccessibilityReportController::BARRIERS;
                $arContact = \App\Http\Controllers\AccessibilityReportController::CONTACT;
                // Podpowiedź adresu: ?strona=… albo strona, z której ktoś przyszedł (tylko ten serwis, nie sama deklaracja).
                $arRef = (string) request()->headers->get('referer', '');
                $arRefOk = $arRef !== '' && parse_url($arRef, PHP_URL_HOST) === request()->getHost() && ! str_contains($arRef, '/deklaracja-dostepnosci');
                $arPageUrl = old('page_url', request()->query('strona') ?: ($arRefOk ? $arRef : ''));
                $arFields = ['kind' => 'ar_kind_bariera', 'email' => 'ar_email', 'phone' => 'ar_phone', 'page_url' => 'ar_page_url', 'message' => 'ar_message', 'rodo_consent' => 'ar_rodo', 'name' => 'ar_name', 'contact' => 'ar_contact_email', 'barriers' => 'ar_b_czytnik'];
                $arInput = 'w-full rounded-md border border-solid border-gray-400 bg-white px-3 text-base text-ink focus:border-brand-dark focus:outline-none focus:ring-2 focus:ring-brand-dark';
                $arErr = fn ($f) => $errors->has($f) ? 'border-red-600 ring-1 ring-red-600' : '';
                $arStep = 'flex items-center gap-3 text-lg font-bold text-ink';
                $arNum = 'ad-step-n';
            @endphp
            <section id="zglos-bariere" class="ad-sec ad-form" aria-labelledby="zglos-bariere-h">
                <h2 class="ad-h2" id="zglos-bariere-h">Zgłoś problem z dostępnością</h2>
                <p class="ad-p">Coś na stronie jest nieczytelne, nie działa z klawiaturą albo czytnikiem ekranu? Potrzebujesz treści w innej formie? Napisz, odpowiemy najpóźniej w ciągu 7 dni.</p>

                @if (session('accessibility_reported'))
                    <div class="mt-6 flex items-start gap-3 rounded-md border border-solid border-green-700 bg-green-50 p-4 text-ink" role="status" tabindex="-1" data-ar-focus>
                        <i class="fa-solid fa-circle-check mt-0.5 text-xl text-green-700" aria-hidden="true"></i>
                        <p><strong>Dziękujemy, zgłoszenie dotarło.</strong> Odpowiemy najpóźniej w ciągu 7 dni na podany adres e-mail.</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mt-6 rounded-md border border-solid border-red-600 bg-red-50 p-4 text-ink" role="alert" tabindex="-1" aria-labelledby="ar-errors-h" data-ar-focus>
                        <h3 id="ar-errors-h" class="font-bold">Popraw {{ $errors->count() === 1 ? 'jedno pole' : 'pola' }}, aby wysłać zgłoszenie:</h3>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->keys() as $key)
                                @php $base = \Illuminate\Support\Str::before($key, '.'); @endphp
                                <li><a href="#{{ $arFields[$base] ?? 'ar_message' }}" class="font-bold text-red-800 underline underline-offset-4">{{ $errors->first($key) }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('accessibility.report') }}#zglos-bariere" class="mt-8 space-y-10" novalidate>
                    @csrf
                    <p class="text-sm text-muted">Pola oznaczone <span class="font-bold text-red-700" aria-hidden="true">*</span><span class="sr-only">gwiazdką</span> są wymagane.</p>

                    <div class="hidden" aria-hidden="true">
                        <label for="website">Zostaw to pole puste</label>
                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    {{-- 1. Czego dotyczy --}}
                    <fieldset>
                        <legend class="{{ $arStep }}"><span class="{{ $arNum }}" aria-hidden="true">1</span> Czego dotyczy zgłoszenie?</legend>
                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            @foreach ($arKinds as $value => $label)
                                <label for="ar_kind_{{ $value }}" class="ad-choice">
                                    <input type="radio" id="ar_kind_{{ $value }}" name="kind" value="{{ $value }}" class="mt-1 flex-none text-brand-dark focus:ring-brand-dark" @checked(old('kind', 'bariera') === $value)>
                                    <span><span class="block font-bold text-ink">{{ $label }}</span><span class="mt-1 block text-sm text-muted">{{ $arKindHints[$value] }}</span></span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    {{-- 2. Gdzie i co --}}
                    <fieldset class="space-y-5">
                        <legend class="{{ $arStep }}"><span class="{{ $arNum }}" aria-hidden="true">2</span> Gdzie jest problem i na czym polega?</legend>

                        <div class="pt-4">
                            <label for="ar_page_url" class="mb-1 block font-bold text-ink">Adres strony lub nazwa elementu <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <p id="ar_page_url_hint" class="mb-2 text-sm text-muted">Np. adres podstrony, tytuł dokumentu albo nazwa przycisku.@if ($arPageUrl && ! old('page_url')) Podpowiedzieliśmy stronę, z której tu trafiono. Popraw, jeśli to nie ta.@endif</p>
                            <input type="text" id="ar_page_url" name="page_url" value="{{ $arPageUrl }}" aria-describedby="ar_page_url_hint" @if ($errors->has('page_url')) aria-invalid="true" @endif
                                   class="{{ $arInput }} min-h-12 {{ $arErr('page_url') }}">
                            @error('page_url') <p class="mt-1 text-sm font-bold text-red-700">{{ $message }}</p> @enderror
                        </div>

                        <fieldset>
                            <legend class="mb-2 font-bold text-ink">Rodzaj bariery <span class="font-normal text-muted">(opcjonalnie, można wybrać kilka)</span></legend>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($arBarriers as $value => $label)
                                    <label for="ar_b_{{ $value }}" class="ad-chip">
                                        <input type="checkbox" id="ar_b_{{ $value }}" name="barriers[]" value="{{ $value }}" class="rounded text-brand-dark focus:ring-brand-dark" @checked(in_array($value, (array) old('barriers', []), true))>
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <div>
                            <label for="ar_message" class="mb-1 block font-bold text-ink">Opis problemu <span aria-hidden="true" class="text-red-700">*</span></label>
                            <p id="ar_message_hint" class="mb-2 text-sm text-muted">Co próbowano zrobić i co się stało? Jeśli to ważne, podaj też, z jakiego urządzenia, przeglądarki albo czytnika ekranu korzystasz.</p>
                            <textarea id="ar_message" name="message" rows="6" required aria-required="true" maxlength="5000" aria-describedby="ar_message_hint" @if ($errors->has('message')) aria-invalid="true" @endif
                                      class="{{ $arInput }} py-2 {{ $arErr('message') }}">{{ old('message') }}</textarea>
                            @error('message') <p class="mt-1 text-sm font-bold text-red-700">{{ $message }}</p> @enderror
                        </div>
                    </fieldset>

                    {{-- 3. Kontakt --}}
                    <fieldset class="space-y-5">
                        <legend class="{{ $arStep }}"><span class="{{ $arNum }}" aria-hidden="true">3</span> Jak się z Tobą skontaktować?</legend>

                        <div class="grid gap-5 pt-4 sm:grid-cols-2">
                            <div>
                                <label for="ar_name" class="mb-1 block font-bold text-ink">Imię i nazwisko <span class="font-normal text-muted">(opcjonalnie)</span></label>
                                <input type="text" id="ar_name" name="name" value="{{ old('name') }}" autocomplete="name" @if ($errors->has('name')) aria-invalid="true" @endif
                                       class="{{ $arInput }} min-h-12 {{ $arErr('name') }}">
                                @error('name') <p class="mt-1 text-sm font-bold text-red-700">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="ar_email" class="mb-1 block font-bold text-ink">E-mail <span aria-hidden="true" class="text-red-700">*</span></label>
                                <input type="email" id="ar_email" name="email" value="{{ old('email') }}" required aria-required="true" autocomplete="email" inputmode="email" aria-describedby="ar_email_hint" @if ($errors->has('email')) aria-invalid="true" @endif
                                       class="{{ $arInput }} min-h-12 {{ $arErr('email') }}">
                                <p id="ar_email_hint" class="mt-1 text-sm text-muted">Na ten adres wyślemy odpowiedź.</p>
                                @error('email') <p class="mt-1 text-sm font-bold text-red-700">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <fieldset>
                            <legend class="mb-2 font-bold text-ink">Preferowany sposób odpowiedzi</legend>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($arContact as $value => $label)
                                    <label for="ar_contact_{{ $value }}" class="ad-chip">
                                        <input type="radio" id="ar_contact_{{ $value }}" name="contact" value="{{ $value }}" class="text-brand-dark focus:ring-brand-dark" @checked(old('contact', 'email') === $value) data-ar-contact>
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <div class="sm:w-1/2" data-ar-phone>
                            <label for="ar_phone" class="mb-1 block font-bold text-ink">Telefon <span class="font-normal text-muted" data-ar-phone-opt>(opcjonalnie)</span><span class="hidden text-red-700" aria-hidden="true" data-ar-phone-req>*</span></label>
                            <input type="tel" id="ar_phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" inputmode="tel" @if ($errors->has('phone')) aria-invalid="true" @endif
                                   class="{{ $arInput }} min-h-12 {{ $arErr('phone') }}">
                            @error('phone') <p class="mt-1 text-sm font-bold text-red-700">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="ar_rodo" class="ad-consent {{ $arErr('rodo_consent') }}">
                                <input type="checkbox" id="ar_rodo" name="rodo_consent" value="1" required aria-required="true" @checked(old('rodo_consent')) @if ($errors->has('rodo_consent')) aria-invalid="true" @endif
                                       class="mt-1 h-5 w-5 flex-none rounded text-brand-dark focus:ring-brand-dark">
                                <span class="text-sm text-ink">
                                    Wyrażam zgodę na przetwarzanie moich danych osobowych w celu udzielenia odpowiedzi na zgłoszenie, zgodnie z
                                    <a href="{{ route('page.show', 'polityka-prywatnosci') }}" class="font-bold text-brand-dark underline underline-offset-4 hover:text-ink">Polityką prywatności</a>.
                                    <span aria-hidden="true" class="font-bold text-red-700">*</span>
                                </span>
                            </label>
                            @error('rodo_consent') <p class="mt-1 text-sm font-bold text-red-700">{{ $message }}</p> @enderror
                        </div>
                    </fieldset>

                    <div class="flex flex-wrap items-center gap-4 pt-2">
                        <button type="submit" class="ad-btn">
                            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Wyślij zgłoszenie
                        </button>
                        <p class="text-sm text-ink">Wolisz inaczej? Napisz na adres z sekcji <a href="#kontakt" class="ad-link">Informacje zwrotne i dane kontaktowe</a>.</p>
                    </div>
                </form>
            </section>

            <script>
                (function () {
                    // Po wysłaniu albo błędzie walidacji — fokus na komunikacie (czytnik ekranu go odczyta).
                    const msg = document.querySelector('[data-ar-focus]');
                    if (msg) msg.focus();
                    // Telefon wymagany, gdy wybrano odpowiedź telefoniczną.
                    const phone = document.getElementById('ar_phone');
                    const opt = document.querySelector('[data-ar-phone-opt]');
                    const req = document.querySelector('[data-ar-phone-req]');
                    function sync() {
                        const tel = (document.querySelector('[data-ar-contact]:checked') || {}).value === 'telefon';
                        phone.required = tel;
                        phone.setAttribute('aria-required', tel ? 'true' : 'false');
                        opt.classList.toggle('hidden', tel);
                        req.classList.toggle('hidden', !tel);
                    }
                    document.querySelectorAll('[data-ar-contact]').forEach(function (r) { r.addEventListener('change', sync); });
                    sync();
                })();
            </script>
        </div>{{-- /.ad-body --}}
    </div>{{-- /.ad-layout --}}
</div>{{-- /.ad --}}

<script>
    (function () {
        // Spis treści: zaznacza sekcję, którą właśnie czytasz (aria-current="location"); bez JS zwykłe odnośniki.
        const toc = document.querySelector('[data-ad-toc]');
        if (! toc || ! ('IntersectionObserver' in window)) return;
        const links = new Map([...toc.querySelectorAll('a[href^="#"]')].map((a) => [a.getAttribute('href').slice(1), a]));
        const mark = (id) => links.forEach((a, key) => key === id ? a.setAttribute('aria-current', 'location') : a.removeAttribute('aria-current'));
        const seen = new Map();
        const io = new IntersectionObserver((entries) => {
            entries.forEach((e) => seen.set(e.target.id, e.isIntersecting));
            const first = [...links.keys()].find((id) => seen.get(id));
            if (first) mark(first);
        }, { rootMargin: '-20% 0px -60% 0px' });
        links.forEach((a, id) => { const el = document.getElementById(id); if (el) io.observe(el); });
    })();
</script>
@endsection
