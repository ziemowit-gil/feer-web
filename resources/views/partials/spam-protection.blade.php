{{--
    Ochrona antyspamowa formularzy publicznych. Trzy elementy:
     1. pole-pułapka (honeypot) — ukryte przez display:none, więc klawiatura
        i czytniki ekranu go nie widzą, a boty wypełniają je automatycznie,
     2. zaszyfrowany żeton z czasem wygenerowania strony i wynikiem zadania,
     3. proste zadanie tekstowe (dodawanie) — świadomie zamiast obrazkowej
        CAPTCHY, która jest barierą dostępności (WCAG 1.1.1). Pytanie jest
        zwykłym tekstem, a odpowiedź można wpisać cyfrą albo słownie.

    Sprawdzane po stronie serwera przez App\Support\SpamGuard.

    Parametry: $spamScope (unikalny sufiks id, gdy na stronie jest wiele formularzy).
--}}
@php
    $spamScope     = $spamScope ?? 'form';
    $spamChallenge = \App\Support\SpamGuard::challenge();
    $spamAnswerId  = 'spam-answer-' . $spamScope;
    $spamHelpId    = $spamAnswerId . '-help';
    $spamHasError  = $errors->has('spam');
@endphp

<div class="hidden" aria-hidden="true" style="display:none!important">
    <label for="{{ \App\Support\SpamGuard::HONEYPOT_FIELD }}-{{ $spamScope }}">Zostaw to pole puste</label>
    <input type="text"
        id="{{ \App\Support\SpamGuard::HONEYPOT_FIELD }}-{{ $spamScope }}"
        name="{{ \App\Support\SpamGuard::HONEYPOT_FIELD }}"
        value=""
        tabindex="-1"
        autocomplete="off">
</div>

<input type="hidden" name="{{ \App\Support\SpamGuard::TOKEN_FIELD }}" value="{{ $spamChallenge['token'] }}">

<div class="space-y-1">
    <label for="{{ $spamAnswerId }}" class="block text-sm font-semibold text-ink">
        Zadanie antyspamowe: {{ $spamChallenge['question'] }}
        <span aria-hidden="true" class="ml-0.5 font-bold text-red-500">*</span>
        <span class="sr-only">(wymagane)</span>
    </label>

    <p id="{{ $spamHelpId }}" class="text-xs text-muted">
        Pytanie chroni formularz przed automatycznym spamem. Wynik możesz wpisać cyfrą lub słownie.
    </p>

    <input id="{{ $spamAnswerId }}"
        name="{{ \App\Support\SpamGuard::ANSWER_FIELD }}"
        type="text"
        inputmode="numeric"
        autocomplete="off"
        required
        aria-required="true"
        aria-describedby="{{ $spamHelpId }}"
        @if ($spamHasError) aria-invalid="true" @endif
        class="w-24 rounded-lg border px-3 py-2 text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1 {{ $spamHasError ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-white' }}">

    @error('spam')
        <p class="flex items-center gap-1 text-sm font-medium text-red-700" role="alert">
            <i class="fa-solid fa-circle-exclamation shrink-0" aria-hidden="true"></i>
            {{ $message }}
        </p>
    @enderror
</div>
