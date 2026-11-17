{{--
    Formularz wpłaty na zbiórkę (Przelewy24). WCAG 2.2 AA: widoczne etykiety, grupy w fieldset/legend,
    podsumowanie błędów z odnośnikami i fokusem, aria-invalid + aria-describedby, cele dotyku ≥ 44 px,
    pola warunkowe (inna kwota, firma) dostępne także bez JS.
    Parametry: $campaign, $texts, $vars, $amounts, $onlineEnabled.
--}}
@php
    $display = 'vm-display';
    $min = \App\Models\Campaign::MIN_AMOUNT;
    $max = \App\Models\Campaign::MAX_AMOUNT;
    $defaultAmount = (string) ($amounts[1] ?? $amounts[0]);
    $oldAmount = old('amount', in_array((int) request('kwota'), $amounts, true) ? (string) (int) request('kwota') : $defaultAmount);
    $fieldBase = 'block min-h-11 w-full rounded-xl border-2 border-gray-500 bg-white px-4 text-base text-ink focus:border-brand-dark focus:outline-none focus:ring-2 focus:ring-brand/30';
    $fieldError = 'border-red-700';
    $errorFields = ['amount', 'amount_other', 'type', 'first_name', 'last_name', 'email', 'phone', 'company_name', 'nip', 'dedication', 'visibility', 'consent_rodo'];
    $hasFormErrors = collect($errorFields)->contains(fn ($f) => $errors->has($f));
    $fieldId = fn ($f) => match ($f) {
        'amount' => 'cd-amount-' . $amounts[0],
        'amount_other' => 'cd-amount-other-value',
        default => 'cd-' . str_replace('_', '-', $f),
    };
    $optional = e($texts->get('optional_marker', $vars));
    // Zaznaczony kafel ma też obrys (zostaje w trybie wymuszonych kolorów, gdzie tło znika).
    $tileChecked = 'peer-checked:bg-brand-dark peer-checked:text-white peer-checked:outline peer-checked:outline-2 peer-checked:outline-offset-2 peer-checked:outline-ink';
    $isCompany = (bool) old('is_company');
    $legendClass = $display . ' mb-3 flex items-center gap-3 text-base text-ink';
    $step = fn ($n) => '<span class="flex h-8 w-8 flex-none items-center justify-center rounded-full bg-brand-dark text-sm text-white" aria-hidden="true">' . $n . '</span>';
@endphp

<section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 sm:p-6" aria-labelledby="cd-heading">
    <h2 id="cd-heading" class="{{ $display }} mb-3 text-xl text-ink sm:text-2xl">{{ $texts->get('form_heading', $vars) }}</h2>
    @foreach ($texts->paragraphs('form_intro', $vars) as $paragraph)
        <p class="mb-3 text-sm leading-relaxed text-ink">{!! nl2br(e($paragraph)) !!}</p>
    @endforeach

    @if (! $onlineEnabled)
        <p class="rounded-xl bg-gray-100 p-4 text-sm text-ink" role="status">{{ $texts->get('unavailable', $vars) }}</p>
    @else
        @if (session('donation_error'))
            <p class="mb-4 rounded-xl bg-red-50 p-4 text-sm font-semibold text-red-800" role="alert">{{ session('donation_error') }}</p>
        @endif

        @if ($hasFormErrors)
            <div id="cd-errors" class="mb-6 rounded-xl bg-red-50 p-4 text-sm text-red-800" role="alert" tabindex="-1">
                <p class="font-bold">{{ $texts->get('errors_heading', $vars) }}</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errorFields as $f)
                        @error($f)
                            <li><a href="#{{ $fieldId($f) }}" class="underline">{{ $message }}</a></li>
                        @enderror
                    @endforeach
                </ul>
            </div>
            <script>document.addEventListener('DOMContentLoaded', () => document.getElementById('cd-errors')?.focus());</script>
        @endif

        <form method="POST" action="{{ route('kampanie.donate', $campaign->slug) }}" novalidate class="space-y-7"
              x-data="{ amount: @js($oldAmount), other: @js(old('amount_other', '')), company: @js($isCompany),
                        get total() { const v = this.amount === 'other' ? this.other : this.amount; return v ? Number(v) : 0; } }">
            @csrf
            <p class="text-xs text-ink">{{ $texts->get('form_required_note', $vars) }}</p>

            {{-- Kwota --}}
            <fieldset>
                <legend class="{{ $legendClass }}">{!! $step(1) !!} {{ $texts->get('amount_legend', $vars) }}</legend>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($amounts as $value)
                        <label class="relative">
                            <input type="radio" name="amount" value="{{ $value }}" id="cd-amount-{{ $value }}" x-model="amount"
                                   class="peer sr-only" @checked($oldAmount === (string) $value)>
                            <span class="flex min-h-12 cursor-pointer items-center justify-center rounded-xl border-2 border-brand-dark bg-white px-3 text-base font-bold text-brand-dark transition hover:bg-brand-light {{ $tileChecked }} peer-focus-visible:ring-2 peer-focus-visible:ring-brand peer-focus-visible:ring-offset-2">
                                {{ \App\Support\Money::zl($value) }}
                            </span>
                        </label>
                    @endforeach
                    @if ($campaign->allow_custom_amount)
                        <label class="relative">
                            <input type="radio" name="amount" value="other" id="cd-amount-other" x-model="amount"
                                   class="peer sr-only" @checked($oldAmount === 'other')>
                            <span class="flex min-h-12 cursor-pointer items-center justify-center rounded-xl border-2 border-brand-dark bg-white px-3 text-base font-bold text-brand-dark transition hover:bg-brand-light {{ $tileChecked }} peer-focus-visible:ring-2 peer-focus-visible:ring-brand peer-focus-visible:ring-offset-2">
                                {{ $texts->get('amount_other_option', $vars) }}
                            </span>
                        </label>
                    @endif
                </div>
                @if ($campaign->allow_custom_amount)
                    <div class="mt-4" x-show="amount === 'other'">
                        <label for="cd-amount-other-value" class="mb-1 block text-sm font-bold text-ink">{{ $texts->get('amount_other_label', $vars) }}</label>
                        <input type="number" name="amount_other" id="cd-amount-other-value" x-model="other"
                               min="{{ $min }}" max="{{ $max }}" step="1" inputmode="decimal" value="{{ old('amount_other') }}"
                               aria-describedby="cd-amount-other-help @error('amount_other') cd-amount-other-error @enderror"
                               @error('amount_other') aria-invalid="true" @enderror
                               class="{{ $fieldBase }} max-w-xs @error('amount_other') {{ $fieldError }} @enderror">
                        <p id="cd-amount-other-help" class="mt-1 text-xs text-ink">{{ $texts->get('amount_other_help', $vars + ['min' => $min, 'max' => number_format($max, 0, ',', ' ')]) }}</p>
                        @error('amount_other') <p id="cd-amount-other-error" class="mt-1 text-sm font-semibold text-red-700">{{ $message }}</p> @enderror
                    </div>
                @endif
                @error('amount') <p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p> @enderror
            </fieldset>

            {{-- Rodzaj wpłaty --}}
            @if ($campaign->allow_recurring)
                @php $type = old('type', 'one_time'); @endphp
                <fieldset>
                    <legend class="{{ $legendClass }}">{{ $texts->get('type_legend', $vars) }}</legend>
                    <div class="grid grid-cols-2 gap-3" id="cd-type">
                        @foreach (['one_time' => 'type_one_time', 'recurring' => 'type_recurring'] as $value => $key)
                            <label class="relative">
                                <input type="radio" name="type" value="{{ $value }}" class="peer sr-only" @checked($type === $value)
                                       @if ($value === 'recurring') aria-describedby="cd-recurring-note" @endif>
                                <span class="flex min-h-12 cursor-pointer items-center justify-center rounded-xl border-2 border-brand-dark bg-white px-3 text-sm font-bold text-brand-dark transition hover:bg-brand-light {{ $tileChecked }} peer-focus-visible:ring-2 peer-focus-visible:ring-brand peer-focus-visible:ring-offset-2">
                                    {{ $texts->get($key, $vars) }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <p id="cd-recurring-note" class="mt-2 text-xs leading-relaxed text-ink">{{ $texts->get('recurring_note', $vars) }}</p>
                </fieldset>
            @endif

            {{-- Dane --}}
            <fieldset>
                <legend class="{{ $legendClass }}">{!! $step(2) !!} {{ $texts->get('donor_legend', $vars) }}</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ([
                        ['first_name', 'first_name_label', 'text', 'given-name', true, 100],
                        ['last_name', 'last_name_label', 'text', 'family-name', true, 100],
                        ['email', 'email_label', 'email', 'email', true, 255],
                        ['phone', 'phone_label', 'tel', 'tel', false, 40],
                    ] as [$name, $labelKey, $inputType, $autocomplete, $required, $maxlength])
                        @php $id = $fieldId($name); @endphp
                        <div>
                            <label for="{{ $id }}" class="mb-1 block text-sm font-bold text-ink">
                                {{ $texts->get($labelKey, $vars) }}@if ($required) <span aria-hidden="true">*</span>@else <span class="font-normal">{!! $optional !!}</span>@endif
                            </label>
                            <input type="{{ $inputType }}" name="{{ $name }}" id="{{ $id }}" value="{{ old($name) }}"
                                   autocomplete="{{ $autocomplete }}" maxlength="{{ $maxlength }}"
                                   @if ($required) required aria-required="true" @endif
                                   @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
                                   class="{{ $fieldBase }} @error($name) {{ $fieldError }} @enderror">
                            @error($name) <p id="{{ $id }}-error" class="mt-1 text-sm font-semibold text-red-700">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>

                <label class="mt-4 flex min-h-11 cursor-pointer items-center gap-3 text-sm text-ink">
                    <input type="checkbox" name="is_company" value="1" x-model="company" @checked($isCompany)
                           aria-controls="cd-company-fields" class="h-5 w-5 rounded border-gray-500 text-brand-dark focus:ring-brand">
                    <span>{{ $texts->get('company_toggle', $vars) }}</span>
                </label>
                <div id="cd-company-fields" class="mt-2 grid gap-4 sm:grid-cols-2" x-show="company">
                    @foreach ([['company_name', 'company_label', 'organization', 200], ['nip', 'nip_label', 'off', 20]] as [$name, $labelKey, $autocomplete, $maxlength])
                        @php $id = $fieldId($name); @endphp
                        <div>
                            <label for="{{ $id }}" class="mb-1 block text-sm font-bold text-ink">{{ $texts->get($labelKey, $vars) }}@if ($name === 'nip') <span class="font-normal">{!! $optional !!}</span>@endif</label>
                            <input type="text" name="{{ $name }}" id="{{ $id }}" value="{{ old($name) }}" autocomplete="{{ $autocomplete }}" maxlength="{{ $maxlength }}"
                                   @if ($name === 'nip') inputmode="numeric" @endif
                                   @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
                                   class="{{ $fieldBase }} @error($name) {{ $fieldError }} @enderror">
                            @error($name) <p id="{{ $id }}-error" class="mt-1 text-sm font-semibold text-red-700">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    <label for="cd-dedication" class="mb-1 block text-sm font-bold text-ink">{{ $texts->get('dedication_label', $vars) }} <span class="font-normal">{!! $optional !!}</span></label>
                    <textarea name="dedication" id="cd-dedication" rows="2" maxlength="300"
                              aria-describedby="cd-dedication-help @error('dedication') cd-dedication-error @enderror"
                              @error('dedication') aria-invalid="true" @enderror
                              class="{{ $fieldBase }} py-2 @error('dedication') {{ $fieldError }} @enderror">{{ old('dedication') }}</textarea>
                    <p id="cd-dedication-help" class="mt-1 text-xs text-ink">{{ $texts->get('dedication_help', $vars) }}</p>
                    @error('dedication') <p id="cd-dedication-error" class="mt-1 text-sm font-semibold text-red-700">{{ $message }}</p> @enderror
                    <label class="mt-2 flex min-h-11 cursor-pointer items-center gap-3 text-sm text-ink">
                        <input type="checkbox" name="dedication_public" value="1" @checked(old('dedication_public'))
                               class="h-5 w-5 rounded border-gray-500 text-brand-dark focus:ring-brand">
                        <span>{{ $texts->get('dedication_public_label', $vars) }}</span>
                    </label>
                </div>
            </fieldset>

            {{-- Widoczność --}}
            <fieldset>
                <legend class="{{ $legendClass }}">{{ $texts->get('visibility_legend', $vars) }}</legend>
                @php $visibility = old('visibility', 'anonymous'); @endphp
                <div class="space-y-1" id="cd-visibility">
                    @foreach (['name' => 'visibility_name', 'anonymous' => 'visibility_anonymous'] as $value => $key)
                        <label class="flex min-h-11 cursor-pointer items-center gap-3 text-sm text-ink">
                            <input type="radio" name="visibility" value="{{ $value }}" @checked($visibility === $value)
                                   class="h-5 w-5 border-gray-500 text-brand-dark focus:ring-brand">
                            <span>{{ $texts->get($key, $vars) }}</span>
                        </label>
                    @endforeach
                </div>
                @error('visibility') <p class="mt-1 text-sm font-semibold text-red-700">{{ $message }}</p> @enderror
            </fieldset>

            {{-- Zgody --}}
            <fieldset>
                <legend class="{{ $legendClass }}">{!! $step(3) !!} {{ $texts->get('consents_legend', $vars) }}</legend>
                @foreach ($texts->paragraphs('rodo_info', $vars) as $paragraph)
                    <p class="mb-3 text-xs leading-relaxed text-ink">{!! nl2br(e($paragraph)) !!}</p>
                @endforeach
                <div class="space-y-2">
                    <div>
                        <label class="flex cursor-pointer items-start gap-3 text-sm text-ink">
                            <input type="checkbox" name="consent_rodo" id="cd-consent-rodo" value="1" @checked(old('consent_rodo'))
                                   required aria-required="true"
                                   @error('consent_rodo') aria-invalid="true" aria-describedby="cd-consent-rodo-error" @enderror
                                   class="mt-0.5 h-5 w-5 flex-none rounded border-gray-500 text-brand-dark focus:ring-brand">
                            <span>{{ $texts->get('consent_rodo_label', $vars) }} <span aria-hidden="true">*</span>
                                (<a href="{{ url($texts->get('privacy_link_url', $vars)) }}" class="font-bold text-brand-dark underline" target="_blank" rel="noopener">{{ $texts->get('privacy_link_label', $vars) }}<span class="sr-only"> {{ $texts->get('new_tab_sr', $vars) }}</span></a>)</span>
                        </label>
                        @error('consent_rodo') <p id="cd-consent-rodo-error" class="ml-8 mt-1 text-sm font-semibold text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <label class="flex cursor-pointer items-start gap-3 text-sm text-ink">
                        <input type="checkbox" name="consent_newsletter" value="1" @checked(old('consent_newsletter'))
                               class="mt-0.5 h-5 w-5 flex-none rounded border-gray-500 text-brand-dark focus:ring-brand">
                        <span>{{ $texts->get('consent_newsletter_label', $vars) }}</span>
                    </label>
                </div>
            </fieldset>

            <button type="submit"
                    class="{{ $display }} inline-flex min-h-12 w-full items-center justify-center gap-3 rounded-2xl bg-brand-dark px-8 text-base text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <span>{{ $texts->get('submit_label', $vars) }}<span x-show="total >= {{ $min }}" x-cloak> <span x-text="total.toLocaleString('pl-PL') + ' zł'"></span></span></span>
                <i class="fa-solid fa-angles-right" aria-hidden="true"></i>
            </button>
            <p class="flex items-start gap-2 text-xs leading-relaxed text-ink">
                <i class="fa-solid fa-lock mt-0.5" aria-hidden="true"></i>
                <span>{{ $texts->get('payment_note', $vars) }}</span>
            </p>
        </form>
    @endif
</section>
