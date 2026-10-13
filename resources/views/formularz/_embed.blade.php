@php
    $fs          = $form->slug;
    // Tytuł widoczny na stronie — osadzający może go nadpisać (np. lustro formularza SZO w ofercie wolontariatu).
    $formTitle   = $title ?? $form->title;
    $fields      = $form->normalizedFields();
    $isThisForm  = session('_form_slug') === $fs;
    $showSuccess = $isThisForm && session('success');
    $showErrors  = $isThisForm && $errors->any();
    $errorSummaryId = 'form-errors-' . $fs;
    // Szablon FEER: formularz bez ramek i linii — bez karty wokół, pola wypełnione jasnym tłem, zaokrąglone;
    // fokus sygnalizuje wyraźny pierścień w kolorze marki (2.4.7), błąd — tło i komunikat z ikoną (nie sam kolor).
    $feerForm    = ($siteSettings->site_template ?? 'default') === 'feer';
    $fLabel      = $feerForm ? 'block text-base font-bold text-ink' : 'block text-sm font-semibold text-ink';
    $fLegend     = $feerForm ? 'text-base font-bold text-ink' : 'text-sm font-semibold text-ink';
    // W FEER wygląd pól i przycisków daje zwykły CSS (klasy ff-*, blok <style> niżej) — nie zależy od przebudowy Tailwinda na serwerze.
    $fPad        = $feerForm ? '' : 'px-3 py-2';
    $hasFile     = collect($fields)->contains(fn ($f) => ($f['type'] ?? '') === 'file');
    $hasInputs   = collect($fields)->contains(fn ($f) => \App\Models\FormDefinition::isInput($f['type'] ?? 'text'));
    $privacyUrl  = \Illuminate\Support\Facades\Route::has('page.show') ? url('/polityka-prywatnosci') : null;
    // Logika warunkowa: uid pola źródłowego → jego klucz (atrybuty data-ff-if dla skryptu).
    $uidToKey    = collect($fields)->filter(fn ($f) => filled($f['uid'] ?? null))->mapWithKeys(fn ($f) => [$f['uid'] => $f['key']])->all();
    // Kroki: pola typu „Nowy krok” dzielą formularz na etapy. Bez JS wszystkie kroki są widoczne naraz.
    $stepFields  = array_values(array_filter($fields, fn ($f) => ($f['type'] ?? '') === 'step'));
    $hasSteps    = count($stepFields) > 0;
    $startsWithStep = $hasSteps && (($fields[0]['type'] ?? '') === 'step');
    $stepTitles  = array_merge($startsWithStep ? [] : [''], array_map(fn ($f) => (string) ($f['label'] ?? ''), $stepFields));
    $stepCount   = count($stepTitles);
    $stepNo      = 0;
@endphp

@if ($feerForm)
    @once
        <style>
            .ff-stack > * + * { margin-top: 1.75rem; }
            .ff-input { display: block; width: 100%; border: 0; border-radius: .75rem; background-color: #e3e7ee; padding: .875rem 1rem; font-size: 1rem; line-height: 1.5; color: #1d1d1a; box-shadow: none; transition: background-color .15s; }
            .ff-input::placeholder { color: #4b5563; opacity: 1; }
            .ff-input:hover { background-color: #d5dbe5; }
            .ff-input:focus { background-color: #fff; outline: 3px solid #1752bf; outline-offset: 2px; box-shadow: none; }
            .ff-input[aria-invalid="true"] { background-color: #fef2f2; }
            .ff-input[aria-invalid="true"]:focus { outline-color: #b91c1c; }
            select.ff-input { padding-right: 2.75rem; }
            textarea.ff-input { min-height: 8rem; }
            .ff-krs { display: flex; flex-wrap: wrap; gap: .5rem; align-items: stretch; }
            .ff-krs .ff-input { flex: 1 1 14rem; min-width: 0; width: auto; letter-spacing: .05em; font-variant-numeric: tabular-nums; }
            .ff-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 3rem; border: 0; border-radius: .75rem; padding: 0 1.25rem; font-size: 1rem; font-weight: 700; cursor: pointer; transition: background-color .15s; }
            .ff-btn[hidden] { display: none; }
            .ff-btn:focus-visible { outline: 3px solid #1752bf; outline-offset: 2px; }
            .ff-btn-dark { background-color: #1d1d1a; color: #fff; }
            .ff-btn-dark:hover { background-color: #1752bf; }
            .ff-btn-primary { background-color: #1e6dff; color: #fff; min-height: 3.25rem; padding: 0 2rem; }
            .ff-btn-primary:hover { background-color: #1752bf; }
            .ff-btn-soft { background-color: #e3e7ee; color: #1d1d1a; }
            .ff-btn-soft:hover { background-color: #d5dbe5; }
            .ff-tile { border-radius: .75rem; background-color: #e3e7ee; }
            .ff-tile:hover { background-color: #d5dbe5; }
            .ff-tile:has(:checked) { background-color: #1e6dff; color: #fff; }
            .ff-tile:has(:focus-visible) { outline: 3px solid #1752bf; outline-offset: 2px; }
            .ff-choice { width: 1.25rem; height: 1.25rem; accent-color: #1e6dff; }
            .ff-indent { padding-left: 1.75rem; }
            .ff-options > * + * { margin-top: .5rem; }
        </style>
    @endonce
@endif
@php
    // hideHeading: osadzający ma już własny nagłówek nad formularzem (np. „Napisz do nas” na stronie kontaktu).
    $hideHeading = $hideHeading ?? false;
@endphp
<section id="formularz-{{ $fs }}"
    @if ($hideHeading) aria-label="{{ $formTitle }}" @else aria-labelledby="form-heading-{{ $fs }}" @endif
    class="not-prose {{ $hideHeading ? 'mt-4' : 'my-10' }} {{ $feerForm || $hideHeading ? '' : 'rounded-xl border border-gray-200 bg-white p-6 shadow-sm' }}">

    @unless ($hideHeading)
        @php $headingTag = in_array($headingTag ?? 'h2', ['h2', 'h3'], true) ? ($headingTag ?? 'h2') : 'h2'; @endphp
        <{{ $headingTag }} id="form-heading-{{ $fs }}" class="mb-1 font-bold text-ink {{ $feerForm ? 'text-2xl sm:text-3xl' : 'text-xl' }}">
            {{ $formTitle }}
        </{{ $headingTag }}>
    @endunless
    @if ($form->description)
        <p class="mb-6 whitespace-pre-line text-muted {{ $feerForm ? 'max-w-2xl text-base' : 'text-sm' }}">{{ $form->description }}</p>
    @elseif (! $hideHeading)
        <div class="mb-5"></div>
    @endif

    @php
        $availability = $form->availability();
        $closedNow = ! $availability['open'];
    @endphp

    {{-- ── Potwierdzenie wysłania ─────────────────────────────────────────── --}}
    @if ($showSuccess)
        <div role="status" aria-live="polite" aria-atomic="true"
            class="flex items-start gap-3 rounded-lg bg-green-50 px-5 py-4 text-sm text-green-800 {{ $feerForm ? '' : 'border border-green-200' }}">
            <i class="fa-solid fa-circle-check mt-0.5 shrink-0 text-green-600" aria-hidden="true"></i>
            <p>{{ session('success') }}</p>
        </div>
    @elseif ($closedNow)
        {{-- Termin minął, jeszcze nie ruszył albo limit wyczerpany — zamiast formularza. --}}
        <div role="status" class="flex items-start gap-3 rounded-lg bg-gray-100 px-5 py-4 text-sm text-ink">
            <i class="fa-solid {{ $availability['reason'] === 'not_yet' ? 'fa-hourglass-start' : 'fa-lock' }} mt-0.5 shrink-0 text-muted" aria-hidden="true"></i>
            <p>{{ $availability['message'] }}</p>
        </div>
    @else

    @if (! empty($form->settings['show_remaining']) && ($availability['remaining'] !== null || $availability['until']))
        <p class="mb-5 flex flex-wrap gap-x-5 gap-y-1 rounded-md bg-brand-light px-4 py-3 text-sm text-ink">
            @if ($availability['remaining'] !== null)
                <span><i class="fa-solid fa-user-check mr-1 text-brand-dark" aria-hidden="true"></i>Wolne miejsca: <strong>{{ $availability['remaining'] }}</strong></span>
            @endif
            @if ($availability['until'])
                <span><i class="fa-solid fa-clock mr-1 text-brand-dark" aria-hidden="true"></i>Zgłoszenia do: <strong>{{ $availability['until']->locale('pl')->translatedFormat('j F Y, H:i') }}</strong></span>
            @endif
        </p>
    @endif

    {{-- ── Podsumowanie błędów (WCAG 3.3.1 / 3.3.3) ──────────────────────── --}}
    @if ($showErrors)
        <div id="{{ $errorSummaryId }}" role="alert" aria-live="assertive" aria-atomic="true"
            tabindex="-1"
            class="mb-5 rounded-lg bg-red-50 px-5 py-4 text-sm text-red-800 {{ $feerForm ? '' : 'border border-red-200' }}">
            <p class="flex items-center gap-2 font-bold">
                <i class="fa-solid fa-triangle-exclamation shrink-0" aria-hidden="true"></i>
                Proszę poprawić następujące błędy:
            </p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @error('spam')
                    <li>{{ $message }}</li>
                @enderror
                @error('closed')
                    <li>{{ $message }}</li>
                @enderror
                @foreach ($fields as $field)
                    @error("data.{$field['key']}")
                        <li>
                            <a href="#field-{{ $fs }}-{{ $field['key'] }}"
                                class="underline hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600">
                                {{ $field['label'] }}: {{ $message }}
                            </a>
                        </li>
                    @enderror
                @endforeach
            </ul>
        </div>
        {{-- Fokus na podsumowaniu: czytnik ekranu odczyta je od razu, a linki prowadzą do pól z błędami. --}}
        <script>(function () { var el = document.getElementById(@js($errorSummaryId)); if (el) { el.focus(); el.scrollIntoView({ block: 'center' }); } })();</script>
    @endif

    {{-- ── Formularz ───────────────────────────────────────────────────────── --}}
    <form method="POST"
        action="{{ route('formularz.store', $fs) }}"
        aria-label="{{ $formTitle }}"
        @if ($hasFile) enctype="multipart/form-data" @endif
        @if ($showErrors) aria-describedby="{{ $errorSummaryId }}" @endif
        novalidate
        class="{{ $feerForm ? 'ff-stack' : 'space-y-5' }}">
        @csrf

        @if ($hasInputs)
            <p class="text-xs text-muted">
                Pola oznaczone <span aria-hidden="true" class="font-bold text-red-500">*</span>
                <span class="sr-only">gwiazdką</span> są wymagane.
            </p>
        @endif

        @if ($hasSteps)
            {{-- Pasek postępu (pokazuje go skrypt; bez JS kroki są po prostu kolejnymi sekcjami). --}}
            <ol class="mb-2 flex flex-wrap gap-x-4 gap-y-2 text-sm" data-ff-progress hidden aria-label="Kroki formularza">
                @foreach ($stepTitles as $i => $t)
                    <li class="flex items-center gap-2 text-muted" data-ff-progress-item>
                        <span class="flex h-7 w-7 flex-none items-center justify-center rounded-full text-xs font-bold {{ $feerForm ? 'bg-gray-200' : 'border-2 border-solid border-gray-300 bg-white' }}" data-ff-off="{{ $feerForm ? 'bg-gray-200' : 'bg-white' }}" aria-hidden="true">{{ $i + 1 }}</span>
                        <span>{{ $t !== '' ? $t : 'Krok ' . ($i + 1) }}</span>
                    </li>
                @endforeach
            </ol>
            @unless ($startsWithStep)
                @php $stepNo = 1; @endphp
                <div class="space-y-5" data-ff-step="1" role="group" aria-labelledby="ff-{{ $fs }}-step-1">
                    <h3 id="ff-{{ $fs }}-step-1" class="text-lg font-bold text-ink focus:outline-none" tabindex="-1" data-ff-step-title>Krok 1 z {{ $stepCount }}</h3>
            @endunless
        @endif

        @foreach ($fields as $field)
            @if (($field['type'] ?? '') === 'step')
                @if ($stepNo > 0)
                    </div>
                @endif
                @php $stepNo++; @endphp
                <div class="space-y-5" data-ff-step="{{ $stepNo }}" role="group" aria-labelledby="ff-{{ $fs }}-step-{{ $stepNo }}">
                    <h3 id="ff-{{ $fs }}-step-{{ $stepNo }}" class="text-lg font-bold text-ink focus:outline-none" tabindex="-1" data-ff-step-title>
                        <span class="block text-xs font-bold uppercase tracking-wide text-muted">Krok {{ $stepNo }} z {{ $stepCount }}</span>
                        {{ $field['label'] }}
                    </h3>
                    @if ($field['help_text'] ?? null)
                        <p class="-mt-3 text-sm text-muted">{{ $field['help_text'] }}</p>
                    @endif
                @continue
            @endif
            @php
                $ffIf = $uidToKey[$field['show_if_uid'] ?? ''] ?? null;
            @endphp
            <div data-ff-field="{{ $field['key'] }}" @if ($ffIf && $ffIf !== $field['key']) data-ff-if="{{ $ffIf }}" data-ff-val="{{ $field['show_if_value'] ?? '' }}" @endif @if (filled($field['krs_fill'] ?? null)) data-krs-fill="{{ $field['krs_fill'] }}" @endif>
            @php
                $fid       = "field-{$fs}-{$field['key']}";
                $fname     = "data[{$field['key']}]";
                $type      = $field['type'] ?? 'text';
                $required  = $field['required'] ?? false;
                $hasError  = $showErrors && $errors->has("data.{$field['key']}");
                $helpId    = $fid . '-help';
                $errorId   = $fid . '-error';
                $oldVal    = old("data.{$field['key']}");

                $describedBy = array_filter([
                    ($field['help_text'] ?? null) ? $helpId  : null,
                    $hasError                     ? $errorId : null,
                ]);
                $describedByStr = implode(' ', $describedBy);

                $baseInputClass = $feerForm
                    ? 'ff-input'
                    : 'w-full rounded-lg border text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1 '
                        . ($hasError ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-white');
            @endphp

            @if ($type === 'radio')
                {{-- ── Radio group (fieldset + legend) ──────────────────────── --}}
                <fieldset class="space-y-1.5">
                    <legend class="{{ $fLegend }}">
                        {{ $field['label'] }}
                        @if ($required)
                            <span aria-hidden="true" class="ml-0.5 font-bold text-red-500">*</span>
                            <span class="sr-only">(wymagane)</span>
                        @endif
                    </legend>

                    @if ($field['help_text'] ?? null)
                        <p id="{{ $helpId }}" class="text-xs text-muted">{{ $field['help_text'] }}</p>
                    @endif

                    <div class="space-y-1.5">
                        @foreach (array_map('trim', explode(',', $field['options'] ?? '')) as $opt)
                            @if (filled($opt))
                                @php $optId = $fid . '-' . \Illuminate\Support\Str::slug($opt, '_'); @endphp
                                <label for="{{ $optId }}" class="flex cursor-pointer items-center gap-2 text-sm">
                                    <input id="{{ $optId }}"
                                        type="radio"
                                        name="{{ $fname }}"
                                        value="{{ $opt }}"
                                        {{ $required ? 'required aria-required="true"' : '' }}
                                        {{ $oldVal === $opt ? 'checked' : '' }}
                                        @if ($hasError) aria-invalid="true" @endif
                                        @if ($describedByStr) aria-describedby="{{ $describedByStr }}" @endif
                                        class="{{ $feerForm ? 'ff-choice' : 'h-4 w-4' }} shrink-0 border-gray-300 text-brand focus:ring-brand {{ $hasError ? 'border-red-400' : '' }}">
                                    {{ $opt }}
                                </label>
                            @endif
                        @endforeach
                    </div>

                    @if ($hasError)
                        <p id="{{ $errorId }}" class="flex items-center gap-1 text-sm font-medium text-red-700">
                            <i class="fa-solid fa-circle-exclamation shrink-0" aria-hidden="true"></i>
                            @error("data.{$field['key']}") {{ $message }} @enderror
                        </p>
                    @endif
                </fieldset>

            @elseif ($type === 'checkbox')
                {{-- ── Checkbox / zgoda ─────────────────────────────────────── --}}
                <div class="space-y-1">
                    <div class="flex items-start gap-2">
                        <input id="{{ $fid }}"
                            type="checkbox"
                            name="{{ $fname }}"
                            value="1"
                            {{ $required ? 'required aria-required="true"' : '' }}
                            {{ $oldVal ? 'checked' : '' }}
                            @if ($hasError) aria-invalid="true" @endif
                            @if ($describedByStr) aria-describedby="{{ $describedByStr }}" @endif
                            class="mt-0.5 {{ $feerForm ? 'ff-choice' : 'h-4 w-4' }} shrink-0 cursor-pointer rounded border-gray-300 text-brand focus:ring-brand {{ $hasError ? 'border-red-400' : '' }}">
                        <label for="{{ $fid }}" class="cursor-pointer text-sm text-ink">
                            {{ $field['label'] }}
                            @if ($required)
                                <span aria-hidden="true" class="ml-0.5 font-bold text-red-500">*</span>
                                <span class="sr-only">(wymagane)</span>
                            @endif
                        </label>
                    </div>

                    @if ($field['help_text'] ?? null)
                        <p id="{{ $helpId }}" class="{{ $feerForm ? 'ff-indent' : 'pl-6' }} text-xs text-muted">{{ $field['help_text'] }}</p>
                    @endif

                    @if ($hasError)
                        <p id="{{ $errorId }}" class="flex items-center gap-1 {{ $feerForm ? 'ff-indent' : 'pl-6' }} text-sm font-medium text-red-700">
                            <i class="fa-solid fa-circle-exclamation shrink-0" aria-hidden="true"></i>
                            @error("data.{$field['key']}") {{ $message }} @enderror
                        </p>
                    @endif
                </div>

            @elseif ($type === 'heading')
                {{-- ── Nagłówek / opis sekcji (bez wartości) ─────────────────── --}}
                <div class="{{ $feerForm ? 'pt-6 first:pt-0' : 'border-t border-solid border-gray-200 pt-5 first:border-t-0 first:pt-0' }}">
                    <h3 class="font-bold text-ink {{ $feerForm ? 'text-xl' : 'text-lg' }}">{{ $field['label'] }}</h3>
                    @if ($field['help_text'] ?? null)
                        <p class="mt-1 text-sm text-muted">{{ $field['help_text'] }}</p>
                    @endif
                </div>

            @elseif ($type === 'link')
                {{-- ── Tekst z przyciskiem: przejście na inny adres (bez wartości) ── --}}
                @php $linkUrl = \App\Models\FormDefinition::safeUrl($field['url'] ?? null); $linkNewTab = ! empty($field['new_tab']); @endphp
                <div class="rounded-md bg-brand-light p-5">
                    @if ($field['help_text'] ?? null)
                        <p class="whitespace-pre-line text-ink">{{ $field['help_text'] }}</p>
                    @endif
                    @if ($linkUrl)
                        <a href="{{ $linkUrl }}" @if ($linkNewTab) target="_blank" rel="noopener" @endif
                            class="{{ ($field['help_text'] ?? null) ? 'mt-3' : '' }} inline-flex min-h-11 items-center gap-2 rounded-md bg-brand-dark px-5 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                            {{ $field['label'] }} <i class="fa-solid {{ $linkNewTab ? 'fa-arrow-up-right-from-square' : 'fa-arrow-right' }}" aria-hidden="true"></i>
                            @if ($linkNewTab)<span class="sr-only"> (otwiera się w nowej karcie)</span>@endif
                        </a>
                    @endif
                </div>

            @elseif ($type === 'checkboxes' || $type === 'rating')
                {{-- ── Wielokrotny wybór / ocena 1–5 (fieldset + legend) ─────── --}}
                @php
                    $isRating = $type === 'rating';
                    $choices  = $isRating ? ['1', '2', '3', '4', '5'] : \App\Models\FormDefinition::fieldOptions($field);
                    $oldArr   = $isRating ? [(string) $oldVal] : array_map('strval', (array) ($oldVal ?? []));
                @endphp
                <fieldset class="space-y-1.5" @if ($describedByStr) aria-describedby="{{ $describedByStr }}" @endif @if ($required && ! $isRating) data-ff-group-required @endif>
                    <legend class="{{ $fLegend }}">
                        {{ $field['label'] }}
                        @if ($required)
                            <span aria-hidden="true" class="ml-0.5 font-bold text-red-500">*</span>
                            <span class="sr-only">(wymagane)</span>
                        @endif
                    </legend>

                    @if ($field['help_text'] ?? null)
                        <p id="{{ $helpId }}" class="text-xs text-muted">{{ $field['help_text'] }}</p>
                    @endif

                    <div class="{{ $isRating ? 'flex flex-wrap gap-2 pt-1' : 'space-y-1.5' }}">
                        @foreach ($choices as $opt)
                            @php $optId = $fid . '-' . \Illuminate\Support\Str::slug($opt, '_'); @endphp
                            @if ($isRating)
                                <label for="{{ $optId }}" class="flex h-11 w-11 cursor-pointer items-center justify-center text-base font-bold text-ink transition {{ $feerForm ? 'ff-tile' : 'rounded-md border-2 border-solid border-gray-300 bg-white hover:border-brand has-[:checked]:border-brand' }} has-[:checked]:bg-brand has-[:checked]:text-white has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand has-[:focus-visible]:ring-offset-2">
                                    <input id="{{ $optId }}" type="radio" name="{{ $fname }}" value="{{ $opt }}" class="sr-only"
                                        {{ $required ? 'required' : '' }} {{ in_array($opt, $oldArr, true) ? 'checked' : '' }}
                                        @if ($hasError) aria-invalid="true" @endif>
                                    {{ $opt }}<span class="sr-only"> z 5</span>
                                </label>
                            @else
                                <label for="{{ $optId }}" class="flex cursor-pointer items-center gap-2 text-sm">
                                    <input id="{{ $optId }}" type="checkbox" name="{{ $fname }}[]" value="{{ $opt }}"
                                        {{ in_array($opt, $oldArr, true) ? 'checked' : '' }}
                                        @if ($hasError) aria-invalid="true" @endif
                                        class="{{ $feerForm ? 'ff-choice' : 'h-4 w-4' }} shrink-0 rounded border-gray-300 text-brand focus:ring-brand {{ $hasError ? 'border-red-400' : '' }}">
                                    {{ $opt }}
                                </label>
                            @endif
                        @endforeach
                    </div>
                    @if ($isRating)
                        <p class="text-xs text-muted" aria-hidden="true">1 — najniżej, 5 — najwyżej</p>
                    @endif

                    @if ($hasError)
                        <p id="{{ $errorId }}" class="flex items-center gap-1 text-sm font-medium text-red-700">
                            <i class="fa-solid fa-circle-exclamation shrink-0" aria-hidden="true"></i>
                            @error("data.{$field['key']}") {{ $message }} @enderror
                        </p>
                    @endif
                </fieldset>

            @elseif ($type === 'consent')
                {{-- ── Zgoda RODO z linkiem do polityki prywatności ──────────── --}}
                <div class="space-y-1">
                    <div class="flex items-start gap-2">
                        <input id="{{ $fid }}" type="checkbox" name="{{ $fname }}" value="1"
                            {{ $required ? 'required aria-required="true"' : '' }}
                            {{ $oldVal ? 'checked' : '' }}
                            @if ($hasError) aria-invalid="true" @endif
                            @if ($describedByStr) aria-describedby="{{ $describedByStr }}" @endif
                            class="mt-0.5 {{ $feerForm ? 'ff-choice' : 'h-4 w-4' }} shrink-0 cursor-pointer rounded border-gray-300 text-brand focus:ring-brand {{ $hasError ? 'border-red-400' : '' }}">
                        <label for="{{ $fid }}" class="cursor-pointer text-sm text-ink">
                            {{ $field['label'] }}
                            @if ($privacyUrl)
                                Szczegóły w <a href="{{ $privacyUrl }}" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline" target="_blank" rel="noopener">Polityce prywatności<span class="sr-only"> (otwiera się w nowej karcie)</span></a>.
                            @endif
                            @if ($required)
                                <span aria-hidden="true" class="ml-0.5 font-bold text-red-500">*</span>
                                <span class="sr-only">(wymagane)</span>
                            @endif
                        </label>
                    </div>
                    @if ($field['help_text'] ?? null)
                        <p id="{{ $helpId }}" class="{{ $feerForm ? 'ff-indent' : 'pl-6' }} text-xs text-muted">{{ $field['help_text'] }}</p>
                    @endif
                    @if ($hasError)
                        <p id="{{ $errorId }}" class="flex items-center gap-1 {{ $feerForm ? 'ff-indent' : 'pl-6' }} text-sm font-medium text-red-700">
                            <i class="fa-solid fa-circle-exclamation shrink-0" aria-hidden="true"></i>
                            @error("data.{$field['key']}") {{ $message }} @enderror
                        </p>
                    @endif
                </div>

            @elseif ($type === 'file')
                {{-- ── Załącznik ─────────────────────────────────────────────── --}}
                @php
                    $accept = \App\Models\FormDefinition::fileAccept($field);
                    $maxMb  = \App\Models\FormDefinition::fileMaxMb($field);
                    $fileHintId = $fid . '-limits';
                    $fileDescribed = trim($describedByStr . ' ' . $fileHintId);
                @endphp
                <div class="space-y-1">
                    <label for="{{ $fid }}" class="{{ $fLabel }}">
                        {{ $field['label'] }}
                        @if ($required)
                            <span aria-hidden="true" class="ml-0.5 font-bold text-red-500">*</span>
                            <span class="sr-only">(wymagane)</span>
                        @endif
                    </label>
                    @if ($field['help_text'] ?? null)
                        <p id="{{ $helpId }}" class="text-xs text-muted">{{ $field['help_text'] }}</p>
                    @endif
                    <input id="{{ $fid }}" name="{{ $fname }}" type="file"
                        accept="{{ collect($accept)->map(fn ($e) => '.' . $e)->implode(',') }}"
                        {{ $required ? 'required aria-required="true"' : '' }}
                        @if ($hasError) aria-invalid="true" @endif
                        aria-describedby="{{ $fileDescribed }}"
                        class="{{ $baseInputClass }} {{ $fPad }} file:mr-3 file:rounded-md file:border-0 file:bg-brand-light file:px-3 file:py-1.5 file:font-bold file:text-brand-dark">
                    <p id="{{ $fileHintId }}" class="text-xs text-muted">Dozwolone: {{ strtoupper(implode(', ', $accept)) }}. Maksymalnie {{ $maxMb }} MB.@if ($showErrors) Po błędzie plik trzeba wybrać ponownie.@endif</p>
                    @if ($hasError)
                        <p id="{{ $errorId }}" class="flex items-center gap-1 text-sm font-medium text-red-700">
                            <i class="fa-solid fa-circle-exclamation shrink-0" aria-hidden="true"></i>
                            @error("data.{$field['key']}") {{ $message }} @enderror
                        </p>
                    @endif
                </div>

            @elseif ($type === 'coordinator')
                {{-- ── Osoba kontaktowa: zwykła lista wyboru koordynatorów działań (wiadomość trafia do wybranej osoby) ── --}}
                @php $coordinatorOptions = \App\Support\ContactCoordinators::all(); @endphp
                @if ($coordinatorOptions->isNotEmpty())
                <div class="space-y-1">
                    <label for="{{ $fid }}" class="{{ $fLabel }}">
                        {{ $field['label'] }}
                        @if ($required)
                            <span aria-hidden="true" class="ml-0.5 font-bold text-red-500">*</span>
                            <span class="sr-only">(wymagane)</span>
                        @endif
                    </label>

                    @if ($field['help_text'] ?? null)
                        <p id="{{ $helpId }}" class="text-xs text-muted">{{ $field['help_text'] }}</p>
                    @endif

                    <select id="{{ $fid }}"
                        name="{{ $fname }}"
                        {{ $required ? 'required aria-required="true"' : '' }}
                        @if ($hasError) aria-invalid="true" @endif
                        @if ($describedByStr) aria-describedby="{{ $describedByStr }}" @endif
                        class="{{ $baseInputClass }} {{ $feerForm ? '' : 'py-2 pl-3 pr-8' }}">
                        <option value="">{{ $required ? '— wybierz —' : '— ogólny kontakt —' }}</option>
                        @foreach ($coordinatorOptions as $c)
                            <option value="{{ $c['email'] }}" {{ $oldVal === $c['email'] ? 'selected' : '' }}>{{ $c['name'] }}{{ $c['project'] !== $c['name'] ? ' — '.$c['project'] : '' }}</option>
                        @endforeach
                    </select>

                    @if ($hasError)
                        <p id="{{ $errorId }}" class="flex items-center gap-1.5 text-sm text-red-700">
                            <i class="fa-solid fa-circle-exclamation shrink-0" aria-hidden="true"></i>
                            {{ $errors->first("data.{$field['key']}") }}
                        </p>
                    @endif
                </div>
                @endif

            @elseif ($type === 'select')
                {{-- ── Select ───────────────────────────────────────────────── --}}
                <div class="space-y-1">
                    <label for="{{ $fid }}" class="{{ $fLabel }}">
                        {{ $field['label'] }}
                        @if ($required)
                            <span aria-hidden="true" class="ml-0.5 font-bold text-red-500">*</span>
                            <span class="sr-only">(wymagane)</span>
                        @endif
                    </label>

                    @if ($field['help_text'] ?? null)
                        <p id="{{ $helpId }}" class="text-xs text-muted">{{ $field['help_text'] }}</p>
                    @endif

                    <select id="{{ $fid }}"
                        name="{{ $fname }}"
                        {{ $required ? 'required aria-required="true"' : '' }}
                        @if ($hasError) aria-invalid="true" @endif
                        @if ($describedByStr) aria-describedby="{{ $describedByStr }}" @endif
                        class="{{ $baseInputClass }} {{ $feerForm ? '' : 'py-2 pl-3 pr-8' }}">
                        <option value="">— wybierz —</option>
                        @foreach (array_map('trim', explode(',', $field['options'] ?? '')) as $opt)
                            @if (filled($opt))
                                <option value="{{ $opt }}" {{ $oldVal === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endif
                        @endforeach
                    </select>

                    @if ($hasError)
                        <p id="{{ $errorId }}" class="flex items-center gap-1 text-sm font-medium text-red-700">
                            <i class="fa-solid fa-circle-exclamation shrink-0" aria-hidden="true"></i>
                            @error("data.{$field['key']}") {{ $message }} @enderror
                        </p>
                    @endif
                </div>

            @elseif ($type === 'textarea')
                {{-- ── Textarea ─────────────────────────────────────────────── --}}
                <div class="space-y-1">
                    <label for="{{ $fid }}" class="{{ $fLabel }}">
                        {{ $field['label'] }}
                        @if ($required)
                            <span aria-hidden="true" class="ml-0.5 font-bold text-red-500">*</span>
                            <span class="sr-only">(wymagane)</span>
                        @endif
                    </label>

                    @if ($field['help_text'] ?? null)
                        <p id="{{ $helpId }}" class="text-xs text-muted">{{ $field['help_text'] }}</p>
                    @endif

                    <textarea id="{{ $fid }}"
                        name="{{ $fname }}"
                        rows="4"
                        {{ $required ? 'required aria-required="true"' : '' }}
                        placeholder="{{ $field['placeholder'] ?? '' }}"
                        @if ($hasError) aria-invalid="true" @endif
                        @if ($describedByStr) aria-describedby="{{ $describedByStr }}" @endif
                        class="{{ $baseInputClass }} {{ $fPad }}">{{ $oldVal }}</textarea>

                    @if ($hasError)
                        <p id="{{ $errorId }}" class="flex items-center gap-1 text-sm font-medium text-red-700">
                            <i class="fa-solid fa-circle-exclamation shrink-0" aria-hidden="true"></i>
                            @error("data.{$field['key']}") {{ $message }} @enderror
                        </p>
                    @endif
                </div>

            @elseif ($type === 'krs')
                {{-- ── Numer KRS: pobiera dane podmiotu i uzupełnia pola oznaczone w kreatorze ── --}}
                @php $krsStatusId = $fid . '-status'; @endphp
                <div class="space-y-1" data-krs-field data-krs-url="{{ url('/krs') }}">
                    <label for="{{ $fid }}" class="{{ $fLabel }}">
                        {{ $field['label'] }}
                        @if ($required)
                            <span aria-hidden="true" class="ml-0.5 font-bold text-red-500">*</span>
                            <span class="sr-only">(wymagane)</span>
                        @endif
                    </label>

                    <p id="{{ $helpId }}" class="text-xs text-muted">{{ ($field['help_text'] ?? null) ?: 'Wpisz 10 cyfr numeru KRS — dane organizacji uzupełnimy z rejestru. Możesz je potem poprawić.' }}</p>

                    <div class="{{ $feerForm ? 'ff-krs' : 'flex flex-wrap items-stretch gap-2' }}">
                        <input id="{{ $fid }}"
                            name="{{ $fname }}"
                            type="text"
                            inputmode="numeric"
                            autocomplete="off"
                            maxlength="10"
                            pattern="[0-9]{1,10}"
                            value="{{ $oldVal }}"
                            {{ $required ? 'required aria-required="true"' : '' }}
                            placeholder="{{ ($field['placeholder'] ?? null) ?: '0000123456' }}"
                            @if ($hasError) aria-invalid="true" @endif
                            aria-describedby="{{ trim($helpId.' '.$krsStatusId.($hasError ? ' '.$errorId : '')) }}"
                            data-krs-input
                            class="{{ $baseInputClass }} {{ $feerForm ? '' : 'max-w-xs px-3 py-2 font-mono tracking-wider' }}">
                        <button type="button" data-krs-button
                            class="{{ $feerForm ? 'ff-btn ff-btn-dark' : 'inline-flex min-h-11 items-center justify-center gap-2 rounded-md bg-ink px-4 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2' }}">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Pobierz dane z KRS
                        </button>
                    </div>
                    <p id="{{ $krsStatusId }}" class="text-sm text-ink" role="status" aria-live="polite" data-krs-status></p>

                    @if ($hasError)
                        <p id="{{ $errorId }}" class="flex items-center gap-1 text-sm font-medium text-red-700">
                            <i class="fa-solid fa-circle-exclamation shrink-0" aria-hidden="true"></i>
                            @error("data.{$field['key']}") {{ $message }} @enderror
                        </p>
                    @endif
                </div>

            @else
                {{-- ── Wszystkie inne pola: text, email, tel, number, date ───── --}}
                <div class="space-y-1">
                    <label for="{{ $fid }}" class="{{ $fLabel }}">
                        {{ $field['label'] }}
                        @if ($required)
                            <span aria-hidden="true" class="ml-0.5 font-bold text-red-500">*</span>
                            <span class="sr-only">(wymagane)</span>
                        @endif
                    </label>

                    @if ($field['help_text'] ?? null)
                        <p id="{{ $helpId }}" class="text-xs text-muted">{{ $field['help_text'] }}</p>
                    @endif

                    <input id="{{ $fid }}"
                        name="{{ $fname }}"
                        type="{{ $type }}"
                        value="{{ $oldVal }}"
                        {{ $required ? 'required aria-required="true"' : '' }}
                        placeholder="{{ $field['placeholder'] ?? '' }}"
                        @if ($hasError) aria-invalid="true" @endif
                        @if ($describedByStr) aria-describedby="{{ $describedByStr }}" @endif
                        @if ($loop->first && !$showErrors) autofocus @endif
                        class="{{ $baseInputClass }} {{ $fPad }}">

                    @if ($hasError)
                        <p id="{{ $errorId }}" class="flex items-center gap-1 text-sm font-medium text-red-700">
                            <i class="fa-solid fa-circle-exclamation shrink-0" aria-hidden="true"></i>
                            @error("data.{$field['key']}") {{ $message }} @enderror
                        </p>
                    @endif
                </div>
            @endif
            </div>
        @endforeach

        @if ($hasSteps && $stepNo > 0)
            </div>
        @endif

        @if ($hasInputs)
            @include('partials.spam-protection', ['spamScope' => $fs, 'flat' => $feerForm, 'cleantalk' => empty($form->settings['cleantalk_disabled'] ?? null)])

            <div class="flex flex-wrap items-center gap-3 pt-2">
                @if ($hasSteps)
                    <button type="button" data-ff-prev hidden
                        class="{{ $feerForm ? 'ff-btn ff-btn-soft' : 'inline-flex min-h-11 items-center gap-2 rounded-md border-2 border-solid border-gray-300 bg-white px-5 text-sm font-bold text-ink hover:border-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2' }}">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wstecz
                    </button>
                    <button type="button" data-ff-next hidden
                        class="{{ $feerForm ? 'ff-btn ff-btn-dark' : 'inline-flex min-h-11 items-center gap-2 rounded-md bg-ink px-5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2' }}">
                        Dalej <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </button>
                @endif
                <button type="submit" data-ff-submit
                    class="{{ $feerForm ? 'ff-btn ff-btn-primary' : 'inline-flex items-center gap-2 rounded-lg bg-brand px-6 py-2.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2' }}">
                    {{ filled($form->settings['submit_label'] ?? null) ? $form->settings['submit_label'] : 'Wyślij zgłoszenie' }}
                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                </button>
            </div>
        @else
            <p class="text-sm text-muted">Ten formularz nie zawiera jeszcze żadnych pól.</p>
        @endif
    </form>

    @endif {{-- /showSuccess --}}
</section>

@if ($showErrors)
    <script>
        (function () {
            var summary = document.getElementById('{{ $errorSummaryId }}');
            if (summary) summary.focus();
        })();
    </script>
@endif

@if (! $showSuccess && ($hasSteps || collect($fields)->contains(fn ($f) => filled($f['show_if_uid'] ?? null))))
    <script>
        (function () {
            var root = document.getElementById('formularz-{{ $fs }}');
            var form = root && root.querySelector('form');
            if (!form) return;
            var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var wraps = Array.prototype.slice.call(form.querySelectorAll('[data-ff-field]'));

            // ── Logika warunkowa ────────────────────────────────────────────
            function valueOf(key) {
                var els = Array.prototype.slice.call(form.querySelectorAll('[name="data[' + key + ']"], [name="data[' + key + '][]"]'));
                if (!els.length) return '';
                var first = els[0];
                if (first.type === 'radio') { var c = els.filter(function (e) { return e.checked; })[0]; return c ? c.value : ''; }
                if (first.type === 'checkbox') {
                    if (first.name.slice(-2) === '[]') return els.filter(function (e) { return e.checked; }).map(function (e) { return e.value; });
                    return first.checked ? '1' : '';
                }
                return first.value;
            }
            function applyConditions() {
                var visible = {};
                wraps.forEach(function (w) {
                    var show = true, target = w.getAttribute('data-ff-if');
                    if (target !== null) {
                        if (!visible[target]) { show = false; }
                        else {
                            var v = valueOf(target), want = w.getAttribute('data-ff-val') || '';
                            show = want === '' ? (Array.isArray(v) ? v.length > 0 : v !== '') : (Array.isArray(v) ? v.indexOf(want) !== -1 : v === want);
                        }
                    }
                    visible[w.getAttribute('data-ff-field')] = show;
                    w.hidden = !show;
                    // Ukryte pola nie są wysyłane ani sprawdzane.
                    w.querySelectorAll('input, select, textarea').forEach(function (e) { e.disabled = !show; });
                });
            }
            form.addEventListener('change', function (e) {
                var group = e.target.closest && e.target.closest('[data-ff-group-required]');
                if (group) group.querySelectorAll('input[type=checkbox]').forEach(function (b) { b.setCustomValidity(''); });
                applyConditions();
            });
            form.addEventListener('input', applyConditions);
            applyConditions();

            // ── Kroki ───────────────────────────────────────────────────────
            var steps = Array.prototype.slice.call(form.querySelectorAll('[data-ff-step]'));
            if (!steps.length) return;
            var progress = form.querySelector('[data-ff-progress]');
            var items = Array.prototype.slice.call(form.querySelectorAll('[data-ff-progress-item]'));
            var prev = form.querySelector('[data-ff-prev]'), next = form.querySelector('[data-ff-next]'), submit = form.querySelector('[data-ff-submit]');
            var cur = 0;
            progress.hidden = false;

            function show(i, focus) {
                cur = i;
                steps.forEach(function (s, k) { s.hidden = k !== i; });
                items.forEach(function (li, k) {
                    var dot = li.firstElementChild;
                    li.classList.toggle('text-muted', k > i);
                    li.classList.toggle('text-ink', k <= i);
                    li.classList.toggle('font-bold', k === i);
                    (dot.getAttribute('data-ff-off') || '').split(' ').forEach(function (c) { if (c) dot.classList.toggle(c, k > i); });
                    dot.classList.toggle('bg-brand', k <= i); dot.classList.toggle('border-brand', k <= i); dot.classList.toggle('text-white', k <= i);
                    if (k === i) li.setAttribute('aria-current', 'step'); else li.removeAttribute('aria-current');
                });
                prev.hidden = i === 0;
                next.hidden = i === steps.length - 1;
                submit.hidden = i !== steps.length - 1;
                if (focus) {
                    var h = steps[i].querySelector('[data-ff-step-title]');
                    if (h) { h.focus({ preventScroll: true }); h.scrollIntoView({ block: 'start', behavior: reduced ? 'auto' : 'smooth' }); }
                }
            }
            function stepValid(i, report) {
                steps[i].querySelectorAll('[data-ff-group-required]').forEach(function (g) {
                    if (g.closest('[hidden]') && g.closest('[hidden]') !== steps[i]) return;
                    var boxes = Array.prototype.slice.call(g.querySelectorAll('input[type=checkbox]'));
                    if (boxes.length) boxes[0].setCustomValidity(boxes.some(function (b) { return b.checked; }) ? '' : 'Zaznacz co najmniej jedną odpowiedź.');
                });
                var els = Array.prototype.slice.call(steps[i].querySelectorAll('input, select, textarea')).filter(function (e) { return !e.disabled && e.type !== 'hidden'; });
                for (var k = 0; k < els.length; k++) {
                    if (!els[k].checkValidity()) { if (report) { els[k].reportValidity(); els[k].focus(); } return false; }
                }
                return true;
            }
            next.addEventListener('click', function () { if (stepValid(cur, true)) show(cur + 1, true); });
            prev.addEventListener('click', function () { show(cur - 1, true); });
            form.addEventListener('submit', function (e) {
                for (var i = 0; i < steps.length; i++) {
                    if (!stepValid(i, false)) { e.preventDefault(); show(i, false); stepValid(i, true); return; }
                }
            });
            // Linki z podsumowania błędów otwierają krok z danym polem.
            root.querySelectorAll('a[href^="#field-"]').forEach(function (a) {
                a.addEventListener('click', function () {
                    var t = document.getElementById(a.getAttribute('href').slice(1));
                    var s = t && t.closest('[data-ff-step]');
                    if (s) show(steps.indexOf(s), false);
                });
            });
            // Po błędach z serwera — krok z pierwszym błędnym polem.
            var bad = form.querySelector('[aria-invalid="true"]');
            var start = bad && bad.closest('[data-ff-step]') ? steps.indexOf(bad.closest('[data-ff-step]')) : 0;
            show(Math.max(0, start), false);
        })();
    </script>
@endif

@if (! $showSuccess && collect($fields)->contains(fn ($f) => ($f['type'] ?? '') === 'krs'))
    <script>
        (function () {
            var root = document.getElementById('formularz-{{ $fs }}');
            var form = root && root.querySelector('form');
            if (!form) return;

            form.querySelectorAll('[data-krs-field]').forEach(function (box) {
                var input = box.querySelector('[data-krs-input]');
                var button = box.querySelector('[data-krs-button]');
                var status = box.querySelector('[data-krs-status]');
                var last = null, timer = null;

                function setStatus(text, isError) {
                    status.textContent = text;
                    status.classList.toggle('text-red-700', !!isError);
                    status.classList.toggle('font-medium', !!isError);
                }

                // Uzupełnia pola oznaczone „Uzupełnij z KRS”, nie nadpisując tego, co osoba wpisała sama.
                function fill(data) {
                    var filled = 0;
                    form.querySelectorAll('[data-krs-fill]').forEach(function (wrap) {
                        var value = data[wrap.getAttribute('data-krs-fill')] || '';
                        if (!value) return;
                        var radios = wrap.querySelectorAll('input[type=radio]');
                        if (radios.length) {
                            radios.forEach(function (r) { if (r.value.toLowerCase() === value.toLowerCase()) { r.checked = true; filled++; } });
                            return;
                        }
                        var el = wrap.querySelector('input, textarea, select');
                        if (!el || el.disabled) return;
                        if (el.value !== '' && el.value !== el.getAttribute('data-krs-value')) return;
                        if (el.tagName === 'SELECT') {
                            var opt = Array.prototype.find.call(el.options, function (o) { return o.value.toLowerCase() === value.toLowerCase(); });
                            if (!opt) return;
                            value = opt.value;
                        }
                        el.value = value;
                        el.setAttribute('data-krs-value', value);
                        el.dispatchEvent(new Event('input', { bubbles: true }));
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                        filled++;
                    });
                    return filled;
                }

                function lookup(force) {
                    var digits = input.value.replace(/\D/g, '');
                    if (!digits || digits.length > 10 || /^0+$/.test(digits)) {
                        if (force) setStatus('Wpisz numer KRS — do 10 cyfr.', true);
                        return;
                    }
                    var krs = ('0000000000' + digits).slice(-10);
                    if (krs === last && !force) return;
                    last = krs;
                    button.disabled = true;
                    setStatus('Szukam w rejestrze KRS…');
                    fetch(box.getAttribute('data-krs-url') + '/' + krs, { headers: { 'Accept': 'application/json' } })
                        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
                        .then(function (res) {
                            if (!res.ok) { setStatus(res.body.message || 'Nie udało się pobrać danych z KRS.', true); return; }
                            input.value = krs;
                            var n = fill(res.body.data);
                            setStatus('Znaleziono: ' + res.body.data.nazwa + (n ? '. Uzupełniono pola: ' + n + ' — sprawdź je przed wysłaniem.' : '.'));
                        })
                        .catch(function () { setStatus('Rejestr KRS chwilowo nie odpowiada. Uzupełnij dane ręcznie.', true); last = null; })
                        .then(function () { button.disabled = false; });
                }

                button.addEventListener('click', function () { lookup(true); });
                input.addEventListener('input', function () {
                    clearTimeout(timer);
                    if (input.value.replace(/\D/g, '').length === 10) timer = setTimeout(function () { lookup(false); }, 400);
                });
                input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); lookup(true); } });
            });
        })();
    </script>
@endif
