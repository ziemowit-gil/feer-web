@extends('admin.layout')

@section('title', $form->exists ? 'Edytuj formularz' : 'Nowy formularz')

@section('content')
    @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            <p class="font-bold">Popraw błędy:</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('status'))
        <div class="mb-4 rounded-md bg-brand-light px-4 py-3 text-sm text-ink" role="status">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST"
        action="{{ $form->exists ? route('admin.formularze.update', $form) : route('admin.formularze.store') }}"
        class="space-y-6"
        x-data="formBuilder({{ Js::from($form->fields ?? []) }})">

        @csrf
        @if ($form->exists) @method('PUT') @endif

        {{-- Podstawowe dane --}}
        <fieldset class="space-y-5 p-6 rounded-md bg-white">
            <legend class="px-2 text-sm font-bold text-ink">Podstawowe informacje</legend>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="title" class="mb-1 block text-sm font-bold">
                        Nazwa formularza <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input id="title" name="title" type="text" required
                        value="{{ old('title', $form->title) }}"
                        @input="syncSlug($el.value)"
                        class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                    @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="slug" class="mb-1 block text-sm font-bold">
                        Identyfikator URL <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <div class="flex items-center gap-1">
                        <span class="shrink-0 text-xs text-muted">/formularz/</span>
                        <input id="slug" name="slug" type="text" required
                            value="{{ old('slug', $form->slug) }}"
                            x-ref="slug"
                            pattern="[a-z0-9\-]+"
                            class="w-full rounded border-gray-500 font-mono text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                    </div>
                    @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="description" class="mb-1 block text-sm font-bold">Opis (widoczny dla edytorów)</label>
                <textarea id="description" name="description" rows="2"
                    class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">{{ old('description', $form->description) }}</textarea>
            </div>

            <div class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <input id="is_active" name="is_active" type="checkbox" value="1"
                    {{ old('is_active', $form->is_active ?? true) ? 'checked' : '' }}
                    class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                <label for="is_active" class="text-sm font-medium">Formularz aktywny (publiczny)</label>
            </div>
        </fieldset>

        {{-- Builder pól --}}
        <fieldset class="p-6 rounded-md bg-white">
            <legend class="px-2 text-sm font-bold text-ink">Pola formularza</legend>

            <p class="mb-4 text-sm text-muted">Dodaj pola, które użytkownicy będą wypełniać. Przeciągnij wiersze, aby zmienić kolejność.</p>

            <div class="space-y-3" id="fields-list">
                <template x-for="(field, index) in fields" :key="field._id">
                    <div class="group relative p-4 rounded-md bg-gray-50"
                        :data-index="index">

                        <input type="hidden" :name="'fields[' + index + '][type]'" x-model="field.type">
                        <input type="hidden" :name="'fields[' + index + '][uid]'" :value="field.uid">
                        <input type="hidden" :name="'fields[' + index + '][required]'" :value="field.required ? '1' : '0'">

                        <div class="flex items-start gap-3">
                            {{-- Uchwyt drag (wizualny, sortowanie przez przyciski) --}}
                            <div class="mt-2 cursor-grab text-gray-300 hover:text-gray-500" aria-hidden="true">
                                <i class="fa-solid fa-grip-vertical"></i>
                            </div>

                            <div class="flex-1 space-y-3">
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <div class="sm:col-span-2">
                                        <label :for="'field-label-' + index" class="mb-1 block text-xs font-bold text-muted uppercase tracking-wide">Etykieta pola</label>
                                        <input :id="'field-label-' + index"
                                            :name="'fields[' + index + '][label]'"
                                            type="text" x-model="field.label" required
                                            placeholder="np. Imię i nazwisko"
                                            class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                                    </div>
                                    <div>
                                        <label :for="'field-type-' + index" class="mb-1 block text-xs font-bold text-muted uppercase tracking-wide">Typ pola</label>
                                        <select :id="'field-type-' + index"
                                            :name="'fields[' + index + '][type]'"
                                            x-model="field.type"
                                            class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                                            @foreach ($types as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div x-show="! ['heading', 'step', 'checkbox', 'consent', 'radio', 'checkboxes', 'rating', 'file'].includes(field.type)">
                                        <label :for="'field-placeholder-' + index" class="mb-1 block text-xs font-bold text-muted uppercase tracking-wide">Placeholder</label>
                                        <input :id="'field-placeholder-' + index"
                                            :name="'fields[' + index + '][placeholder]'"
                                            type="text" x-model="field.placeholder"
                                            placeholder="Tekst pomocniczy w pustym polu"
                                            class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                                    </div>
                                    <div>
                                        <label :for="'field-help-' + index" class="mb-1 block text-xs font-bold text-muted uppercase tracking-wide" x-text="field.type === 'heading' ? 'Opis sekcji (pod nagłówkiem)' : (field.type === 'step' ? 'Opis kroku' : 'Tekst pomocy')">Tekst pomocy</label>
                                        <input :id="'field-help-' + index"
                                            :name="'fields[' + index + '][help_text]'"
                                            type="text" x-model="field.help_text"
                                            placeholder="Dodatkowy opis pod polem"
                                            class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                                    </div>
                                </div>

                                {{-- Opcje (dla select/radio/wielokrotnego wyboru) --}}
                                <div x-show="['select', 'radio', 'checkboxes'].includes(field.type)">
                                    <label :for="'field-options-' + index" class="mb-1 block text-xs font-bold text-muted uppercase tracking-wide">
                                        Opcje (oddzielone przecinkiem)
                                    </label>
                                    <input :id="'field-options-' + index"
                                        :name="'fields[' + index + '][options]'"
                                        type="text" x-model="field.options"
                                        placeholder="np. Tak, Nie, Nie wiem"
                                        class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                                </div>

                                {{-- Załącznik: dozwolone typy i rozmiar --}}
                                <div x-show="field.type === 'file'" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <div class="sm:col-span-2">
                                        <label :for="'field-accept-' + index" class="mb-1 block text-xs font-bold text-muted uppercase tracking-wide">Dozwolone typy plików</label>
                                        <input :id="'field-accept-' + index" :name="'fields[' + index + '][accept]'" type="text" x-model="field.accept"
                                            placeholder="{{ \App\Models\FormDefinition::FILE_DEFAULT_ACCEPT }}"
                                            class="w-full rounded border-gray-500 font-mono text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                                        <p class="mt-1 text-xs text-muted">Rozszerzenia po przecinku. Puste = dokumenty i zdjęcia. Pliki są dostępne tylko w panelu.</p>
                                    </div>
                                    <div>
                                        <label :for="'field-maxmb-' + index" class="mb-1 block text-xs font-bold text-muted uppercase tracking-wide">Maks. rozmiar (MB)</label>
                                        <input :id="'field-maxmb-' + index" :name="'fields[' + index + '][max_mb]'" type="number" min="1" max="{{ \App\Models\FormDefinition::FILE_MAX_MB }}" x-model="field.max_mb" placeholder="10"
                                            class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                                    </div>
                                </div>

                                <p x-show="field.type === 'consent'" class="rounded bg-white px-3 py-2 text-xs text-muted">
                                    Etykieta to treść zgody. Na końcu automatycznie pojawi się link do Polityki prywatności.
                                </p>
                                <p x-show="field.type === 'rating'" class="rounded bg-white px-3 py-2 text-xs text-muted">
                                    Odpowiedź: 1–5 (przyciski). W „Opcjach” nie trzeba nic wpisywać.
                                </p>

                                {{-- Logika warunkowa: pokaż pole tylko przy określonej odpowiedzi we wcześniejszym polu --}}
                                <div x-show="field.type !== 'step' && conditionSources(index).length" class="rounded bg-white p-3">
                                    <label class="flex items-center gap-2 text-sm font-medium">
                                        <input type="checkbox" class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark"
                                            :checked="!! field.show_if_uid"
                                            @change="field.show_if_uid = $event.target.checked ? (conditionSources(index)[0]?.uid || '') : ''; field.show_if_value = ''">
                                        Pokaż tylko, gdy…
                                    </label>
                                    <div x-show="field.show_if_uid" class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        <div>
                                            <label :for="'field-cond-' + index" class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">pole</label>
                                            <select :id="'field-cond-' + index" :name="'fields[' + index + '][show_if_uid]'" x-model="field.show_if_uid" @change="field.show_if_value = ''"
                                                :disabled="! field.show_if_uid"
                                                class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                                                <template x-for="src in conditionSources(index)" :key="src.uid">
                                                    <option :value="src.uid" x-text="src.label || '(bez etykiety)'" :selected="src.uid === field.show_if_uid"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <label :for="'field-condv-' + index" class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">ma odpowiedź</label>
                                            <template x-if="conditionChoices(field.show_if_uid) !== null">
                                                <select :id="'field-condv-' + index" :name="'fields[' + index + '][show_if_value]'" x-model="field.show_if_value"
                                                    class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                                                    <option value="">— dowolną (pole wypełnione) —</option>
                                                    <template x-for="opt in conditionChoices(field.show_if_uid)" :key="opt.value">
                                                        <option :value="opt.value" x-text="opt.label" :selected="opt.value === field.show_if_value"></option>
                                                    </template>
                                                </select>
                                            </template>
                                            <template x-if="conditionChoices(field.show_if_uid) === null">
                                                <input :id="'field-condv-' + index" :name="'fields[' + index + '][show_if_value]'" type="text" x-model="field.show_if_value"
                                                    placeholder="puste = gdy pole jest wypełnione"
                                                    class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <p x-show="field.type === 'step'" class="rounded bg-white px-3 py-2 text-xs text-muted">
                                    Od tego miejsca zaczyna się kolejny krok formularza. Etykieta to tytuł kroku (widoczny na pasku postępu), tekst pomocy — opis kroku.
                                </p>

                                <div class="flex items-center gap-3" x-show="! ['heading', 'step'].includes(field.type)">
                                    <input :id="'field-required-' + index"
                                        type="checkbox" x-model="field.required"
                                        class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                                    <label :for="'field-required-' + index" class="text-sm font-medium">Pole wymagane</label>
                                </div>
                            </div>

                            {{-- Przyciski operacji --}}
                            <div class="flex shrink-0 flex-col gap-1">
                                <button type="button" @click="moveUp(index)" :disabled="index === 0"
                                    class="rounded p-1.5 text-gray-400 hover:text-brand-dark disabled:opacity-30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                                    :aria-label="'Przesuń pole „' + field.label + '” w górę'">
                                    <i class="fa-solid fa-arrow-up text-xs" aria-hidden="true"></i>
                                </button>
                                <button type="button" @click="moveDown(index)" :disabled="index === fields.length - 1"
                                    class="rounded p-1.5 text-gray-400 hover:text-brand-dark disabled:opacity-30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"
                                    :aria-label="'Przesuń pole „' + field.label + '” w dół'">
                                    <i class="fa-solid fa-arrow-down text-xs" aria-hidden="true"></i>
                                </button>
                                <button type="button" @click="removeField(index)"
                                    class="rounded p-1.5 text-gray-400 hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                                    :aria-label="'Usuń pole „' + field.label + '”'">
                                    <i class="fa-solid fa-xmark text-xs" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="fields.length === 0">
                    <p class="rounded-lg border border-dashed border-gray-300 py-6 text-center text-sm text-muted">
                        Brak pól — dodaj pierwsze poniżej.
                    </p>
                </template>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($types as $value => $label)
                    <button type="button" @click="addField('{{ $value }}')"
                        class="rounded-md px-3 py-1.5 text-xs font-medium text-ink hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark bg-gray-100 hover:bg-brand-light">
                        <i class="fa-solid fa-plus mr-1" aria-hidden="true"></i>{{ $label }}
                    </button>
                @endforeach
            </div>
        </fieldset>

        {{-- Ustawienia --}}
        <fieldset class="space-y-4 p-6 rounded-md bg-white">
            <legend class="px-2 text-sm font-bold text-ink">Ustawienia formularza</legend>

            <div>
                <label for="confirmation_message" class="mb-1 block text-sm font-bold">
                    Wiadomość po wysłaniu
                </label>
                <textarea id="confirmation_message" name="settings[confirmation_message]" rows="2"
                    placeholder="Dziękujemy! Twoje zgłoszenie zostało przyjęte."
                    class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">{{ old('settings.confirmation_message', $form->settings['confirmation_message'] ?? '') }}</textarea>
            </div>

            {{-- Doręczenie zgłoszeń: e-mail / API / oba --}}
            @php $delivery = old('settings.delivery', $form->delivery()); @endphp
            <fieldset class="space-y-4 rounded-md bg-gray-50 p-4" x-data="{ delivery: @js($delivery) }">
                <legend class="px-1 text-sm font-bold text-ink"><i class="fa-solid fa-paper-plane mr-1 text-brand-dark" aria-hidden="true"></i> Gdzie trafiają zgłoszenia</legend>
                <p class="text-xs text-muted">Każde zgłoszenie jest zawsze zapisywane w panelu (Zgłoszenia). Tu wybierasz, dokąd ma być dodatkowo przesłane.</p>
                <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Gdzie trafiają zgłoszenia">
                    @foreach (\App\Models\FormDefinition::DELIVERY as $value => $label)
                        <label class="inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-md border border-solid border-gray-300 bg-white px-3 text-sm has-[:checked]:border-brand-dark has-[:checked]:bg-brand-light has-[:checked]:font-bold">
                            <input type="radio" name="settings[delivery]" value="{{ $value }}" x-model="delivery" @checked($delivery === $value)
                                class="border-gray-500 text-brand-dark focus:ring-brand-dark">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <div x-show="delivery !== 'email'" class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="webhook_url" class="mb-1 block text-sm font-bold">Adres API <span class="text-red-500" aria-hidden="true">*</span></label>
                        <input id="webhook_url" name="settings[webhook_url]" type="url" inputmode="url" autocomplete="off"
                            value="{{ old('settings.webhook_url', $form->settings['webhook_url'] ?? '') }}" placeholder="https://api.przyklad.pl/zgloszenia"
                            class="w-full rounded border-gray-500 font-mono text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                        <p class="mt-1 text-xs text-muted">Zgłoszenie idzie jako JSON (POST): formularz, numer, pola z etykietą, wartością i tekstem.</p>
                        @error('settings.webhook_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="webhook_secret" class="mb-1 block text-sm font-bold">Sekret do podpisu <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input id="webhook_secret" name="settings[webhook_secret]" type="password" autocomplete="new-password" value=""
                            placeholder="{{ filled($form->settings['webhook_secret'] ?? null) ? '•••••••• (zapisany — wpisz nowy, aby zmienić)' : 'np. długi losowy ciąg' }}"
                            class="w-full rounded border-gray-500 font-mono text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                        <p class="mt-1 text-xs text-muted">Podpis HMAC SHA-256 treści w nagłówku <code>X-Form-Signature: sha256=…</code> — odbiorca sprawdzi, że zgłoszenie jest od nas.</p>
                    </div>
                </div>
                <p x-show="delivery === 'api'" class="text-xs text-muted">Powiadomienia e-mail poniżej nie będą wysyłane.</p>
            </fieldset>

            {{-- Terminy i limity --}}
            @php
                $fs = $form->settings ?? [];
                $dtl = fn ($v) => filled($v) ? \Illuminate\Support\Carbon::parse($v)->format('Y-m-d\\TH:i') : '';
                $avail = $form->exists ? $form->availability() : null;
            @endphp
            <fieldset class="space-y-4 rounded-md bg-gray-50 p-4">
                <legend class="px-1 text-sm font-bold text-ink"><i class="fa-solid fa-calendar-check mr-1 text-brand-dark" aria-hidden="true"></i> Terminy i limity</legend>
                @if ($avail)
                    <p class="text-sm {{ $avail['open'] ? 'text-ink' : 'font-bold text-red-700' }}" role="status">
                        {{ $avail['open'] ? 'Formularz przyjmuje zgłoszenia.' : 'Zamknięty: '.$avail['message'] }}
                        @if ($avail['remaining'] !== null) Pozostało miejsc: {{ $avail['remaining'] }}. @endif
                    </p>
                @endif
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="open_from" class="mb-1 block text-sm font-bold">Zbieranie od</label>
                        <input id="open_from" name="settings[open_from]" type="datetime-local" value="{{ old('settings.open_from', $dtl($fs['open_from'] ?? null)) }}"
                            class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                    </div>
                    <div>
                        <label for="open_until" class="mb-1 block text-sm font-bold">Zbieranie do</label>
                        <input id="open_until" name="settings[open_until]" type="datetime-local" value="{{ old('settings.open_until', $dtl($fs['open_until'] ?? null)) }}"
                            class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                    </div>
                    <div>
                        <label for="max_submissions" class="mb-1 block text-sm font-bold">Limit zgłoszeń</label>
                        <input id="max_submissions" name="settings[max_submissions]" type="number" min="1" value="{{ old('settings.max_submissions', $fs['max_submissions'] ?? '') }}" placeholder="bez limitu"
                            class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="settings[show_remaining]" value="0">
                    <input type="checkbox" name="settings[show_remaining]" value="1" @checked(old('settings.show_remaining', $fs['show_remaining'] ?? false))
                        class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                    Pokazuj nad formularzem liczbę wolnych miejsc (przy ustawionym limicie) i termin końca
                </label>
                <div>
                    <label for="closed_message" class="mb-1 block text-sm font-bold">Komunikat po zamknięciu</label>
                    <textarea id="closed_message" name="settings[closed_message]" rows="2" placeholder="np. Zapisy zakończone. Kolejna edycja szkolenia jesienią — zapisz się na newsletter."
                        class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">{{ old('settings.closed_message', $fs['closed_message'] ?? '') }}</textarea>
                    <p class="mt-1 text-xs text-muted">Pokazywany zamiast formularza po terminie albo po wyczerpaniu limitu. Puste = komunikat domyślny.</p>
                </div>
            </fieldset>

            {{-- Powiadomienia e-mail o zgłoszeniach --}}
            @php
                $siteCfg = \App\Models\SiteSetting::current();
                $graphReady = $siteCfg->msGraphConfigured();
                $formMailer = old('settings.mailer', $form->settings['mailer'] ?? '');
                $inheritedLabel = $siteCfg->formsMailer() === 'msgraph'
                    ? 'Microsoft 365 (Graph API)'
                    : (\App\Models\SiteSetting::MAIL_TRANSPORTS[$siteCfg->mail_transport ?? 'default'] ?? 'domyślny tryb wysyłki');
            @endphp
            <fieldset class="space-y-4 p-4 rounded-md bg-gray-50">
                <legend class="px-1 text-sm font-bold text-ink"><i class="fa-solid fa-envelope mr-1 text-brand-dark" aria-hidden="true"></i> Powiadomienia e-mail o zgłoszeniach</legend>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="notification_email" class="mb-1 block text-sm font-bold">Odbiorcy powiadomień</label>
                        <input id="notification_email" name="settings[notification_email]" type="text" inputmode="email" autocomplete="off"
                            value="{{ old('settings.notification_email', $form->settings['notification_email'] ?? '') }}"
                            placeholder="np. biuro@feer.org.pl, sekretariat@feer.org.pl"
                            aria-describedby="notification_email_help"
                            class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                        <p id="notification_email_help" class="mt-1 text-xs text-muted">Kilka adresów rozdziel przecinkami. Puste = brak powiadomień.</p>
                        @error('settings.notification_email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="notification_cc" class="mb-1 block text-sm font-bold">Kopia (DW)</label>
                        <input id="notification_cc" name="settings[notification_cc]" type="text" inputmode="email" autocomplete="off"
                            value="{{ old('settings.notification_cc', $form->settings['notification_cc'] ?? '') }}"
                            placeholder="opcjonalnie"
                            class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                        @error('settings.notification_cc')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="notification_subject" class="mb-1 block text-sm font-bold">Temat wiadomości</label>
                        <input id="notification_subject" name="settings[notification_subject]" type="text"
                            value="{{ old('settings.notification_subject', $form->settings['notification_subject'] ?? '') }}"
                            placeholder="Nowe zgłoszenie: {tytul}"
                            aria-describedby="notification_subject_help"
                            class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                        <p id="notification_subject_help" class="mt-1 text-xs text-muted">Znaczniki: <code>{tytul}</code> — nazwa formularza, <code>{id}</code> — numer zgłoszenia.</p>
                    </div>
                    <div>
                        <label for="form_mailer" class="mb-1 block text-sm font-bold">Sposób wysyłki</label>
                        <select id="form_mailer" name="settings[mailer]" aria-describedby="form_mailer_help"
                            class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                            <option value="" @selected($formMailer === '')>Dziedzicz z ustawień ({{ $inheritedLabel }})</option>
                            <option value="msgraph" @selected($formMailer === 'msgraph')>Microsoft 365 (Graph API){{ $graphReady ? '' : ' — nieskonfigurowany' }}</option>
                            <option value="default" @selected($formMailer === 'default')>Domyślny tryb wysyłki serwisu</option>
                        </select>
                        <p id="form_mailer_help" class="mt-1 text-xs text-muted">
                            Formularze domyślnie wysyłają przez Microsoft Graph, gdy jest skonfigurowany w
                            <a href="{{ route('admin.ustawienia.edit', ['tab' => 'mail']) }}" class="text-brand-dark underline-offset-2 hover:underline">Ustawienia → Poczta</a>.
                            @unless ($graphReady) <span class="text-amber-700">Graph nie jest jeszcze skonfigurowany — do czasu uzupełnienia danych używany jest domyślny tryb.</span> @endunless
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-3">
                        <input type="hidden" name="settings[reply_to_submitter]" value="0">
                        <input type="checkbox" name="settings[reply_to_submitter]" value="1"
                            @checked(old('settings.reply_to_submitter', $form->settings['reply_to_submitter'] ?? true))
                            class="mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                        <span>
                            <span class="block text-sm font-bold">Odpowiedz-do: adres zgłaszającego</span>
                            <span class="block text-xs text-muted">Jeśli formularz ma pole „Adres e-mail”, odpowiedź na powiadomienie trafi wprost do tej osoby.</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-3">
                        <input type="hidden" name="settings[send_copy_to_submitter]" value="0">
                        <input type="checkbox" name="settings[send_copy_to_submitter]" value="1"
                            @checked(old('settings.send_copy_to_submitter', $form->settings['send_copy_to_submitter'] ?? false))
                            class="mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                        <span>
                            <span class="block text-sm font-bold">Wyślij kopię zgłaszającemu</span>
                            <span class="block text-xs text-muted">Potwierdzenie z kopią przesłanych danych na adres z pola „Adres e-mail”.</span>
                        </span>
                    </label>
                </div>

                <div>
                    <label for="submitter_copy_message" class="mb-1 block text-sm font-bold">Treść wstępu w kopii dla zgłaszającego</label>
                    <textarea id="submitter_copy_message" name="settings[submitter_copy_message]" rows="2"
                        placeholder="Dziękujemy za wypełnienie formularza. Poniżej kopia przesłanych danych."
                        class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">{{ old('settings.submitter_copy_message', $form->settings['submitter_copy_message'] ?? '') }}</textarea>
                </div>
            </fieldset>

            {{-- Ochrona CleanTalk: globalnie w Ustawienia → Logowanie i integracje; tu wyłączenie dla jednego formularza --}}
            @if (\App\Support\CleanTalkGuard::enabled())
                <label class="mt-4 flex items-start gap-3 rounded-lg border border-gray-200 p-3">
                    <input type="hidden" name="settings[cleantalk_disabled]" value="0">
                    <input type="checkbox" name="settings[cleantalk_disabled]" value="1"
                        @checked(old('settings.cleantalk_disabled', $form->settings['cleantalk_disabled'] ?? false))
                        class="mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                    <span>
                        <span class="block text-sm font-bold">Nie sprawdzaj tego formularza w CleanTalk</span>
                        <span class="block text-xs text-muted">Domyślnie zgłoszenia są oceniane przez CleanTalk (włączone w ustawieniach). Zaznacz, jeśli formularz jest wewnętrzny albo nie powinien wysyłać danych do zewnętrznej usługi. Lokalna ochrona antyspamowa działa nadal.</span>
                    </span>
                </label>
            @endif

            {{-- Podpięcie do CRM w SZO --}}
            <div class="mt-4 border-t border-gray-200 pt-4">
                <label for="szo_form_slug" class="mb-1 block text-sm font-bold">
                    Przekazuj zgłoszenia do CRM (SZO)
                </label>

                @if (empty($szoForms))
                    <p class="text-sm text-gray-600">
                        Integracja z SZO jest wyłączona albo SZO nie odpowiada. Ustaw
                        <code>SZO_ENABLED</code>, <code>SZO_URL</code> i <code>SZO_TOKEN</code>
                        w pliku <code>.env</code>. Do czasu włączenia zgłoszenia zapisują się
                        tylko tutaj.
                    </p>
                    <input type="hidden" name="settings[szo_form_slug]"
                        value="{{ old('settings.szo_form_slug', $form->settings['szo_form_slug'] ?? '') }}">
                @else
                    <select id="szo_form_slug" name="settings[szo_form_slug]"
                        class="w-full max-w-sm rounded border-gray-500 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                        <option value="">— nie przekazuj —</option>
                        @foreach ($szoForms as $szoForm)
                            <option value="{{ $szoForm['slug'] }}"
                                @selected(old('settings.szo_form_slug', $form->settings['szo_form_slug'] ?? '') === $szoForm['slug'])>
                                {{ $szoForm['title'] }} ({{ $szoForm['slug'] }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-sm text-gray-600">
                        Zgłoszenie założy kontakt w CRM, trafi do Skrzynki CRM jako wiadomość
                        przychodząca i utworzy działanie „odpowiedzieć". Pola dopasowywane są po
                        nazwach — sprawdź, czy etykiety pól odpowiadają polom formularza w SZO.
                    </p>
                @endif

                @error('settings.szo_form_slug')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </fieldset>

        {{-- Osadzanie formularza --}}
        @if ($form->exists)
            <div class="rounded-md bg-brand-light p-4 text-sm">
                <p class="mb-2 font-bold text-blue-800">
                    <i class="fa-solid fa-code mr-1" aria-hidden="true"></i>
                    Osadzanie formularza
                </p>
                <p class="mb-2 text-blue-700">Wklej shortcode w dowolne miejsce treści strony lub aktualności:</p>
                <div class="flex items-center gap-2">
                    <code id="form-shortcode" class="flex-1 rounded bg-white px-3 py-1.5 font-mono text-blue-800 ring-1 ring-blue-200">[formularz:{{ $form->slug }}]</code>
                    <button type="button"
                        onclick="navigator.clipboard.writeText('[formularz:{{ $form->slug }}]').then(() => { this.textContent='Skopiowano!'; setTimeout(() => this.textContent='Kopiuj', 2000) })"
                        class="rounded bg-blue-700 px-3 py-1.5 text-xs font-bold text-white hover:bg-blue-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600">
                        Kopiuj
                    </button>
                </div>
                <p class="mt-2 text-xs text-blue-600">
                    Bezpośredni link: <a href="{{ route('formularz.show', $form->slug) }}" target="_blank" rel="noopener" class="underline hover:no-underline">{{ route('formularz.show', $form->slug) }}</a>
                </p>
            </div>
        @endif

        <div class="flex items-center gap-3">
            <button type="submit"
                class="rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                Zapisz formularz
            </button>
            <a href="{{ route('admin.formularze.index') }}"
                class="text-sm text-muted hover:text-brand-dark focus-visible:rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                Anuluj
            </a>
            @if ($form->exists)
                <a href="{{ route('admin.formularze.zgloszenia', $form) }}"
                    class="ml-auto text-sm text-muted hover:text-brand-dark focus-visible:rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                    <i class="fa-solid fa-inbox mr-1" aria-hidden="true"></i>
                    Zgłoszenia ({{ $form->submissions()->count() }})
                </a>
            @endif
        </div>
    </form>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('formBuilder', (initialFields) => ({
                fields: (initialFields || []).map((f, i) => ({ show_if_uid: '', show_if_value: '', ...f, uid: f.uid || Math.random().toString(36).slice(2, 10), _id: i })),
                _nextId: (initialFields || []).length,

                // Pola, od których może zależeć pole nr index: wcześniejsze pola z wartością.
                conditionSources(index) {
                    return this.fields.slice(0, index).filter(f => ! ['heading', 'step', 'file'].includes(f.type));
                },

                // Lista możliwych odpowiedzi pola źródłowego; null = dowolny tekst.
                conditionChoices(uid) {
                    const src = this.fields.find(f => f.uid === uid);
                    if (! src) return null;
                    if (['select', 'radio', 'checkboxes'].includes(src.type)) {
                        return String(src.options || '').split(',').map(o => o.trim()).filter(Boolean).map(o => ({ value: o, label: o }));
                    }
                    if (['checkbox', 'consent'].includes(src.type)) return [{ value: '1', label: 'zaznaczone' }];
                    if (src.type === 'rating') return ['1', '2', '3', '4', '5'].map(v => ({ value: v, label: v + '/5' }));
                    return null;
                },

                addField(type) {
                    this.fields.push({
                        _id:         this._nextId++,
                        uid:         Math.random().toString(36).slice(2, 10),
                        show_if_uid: '',
                        show_if_value: '',
                        label:       '',
                        type:        type,
                        placeholder: '',
                        options:     '',
                        help_text:   '',
                        accept:      '',
                        max_mb:      '',
                        required:    type === 'consent',
                    });
                    this.$nextTick(() => {
                        const inputs = document.querySelectorAll('#fields-list [name$="[label]"]');
                        if (inputs.length) inputs[inputs.length - 1].focus();
                    });
                },

                removeField(index) {
                    this.fields.splice(index, 1);
                },

                moveUp(index) {
                    if (index === 0) return;
                    [this.fields[index - 1], this.fields[index]] = [this.fields[index], this.fields[index - 1]];
                },

                moveDown(index) {
                    if (index >= this.fields.length - 1) return;
                    [this.fields[index], this.fields[index + 1]] = [this.fields[index + 1], this.fields[index]];
                },

                syncSlug(title) {
                    // Tylko jeśli slug jest pusty lub identyczny z poprzednim tytułem (nowy formularz)
                    if (!this.$refs.slug || (this.$refs.slug.dataset.userEdited)) return;
                    this.$refs.slug.value = title
                        .toLowerCase()
                        .normalize('NFD')
                        .replace(/[̀-ͯ]/g, '')
                        .replace(/ą/g, 'a').replace(/ć/g, 'c').replace(/ę/g, 'e')
                        .replace(/ł/g, 'l').replace(/ń/g, 'n').replace(/ó/g, 'o')
                        .replace(/ś/g, 's').replace(/ź/g, 'z').replace(/ż/g, 'z')
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '')
                        .slice(0, 100);
                },
            }));
        });

        // Oznacz slug jako ręcznie edytowany — wtedy nie nadpisujemy go przy zmianie tytułu.
        document.addEventListener('DOMContentLoaded', () => {
            const slugInput = document.getElementById('slug');
            if (slugInput) {
                slugInput.addEventListener('input', () => {
                    slugInput.dataset.userEdited = '1';
                });
                @if ($form->exists)
                    slugInput.dataset.userEdited = '1';
                @endif
            }
        });
    </script>
@endsection
