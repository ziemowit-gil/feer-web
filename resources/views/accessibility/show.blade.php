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
    <section class="mx-auto max-w-3xl px-4 py-12">
        <h1 class="mb-6 text-3xl font-bold text-ink">Deklaracja dostępności</h1>

        <div class="space-y-4 text-ink">
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
        <h2 class="mt-10 mb-3 text-xl font-bold text-ink">Status pod względem zgodności z ustawą</h2>
        <p class="text-ink">
            Strona internetowa jest <strong>{{ mb_strtolower($settings->accessibilityStatusLabel()) }}</strong>
            z ustawą o dostępności cyfrowej stron internetowych i aplikacji mobilnych podmiotów publicznych.
        </p>
        @if (filled($settings->accessibility_status_note))
            <div class="prose mt-3 max-w-none text-muted">{!! nl2br(e($settings->accessibility_status_note)) !!}</div>
        @endif

        {{-- Sporządzenie deklaracji --}}
        <h2 class="mt-10 mb-3 text-xl font-bold text-ink">Przygotowanie deklaracji dostępności</h2>
        <ul class="list-inside list-disc space-y-1 text-ink">
            @if ($fmt($settings->accessibility_declaration_date))
                <li>Deklarację sporządzono dnia: <strong>{{ $fmt($settings->accessibility_declaration_date) }}</strong>.</li>
            @endif
            <li>Deklarację sporządzono na podstawie {{ \App\Models\SiteSetting::ACCESSIBILITY_REVIEW_METHODS[$settings->accessibility_review_method] ?? \App\Models\SiteSetting::ACCESSIBILITY_REVIEW_METHODS['self'] }}.</li>
        </ul>

        {{-- Skróty klawiaturowe --}}
        <h2 class="mt-10 mb-3 text-xl font-bold text-ink">Skróty klawiaturowe</h2>
        <p class="text-ink">Na stronie internetowej można korzystać ze standardowych skrótów klawiaturowych przeglądarki.</p>

        {{-- Informacje zwrotne i dane kontaktowe --}}
        <h2 class="mt-10 mb-3 text-xl font-bold text-ink">Informacje zwrotne i dane kontaktowe</h2>
        <p class="text-ink">
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
        <p class="mt-3 text-ink">Tą samą drogą — lub przez formularz poniżej — można składać wnioski o udostępnienie informacji niedostępnej oraz żądania zapewnienia dostępności.</p>
        <p class="mt-3 text-muted">
            Każdy ma prawo wystąpić z żądaniem zapewnienia dostępności cyfrowej strony internetowej, aplikacji mobilnej lub ich elementu.
            Żądanie powinno zawierać dane osoby zgłaszającej, wskazanie, o którą stronę lub element chodzi, oraz sposób kontaktu.
            Podmiot publiczny zrealizuje żądanie bez zbędnej zwłoki, nie później niż w ciągu 7 dni od dnia wystąpienia z żądaniem.
            Jeżeli dotrzymanie tego terminu nie jest możliwe, poinformujemy o tym wnoszącego oraz o nowym terminie — nie dłuższym niż 2 miesiące.
        </p>

        {{-- Procedura odwoławcza --}}
        <h2 class="mt-10 mb-3 text-xl font-bold text-ink">Postępowanie odwoławcze</h2>
        <p class="text-ink">
            W przypadku odmowy zapewnienia dostępności można złożyć skargę na sposób rozpatrzenia zgłoszenia. Po wyczerpaniu tej procedury
            można również złożyć wniosek do <a href="https://www.rpo.gov.pl" target="_blank" rel="noopener" class="font-bold text-brand hover:text-brand-dark">Rzecznika Praw Obywatelskich</a>.
        </p>

        {{-- Dostępność architektoniczna --}}
        @if (filled($settings->accessibility_architectural))
            <h2 class="mt-10 mb-3 text-xl font-bold text-ink">Dostępność architektoniczna</h2>
            <div class="prose mt-1 max-w-none text-muted">{!! nl2br(e($settings->accessibility_architectural)) !!}</div>
        @endif

        {{-- Formularz zgłaszania barier --}}
        <div id="zglos-bariere" class="mt-12 rounded-xl border-2 border-brand-light bg-brand-light/40 p-6 sm:p-8">
            <h2 class="flex items-center gap-2 text-xl font-bold text-ink">
                <i class="fa-solid fa-universal-access text-brand" aria-hidden="true"></i> Zgłoś problem z dostępnością
            </h2>
            <p class="mt-2 text-sm text-muted">Napotkałeś barierę — element, którego nie da się odczytać, obsłużyć klawiaturą albo zrozumieć? Daj nam znać, a naprawimy to bez zbędnej zwłoki.</p>

            @if (session('accessibility_reported'))
                <div class="mt-5 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700" role="status">
                    Dziękujemy za zgłoszenie. Odpowiemy najpóźniej w ciągu 7 dni.
                </div>
            @endif

            <form method="POST" action="{{ route('accessibility.report') }}" class="mt-5 space-y-5">
                @csrf

                <div class="hidden" aria-hidden="true">
                    <label for="website">Zostaw to pole puste</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="ar_name" class="mb-1 block text-sm font-bold">Imię i nazwisko <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input type="text" id="ar_name" name="name" value="{{ old('name') }}"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="ar_email" class="mb-1 block text-sm font-bold">E-mail (do odpowiedzi) <span aria-hidden="true" class="text-red-600">*</span></label>
                        <input type="email" id="ar_email" name="email" value="{{ old('email') }}" required
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="ar_page_url" class="mb-1 block text-sm font-bold">Której strony lub elementu dotyczy problem? <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <input type="text" id="ar_page_url" name="page_url" value="{{ old('page_url') }}" placeholder="np. adres podstrony albo nazwa przycisku"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('page_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="ar_message" class="mb-1 block text-sm font-bold">Opis problemu <span aria-hidden="true" class="text-red-600">*</span></label>
                    <textarea id="ar_message" name="message" rows="5" required
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('message') }}</textarea>
                    @error('message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="flex items-start gap-2">
                        <input type="checkbox" name="rodo_consent" value="1" required {{ old('rodo_consent') ? 'checked' : '' }}
                            class="mt-1 flex-none rounded border-gray-300 text-brand focus:ring-brand">
                        <span class="text-sm text-muted">
                            Wyrażam zgodę na przetwarzanie moich danych osobowych (adresu e-mail) w celu udzielenia odpowiedzi na zgłoszenie, zgodnie z
                            <a href="{{ route('page.show', 'polityka-prywatnosci') }}" class="font-bold text-brand hover:text-brand-dark">Polityką prywatności</a>.
                            <span aria-hidden="true" class="font-bold text-red-600">*</span>
                        </span>
                    </label>
                    @error('rodo_consent') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="rounded-lg bg-brand px-6 py-3 font-bold text-white hover:bg-brand-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    Wyślij zgłoszenie
                </button>
            </form>
        </div>
    </section>
@endsection
