@extends('layouts.site')

@section('title', 'Darowizna jednorazowa — ' . $siteSettings->site_name)
@section('meta_description', 'Wesprzyj ' . $siteSettings->site_name . ' jednorazową darowizną — szybka płatność online przez Przelewy24 lub tradycyjny przelew.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Wesprzyj nas', 'url' => route('support.show')],
        ['label' => 'Darowizna jednorazowa', 'url' => null],
    ]])
@endsection

@php
    // Ten sam wygląd strony w każdym szablonie (klasy z app.css, niezależne od .template-vm).
    $display = 'vm-display';
    $bankNumber = trim((string) $siteSettings->bank_account_number);
    $bankDigits = preg_replace('/\D+/', '', $bankNumber);
    $iban = strlen($bankDigits) === 26 ? trim(chunk_split('PL' . $bankDigits, 4, ' ')) : null;
    $transferTitle = config('szo.donation_purpose', 'Darowizna na cele statutowe');
    $intro = filled($siteSettings->donation_intro)
        ? preg_split('/\R{2,}/', trim($siteSettings->donation_intro))
        : ['Dzięki Twojej pomocy możemy zrobić więcej. Docenimy każdą, nawet niewielką darowiznę — razem z innymi wpłatami pozwala nam skutecznie działać.'];

    $headline = filled($siteSettings->donation_headline) ? $siteSettings->donation_headline : 'Twoja wpłata zamienia się w realną pomoc';
    $useNote = filled($siteSettings->donation_use_note)
        ? preg_split('/\R{2,}/', trim($siteSettings->donation_use_note))
        : [];
    $reportsUrl = trim((string) $siteSettings->donation_reports_url);
    $reportsUrl = $reportsUrl !== '' && ! preg_match('#^(https?:)?//#i', $reportsUrl) ? url('/' . ltrim($reportsUrl, '/')) : $reportsUrl;
    $contactMail = $siteSettings->contact_email;
    // ?kwota=60 (np. z kafelków na stronie /wsparcie) wstępnie zaznacza kwotę z listy.
    $presetAmount = in_array((int) request('kwota'), $amounts, true) ? (string) (int) request('kwota') : null;
    $oldAmount = old('amount', $presetAmount ?? (string) ($amounts[1] ?? $amounts[0]));
    $fieldBase = 'block min-h-11 w-full rounded-xl border-2 border-gray-300 bg-white px-4 text-base text-ink placeholder:text-gray-600 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/30';
    $fieldError = 'border-red-600';
    $errorFields = ['amount', 'amount_other', 'first_name', 'last_name', 'email', 'phone', 'visibility', 'consent_rodo'];
    $hasFormErrors = collect($errorFields)->contains(fn ($f) => $errors->has($f));
@endphp

@section('content')
<div class="{{ ($siteSettings->site_template ?? 'default') === 'feer' ? 'feer-flat' : '' }}">
<div class="mx-auto max-w-[1200px] px-4 py-10 lg:py-14">
    {{-- ── Nagłówek: korzyść darczyńcy, krótki opis i sygnały zaufania ─────── --}}
    <header class="mb-10 grid gap-8 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:items-end">
        <div>
            <p class="mb-3 inline-flex items-center gap-2 rounded-full bg-brand-light px-3 py-1 text-xs font-bold uppercase tracking-wide text-brand">
                <i class="fa-solid fa-heart" aria-hidden="true"></i> Darowizna jednorazowa
            </p>
            <h1 class="{{ $display }} mb-5 text-3xl leading-tight text-ink sm:text-4xl lg:text-5xl">{{ $headline }}</h1>
            <div class="max-w-3xl space-y-4 text-base leading-relaxed text-ink sm:text-lg">
                @foreach ($intro as $paragraph)
                    <p>{!! nl2br(e($paragraph)) !!}</p>
                @endforeach
            </div>
        </div>

        <ul class="grid gap-3 text-sm text-ink sm:grid-cols-3 lg:grid-cols-1" role="list" aria-label="Dlaczego możesz nam zaufać">
            <li class="flex items-start gap-3 rounded-2xl border border-gray-200 bg-white p-4">
                <i class="fa-solid fa-shield-halved mt-0.5 text-lg text-brand" aria-hidden="true"></i>
                <span><strong class="block">Bezpieczna płatność</strong>Obsługuje ją Przelewy24 — BLIK, szybki przelew lub karta.</span>
            </li>
            <li class="flex items-start gap-3 rounded-2xl border border-gray-200 bg-white p-4">
                <i class="fa-solid fa-scale-balanced mt-0.5 text-lg text-brand" aria-hidden="true"></i>
                <span>
                    <strong class="block">Organizacja pozarządowa</strong>
                    @if ($siteSettings->krs_number) KRS {{ $siteSettings->krs_number }}@endif
                    @if ($siteSettings->krs_number && $siteSettings->nip_number), @endif
                    @if ($siteSettings->nip_number) NIP {{ $siteSettings->nip_number }}@endif
                    @unless ($siteSettings->krs_number || $siteSettings->nip_number) Dane rejestrowe znajdziesz w stopce strony. @endunless
                </span>
            </li>
            <li class="flex items-start gap-3 rounded-2xl border border-gray-200 bg-white p-4">
                <i class="fa-solid fa-user-lock mt-0.5 text-lg text-brand" aria-hidden="true"></i>
                <span><strong class="block">Ty decydujesz o widoczności</strong>Możesz wpłacić anonimowo — imię na liście wpłat jest opcjonalne.</span>
            </li>
        </ul>
    </header>

    <div class="grid gap-10 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)]">

        {{-- ── Wpłata online ─────────────────────────────────────────────── --}}
        <section aria-labelledby="donation-online-heading">
            <h2 id="donation-online-heading" class="{{ $display }} mb-5 text-xl text-brand sm:text-2xl">Wesprzyj nas online</h2>

            @if (! $onlineEnabled)
                <div class="rounded-xl border border-amber-300 bg-amber-50 p-5 text-sm text-ink" role="status">
                    <p class="font-bold">Płatności online są chwilowo niedostępne.</p>
                    <p class="mt-1">Możesz przekazać darowiznę tradycyjnym przelewem — dane znajdziesz obok.</p>
                </div>
            @else
                @if (session('donation_error'))
                    <div class="mb-6 rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-800" role="alert">
                        {{ session('donation_error') }}
                    </div>
                @endif

                @if ($hasFormErrors)
                    {{-- Podsumowanie błędów z odnośnikami do pól (WCAG 3.3.1, 3.3.3). --}}
                    <div id="donation-errors" class="mb-6 rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-800" role="alert" tabindex="-1">
                        <p class="font-bold">Popraw zaznaczone pola:</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errorFields as $f)
                                @error($f)
                                    <li><a href="#donation-{{ $f === 'amount' ? 'amount-' . $amounts[0] : str_replace('_', '-', $f) }}" class="underline">{{ $message }}</a></li>
                                @enderror
                            @endforeach
                        </ul>
                    </div>
                    <script>document.addEventListener('DOMContentLoaded', () => document.getElementById('donation-errors')?.focus());</script>
                @endif

                <form method="POST" action="{{ route('donation.store') }}" novalidate
                      class="space-y-8 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-8"
                      x-data="{
                          amount: @js($oldAmount),
                          other: @js(old('amount_other', '')),
                          impacts: @js($impacts),
                          get total() { const v = this.amount === 'other' ? this.other : this.amount; return v ? Number(v) : 0; },
                          get impact() { return this.amount === 'other' ? '' : (this.impacts[this.amount] || ''); },
                      }">
                    @csrf

                    {{-- Kwota --}}
                    <fieldset>
                        <legend class="{{ $display }} mb-4 flex items-center gap-3 text-base text-ink"><span class="flex h-8 w-8 flex-none items-center justify-center rounded-full bg-brand text-sm text-white" aria-hidden="true">1</span> Wybierz kwotę</legend>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach ($amounts as $value)
                                <label class="relative">
                                    <input type="radio" name="amount" value="{{ $value }}" id="donation-amount-{{ $value }}" x-model="amount"
                                           class="peer sr-only" @checked($oldAmount === (string) $value)>
                                    <span class="flex min-h-12 cursor-pointer items-center justify-center rounded-xl border-2 border-brand bg-white px-3 text-base font-bold text-brand transition peer-checked:bg-brand peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand peer-focus-visible:ring-offset-2 hover:bg-brand-light">
                                        {{ $value }} zł
                                    </span>
                                </label>
                            @endforeach
                            <label class="relative">
                                <input type="radio" name="amount" value="other" id="donation-amount-other" x-model="amount"
                                       class="peer sr-only" @checked($oldAmount === 'other')>
                                <span class="flex min-h-12 cursor-pointer items-center justify-center rounded-xl border-2 border-brand bg-white px-3 text-base font-bold text-brand transition peer-checked:bg-brand peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand peer-focus-visible:ring-offset-2 hover:bg-brand-light">
                                    Inna kwota
                                </span>
                            </label>
                        </div>

                        <div class="mt-4" x-show="amount === 'other'" @if ($oldAmount !== 'other') style="display: none" @endif>
                            <label for="donation-amount-other-value" class="mb-1 block text-sm font-bold text-ink">Twoja kwota (zł)</label>
                            <div class="flex max-w-xs items-center gap-2">
                                <input type="number" name="amount_other" id="donation-amount-other-value" x-model="other"
                                       min="{{ $minAmount }}" max="{{ $maxAmount }}" step="1" inputmode="decimal"
                                       value="{{ old('amount_other') }}"
                                       aria-describedby="donation-amount-other-help @error('amount_other') donation-amount-other-error @enderror"
                                       @error('amount_other') aria-invalid="true" @enderror
                                       class="{{ $fieldBase }} @error('amount_other') {{ $fieldError }} @enderror">
                                <span class="font-bold" aria-hidden="true">zł</span>
                            </div>
                            <p id="donation-amount-other-help" class="mt-1 text-xs text-muted">Od {{ $minAmount }} do {{ number_format($maxAmount, 0, ',', ' ') }} zł.</p>
                            @error('amount_other') <p id="donation-amount-other-error" class="mt-1 text-sm font-semibold text-red-700">{{ $message }}</p> @enderror
                        </div>
                        @error('amount') <p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p> @enderror

                        {{-- Efekt wybranej kwoty — ogłaszany czytnikom ekranu po zmianie wyboru. --}}
                        <p class="mt-4 min-h-6 rounded-xl bg-brand-light px-4 py-3 text-sm font-semibold text-brand-dark" x-show="impact" x-cloak aria-live="polite">
                            <i class="fa-solid fa-circle-check mr-1.5" aria-hidden="true"></i><span x-text="impact"></span>
                        </p>
                    </fieldset>

                    {{-- Dane --}}
                    <fieldset>
                        <legend class="{{ $display }} mb-4 flex items-center gap-3 text-base text-ink"><span class="flex h-8 w-8 flex-none items-center justify-center rounded-full bg-brand text-sm text-white" aria-hidden="true">2</span> Twoje dane</legend>
                        <p class="mb-4 text-xs text-muted">Pola oznaczone gwiazdką (*) są wymagane.</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ([
                                ['first_name', 'Imię', 'text', 'given-name', true],
                                ['last_name', 'Nazwisko', 'text', 'family-name', true],
                                ['email', 'E-mail', 'email', 'email', true],
                                ['phone', 'Numer telefonu', 'tel', 'tel', false],
                            ] as [$name, $label, $type, $autocomplete, $required])
                                @php $id = 'donation-' . str_replace('_', '-', $name); @endphp
                                <div>
                                    <label for="{{ $id }}" class="mb-1 block text-sm font-bold text-ink">
                                        {{ $label }}@if ($required) <span aria-hidden="true">*</span>@else <span class="font-normal text-muted">(opcjonalnie)</span>@endif
                                    </label>
                                    <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}" value="{{ old($name) }}"
                                           autocomplete="{{ $autocomplete }}" maxlength="{{ $name === 'phone' ? 40 : ($name === 'email' ? 255 : 100) }}"
                                           @if ($required) required aria-required="true" @endif
                                           @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
                                           class="{{ $fieldBase }} @error($name) {{ $fieldError }} @enderror">
                                    @error($name) <p id="{{ $id }}-error" class="mt-1 text-sm font-semibold text-red-700">{{ $message }}</p> @enderror
                                </div>
                            @endforeach
                        </div>
                    </fieldset>

                    {{-- Widoczność --}}
                    <fieldset>
                        <legend class="{{ $display }} mb-3 text-base text-ink">Lista „Ostatnie wpłaty"</legend>
                        @php $visibility = old('visibility', 'anonymous'); @endphp
                        <div class="space-y-2" id="donation-visibility">
                            <label class="flex min-h-11 cursor-pointer items-start gap-3 text-sm text-ink">
                                <input type="radio" name="visibility" value="name" @checked($visibility === 'name')
                                       class="mt-0.5 h-5 w-5 border-gray-400 text-brand focus:ring-brand">
                                <span>Pokaż moje imię i pierwszą literę nazwiska</span>
                            </label>
                            <label class="flex min-h-11 cursor-pointer items-start gap-3 text-sm text-ink">
                                <input type="radio" name="visibility" value="anonymous" @checked($visibility === 'anonymous')
                                       class="mt-0.5 h-5 w-5 border-gray-400 text-brand focus:ring-brand">
                                <span>Wpłata anonimowa</span>
                            </label>
                        </div>
                        @error('visibility') <p class="mt-1 text-sm font-semibold text-red-700">{{ $message }}</p> @enderror
                    </fieldset>

                    {{-- Oświadczenia --}}
                    <fieldset>
                        <legend class="{{ $display }} mb-3 flex items-center gap-3 text-base text-ink"><span class="flex h-8 w-8 flex-none items-center justify-center rounded-full bg-brand text-sm text-white" aria-hidden="true">3</span> Zgody</legend>
                        <div class="space-y-3 text-xs leading-relaxed text-muted">
                            <p>
                                Administratorem danych osobowych jest {{ $siteSettings->site_name }}@if ($siteSettings->contact_address) z siedzibą: {{ $siteSettings->contact_address }}, {{ $siteSettings->contact_city }}@endif{{ $siteSettings->krs_number ? ', KRS: ' . $siteSettings->krs_number : '' }}.
                                Dane przetwarzamy w celu przyjęcia i rozliczenia darowizny oraz — jeśli wyrazisz zgodę — wysyłki newslettera.
                            </p>
                        </div>
                        <div class="mt-4 space-y-3">
                            <div>
                                <label class="flex cursor-pointer items-start gap-3 text-sm text-ink">
                                    <input type="checkbox" name="consent_rodo" id="donation-consent-rodo" value="1" @checked(old('consent_rodo'))
                                           required aria-required="true"
                                           @error('consent_rodo') aria-invalid="true" aria-describedby="donation-consent-rodo-error" @enderror
                                           class="mt-0.5 h-5 w-5 flex-none rounded border-gray-400 text-brand focus:ring-brand">
                                    <span>
                                        Zapoznałem/-am się z <a href="{{ url('/polityka-prywatnosci') }}" class="font-bold text-brand underline hover:text-brand-dark" target="_blank" rel="noopener">Polityką prywatności<span class="sr-only"> (otwiera się w nowej karcie)</span></a>
                                        i wyrażam zgodę na przetwarzanie moich danych osobowych w celu przyjęcia darowizny. <span aria-hidden="true">*</span>
                                    </span>
                                </label>
                                @error('consent_rodo') <p id="donation-consent-rodo-error" class="ml-8 mt-1 text-sm font-semibold text-red-700">{{ $message }}</p> @enderror
                            </div>
                            <label class="flex cursor-pointer items-start gap-3 text-sm text-ink">
                                <input type="checkbox" name="consent_newsletter" value="1" @checked(old('consent_newsletter'))
                                       class="mt-0.5 h-5 w-5 flex-none rounded border-gray-400 text-brand focus:ring-brand">
                                <span>Chcę otrzymywać newsletter {{ $siteSettings->siteNameGenitive() }} (opcjonalnie; zgodę można w każdej chwili wycofać).</span>
                            </label>
                        </div>
                    </fieldset>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit"
                                class="{{ $display }} inline-flex min-h-12 items-center gap-3 rounded-2xl bg-brand px-8 text-sm text-white shadow-md transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                            <span>Wpłacam<span x-show="total >= {{ $minAmount }}" x-cloak> <span x-text="total.toLocaleString('pl-PL') + ' zł'"></span></span></span>
                            <i class="fa-solid fa-angles-right" aria-hidden="true"></i>
                        </button>
                        @if ($bankNumber !== '')
                            <a href="{{ route('donation.slip') }}"
                               :href="@js(route('donation.slip')) + ((amount === 'other' ? other : amount) ? '?kwota=' + encodeURIComponent(amount === 'other' ? other : amount) : '')"
                               class="{{ $display }} inline-flex min-h-12 items-center gap-2 rounded-2xl border-2 border-brand px-6 text-sm text-brand transition hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                                Druk przelewu <span class="sr-only">(plik PDF z wybraną kwotą)</span>
                            </a>
                        @endif
                    </div>
                    <p class="flex items-start gap-2 text-xs text-muted">
                        <i class="fa-solid fa-lock mt-0.5" aria-hidden="true"></i>
                        <span>Po kliknięciu „Wpłacam" przejdziesz do bezpiecznej płatności w serwisie Przelewy24
                        (BLIK, szybki przelew, karta). Nie przechowujemy danych Twojej karty ani banku. Minimalna kwota: {{ $minAmount }} zł.</span>
                    </p>
                </form>
            @endif
        </section>

        {{-- ── Panel boczny: przejrzystość, regularne wsparcie, przelew tradycyjny ── --}}
        <aside aria-label="Informacje o wpłatach" class="space-y-8 lg:sticky lg:top-24 lg:self-start">
            @if ($useNote || $reportsUrl !== '')
                <section class="rounded-2xl border border-gray-200 bg-gray-50 p-5 sm:p-6" aria-labelledby="donation-use-heading">
                    <h2 id="donation-use-heading" class="{{ $display }} mb-3 text-xl text-brand sm:text-2xl">Na co trafią Twoje pieniądze</h2>
                    <div class="space-y-3 text-sm leading-relaxed text-ink">
                        @foreach ($useNote as $paragraph)
                            <p>{!! nl2br(e($paragraph)) !!}</p>
                        @endforeach
                    </div>
                    @if ($reportsUrl !== '')
                        <a href="{{ $reportsUrl }}" class="mt-4 inline-flex min-h-11 items-center gap-2 text-sm font-bold text-brand underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                            <i class="fa-solid fa-file-lines" aria-hidden="true"></i> Zobacz sprawozdania i rozliczenia
                        </a>
                    @endif
                </section>
            @endif

            @if ($bankNumber !== '')
                <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6" aria-labelledby="donation-regular-heading">
                    <h2 id="donation-regular-heading" class="{{ $display }} mb-3 text-xl text-brand sm:text-2xl">Chcesz wspierać regularnie?</h2>
                    <p class="text-sm leading-relaxed text-ink">
                        Najwięcej zmienia stała pomoc. Ustaw w swoim banku zlecenie stałe na nasz numer konta (dane poniżej) —
                        miesięczna kwota, którą zaplanujesz, pozwala nam z wyprzedzeniem planować działania.
                    </p>
                </section>
            @endif

        @if ($bankNumber !== '')
            <div class="space-y-8">
                <section aria-labelledby="donation-bank-heading" class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
                    <h2 id="donation-bank-heading" class="{{ $display }} mb-4 text-xl text-brand sm:text-2xl">Przelew tradycyjny</h2>
                    <dl class="space-y-4 text-sm text-ink">
                        <div>
                            <dt class="{{ $display }} text-base">Odbiorca</dt>
                            <dd class="mt-1 uppercase leading-relaxed">
                                {{ $siteSettings->site_name }}
                                @if ($siteSettings->contact_address)<br>{{ $siteSettings->contact_address }}<br>{{ $siteSettings->contact_city }}@endif
                            </dd>
                        </div>
                        <div>
                            <dt class="{{ $display }} text-base">Numer konta</dt>
                            <dd class="mt-1">
                                <span class="font-mono tabular-nums">{{ $bankNumber }}</span>
                                <button type="button" data-copy-button data-copy-value="{{ $bankDigits }}"
                                    class="mt-2 flex min-h-11 items-center gap-1.5 rounded-full border border-brand px-4 text-xs font-bold text-brand transition hover:bg-brand-light focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                    <i class="fa-regular fa-copy" aria-hidden="true"></i> Kopiuj numer konta
                                </button>
                            </dd>
                        </div>
                        <div>
                            <dt class="{{ $display }} text-base">Tytuł wpłaty</dt>
                            <dd class="mt-1 uppercase">{{ $transferTitle }}</dd>
                        </div>
                    </dl>
                </section>

                @if ($iban)
                    <section aria-labelledby="donation-iban-heading" class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
                        <h2 id="donation-iban-heading" class="{{ $display }} mb-4 text-xl text-brand sm:text-2xl">Przelew zagraniczny</h2>
                        <dl class="text-sm text-ink">
                            <dt class="{{ $display }} text-base">IBAN</dt>
                            <dd class="mt-1 font-mono tabular-nums">{{ $iban }}</dd>
                        </dl>
                    </section>
                @endif
            </div>
        @endif

            @if ($contactMail)
                <p class="text-sm text-muted">
                    Masz pytania o darowiznę? Napisz: <a href="mailto:{{ $contactMail }}" class="font-bold text-brand underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">{{ $contactMail }}</a>.
                </p>
            @endif
        </aside>
    </div>

    {{-- ── Ostatnie wpłaty ───────────────────────────────────────────────── --}}
    @if ($recent->isNotEmpty())
        <section class="mt-16" aria-labelledby="donation-recent-heading">
            <h2 id="donation-recent-heading" class="vm-display vm-section-title mb-8 text-2xl text-ink sm:text-3xl">Ostatnie wpłaty</h2>
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" role="list">
                @foreach ($recent as $donation)
                    <li class="flex items-center gap-4 rounded-2xl border border-gray-200 p-4">
                        <span class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-gray-100 text-xl text-gray-500" aria-hidden="true">
                            <i class="fa-solid fa-user"></i>
                        </span>
                        <span>
                            <span class="{{ $display }} block text-sm text-ink">{{ $donation->publicLabel() }}</span>
                            <span class="{{ $display }} block text-base text-brand">{{ $donation->amountLabel() }}</span>
                            @if ($donation->paid_at)
                                <span class="block text-xs text-muted"><time datetime="{{ $donation->paid_at->toDateString() }}">{{ $donation->paid_at->translatedFormat('j F Y') }}</time></span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>

@include('contact.partials.copy-script')
</div>
@endsection
