{{-- ═══ REKRUTACJA DO PROJEKTU ═══ --}}
@php
    $recLists = [
        'audience' => ['Dla kogo jest projekt', 'Dodaj grupę', 'Usuń grupę', ['text' => ['label' => 'Grupa uczestników', 'ph' => 'np. Osoby z niepełnosprawnością w wieku 18–29 lat']]],
        'requirements' => ['Kryteria udziału (obowiązkowe)', 'Dodaj kryterium', 'Usuń kryterium', ['text' => ['label' => 'Kryterium', 'ph' => 'np. Orzeczenie o niepełnosprawności']]],
        'bonus' => ['Kryteria premiujące (opcjonalnie z punktami)', 'Dodaj kryterium premiujące', 'Usuń kryterium premiujące', ['text' => ['label' => 'Kryterium premiujące', 'ph' => 'np. Zamieszkanie na wsi'], 'points' => ['label' => 'Punkty', 'ph' => 'np. +5 pkt']]],
        'benefits' => ['Co oferujemy uczestnikom (ikona, nazwa, opis)', 'Dodaj formę wsparcia', 'Usuń formę wsparcia', ['icon' => ['label' => 'Ikona', 'ph' => 'fa-solid fa-check'], 'title' => ['label' => 'Forma wsparcia', 'ph' => 'np. Szkolenie zawodowe'], 'text' => ['label' => 'Opis', 'ph' => 'np. 80 godzin, z certyfikatem']]],
        'steps' => ['Jak się zgłosić — etapy rekrutacji', 'Dodaj etap', 'Usuń etap', ['title' => ['label' => 'Etap', 'ph' => 'np. Złożenie formularza'], 'text' => ['label' => 'Opis etapu', 'ph' => 'Co trzeba zrobić, w jakim terminie']]],
        'docs' => ['Dokumenty rekrutacyjne (regulamin, formularz, oświadczenia)', 'Dodaj dokument', 'Usuń dokument', ['title' => ['label' => 'Nazwa dokumentu', 'ph' => 'np. Regulamin rekrutacji'], 'url' => ['label' => 'Adres pliku', 'ph' => '/storage/… lub https://…'], 'note' => ['label' => 'Opis', 'ph' => 'np. PDF dostępny, 4 strony']]],
        'rec_faq' => ['Pytania i odpowiedzi', 'Dodaj pytanie', 'Usuń pytanie', ['q' => ['label' => 'Pytanie'], 'a' => ['label' => 'Odpowiedź', 'textarea' => true]]],
    ];
    $recForms = \App\Models\SiteSetting::current()->isModuleEnabled('forms')
        ? \App\Models\FormDefinition::where('is_active', true)->orderBy('title')->get(['slug', 'title'])
        : collect();
    $recStatus = $td['rec_status'] ?? 'auto';
@endphp
<div data-type-fields="recruitment" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'recruitment' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Rekrutacja do projektu</p>

    <div>
        <label for="td-rec-lead" class="{{ $lbl }}">Lead (1–2 zdania: kogo szukamy i co oferujemy)</label>
        <textarea id="td-rec-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>

    <fieldset class="{{ $box }}">
        <legend class="text-xs font-bold uppercase text-muted">Nabór</legend>
        <div class="grid gap-3 sm:grid-cols-3">
            <div>
                <label for="td-rec-status" class="{{ $lbl }}">Status naboru</label>
                <select id="td-rec-status" name="type_data[rec_status]" class="{{ $inp }}" aria-describedby="td-rec-status-help">
                    @foreach (\App\Models\Page::RECRUITMENT_STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected($recStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="td-rec-start" class="{{ $lbl }}">Początek naboru</label>
                <input type="datetime-local" id="td-rec-start" name="type_data[rec_starts_at]" value="{{ $td['rec_starts_at'] ?? '' }}" class="{{ $inp }}">
            </div>
            <div>
                <label for="td-rec-end" class="{{ $lbl }}">Koniec naboru</label>
                <input type="datetime-local" id="td-rec-end" name="type_data[rec_ends_at]" value="{{ $td['rec_ends_at'] ?? '' }}" class="{{ $inp }}">
                @error('type_data.rec_ends_at') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
            </div>
        </div>
        <p id="td-rec-status-help" class="text-xs text-muted">„Automatycznie” — przed początkiem „Nabór wkrótce”, w terminie „Nabór trwa”, po końcu „Nabór zakończony”, bez dat „Nabór ciągły”. Przycisk zgłoszenia jest widoczny tylko przy naborze trwającym, ciągłym i na listę rezerwową.</p>
        <div class="grid gap-3 sm:grid-cols-2">
            <div><label for="td-rec-places" class="{{ $lbl }}">Liczba miejsc</label><input type="text" id="td-rec-places" name="type_data[rec_places]" value="{{ $td['rec_places'] ?? '' }}" placeholder="np. 40 osób (w tym 24 kobiety)" class="{{ $inp }}"></div>
            <div><label for="td-rec-period" class="{{ $lbl }}">Okres wsparcia</label><input type="text" id="td-rec-period" name="type_data[rec_period]" value="{{ $td['rec_period'] ?? '' }}" placeholder="np. styczeń – czerwiec 2027" class="{{ $inp }}"></div>
            <div><label for="td-rec-mode" class="{{ $lbl }}">Miejsce / forma udziału</label><input type="text" id="td-rec-mode" name="type_data[rec_mode]" value="{{ $td['rec_mode'] ?? '' }}" placeholder="np. Kraków i online" class="{{ $inp }}"></div>
            <div><label for="td-rec-cost" class="{{ $lbl }}">Koszt dla uczestnika</label><input type="text" id="td-rec-cost" name="type_data[rec_cost]" value="{{ $td['rec_cost'] ?? '' }}" placeholder="np. Bezpłatnie" class="{{ $inp }}"></div>
        </div>
        <p class="text-xs text-muted">Projekt, w ramach którego prowadzisz nabór, wskaż w polu „Działanie / projekt” w ustawieniach strony — na stronie pojawi się odnośnik i informacja o finansowaniu.</p>
    </fieldset>

    @foreach ($recLists as $list => [$title, $addLabel, $removeLabel, $fields])
        <div class="{{ $box }}" data-td-repeater>
            <p class="text-xs font-bold uppercase text-muted">{{ $title }}</p>
            <div data-td-rows class="space-y-2">
                @foreach ($tdRows($list) as $i => $row)
                    {!! $recRow($list, $fields, $removeLabel, $i, $row) !!}
                @endforeach
            </div>
            <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> {{ $addLabel }}</button>
            <template data-td-template>{!! $recRow($list, $fields, $removeLabel) !!}</template>
        </div>
    @endforeach

    <fieldset class="{{ $box }}">
        <legend class="text-xs font-bold uppercase text-muted">Zgłoszenie</legend>
        <div>
            <label for="td-rec-form" class="{{ $lbl }}">Formularz zgłoszeniowy na stronie</label>
            <select id="td-rec-form" name="type_data[rec_form]" class="{{ $inp }}" aria-describedby="td-rec-form-help">
                <option value="">— bez formularza (tylko przycisk) —</option>
                @foreach ($recForms as $f)
                    <option value="{{ $f->slug }}" @selected(($td['rec_form'] ?? '') === $f->slug)>{{ $f->title }}</option>
                @endforeach
            </select>
            <p id="td-rec-form-help" class="mt-1 text-xs text-muted">Formularz z modułu Formularze. Jest widoczny tylko, gdy nabór przyjmuje zgłoszenia.</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div><label for="td-rec-cta-label" class="{{ $lbl }}">Przycisk zgłoszenia — etykieta</label><input type="text" id="td-rec-cta-label" name="type_data[cta_label]" value="{{ $td['cta_label'] ?? '' }}" placeholder="np. Zgłoś się do projektu" class="{{ $inp }}"></div>
            <div><label for="td-rec-cta-url" class="{{ $lbl }}">Przycisk zgłoszenia — adres</label><input type="text" id="td-rec-cta-url" name="type_data[cta_url]" value="{{ $td['cta_url'] ?? '' }}" placeholder="pusty = przewija do formularza" class="{{ $inp }}"></div>
        </div>
    </fieldset>

    <div>
        <label for="td-rec-a11y" class="{{ $lbl }}">Dostępność rekrutacji i wsparcie przy zgłoszeniu</label>
        <textarea id="td-rec-a11y" name="type_data[rec_accessibility]" rows="3" placeholder="np. Zgłoszenie przyjmiemy też telefonicznie, mailowo lub w PJM. Pomożemy wypełnić dokumenty. Biuro jest dostępne dla osób poruszających się na wózku." class="{{ $inp }}">{{ $td['rec_accessibility'] ?? '' }}</textarea>
    </div>
    <div>
        <label for="td-rec-results" class="{{ $lbl }}">Informacja po zakończeniu naboru</label>
        <textarea id="td-rec-results" name="type_data[rec_results]" rows="2" placeholder="np. Listę zakwalifikowanych osób opublikujemy do 15 listopada. Każda osoba dostanie informację mailem." class="{{ $inp }}">{{ $td['rec_results'] ?? '' }}</textarea>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <div><label for="td-rec-contact-name" class="{{ $lbl }}">Osoba do kontaktu</label><input type="text" id="td-rec-contact-name" name="type_data[contact_name]" value="{{ $td['contact_name'] ?? '' }}" class="{{ $inp }}"></div>
        <div><label for="td-rec-contact-email" class="{{ $lbl }}">E-mail</label><input type="email" id="td-rec-contact-email" name="type_data[contact_email]" value="{{ $td['contact_email'] ?? '' }}" class="{{ $inp }}"></div>
        <div><label for="td-rec-contact-phone" class="{{ $lbl }}">Telefon</label><input type="text" id="td-rec-contact-phone" name="type_data[contact_phone]" value="{{ $td['contact_phone'] ?? '' }}" class="{{ $inp }}"></div>
    </div>
    <p class="text-xs text-muted">Treść główna strony (edytor powyżej) wyświetla się pod informacjami o naborze — opisz w niej projekt.</p>
</div>
