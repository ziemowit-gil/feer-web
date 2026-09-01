{{-- Formularz kontaktowy (status wysyłki, błędy walidacji, honeypot, RODO). --}}
@php
    $isFederationTemplate = ($siteSettings->site_template ?? 'default') === 'federation';
    $fieldClass = $isFederationTemplate
        ? 'rounded-md border-gray-300 py-2.5 focus:border-brand focus:ring-1 focus:ring-brand'
        : 'rounded border-gray-300 focus:border-brand focus:ring-brand';
@endphp
@if (session('status'))
    <div class="mb-6 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
        {{ session('status') }}
    </div>
@endif

<form method="POST" action="{{ route('contact.store') }}" class="max-w-xl space-y-5"
      novalidate aria-label="Formularz kontaktowy">
    @csrf

    {{-- Honeypot --}}
    <div class="hidden" aria-hidden="true">
        <label for="website">Zostaw to pole puste</label>
        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
    </div>

    @if ($errors->any())
        <div role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-bold">Popraw poniższe błędy, aby wysłać wiadomość:</p>
            <ul class="mt-1 list-disc pl-4">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($coordinators->isNotEmpty())
        <div>
            <label for="coordinator_email" class="mb-1 block text-sm font-bold">
                Do kogo piszesz? <span class="font-normal text-muted">(opcjonalnie)</span>
            </label>
            <select id="coordinator_email" name="coordinator_email"
                    aria-describedby="coordinator-hint"
                    class="w-full {{ $fieldClass }} {{ $errors->has('coordinator_email') ? 'border-red-400' : '' }}">
                <option value="">— Ogólny kontakt —</option>
                @foreach ($coordinators as $c)
                    <option value="{{ $c['email'] }}" {{ old('coordinator_email') === $c['email'] ? 'selected' : '' }}>
                        {{ $c['name'] }} ({{ $c['project'] }})
                    </option>
                @endforeach
            </select>
            <p id="coordinator-hint" class="mt-1 text-xs text-muted">Wiadomość trafi bezpośrednio do wybranej osoby.</p>
            @error('coordinator_email') <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
        </div>
    @endif

    <div>
        <label for="name" class="mb-1 block text-sm font-bold">
            Imię i nazwisko <span aria-hidden="true" class="text-red-600">*</span>
        </label>
        <input type="text" id="name" name="name" value="{{ old('name') }}"
               required aria-required="true" autocomplete="name"
               @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
               class="w-full {{ $fieldClass }} {{ $errors->has('name') ? 'border-red-400' : '' }}">
        @error('name') <p id="name-error" class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="email" class="mb-1 block text-sm font-bold">
            E-mail <span aria-hidden="true" class="text-red-600">*</span>
        </label>
        <input type="email" id="email" name="email" value="{{ old('email') }}"
               required aria-required="true" autocomplete="email"
               @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
               class="w-full {{ $fieldClass }} {{ $errors->has('email') ? 'border-red-400' : '' }}">
        @error('email') <p id="email-error" class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="phone" class="mb-1 block text-sm font-bold">
            Telefon <span class="font-normal text-muted">(opcjonalnie)</span>
        </label>
        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel"
               @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror
               class="w-full {{ $fieldClass }} {{ $errors->has('phone') ? 'border-red-400' : '' }}">
        @error('phone') <p id="phone-error" class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="subject" class="mb-1 block text-sm font-bold">
            Temat <span class="font-normal text-muted">(opcjonalnie)</span>
        </label>
        <input type="text" id="subject" name="subject" value="{{ old('subject') }}"
               @error('subject') aria-invalid="true" aria-describedby="subject-error" @enderror
               class="w-full {{ $fieldClass }} {{ $errors->has('subject') ? 'border-red-400' : '' }}">
        @error('subject') <p id="subject-error" class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="message" class="mb-1 block text-sm font-bold">
            Wiadomość <span aria-hidden="true" class="text-red-600">*</span>
        </label>
        <textarea id="message" name="message" rows="6"
                  required aria-required="true"
                  @error('message') aria-invalid="true" aria-describedby="message-error" @enderror
                  class="w-full {{ $fieldClass }} {{ $errors->has('message') ? 'border-red-400' : '' }}">{{ old('message') }}</textarea>
        @error('message') <p id="message-error" class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
    </div>

    <div>
        <div class="flex items-start gap-2">
            <input type="checkbox" id="rodo_consent" name="rodo_consent" value="1"
                   required aria-required="true" {{ old('rodo_consent') ? 'checked' : '' }}
                   @error('rodo_consent') aria-invalid="true" aria-describedby="rodo-error" @enderror
                   class="mt-1 flex-none rounded border-gray-300 text-brand focus:ring-brand {{ $errors->has('rodo_consent') ? 'border-red-400' : '' }}">
            <label for="rodo_consent" class="text-sm text-muted">
                Wyrażam zgodę na przetwarzanie moich danych osobowych (imienia i adresu e-mail) w celu udzielenia odpowiedzi na przesłaną wiadomość, zgodnie z
                <a href="{{ route('page.show', 'polityka-prywatnosci') }}" class="font-bold text-brand hover:text-brand-dark">Polityką prywatności</a>.
                <span aria-hidden="true" class="font-bold text-red-600">*</span>
            </label>
        </div>
        @error('rodo_consent') <p id="rodo-error" class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
    </div>

    <p class="text-xs text-muted">
        <span aria-hidden="true" class="text-red-600">*</span> Pola wymagane.
        Administratorem Twoich danych jest {{ $siteSettings->site_name }}.
        Dane przetwarzamy wyłącznie w celu obsługi zapytania.
        Szczegóły w <a href="{{ route('page.show', 'polityka-prywatnosci') }}" class="font-bold text-brand hover:text-brand-dark">Polityce prywatności</a>.
    </p>

    <button type="submit"
            class="{{ $isFederationTemplate ? 'rounded-md px-6 py-3' : 'rounded px-5 py-2.5' }} bg-brand text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
        @if ($isFederationTemplate)
            <i class="fa-solid fa-paper-plane mr-1.5" aria-hidden="true"></i>
        @endif
        Wyślij wiadomość
    </button>
</form>
