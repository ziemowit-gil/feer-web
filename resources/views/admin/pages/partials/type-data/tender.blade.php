{{-- ═══ ZAPYTANIE OFERTOWE ═══ --}}
@php
    $tenderLists = [
        'requirements' => ['Warunki udziału w postępowaniu', 'Dodaj warunek', 'Usuń warunek', ['text' => ['label' => 'Warunek', 'ph' => 'np. Co najmniej 2 usługi szkoleniowe w ostatnich 3 latach']]],
        'criteria' => ['Kryteria oceny ofert (nazwa, waga, opis sposobu oceny)', 'Dodaj kryterium', 'Usuń kryterium', ['name' => ['label' => 'Kryterium', 'ph' => 'np. Cena'], 'weight' => ['label' => 'Waga', 'ph' => 'np. 60%'], 'text' => ['label' => 'Sposób oceny', 'ph' => 'np. najniższa cena / cena oferty × 60 pkt']]],
        'docs' => ['Dokumentacja (zapytanie, formularz oferty, załączniki)', 'Dodaj dokument', 'Usuń dokument', ['title' => ['label' => 'Nazwa dokumentu', 'ph' => 'np. Zapytanie ofertowe nr 3/2026'], 'url' => ['label' => 'Adres pliku', 'ph' => '/storage/… lub https://…'], 'note' => ['label' => 'Opis', 'ph' => 'np. PDF dostępny']]],
        'notices' => ['Zmiany i wyjaśnienia treści zapytania (z datą publikacji)', 'Dodaj zmianę lub wyjaśnienie', 'Usuń wpis', ['date' => ['label' => 'Data', 'type' => 'date'], 'title' => ['label' => 'Tytuł', 'ph' => 'np. Wyjaśnienie nr 1'], 'text' => ['label' => 'Treść', 'textarea' => true], 'url' => ['label' => 'Plik (opcjonalnie)', 'ph' => '/storage/…']]],
    ];
    $tenderStatus = $td['tender_status'] ?? 'auto';
@endphp
<div data-type-fields="tender" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'tender' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Zapytanie ofertowe</p>

    <div>
        <label for="td-tender-lead" class="{{ $lbl }}">Przedmiot zamówienia (1–2 zdania)</label>
        <textarea id="td-tender-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>

    <fieldset class="{{ $box }}">
        <legend class="text-xs font-bold uppercase text-muted">Postępowanie</legend>
        <div class="grid gap-3 sm:grid-cols-3">
            <div><label for="td-tender-number" class="{{ $lbl }}">Znak sprawy</label><input type="text" id="td-tender-number" name="type_data[tender_number]" value="{{ $td['tender_number'] ?? '' }}" placeholder="np. ZO/3/2026" class="{{ $inp }}"></div>
            <div><label for="td-tender-published" class="{{ $lbl }}">Data publikacji</label><input type="date" id="td-tender-published" name="type_data[tender_published_at]" value="{{ $td['tender_published_at'] ?? '' }}" class="{{ $inp }}"></div>
            <div>
                <label for="td-tender-deadline" class="{{ $lbl }}">Termin składania ofert</label>
                <input type="datetime-local" id="td-tender-deadline" name="type_data[tender_deadline]" value="{{ $td['tender_deadline'] ?? '' }}" class="{{ $inp }}">
            </div>
        </div>
        <div>
            <label for="td-tender-status" class="{{ $lbl }}">Status</label>
            <select id="td-tender-status" name="type_data[tender_status]" class="{{ $inp }}" aria-describedby="td-tender-status-help">
                @foreach (\App\Models\Page::TENDER_STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected($tenderStatus === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p id="td-tender-status-help" class="mt-1 text-xs text-muted">„Automatycznie” — do terminu „Przyjmowanie ofert”, po terminie „Ocena ofert”, po wpisaniu wyniku „Rozstrzygnięte”. „Unieważnione” ustaw ręcznie.</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div><label for="td-tender-cpv" class="{{ $lbl }}">Kod CPV</label><input type="text" id="td-tender-cpv" name="type_data[tender_cpv]" value="{{ $td['tender_cpv'] ?? '' }}" placeholder="np. 80500000-9 Usługi szkoleniowe" class="{{ $inp }}"></div>
            <div><label for="td-tender-place" class="{{ $lbl }}">Miejsce realizacji</label><input type="text" id="td-tender-place" name="type_data[tender_place]" value="{{ $td['tender_place'] ?? '' }}" placeholder="np. Nowy Sącz" class="{{ $inp }}"></div>
            <div><label for="td-tender-period" class="{{ $lbl }}">Termin realizacji</label><input type="text" id="td-tender-period" name="type_data[tender_period]" value="{{ $td['tender_period'] ?? '' }}" placeholder="np. do 30 czerwca 2027" class="{{ $inp }}"></div>
            <div>
                <label for="td-tender-bk" class="{{ $lbl }}">Ogłoszenie w Bazie Konkurencyjności (adres)</label>
                <input type="url" id="td-tender-bk" name="type_data[tender_bk_url]" value="{{ $td['tender_bk_url'] ?? '' }}" placeholder="https://bazakonkurencyjnosci.funduszeeuropejskie.gov.pl/…" class="{{ $inp }}">
                @error('type_data.tender_bk_url') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
            </div>
        </div>
        <p class="text-xs text-muted">Projekt, z którego finansowane jest zamówienie, wskaż w polu „Działanie / projekt” w ustawieniach strony — pojawi się odnośnik i informacja o finansowaniu.</p>
    </fieldset>

    @foreach ($tenderLists as $list => [$title, $addLabel, $removeLabel, $fields])
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

    <div>
        <label for="td-tender-submission" class="{{ $lbl }}">Sposób i miejsce składania ofert</label>
        <textarea id="td-tender-submission" name="type_data[tender_submission]" rows="3" placeholder="np. Ofertę składa się przez Bazę Konkurencyjności. Oferty złożone po terminie nie będą rozpatrywane." class="{{ $inp }}">{{ $td['tender_submission'] ?? '' }}</textarea>
    </div>
    <div>
        <label for="td-tender-exclusions" class="{{ $lbl }}">Wykluczenia (np. powiązania osobowe lub kapitałowe)</label>
        <textarea id="td-tender-exclusions" name="type_data[tender_exclusions]" rows="3" class="{{ $inp }}">{{ $td['tender_exclusions'] ?? '' }}</textarea>
    </div>

    <fieldset class="{{ $box }}">
        <legend class="text-xs font-bold uppercase text-muted">Wynik postępowania</legend>
        <div class="grid gap-3 sm:grid-cols-[1fr_12rem]">
            <div>
                <label for="td-tender-result" class="{{ $lbl }}">Informacja o wyniku / unieważnieniu</label>
                <textarea id="td-tender-result" name="type_data[tender_result]" rows="3" placeholder="np. Wybrano ofertę: Firma XYZ, ul. …, cena brutto 12 300 zł, 100 pkt." class="{{ $inp }}">{{ $td['tender_result'] ?? '' }}</textarea>
            </div>
            <div><label for="td-tender-result-at" class="{{ $lbl }}">Data rozstrzygnięcia</label><input type="date" id="td-tender-result-at" name="type_data[tender_result_at]" value="{{ $td['tender_result_at'] ?? '' }}" class="{{ $inp }}"></div>
        </div>
        <p class="text-xs text-muted">Protokół lub zawiadomienie dodaj w „Dokumentacji” albo w „Zmianach i wyjaśnieniach”.</p>
    </fieldset>

    <div class="grid gap-3 sm:grid-cols-3">
        <div><label for="td-tender-contact-name" class="{{ $lbl }}">Osoba do kontaktu</label><input type="text" id="td-tender-contact-name" name="type_data[contact_name]" value="{{ $td['contact_name'] ?? '' }}" class="{{ $inp }}"></div>
        <div><label for="td-tender-contact-email" class="{{ $lbl }}">E-mail</label><input type="email" id="td-tender-contact-email" name="type_data[contact_email]" value="{{ $td['contact_email'] ?? '' }}" class="{{ $inp }}"></div>
        <div><label for="td-tender-contact-phone" class="{{ $lbl }}">Telefon</label><input type="text" id="td-tender-contact-phone" name="type_data[contact_phone]" value="{{ $td['contact_phone'] ?? '' }}" class="{{ $inp }}"></div>
    </div>
    <p class="text-xs text-muted">Treść główna strony (edytor powyżej) to opis przedmiotu zamówienia.</p>
</div>
