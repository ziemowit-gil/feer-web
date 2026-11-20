{{--
    Ustawienia dziedziczone w głąb drzewa (slide w TYPO3): baner sekcji i kontakt opiekuna działu.
    Ustawione na stronie działu obowiązują na wszystkich jej podstronach; podstrona może je nadpisać
    własną wartością albo wyłączyć („Bez…” — także dla jej podstron bez własnej wartości).
    Puste pola = dziedziczy z najbliższej strony nadrzędnej (Page::inheritedSetting()).
--}}
@php
    $ld = (array) old('layout_data', $page->layout_data ?? []);
    $inhBanner = $page->inheritedFromAncestors('banner');
    $inhContact = $page->inheritedFromAncestors('contact');
    $inhInp = 'w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark';
    $inhSource = fn (?array $inh) => $inh
        ? '<a href="'.e(route('admin.podstrony.index', ['wybrana' => $inh['source']->id])).'" class="font-bold text-brand-dark underline">'.e($inh['source']->title).'</a>'
        : null;
    $hasOwn = filled($ld['banner_image'] ?? null) || filled($ld['contact_name'] ?? null) || ! empty($ld['banner_off']) || ! empty($ld['contact_off']);
@endphp
<details class="pe-section rounded-md bg-white" data-pe-section="dziedziczone" data-hide-for-link @class(['hidden' => $isLinkType]) @if ($hasOwn) open @endif>
    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-6 py-4 text-base font-bold text-ink [&::-webkit-details-marker]:hidden">
        <span>Baner i kontakt działu <span class="font-normal text-muted">(dziedziczone przez podstrony)</span></span>
        <i class="fa-solid fa-chevron-down text-sm text-muted transition-transform" aria-hidden="true"></i>
    </summary>
    <div class="space-y-8 border-t border-gray-100 px-6 pb-6 pt-5">
        <p class="text-sm text-muted">Ustaw raz na stronie działu — pokaże się na wszystkich jej podstronach. Podstrona może podać własne albo zaznaczyć „Bez…”. Puste pola = tak jak na stronie nadrzędnej.</p>

        {{-- Baner sekcji --}}
        <fieldset class="space-y-4">
            <legend class="text-sm font-bold uppercase tracking-widest text-muted">Baner sekcji</legend>
            <p class="text-sm text-ink" id="inh-banner-state">
                @if (filled($ld['banner_image'] ?? null))
                    Ta strona ma własny baner{{ $inhBanner ? ' (zastępuje baner ze strony nadrzędnej)' : '' }}.
                @elseif (! empty($ld['banner_off']))
                    Baner wyłączony na tej stronie i jej podstronach bez własnego.
                @elseif ($inhBanner)
                    Dziedziczy baner ze strony {!! $inhSource($inhBanner) !!}.
                @else
                    Brak banera — ani ta strona, ani strony nadrzędne go nie mają.
                @endif
            </p>
            @if ($inhBanner && blank($ld['banner_image'] ?? null))
                <img src="{{ $inhBanner['values']['banner_image'] }}" alt="Dziedziczony baner: {{ $inhBanner['values']['banner_alt'] ?: 'bez opisu' }}" class="h-20 w-full max-w-sm rounded-md object-cover">
            @endif
            <div>
                <label for="ld_banner_image" class="mb-1 block text-sm font-bold">Własny baner — adres obrazu</label>
                <input type="text" id="ld_banner_image" name="layout_data[banner_image]" value="{{ $ld['banner_image'] ?? '' }}" maxlength="1000"
                    data-library-url data-library-alt="ld_banner_alt" placeholder="wybierz z biblioteki albo wklej adres" aria-describedby="inh-banner-state ld_banner_image_hint"
                    class="{{ $inhInp }}">
                <p id="ld_banner_image_hint" class="mt-1 text-xs text-muted">Szeroki obraz nad treścią strony (w układzie domyślnym i szerokim), np. 1600 × 400 px.</p>
                @error('layout_data.banner_image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="ld_banner_alt" class="mb-1 block text-sm font-bold">Opis banera dla czytników ekranu</label>
                <input type="text" id="ld_banner_alt" name="layout_data[banner_alt]" value="{{ $ld['banner_alt'] ?? '' }}" maxlength="255" aria-describedby="ld_banner_alt_hint" class="{{ $inhInp }}">
                <p id="ld_banner_alt_hint" class="mt-1 text-xs text-muted">Zostaw puste, jeśli obraz jest tylko ozdobą.</p>
                @error('layout_data.banner_alt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-start gap-2 text-sm">
                <input type="hidden" name="layout_data[banner_off]" value="0">
                <input type="checkbox" name="layout_data[banner_off]" value="1" @checked(! empty($ld['banner_off'])) class="mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                <span><span class="font-bold">Bez banera</span> — na tej stronie i jej podstronach, które nie mają własnego.</span>
            </label>
        </fieldset>

        {{-- Kontakt opiekuna działu --}}
        <fieldset class="space-y-4">
            <legend class="text-sm font-bold uppercase tracking-widest text-muted">Kontakt opiekuna działu</legend>
            <p class="text-sm text-ink" id="inh-contact-state">
                @if (filled($ld['contact_name'] ?? null))
                    Ta strona ma własny kontakt{{ $inhContact ? ' (zastępuje kontakt ze strony nadrzędnej)' : '' }}.
                @elseif (! empty($ld['contact_off']))
                    Kontakt wyłączony na tej stronie i jej podstronach bez własnego.
                @elseif ($inhContact)
                    Dziedziczy kontakt ze strony {!! $inhSource($inhContact) !!}: {{ collect($inhContact['values'])->filter()->implode(', ') }}.
                @else
                    Brak kontaktu — ani ta strona, ani strony nadrzędne go nie mają.
                @endif
            </p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="ld_contact_name" class="mb-1 block text-sm font-bold">Imię i nazwisko</label>
                    <input type="text" id="ld_contact_name" name="layout_data[contact_name]" value="{{ $ld['contact_name'] ?? '' }}" maxlength="120" aria-describedby="inh-contact-state" class="{{ $inhInp }}">
                    @error('layout_data.contact_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="ld_contact_role" class="mb-1 block text-sm font-bold">Rola <span class="font-normal text-muted">(np. koordynatorka działu)</span></label>
                    <input type="text" id="ld_contact_role" name="layout_data[contact_role]" value="{{ $ld['contact_role'] ?? '' }}" maxlength="120" class="{{ $inhInp }}">
                    @error('layout_data.contact_role') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="ld_contact_email" class="mb-1 block text-sm font-bold">E-mail</label>
                    <input type="email" id="ld_contact_email" name="layout_data[contact_email]" value="{{ $ld['contact_email'] ?? '' }}" maxlength="190" autocomplete="off" class="{{ $inhInp }}">
                    @error('layout_data.contact_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="ld_contact_phone" class="mb-1 block text-sm font-bold">Telefon</label>
                    <input type="text" id="ld_contact_phone" name="layout_data[contact_phone]" value="{{ $ld['contact_phone'] ?? '' }}" maxlength="40" autocomplete="off" class="{{ $inhInp }}">
                    @error('layout_data.contact_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="text-xs text-muted">Kontakt pokazuje się pod treścią strony jako „Kontakt w sprawie tej strony”. Wymagane jest imię i nazwisko — bez niego pozostałe pola nie zostaną użyte.</p>
            <label class="flex items-start gap-2 text-sm">
                <input type="hidden" name="layout_data[contact_off]" value="0">
                <input type="checkbox" name="layout_data[contact_off]" value="1" @checked(! empty($ld['contact_off'])) class="mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                <span><span class="font-bold">Bez kontaktu</span> — na tej stronie i jej podstronach, które nie mają własnego.</span>
            </label>
        </fieldset>
    </div>
</details>
