@php
    $fs          = $form->slug;
    $fields      = $form->normalizedFields();
    $isThisForm  = session('_form_slug') === $fs;
    $showSuccess = $isThisForm && session('success');
    $showErrors  = $isThisForm && $errors->any();
    $errorSummaryId = 'form-errors-' . $fs;
@endphp

<section id="formularz-{{ $fs }}"
    aria-labelledby="form-heading-{{ $fs }}"
    class="not-prose my-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

    <h2 id="form-heading-{{ $fs }}" class="mb-1 text-xl font-bold text-ink">
        {{ $form->title }}
    </h2>
    @if ($form->description)
        <p class="mb-5 text-sm text-muted">{{ $form->description }}</p>
    @else
        <div class="mb-5"></div>
    @endif

    {{-- ── Potwierdzenie wysłania ─────────────────────────────────────────── --}}
    @if ($showSuccess)
        <div role="status" aria-live="polite" aria-atomic="true"
            class="flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800">
            <i class="fa-solid fa-circle-check mt-0.5 shrink-0 text-green-600" aria-hidden="true"></i>
            <p>{{ session('success') }}</p>
        </div>
    @else

    {{-- ── Podsumowanie błędów (WCAG 3.3.1 / 3.3.3) ──────────────────────── --}}
    @if ($showErrors)
        <div id="{{ $errorSummaryId }}" role="alert" aria-live="assertive" aria-atomic="true"
            tabindex="-1"
            class="mb-5 rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
            <p class="flex items-center gap-2 font-bold">
                <i class="fa-solid fa-triangle-exclamation shrink-0" aria-hidden="true"></i>
                Proszę poprawić następujące błędy:
            </p>
            <ul class="mt-2 list-inside list-disc space-y-1">
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
    @endif

    {{-- ── Formularz ───────────────────────────────────────────────────────── --}}
    <form method="POST"
        action="{{ route('formularz.store', $fs) }}"
        aria-label="{{ $form->title }}"
        @if ($showErrors) aria-describedby="{{ $errorSummaryId }}" @endif
        novalidate
        class="space-y-5">
        @csrf

        @if (count($fields) > 0)
            <p class="text-xs text-muted">
                Pola oznaczone <span aria-hidden="true" class="font-bold text-red-500">*</span>
                <span class="sr-only">gwiazdką</span> są wymagane.
            </p>
        @endif

        @foreach ($fields as $field)
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

                $baseInputClass = 'w-full rounded-lg border text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1 '
                    . ($hasError ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-white');
            @endphp

            @if ($type === 'radio')
                {{-- ── Radio group (fieldset + legend) ──────────────────────── --}}
                <fieldset class="space-y-1.5">
                    <legend class="text-sm font-semibold text-ink">
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
                                        class="h-4 w-4 shrink-0 border-gray-300 text-brand focus:ring-brand {{ $hasError ? 'border-red-400' : '' }}">
                                    {{ $opt }}
                                </label>
                            @endif
                        @endforeach
                    </div>

                    @if ($hasError)
                        <p id="{{ $errorId }}" class="flex items-center gap-1 text-sm font-medium text-red-700" role="alert">
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
                            class="mt-0.5 h-4 w-4 shrink-0 cursor-pointer rounded border-gray-300 text-brand focus:ring-brand {{ $hasError ? 'border-red-400' : '' }}">
                        <label for="{{ $fid }}" class="cursor-pointer text-sm text-ink">
                            {{ $field['label'] }}
                            @if ($required)
                                <span aria-hidden="true" class="ml-0.5 font-bold text-red-500">*</span>
                                <span class="sr-only">(wymagane)</span>
                            @endif
                        </label>
                    </div>

                    @if ($field['help_text'] ?? null)
                        <p id="{{ $helpId }}" class="pl-6 text-xs text-muted">{{ $field['help_text'] }}</p>
                    @endif

                    @if ($hasError)
                        <p id="{{ $errorId }}" class="flex items-center gap-1 pl-6 text-sm font-medium text-red-700" role="alert">
                            <i class="fa-solid fa-circle-exclamation shrink-0" aria-hidden="true"></i>
                            @error("data.{$field['key']}") {{ $message }} @enderror
                        </p>
                    @endif
                </div>

            @elseif ($type === 'select')
                {{-- ── Select ───────────────────────────────────────────────── --}}
                <div class="space-y-1">
                    <label for="{{ $fid }}" class="block text-sm font-semibold text-ink">
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
                        class="{{ $baseInputClass }} py-2 pl-3 pr-8">
                        <option value="">— wybierz —</option>
                        @foreach (array_map('trim', explode(',', $field['options'] ?? '')) as $opt)
                            @if (filled($opt))
                                <option value="{{ $opt }}" {{ $oldVal === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endif
                        @endforeach
                    </select>

                    @if ($hasError)
                        <p id="{{ $errorId }}" class="flex items-center gap-1 text-sm font-medium text-red-700" role="alert">
                            <i class="fa-solid fa-circle-exclamation shrink-0" aria-hidden="true"></i>
                            @error("data.{$field['key']}") {{ $message }} @enderror
                        </p>
                    @endif
                </div>

            @elseif ($type === 'textarea')
                {{-- ── Textarea ─────────────────────────────────────────────── --}}
                <div class="space-y-1">
                    <label for="{{ $fid }}" class="block text-sm font-semibold text-ink">
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
                        class="{{ $baseInputClass }} px-3 py-2">{{ $oldVal }}</textarea>

                    @if ($hasError)
                        <p id="{{ $errorId }}" class="flex items-center gap-1 text-sm font-medium text-red-700" role="alert">
                            <i class="fa-solid fa-circle-exclamation shrink-0" aria-hidden="true"></i>
                            @error("data.{$field['key']}") {{ $message }} @enderror
                        </p>
                    @endif
                </div>

            @else
                {{-- ── Wszystkie inne pola: text, email, tel, number, date ───── --}}
                <div class="space-y-1">
                    <label for="{{ $fid }}" class="block text-sm font-semibold text-ink">
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
                        class="{{ $baseInputClass }} px-3 py-2">

                    @if ($hasError)
                        <p id="{{ $errorId }}" class="flex items-center gap-1 text-sm font-medium text-red-700" role="alert">
                            <i class="fa-solid fa-circle-exclamation shrink-0" aria-hidden="true"></i>
                            @error("data.{$field['key']}") {{ $message }} @enderror
                        </p>
                    @endif
                </div>
            @endif
        @endforeach

        @if (count($fields) > 0)
            <div class="pt-2">
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand px-6 py-2.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    Wyślij zgłoszenie
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
