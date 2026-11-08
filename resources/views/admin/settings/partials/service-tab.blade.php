{{--
    Zakładka „Serwis” (Ustawienia → Serwis): stan witryny na górze (zawsze widoczny), pod nim karty
    (admin/partials/section-cards): Przerwa techniczna, Dopiero tworzymy, Komunikat, Żałoba, Adres techniczny.
    Nazwy pól bez zmian (SiteSettingController). Stan na górze reaguje na zaznaczenia na bieżąco (Alpine); każdy stan
    ma też nazwę słowną, nie tylko kolor. Dołączana wewnątrz kontenera `tab === 'maintenance'`.
--}}
@php
    $svcMourningPlan = $settings->mourning_from || $settings->mourning_until
        ? trim(($settings->mourning_from ? 'od '.$settings->mourning_from->format('d.m.Y') : '').' '.($settings->mourning_until ? 'do '.$settings->mourning_until->format('d.m.Y') : ''))
        : null;
    $svcInput = 'w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark';
    $svcCheck = 'mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark';
@endphp
<div class="space-y-6" data-cards="maintenance" data-cards-label="Karty sekcji Serwis"
     x-data="{
        maintenance: {{ old('maintenance_mode', $settings->maintenance_mode) ? 'true' : 'false' }},
        building: {{ old('under_construction', $settings->under_construction) ? 'true' : 'false' }},
        mourning: {{ old('mourning_mode', $settings->mourning_mode) ? 'true' : 'false' }},
        notice: {{ old('notice_enabled', $settings->notice_enabled) ? 'true' : 'false' }},
     }">

    {{-- Stan witryny --}}
    <section aria-labelledby="svc-status-h" class="rounded-lg border border-gray-200 bg-gray-50 p-5">
        <h2 id="svc-status-h" class="text-base font-bold text-ink">Stan witryny teraz</h2>
        <p class="mt-1 text-xs text-muted">Podsumowanie zmienia się razem z zaznaczeniami w kartach poniżej; obowiązuje dopiero po zapisaniu.</p>
        <dl class="mt-4 grid gap-x-8 gap-y-2 text-sm sm:grid-cols-2">
            <div class="flex justify-between gap-4 border-b border-gray-200 py-1.5">
                <dt>Przerwa techniczna</dt>
                <dd class="font-bold" x-text="maintenance ? 'Włączona' : 'Wyłączona'"></dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-gray-200 py-1.5">
                <dt>Strona „dopiero tworzymy”</dt>
                <dd class="font-bold" x-text="maintenance && building ? 'Włączona (przykryta przerwą techniczną)' : (building ? 'Włączona' : 'Wyłączona')"></dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-gray-200 py-1.5">
                <dt>Żałoba (czarno-biała witryna)</dt>
                <dd class="font-bold" x-text="mourning ? 'Włączona ręcznie' : '{{ $settings->mourningActive() ? 'Włączona według dat' : ($svcMourningPlan ? 'Zaplanowana '.$svcMourningPlan : 'Wyłączona') }}'"></dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-gray-200 py-1.5">
                <dt>Ramka z komunikatem</dt>
                <dd class="font-bold" x-text="notice ? 'Włączona' : 'Wyłączona'"></dd>
            </div>
        </dl>
    </section>

    {{-- Przerwa techniczna --}}
    <section aria-labelledby="svc-maintenance-h" class="rounded-lg border border-gray-200 p-5"
             data-card="przerwa" data-card-label="Przerwa techniczna">
        <h2 id="svc-maintenance-h" class="text-base font-bold text-ink" data-card-title>Przerwa techniczna (tryb konserwacji)</h2>
        <p class="mt-1 text-xs text-muted">Zwykli użytkownicy zobaczą komunikat o przerwie (HTTP 503) zamiast witryny. Zalogowani administratorzy mają dalej dostęp do panelu i strony, aby móc wyłączyć tryb. Przerwa techniczna ma pierwszeństwo przed stroną „dopiero tworzymy”.</p>
        <label class="mt-4 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm">
            <input type="checkbox" name="maintenance_mode" value="1" x-model="maintenance" @checked(old('maintenance_mode', $settings->maintenance_mode)) class="{{ $svcCheck }}">
            <span>
                <span class="font-bold text-amber-900">Włącz tryb konserwacji</span>
                <span class="block text-xs text-amber-800">Strona publiczna zostanie zastąpiona komunikatem o przerwie technicznej.</span>
            </span>
        </label>
        <div class="mt-4" x-show="maintenance" x-cloak>
            <label for="maintenance_message" class="mb-1 block text-sm font-bold">Komunikat dla użytkowników</label>
            <textarea id="maintenance_message" name="maintenance_message" rows="3" placeholder="{{ \App\Models\SiteSetting::DEFAULT_MAINTENANCE_MESSAGE }}" class="{{ $svcInput }}">{{ old('maintenance_message', $settings->maintenance_message) }}</textarea>
            <p class="mt-1 text-xs text-muted">Puste = komunikat domyślny (widoczny jako podpowiedź).</p>
            @error('maintenance_message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <p class="mt-4 flex items-start gap-2 rounded-md bg-red-50 px-3 py-2 text-xs text-red-800" x-show="maintenance" x-cloak>
            <i class="fa-solid fa-shield-halved mt-0.5" aria-hidden="true"></i>
            <span>W trybie konserwacji <strong>logowanie przez Microsoft 365 jest całkowicie zablokowane</strong>. Zaloguj się loginem i hasłem, aby wyłączyć tryb.</span>
        </p>
    </section>

    {{-- Strona „dopiero tworzymy” --}}
    <section aria-labelledby="svc-building-h" class="rounded-lg border border-gray-200 p-5"
             data-card="w-budowie" data-card-label="Dopiero tworzymy">
        <h2 id="svc-building-h" class="text-base font-bold text-ink" data-card-title>Strona „dopiero tworzymy”</h2>
        <p class="mt-1 text-xs text-muted">Krótka strona informacyjna, że witryna jest w budowie, zamiast witryny. Zalogowani użytkownicy panelu widzą witrynę normalnie. Nowe sub-witryny startują z włączoną stroną.</p>
        <label class="mt-4 flex items-start gap-2 rounded-lg border border-sky-200 bg-sky-50 p-3 text-sm">
            <input type="checkbox" name="under_construction" value="1" x-model="building" @checked(old('under_construction', $settings->under_construction)) class="{{ $svcCheck }}">
            <span>
                <span class="font-bold text-sky-900">Pokaż stronę „dopiero tworzymy”</span>
                <span class="block text-xs text-sky-800">Wyłącz, gdy witryna jest gotowa do publikacji.</span>
            </span>
        </label>
        <div class="mt-4" x-show="building" x-cloak>
            <label for="under_construction_message" class="mb-1 block text-sm font-bold">Tekst na stronie</label>
            <textarea id="under_construction_message" name="under_construction_message" rows="3" placeholder="{{ \App\Models\SiteSetting::DEFAULT_UNDER_CONSTRUCTION_MESSAGE }}" class="{{ $svcInput }}">{{ old('under_construction_message', $settings->under_construction_message) }}</textarea>
            <p class="mt-1 text-xs text-muted">Puste = tekst domyślny (widoczny jako podpowiedź).</p>
            @error('under_construction_message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </section>

    {{-- Ramka z komunikatem --}}
    <section aria-labelledby="svc-notice-h" class="rounded-lg border border-gray-200 p-5"
             data-card="komunikat" data-card-label="Komunikat">
        <h2 id="svc-notice-h" class="text-base font-bold text-ink" data-card-title>Ramka z komunikatem</h2>
        <p class="mt-1 text-xs text-muted">Krótka, wyróżniona informacja na stronie głównej i/lub w Kontakcie (np. zmiana godzin pracy, przerwa urlopowa).</p>
        <label class="mt-4 flex items-start gap-2 rounded-lg border border-sky-200 bg-sky-50 p-3 text-sm">
            <input type="checkbox" name="notice_enabled" value="1" x-model="notice" @checked(old('notice_enabled', $settings->notice_enabled)) class="{{ $svcCheck }}">
            <span class="font-bold text-sky-900">Pokaż ramkę z komunikatem</span>
        </label>
        <div class="mt-4 space-y-4" x-show="notice" x-cloak>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="notice_title" class="mb-1 block text-sm font-bold">Nagłówek (opcjonalny)</label>
                    <input type="text" id="notice_title" name="notice_title" maxlength="120" value="{{ old('notice_title', $settings->notice_title) }}" class="{{ $svcInput }}">
                    @error('notice_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="notice_style" class="mb-1 block text-sm font-bold">Wygląd</label>
                    <select id="notice_style" name="notice_style" class="{{ $svcInput }}">
                        @foreach (\App\Models\SiteSetting::NOTICE_STYLES as $key => $label)
                            <option value="{{ $key }}" @selected(old('notice_style', $settings->notice_style ?: 'info') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label for="notice_text" class="mb-1 block text-sm font-bold">Treść komunikatu</label>
                <textarea id="notice_text" name="notice_text" rows="3" maxlength="1000" class="{{ $svcInput }}">{{ old('notice_text', $settings->notice_text) }}</textarea>
                @error('notice_text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <fieldset>
                <legend class="mb-1 text-sm font-bold">Gdzie pokazać</legend>
                <label class="mr-6 inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="notice_on_home" value="1" @checked(old('notice_on_home', $settings->notice_on_home ?? true)) class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark"> Strona główna
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="notice_on_contact" value="1" @checked(old('notice_on_contact', $settings->notice_on_contact ?? true)) class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark"> Kontakt
                </label>
            </fieldset>
        </div>
    </section>

    {{-- Żałoba narodowa --}}
    <section aria-labelledby="svc-mourning-h" class="rounded-lg border border-gray-200 p-5"
             data-card="zaloba" data-card-label="Żałoba narodowa">
        <h2 id="svc-mourning-h" class="text-base font-bold text-ink" data-card-title>Żałoba narodowa</h2>
        <p class="mt-1 text-xs text-muted">Cała witryna publiczna wyświetla się odwiedzającym w odcieniach szarości (czerń i biel). Panel administracyjny zostaje kolorowy, a zalogowany administrator widzi także samą witrynę w kolorach (żeby mógł ją edytować); żałobę widzą wszyscy pozostali.</p>
        <label class="mt-4 flex items-start gap-2 rounded-lg border border-sky-200 bg-sky-50 p-3 text-sm">
            <input type="checkbox" name="mourning_mode" value="1" x-model="mourning" @checked(old('mourning_mode', $settings->mourning_mode)) class="{{ $svcCheck }}">
            <span>
                <span class="font-bold text-sky-900">Włącz tryb czarno-biały (żałoba)</span>
                <span class="block text-xs text-sky-800">Wyłącz po zakończeniu żałoby.</span>
            </span>
        </label>
        <fieldset class="mt-4">
            <legend class="text-sm font-bold">Albo zaplanuj z góry</legend>
            <p class="mt-1 text-xs text-muted">W podanym przedziale dat (włącznie) tryb włączy się i wyłączy sam. Zostaw puste, jeśli nie planujesz.</p>
            <div class="mt-2 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="mourning_from" class="mb-1 block text-sm font-bold">Od dnia</label>
                    <input type="date" id="mourning_from" name="mourning_from" value="{{ old('mourning_from', $settings->mourning_from?->format('Y-m-d')) }}" class="{{ $svcInput }}">
                    @error('mourning_from') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="mourning_until" class="mb-1 block text-sm font-bold">Do dnia (włącznie)</label>
                    <input type="date" id="mourning_until" name="mourning_until" value="{{ old('mourning_until', $settings->mourning_until?->format('Y-m-d')) }}" class="{{ $svcInput }}">
                    @error('mourning_until') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>
    </section>

    {{-- Adres techniczny --}}
    <section aria-labelledby="svc-url-h" class="rounded-lg border border-gray-200 p-5"
             data-card="adres" data-card-label="Adres techniczny">
        <h2 id="svc-url-h" class="text-base font-bold text-ink" data-card-title>Adres techniczny</h2>
        <label for="site_url" class="mb-1 mt-3 block text-sm font-bold">Główny adres strony (Site URL)</label>
        <input type="url" id="site_url" name="site_url" value="{{ old('site_url', $settings->site_url) }}" placeholder="{{ config('app.url') }}" class="{{ $svcInput }} font-mono text-sm">
        <p class="mt-1 text-xs text-muted">Adres używany do budowania linków bezwzględnych (mapa strony, e-maile, powiadomienia, adres powrotny logowania Microsoft). Puste = wartość <code>APP_URL</code> z pliku <code>.env</code>. Zmieniaj tylko, gdy domena serwisu jest inna niż wykrywana automatycznie.</p>
        @error('site_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </section>
</div>
