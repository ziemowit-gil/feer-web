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
@include('partials.flat-page-styles')
@php $disabledCount = \App\Models\Page::forCurrentSite()->where('is_disabled', true)->count(); @endphp
<div class="fp-head">
    <h1 class="fp-h1">Deklaracja dostępności</h1>
    <span class="fp-bar" aria-hidden="true"></span>
</div>
    <section class="fp-wrap">

        <div class="fp-p">
            <p>{{ $settings->accessibilityEntityName() }} zobowiązuje się zapewnić dostępność swojej strony internetowej zgodnie z ustawą z dnia 4 kwietnia 2019 r. o dostępności cyfrowej stron internetowych i aplikacji mobilnych podmiotów publicznych.</p>

            <dl class="grid gap-x-6 gap-y-2 sm:grid-cols-[auto_1fr]">
                @if ($fmt($settings->accessibility_page_published_at))
                    <dt class="font-bold">Data publikacji strony internetowej:</dt>
                    <dd>{{ $fmt($settings->accessibility_page_published_at) }}</dd>
                @endif
                @if ($fmt($settings->accessibility_page_updated_at))
                    <dt class="font-bold">Data ostatniej istotnej aktualizacji:</dt>
                    <dd>{{ $fmt($settings->accessibility_page_updated_at) }}</dd>
                @endif
            </dl>
        </div>

        {{-- Status zgodności --}}
        <h2 class="fp-h2">Status pod względem zgodności z ustawą</h2>
        <p class="fp-p">
            Strona internetowa jest <strong>{{ mb_strtolower($settings->accessibilityStatusLabel()) }}</strong>
            z ustawą o dostępności cyfrowej stron internetowych i aplikacji mobilnych podmiotów publicznych.
        </p>
        @if (filled($settings->accessibility_status_note))
            <div class="prose mt-3 max-w-none text-muted">{!! nl2br(e($settings->accessibility_status_note)) !!}</div>
        @endif

        {{-- Sporządzenie deklaracji --}}
        <h2 class="fp-h2">Przygotowanie deklaracji dostępności</h2>
        <ul class="fp-p" style="padding-left:1.25rem;list-style:disc">
            @if ($fmt($settings->accessibility_declaration_date))
                <li>Deklarację sporządzono dnia: <strong>{{ $fmt($settings->accessibility_declaration_date) }}</strong>.</li>
            @endif
            <li>Deklarację sporządzono na podstawie {{ \App\Models\SiteSetting::ACCESSIBILITY_REVIEW_METHODS[$settings->accessibility_review_method] ?? \App\Models\SiteSetting::ACCESSIBILITY_REVIEW_METHODS['self'] }}.</li>
        </ul>

        {{-- Skróty klawiaturowe --}}
        <h2 class="fp-h2">Skróty klawiaturowe</h2>
        <p class="fp-p">Na stronie internetowej można korzystać ze standardowych skrótów klawiaturowych przeglądarki.</p>

        {{-- Informacje zwrotne i dane kontaktowe --}}
        <h2 class="fp-h2 scroll-mt-24" id="kontakt">Informacje zwrotne i dane kontaktowe</h2>
        <p class="fp-p">
            W przypadku problemów z dostępnością strony internetowej prosimy o kontakt.
            @if ($settings->accessibility_contact_name)
                Osoba odpowiedzialna: <strong>{{ $settings->accessibility_contact_name }}</strong>.
            @endif
            @if ($email)
                Adres e-mail: <a href="mailto:{{ $email }}" class="font-bold text-brand hover:text-brand-dark">{{ $email }}</a>.
            @endif
            @if ($phone)
                Telefon: <a href="tel:{{ preg_replace('/\s+/', '', $phone) }}" class="font-bold text-brand hover:text-brand-dark">{{ $phone }}</a>.
            @endif
        </p>
        <p class="fp-p">Tą samą drogą, lub przez formularz poniżej, można składać wnioski o udostępnienie informacji niedostępnej oraz żądania zapewnienia dostępności.</p>
        <p class="fp-p">
            Każdy ma prawo wystąpić z żądaniem zapewnienia dostępności cyfrowej strony internetowej, aplikacji mobilnej lub ich elementu.
            Żądanie powinno zawierać dane osoby zgłaszającej, wskazanie, o którą stronę lub element chodzi, oraz sposób kontaktu.
            Podmiot publiczny zrealizuje żądanie bez zbędnej zwłoki, nie później niż w ciągu 7 dni od dnia wystąpienia z żądaniem.
            Jeżeli dotrzymanie tego terminu nie jest możliwe, poinformujemy o tym wnoszącego oraz o nowym terminie, nie dłuższym niż 2 miesiące.
        </p>

        {{-- Procedura odwoławcza --}}
        <h2 class="fp-h2">Postępowanie odwoławcze</h2>
        <p class="fp-p">
            W przypadku odmowy zapewnienia dostępności można złożyć skargę na sposób rozpatrzenia zgłoszenia. Po wyczerpaniu tej procedury
            można również złożyć wniosek do <a href="https://www.rpo.gov.pl" target="_blank" rel="noopener" class="font-bold text-brand hover:text-brand-dark">Rzecznika Praw Obywatelskich</a>.
        </p>

        {{-- Dostępność architektoniczna --}}
        @if (filled($settings->accessibility_architectural))
            <h2 class="fp-h2">Dostępność architektoniczna</h2>
            <div class="prose mt-1 max-w-none text-muted">{!! nl2br(e($settings->accessibility_architectural)) !!}</div>
        @endif

        {{-- Wyłączenia treści: lista stron tymczasowo niedostępnych --}}
        <h2 class="fp-h2" id="wylaczenia"><i class="fa-solid fa-circle-pause" aria-hidden="true"></i>Wyłączenia treści</h2>
        <div class="fp-box fp-box-note">
            <p class="fp-p" style="margin:0">{{ $disabledCount === 0 ? 'Obecnie żadna treść nie jest tymczasowo wyłączona.' : 'Obecnie tymczasowo wyłączonych treści: '.$disabledCount.'.' }} Pełna lista z powodami znajduje się na stronie <a href="{{ route('exclusions.index') }}" class="fp-link">Wyłączenia treści</a>.</p>
        </div>

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
            $arNum = 'flex h-8 w-8 flex-none items-center justify-center rounded-full bg-brand-dark text-sm font-bold text-white';
        @endphp
        <div id="zglos-bariere" class="mt-10 scroll-mt-24 rounded-md border border-solid border-gray-200 bg-white p-5 shadow-sm sm:p-8">
            <h2 class="fp-h2" style="margin-top:0">
                <i class="fa-solid fa-universal-access text-brand" aria-hidden="true"></i> Zgłoś problem z dostępnością
            </h2>
            <p class="fp-p" style="font-size:.95rem">Coś na stronie jest nieczytelne, nie działa z klawiaturą albo czytnikiem ekranu? Potrzebujesz treści w innej formie? Napisz, odpowiemy najpóźniej w ciągu 7 dni.</p>

            <ol class="mt-4 grid gap-2 text-sm text-ink sm:grid-cols-3" aria-label="Jak to działa">
                <li class="flex items-center gap-2 rounded-md bg-gray-50 px-3 py-2"><i class="fa-solid fa-pen text-brand-dark" aria-hidden="true"></i> Opisujesz problem</li>
                <li class="flex items-center gap-2 rounded-md bg-gray-50 px-3 py-2"><i class="fa-solid fa-envelope-circle-check text-brand-dark" aria-hidden="true"></i> Odpowiadamy do 7 dni</li>
                <li class="flex items-center gap-2 rounded-md bg-gray-50 px-3 py-2"><i class="fa-solid fa-screwdriver-wrench text-brand-dark" aria-hidden="true"></i> Naprawiamy lub udostępniamy</li>
            </ol>

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
                            <label for="ar_kind_{{ $value }}" class="flex cursor-pointer gap-3 rounded-md border-2 border-solid border-gray-200 p-4 transition hover:border-brand has-[:checked]:border-brand-dark has-[:checked]:bg-brand-light has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand-dark">
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
                                <label for="ar_b_{{ $value }}" class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-full border border-solid border-gray-300 bg-white px-4 text-sm text-ink transition hover:border-brand has-[:checked]:border-brand-dark has-[:checked]:bg-brand-light has-[:checked]:font-bold has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand-dark">
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
                                <label for="ar_contact_{{ $value }}" class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-full border border-solid border-gray-300 bg-white px-4 text-sm text-ink transition hover:border-brand has-[:checked]:border-brand-dark has-[:checked]:bg-brand-light has-[:checked]:font-bold has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand-dark">
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
                        <label for="ar_rodo" class="flex cursor-pointer items-start gap-3 rounded-md bg-gray-50 p-4 {{ $arErr('rodo_consent') }}">
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

                <div class="flex flex-wrap items-center gap-4 border-t border-solid border-gray-200 pt-6">
                    <button type="submit" class="inline-flex min-h-12 items-center gap-2 rounded-md bg-brand-dark px-6 font-bold text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                        <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Wyślij zgłoszenie
                    </button>
                    <p class="text-sm text-muted">Wolisz inaczej? Napisz na adres z sekcji <a href="#kontakt" class="font-bold text-brand-dark underline underline-offset-4">Informacje zwrotne i dane kontaktowe</a>.</p>
                </div>
            </form>
        </div>

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
    </section>
@endsection
