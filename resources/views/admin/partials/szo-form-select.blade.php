{{--
    Wybór formularza z SZO osadzanego na stronie treści (HasSzoForm).
    Parametry: $model (szo_form_slug), $szoForms (SzoClient::forms()), $help (opis, gdzie pojawi się formularz),
    opcjonalnie $label i $emptyLabel.
--}}
<div>
    <label for="szo_form_slug" class="mb-1 block text-sm font-bold">{{ $label ?? 'Formularz zgłoszeniowy z SZO' }} <span class="font-normal text-muted">(opcjonalnie)</span></label>
    @if (empty($szoForms))
        <p class="text-sm text-muted">
            Lista formularzy z SZO jest niedostępna — integracja jest wyłączona albo SZO nie odpowiada
            (Ustawienia → Integracje → SZO).
            @if ($model->szo_form_slug) Zostaje dotychczasowy formularz: <code>{{ $model->szo_form_slug }}</code>.@endif
        </p>
        <input type="hidden" name="szo_form_slug" value="{{ old('szo_form_slug', $model->szo_form_slug) }}">
    @else
        <select id="szo_form_slug" name="szo_form_slug" aria-describedby="szo_form_slug_help"
            class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark sm:max-w-md">
            <option value="">{{ $emptyLabel ?? '— bez formularza (przycisk z linkiem lub e-mailem) —' }}</option>
            @foreach ($szoForms as $szoForm)
                <option value="{{ $szoForm['slug'] }}" @selected(old('szo_form_slug', $model->szo_form_slug) === $szoForm['slug'])>
                    {{ $szoForm['title'] }} ({{ $szoForm['slug'] }})
                </option>
            @endforeach
        </select>
        <p id="szo_form_slug_help" class="mt-1 text-xs text-muted">
            {{ $help }} Zgłoszenie założy kontakt w CRM i trafi do Skrzynki SZO; kopia zostaje w Formularze → „SZO: …”.
            Pola i zgody odświeżają się z SZO przy każdym zapisie.
        </p>
    @endif
    @error('szo_form_slug')
        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
