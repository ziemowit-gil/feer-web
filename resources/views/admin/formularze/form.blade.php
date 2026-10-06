@extends('admin.layout')

@section('title', $form->exists ? 'Edytuj formularz' : 'Nowy formularz')

@section('content')
    @if ($errors->any())
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            <p class="font-bold">Popraw błędy:</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('status'))
        <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700" role="status">
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
        <fieldset class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
            <legend class="px-2 text-sm font-bold text-ink">Podstawowe informacje</legend>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="title" class="mb-1 block text-sm font-bold">
                        Nazwa formularza <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input id="title" name="title" type="text" required
                        value="{{ old('title', $form->title) }}"
                        @input="syncSlug($el.value)"
                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
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
                            class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
                    </div>
                    @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="description" class="mb-1 block text-sm font-bold">Opis (widoczny dla edytorów)</label>
                <textarea id="description" name="description" rows="2"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">{{ old('description', $form->description) }}</textarea>
            </div>

            <div class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <input id="is_active" name="is_active" type="checkbox" value="1"
                    {{ old('is_active', $form->is_active ?? true) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand focus:ring-brand">
                <label for="is_active" class="text-sm font-medium">Formularz aktywny (publiczny)</label>
            </div>
        </fieldset>

        {{-- Builder pól --}}
        <fieldset class="rounded-lg border border-gray-200 bg-white p-6">
            <legend class="px-2 text-sm font-bold text-ink">Pola formularza</legend>

            <p class="mb-4 text-sm text-muted">Dodaj pola, które użytkownicy będą wypełniać. Przeciągnij wiersze, aby zmienić kolejność.</p>

            <div class="space-y-3" id="fields-list">
                <template x-for="(field, index) in fields" :key="field._id">
                    <div class="group relative rounded-lg border border-gray-200 bg-gray-50 p-4"
                        :data-index="index">

                        <input type="hidden" :name="'fields[' + index + '][type]'" x-model="field.type">
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
                                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
                                    </div>
                                    <div>
                                        <label :for="'field-type-' + index" class="mb-1 block text-xs font-bold text-muted uppercase tracking-wide">Typ pola</label>
                                        <select :id="'field-type-' + index"
                                            :name="'fields[' + index + '][type]'"
                                            x-model="field.type"
                                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
                                            @foreach ($types as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <label :for="'field-placeholder-' + index" class="mb-1 block text-xs font-bold text-muted uppercase tracking-wide">Placeholder</label>
                                        <input :id="'field-placeholder-' + index"
                                            :name="'fields[' + index + '][placeholder]'"
                                            type="text" x-model="field.placeholder"
                                            placeholder="Tekst pomocniczy w pustym polu"
                                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
                                    </div>
                                    <div>
                                        <label :for="'field-help-' + index" class="mb-1 block text-xs font-bold text-muted uppercase tracking-wide">Tekst pomocy</label>
                                        <input :id="'field-help-' + index"
                                            :name="'fields[' + index + '][help_text]'"
                                            type="text" x-model="field.help_text"
                                            placeholder="Dodatkowy opis pod polem"
                                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
                                    </div>
                                </div>

                                {{-- Opcje (dla select/radio) --}}
                                <div x-show="field.type === 'select' || field.type === 'radio'">
                                    <label :for="'field-options-' + index" class="mb-1 block text-xs font-bold text-muted uppercase tracking-wide">
                                        Opcje (oddzielone przecinkiem)
                                    </label>
                                    <input :id="'field-options-' + index"
                                        :name="'fields[' + index + '][options]'"
                                        type="text" x-model="field.options"
                                        placeholder="np. Tak, Nie, Nie wiem"
                                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
                                </div>

                                <div class="flex items-center gap-3">
                                    <input :id="'field-required-' + index"
                                        type="checkbox" x-model="field.required"
                                        class="rounded border-gray-300 text-brand focus:ring-brand">
                                    <label :for="'field-required-' + index" class="text-sm font-medium">Pole wymagane</label>
                                </div>
                            </div>

                            {{-- Przyciski operacji --}}
                            <div class="flex shrink-0 flex-col gap-1">
                                <button type="button" @click="moveUp(index)" :disabled="index === 0"
                                    class="rounded p-1.5 text-gray-400 hover:text-brand disabled:opacity-30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                    :aria-label="'Przesuń pole „' + field.label + '" w górę'">
                                    <i class="fa-solid fa-arrow-up text-xs" aria-hidden="true"></i>
                                </button>
                                <button type="button" @click="moveDown(index)" :disabled="index === fields.length - 1"
                                    class="rounded p-1.5 text-gray-400 hover:text-brand disabled:opacity-30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                    :aria-label="'Przesuń pole „' + field.label + '" w dół'">
                                    <i class="fa-solid fa-arrow-down text-xs" aria-hidden="true"></i>
                                </button>
                                <button type="button" @click="removeField(index)"
                                    class="rounded p-1.5 text-gray-400 hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                                    :aria-label="'Usuń pole „' + field.label + '"'">
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
                        class="rounded border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-ink hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                        <i class="fa-solid fa-plus mr-1" aria-hidden="true"></i>{{ $label }}
                    </button>
                @endforeach
            </div>
        </fieldset>

        {{-- Ustawienia --}}
        <fieldset class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
            <legend class="px-2 text-sm font-bold text-ink">Ustawienia formularza</legend>

            <div>
                <label for="confirmation_message" class="mb-1 block text-sm font-bold">
                    Wiadomość po wysłaniu
                </label>
                <textarea id="confirmation_message" name="settings[confirmation_message]" rows="2"
                    placeholder="Dziękujemy! Twoje zgłoszenie zostało przyjęte."
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">{{ old('settings.confirmation_message', $form->settings['confirmation_message'] ?? '') }}</textarea>
            </div>

            {{-- Powiadomienia e-mail o zgłoszeniach --}}
            @php
                $siteCfg = \App\Models\SiteSetting::current();
                $graphReady = $siteCfg->msGraphConfigured();
                $formMailer = old('settings.mailer', $form->settings['mailer'] ?? '');
                $inheritedLabel = $siteCfg->formsMailer() === 'msgraph'
                    ? 'Microsoft 365 (Graph API)'
                    : (\App\Models\SiteSetting::MAIL_TRANSPORTS[$siteCfg->mail_transport ?? 'default'] ?? 'domyślny tryb wysyłki');
            @endphp
            <fieldset class="space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                <legend class="px-1 text-sm font-bold text-ink"><i class="fa-solid fa-envelope mr-1 text-brand" aria-hidden="true"></i> Powiadomienia e-mail o zgłoszeniach</legend>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="notification_email" class="mb-1 block text-sm font-bold">Odbiorcy powiadomień</label>
                        <input id="notification_email" name="settings[notification_email]" type="text" inputmode="email" autocomplete="off"
                            value="{{ old('settings.notification_email', $form->settings['notification_email'] ?? '') }}"
                            placeholder="np. biuro@feer.org.pl, sekretariat@feer.org.pl"
                            aria-describedby="notification_email_help"
                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
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
                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
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
                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
                        <p id="notification_subject_help" class="mt-1 text-xs text-muted">Znaczniki: <code>{tytul}</code> — nazwa formularza, <code>{id}</code> — numer zgłoszenia.</p>
                    </div>
                    <div>
                        <label for="form_mailer" class="mb-1 block text-sm font-bold">Sposób wysyłki</label>
                        <select id="form_mailer" name="settings[mailer]" aria-describedby="form_mailer_help"
                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
                            <option value="" @selected($formMailer === '')>Dziedzicz z ustawień ({{ $inheritedLabel }})</option>
                            <option value="msgraph" @selected($formMailer === 'msgraph')>Microsoft 365 (Graph API){{ $graphReady ? '' : ' — nieskonfigurowany' }}</option>
                            <option value="default" @selected($formMailer === 'default')>Domyślny tryb wysyłki serwisu</option>
                        </select>
                        <p id="form_mailer_help" class="mt-1 text-xs text-muted">
                            Formularze domyślnie wysyłają przez Microsoft Graph, gdy jest skonfigurowany w
                            <a href="{{ route('admin.ustawienia.edit', ['tab' => 'mail']) }}" class="text-brand underline-offset-2 hover:underline">Ustawienia → Poczta</a>.
                            @unless ($graphReady) <span class="text-amber-700">Graph nie jest jeszcze skonfigurowany — do czasu uzupełnienia danych używany jest domyślny tryb.</span> @endunless
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-3">
                        <input type="hidden" name="settings[reply_to_submitter]" value="0">
                        <input type="checkbox" name="settings[reply_to_submitter]" value="1"
                            @checked(old('settings.reply_to_submitter', $form->settings['reply_to_submitter'] ?? true))
                            class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                        <span>
                            <span class="block text-sm font-bold">Odpowiedz-do: adres zgłaszającego</span>
                            <span class="block text-xs text-muted">Jeśli formularz ma pole „Adres e-mail”, odpowiedź na powiadomienie trafi wprost do tej osoby.</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-3">
                        <input type="hidden" name="settings[send_copy_to_submitter]" value="0">
                        <input type="checkbox" name="settings[send_copy_to_submitter]" value="1"
                            @checked(old('settings.send_copy_to_submitter', $form->settings['send_copy_to_submitter'] ?? false))
                            class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
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
                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">{{ old('settings.submitter_copy_message', $form->settings['submitter_copy_message'] ?? '') }}</textarea>
                </div>
            </fieldset>

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
                        class="w-full max-w-sm rounded border-gray-300 text-sm focus:border-brand focus-visible:ring-2 focus-visible:ring-brand">
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
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm">
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
                class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                Zapisz formularz
            </button>
            <a href="{{ route('admin.formularze.index') }}"
                class="text-sm text-muted hover:text-brand focus-visible:rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                Anuluj
            </a>
            @if ($form->exists)
                <a href="{{ route('admin.formularze.zgloszenia', $form) }}"
                    class="ml-auto text-sm text-muted hover:text-brand focus-visible:rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                    <i class="fa-solid fa-inbox mr-1" aria-hidden="true"></i>
                    Zgłoszenia ({{ $form->submissions()->count() }})
                </a>
            @endif
        </div>
    </form>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('formBuilder', (initialFields) => ({
                fields: (initialFields || []).map((f, i) => ({ ...f, _id: i })),
                _nextId: (initialFields || []).length,

                addField(type) {
                    this.fields.push({
                        _id:         this._nextId++,
                        label:       '',
                        type:        type,
                        required:    false,
                        placeholder: '',
                        options:     '',
                        help_text:   '',
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
