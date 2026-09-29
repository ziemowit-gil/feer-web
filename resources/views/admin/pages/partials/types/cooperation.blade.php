{{-- Sekcja formularza strony: typ "cooperation" (wydzielona z admin/pages/form.blade.php; zmienne dziedziczone z formularza). --}}
{{-- ======= WSPÓŁPRACA — pola edycji sekcji ======= --}}
@php
    $cd = old('cooperation_data', $page->cooperation_data ?? []);
    $cdSectors = $cd['sectors'] ?? [
        ['icon' => 'fa-solid fa-building',    'color' => 'blue',   'title' => 'Biznes',               'text' => '', 'tag1' => 'CSR / ESG', 'tag2' => 'Wolontariat pracowniczy', 'tag3' => 'Wizerunek marki'],
        ['icon' => 'fa-solid fa-landmark',    'color' => 'green',  'title' => 'Samorząd i instytucje','text' => '', 'tag1' => 'Dialog obywatelski', 'tag2' => 'Polityka społeczna', 'tag3' => 'Aktywizacja lokalna'],
        ['icon' => 'fa-solid fa-flask',       'color' => 'purple', 'title' => 'Nauka i edukacja',     'text' => '', 'tag1' => 'Innowacje społeczne', 'tag2' => 'Praktyki i badania', 'tag3' => 'Transfer wiedzy'],
        ['icon' => 'fa-solid fa-people-group','color' => 'orange', 'title' => 'Inne NGO',             'text' => '', 'tag1' => 'Koalicje i synergia', 'tag2' => 'Wymiana zasobów', 'tag3' => 'Wspólny advocacy'],
    ];
    $cdForms = $cd['forms'] ?? [
        ['icon' => 'fa-solid fa-star',                    'title' => 'Partnerstwo strategiczne',  'text' => ''],
        ['icon' => 'fa-solid fa-circle-dollar-to-slot',   'title' => 'Sponsoring',               'text' => ''],
        ['icon' => 'fa-solid fa-user-gear',               'title' => 'Wolontariat kompetencyjny','text' => ''],
        ['icon' => 'fa-solid fa-sitemap',                 'title' => 'Koalicje i sieci',         'text' => ''],
    ];
@endphp
<div data-cooperation-fields class="space-y-6 border-t border-gray-100 pt-5 {{ $currentType === 'wspolpraca' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Współpraca — treść sekcji</p>

    {{-- Hero --}}
    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 space-y-3">
        <p class="text-xs font-bold uppercase text-muted">Hero</p>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-bold">Badge (tekst przy ikonie)</label>
                <input type="text" name="cooperation_data[hero_badge]" value="{{ $cd['hero_badge'] ?? 'Partnerstwo i współpraca' }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold">Tytuł sekcji (h1)</label>
                <p class="text-xs text-muted">Pobierany z pola <strong>Tytuł strony</strong> powyżej.</p>
            </div>
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold">Podtytuł hero</label>
            <textarea name="cooperation_data[hero_subtitle]" rows="2" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">{{ $cd['hero_subtitle'] ?? 'FEER łączy biznes, samorząd, naukę i organizacje pozarządowe wokół wspólnych wartości. Razem działamy skuteczniej, docieramy dalej i tworzymy zmiany, które zostają.' }}</textarea>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-bold">Przycisk CTA 1 — etykieta</label>
                <input type="text" name="cooperation_data[hero_cta1_label]" value="{{ $cd['hero_cta1_label'] ?? 'Zostań partnerem' }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold">Przycisk CTA 1 — URL</label>
                <input type="text" name="cooperation_data[hero_cta1_url]" value="{{ $cd['hero_cta1_url'] ?? '/kontakt' }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold">Przycisk CTA 2 — etykieta</label>
                <input type="text" name="cooperation_data[hero_cta2_label]" value="{{ $cd['hero_cta2_label'] ?? 'Poznaj formy współpracy' }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
        </div>
    </div>

    {{-- Statystyki --}}
    @php $cdStats = $cd['stats'] ?? [['value'=>'','label'=>''],['value'=>'','label'=>''],['value'=>'','label'=>''],['value'=>'','label'=>'']]; @endphp
    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 space-y-3">
        <p class="text-xs font-bold uppercase text-muted">Pasek liczb (opcjonalne — maks. 4)</p>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($cdStats as $si => $stat)
            <div class="flex gap-2">
                <div class="flex-1">
                    <label class="mb-0.5 block text-xs">Wartość {{ $si + 1 }}</label>
                    <input type="text" name="cooperation_data[stats][{{ $si }}][value]" value="{{ $stat['value'] ?? '' }}" placeholder="np. 15 lat" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                </div>
                <div class="flex-1">
                    <label class="mb-0.5 block text-xs">Etykieta {{ $si + 1 }}</label>
                    <input type="text" name="cooperation_data[stats][{{ $si }}][label]" value="{{ $stat['label'] ?? '' }}" placeholder="np. doświadczenia" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Sektory — dynamiczny repeater --}}
    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 space-y-3">
        <p class="text-xs font-bold uppercase text-muted">Sekcja: Dlaczego warto (sektory)</p>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-bold">Nagłówek sekcji</label>
                <input type="text" name="cooperation_data[sectors_heading]" value="{{ $cd['sectors_heading'] ?? 'Dlaczego warto z nami współpracować?' }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold">Podtytuł sekcji</label>
                <input type="text" name="cooperation_data[sectors_subtitle]" value="{{ $cd['sectors_subtitle'] ?? 'Każdy sektor ma inne potrzeby — mamy na to odpowiedź.' }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
        </div>
        <div data-repeater>
            <div data-repeater-rows class="space-y-3">
                @foreach ($cdSectors as $si => $sector)
                    <div data-repeater-row class="rounded border border-gray-200 bg-white p-3 space-y-2">
                        <div class="grid gap-2 sm:grid-cols-3">
                            <div>
                                <label class="mb-0.5 block text-xs">Ikona (fa-solid fa-…)</label>
                                <input type="text" name="cooperation_data[sectors][{{ $si }}][icon]" data-icon-picker data-icon-format="class" value="{{ $sector['icon'] ?? '' }}" placeholder="fa-solid fa-building" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                            </div>
                            <div>
                                <label class="mb-0.5 block text-xs">Kolor</label>
                                <select name="cooperation_data[sectors][{{ $si }}][color]" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                    @foreach (['blue' => 'Niebieski', 'green' => 'Zielony', 'purple' => 'Fioletowy', 'orange' => 'Pomarańczowy', 'brand' => 'Brand'] as $val => $lbl)
                                        <option value="{{ $val }}" {{ ($sector['color'] ?? 'blue') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-0.5 block text-xs">Tytuł</label>
                                <input type="text" name="cooperation_data[sectors][{{ $si }}][title]" value="{{ $sector['title'] ?? '' }}" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                            </div>
                        </div>
                        <div>
                            <label class="mb-0.5 block text-xs">Treść</label>
                            <textarea name="cooperation_data[sectors][{{ $si }}][text]" rows="2" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">{{ $sector['text'] ?? '' }}</textarea>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-3">
                            <div><label class="mb-0.5 block text-xs">Tag 1</label><input type="text" name="cooperation_data[sectors][{{ $si }}][tag1]" value="{{ $sector['tag1'] ?? '' }}" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand"></div>
                            <div><label class="mb-0.5 block text-xs">Tag 2</label><input type="text" name="cooperation_data[sectors][{{ $si }}][tag2]" value="{{ $sector['tag2'] ?? '' }}" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand"></div>
                            <div><label class="mb-0.5 block text-xs">Tag 3</label><input type="text" name="cooperation_data[sectors][{{ $si }}][tag3]" value="{{ $sector['tag3'] ?? '' }}" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand"></div>
                        </div>
                        <div class="flex items-center justify-end gap-1">
                            <button type="button" data-repeater-move="up" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                            <button type="button" data-repeater-move="down" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                            <button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-1.5 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń sektor"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                        </div>
                    </div>
                @endforeach
            </div>
            <button type="button" data-repeater-add class="mt-2 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj sektor</button>
            <template data-repeater-template>
                <div data-repeater-row class="rounded border border-gray-200 bg-white p-3 space-y-2">
                    <div class="grid gap-2 sm:grid-cols-3">
                        <div>
                            <label class="mb-0.5 block text-xs">Ikona (fa-solid fa-…)</label>
                            <input type="text" name="cooperation_data[sectors][__INDEX__][icon]" data-icon-picker data-icon-format="class" placeholder="fa-solid fa-building" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                        </div>
                        <div>
                            <label class="mb-0.5 block text-xs">Kolor</label>
                            <select name="cooperation_data[sectors][__INDEX__][color]" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                                <option value="blue">Niebieski</option>
                                <option value="green">Zielony</option>
                                <option value="purple">Fioletowy</option>
                                <option value="orange">Pomarańczowy</option>
                                <option value="brand">Brand</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-0.5 block text-xs">Tytuł</label>
                            <input type="text" name="cooperation_data[sectors][__INDEX__][title]" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                        </div>
                    </div>
                    <div>
                        <label class="mb-0.5 block text-xs">Treść</label>
                        <textarea name="cooperation_data[sectors][__INDEX__][text]" rows="2" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand"></textarea>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-3">
                        <div><label class="mb-0.5 block text-xs">Tag 1</label><input type="text" name="cooperation_data[sectors][__INDEX__][tag1]" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand"></div>
                        <div><label class="mb-0.5 block text-xs">Tag 2</label><input type="text" name="cooperation_data[sectors][__INDEX__][tag2]" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand"></div>
                        <div><label class="mb-0.5 block text-xs">Tag 3</label><input type="text" name="cooperation_data[sectors][__INDEX__][tag3]" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand"></div>
                    </div>
                    <div class="flex items-center justify-end gap-1">
                        <button type="button" data-repeater-move="up" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                        <button type="button" data-repeater-move="down" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                        <button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-1.5 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń sektor"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Formy współpracy — dynamiczny repeater --}}
    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 space-y-3">
        <p class="text-xs font-bold uppercase text-muted">Sekcja: Formy współpracy</p>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-bold">Nagłówek sekcji</label>
                <input type="text" name="cooperation_data[forms_heading]" value="{{ $cd['forms_heading'] ?? 'Formy współpracy' }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold">Podtytuł sekcji</label>
                <input type="text" name="cooperation_data[forms_subtitle]" value="{{ $cd['forms_subtitle'] ?? 'Wybierz formułę dopasowaną do Twoich możliwości i celów.' }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
        </div>
        <div data-repeater>
            <div data-repeater-rows class="space-y-3">
                @foreach ($cdForms as $fi => $form)
                    <div data-repeater-row class="rounded border border-gray-200 bg-white p-3 space-y-2">
                        <div class="grid gap-2 sm:grid-cols-2">
                            <div>
                                <label class="mb-0.5 block text-xs">Ikona (fa-solid fa-…)</label>
                                <input type="text" name="cooperation_data[forms][{{ $fi }}][icon]" data-icon-picker data-icon-format="class" value="{{ $form['icon'] ?? '' }}" placeholder="fa-solid fa-star" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                            </div>
                            <div>
                                <label class="mb-0.5 block text-xs">Tytuł</label>
                                <input type="text" name="cooperation_data[forms][{{ $fi }}][title]" value="{{ $form['title'] ?? '' }}" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                            </div>
                        </div>
                        <div>
                            <label class="mb-0.5 block text-xs">Treść</label>
                            <textarea name="cooperation_data[forms][{{ $fi }}][text]" rows="2" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">{{ $form['text'] ?? '' }}</textarea>
                        </div>
                        <div class="flex items-center justify-end gap-1">
                            <button type="button" data-repeater-move="up" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                            <button type="button" data-repeater-move="down" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                            <button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-1.5 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń formę"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                        </div>
                    </div>
                @endforeach
            </div>
            <button type="button" data-repeater-add class="mt-2 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj formę</button>
            <template data-repeater-template>
                <div data-repeater-row class="rounded border border-gray-200 bg-white p-3 space-y-2">
                    <div class="grid gap-2 sm:grid-cols-2">
                        <div>
                            <label class="mb-0.5 block text-xs">Ikona (fa-solid fa-…)</label>
                            <input type="text" name="cooperation_data[forms][__INDEX__][icon]" data-icon-picker data-icon-format="class" placeholder="fa-solid fa-star" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                        </div>
                        <div>
                            <label class="mb-0.5 block text-xs">Tytuł</label>
                            <input type="text" name="cooperation_data[forms][__INDEX__][title]" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                        </div>
                    </div>
                    <div>
                        <label class="mb-0.5 block text-xs">Treść</label>
                        <textarea name="cooperation_data[forms][__INDEX__][text]" rows="2" class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand"></textarea>
                    </div>
                    <div class="flex items-center justify-end gap-1">
                        <button type="button" data-repeater-move="up" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                        <button type="button" data-repeater-move="down" class="rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                        <button type="button" data-repeater-remove class="inline-flex items-center gap-1.5 rounded p-1.5 text-xs font-bold text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń formę"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- CTA --}}
    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 space-y-3">
        <p class="text-xs font-bold uppercase text-muted">Sekcja: CTA — Zacznijmy rozmowę</p>
        <div>
            <label class="mb-1 block text-xs font-bold">Nagłówek CTA</label>
            <input type="text" name="cooperation_data[cta_heading]" value="{{ $cd['cta_heading'] ?? 'Zacznijmy rozmowę' }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold">Treść CTA</label>
            <textarea name="cooperation_data[cta_text]" rows="2" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">{{ $cd['cta_text'] ?? 'Każda trwała współpraca zaczyna się od jednej wiadomości. Napisz do nas — opowiedz, kim jesteś i co chcesz osiągnąć, a my odpiszemy z propozycją kolejnych kroków.' }}</textarea>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-bold">Etykieta przycisku</label>
                <input type="text" name="cooperation_data[cta_button_label]" value="{{ $cd['cta_button_label'] ?? 'Napisz do nas' }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold">URL przycisku</label>
                <input type="text" name="cooperation_data[cta_button_url]" value="{{ $cd['cta_button_url'] ?? '/kontakt' }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
        </div>
    </div>

    {{-- Strona w budowie --}}
    <div class="rounded-lg border border-amber-200 bg-amber-50/50 p-4">
        <label class="flex cursor-pointer items-center gap-2.5 text-sm">
            <input type="hidden" name="cooperation_data[under_construction]" value="0">
            <input type="checkbox" name="cooperation_data[under_construction]" value="1"
                {{ !empty($cd['under_construction']) ? 'checked' : '' }}
                class="rounded border-gray-300 text-brand focus:ring-brand">
            <span class="font-semibold text-ink">Strona w budowie</span>
        </label>
        <p class="ml-6 mt-1 text-xs text-muted">Gdy zaznaczone, zamiast pełnej treści odwiedzający widzą komunikat „W przygotowaniu".</p>
    </div>

    {{-- Formularz zgłoszeniowy --}}
    <div class="rounded-lg border border-brand/20 bg-brand-light/30 p-4">
        <div class="mb-3 flex items-center justify-between">
            <div>
                <p class="text-sm font-bold text-ink">Formularz zgłoszeniowy</p>
                <p class="text-xs text-muted">Formularz dostępny pod adresem /{slug}/formularz</p>
            </div>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="hidden" name="cooperation_data[form_enabled]" value="0">
                <input type="checkbox" name="cooperation_data[form_enabled]" value="1"
                    {{ !empty($cd['form_enabled']) ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand focus:ring-brand">
                <span class="font-semibold text-ink">Aktywny</span>
            </label>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-bold">Tytuł formularza</label>
                <input type="text" name="cooperation_data[form_title]"
                    value="{{ $cd['form_title'] ?? 'Formularz współpracy' }}"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold">E-mail odbiorcy zgłoszeń</label>
                <input type="email" name="cooperation_data[form_recipient]"
                    value="{{ $cd['form_recipient'] ?? '' }}"
                    placeholder="Domyślnie: kontaktowy z Ustawień"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-bold">Podtytuł / intro formularza</label>
                <input type="text" name="cooperation_data[form_subtitle]"
                    value="{{ $cd['form_subtitle'] ?? '' }}"
                    placeholder="np. Wypełnij poniższe pola — odezwiemy się w ciągu 2 dni roboczych."
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-bold">Komunikat po wysłaniu</label>
                <input type="text" name="cooperation_data[form_confirmation]"
                    value="{{ $cd['form_confirmation'] ?? '' }}"
                    placeholder="np. Dziękujemy! Odezwiemy się wkrótce."
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
        </div>
    </div>
</div>
