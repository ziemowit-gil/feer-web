{{-- ═══ SZKOLENIE ═══ --}}
<div data-type-fields="training_offer" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'training_offer' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Szkolenie</p>

    <div>
        <label for="td-to-lead" class="{{ $lbl }}">Lead (jedno, dwa zdania o tym, czego uczy szkolenie)</label>
        <textarea id="td-to-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <div><label for="td-to-category" class="{{ $lbl }}">Kategoria (filtr w katalogu)</label><input type="text" id="td-to-category" name="type_data[category]" value="{{ $td['category'] ?? '' }}" placeholder="np. Dostępność" class="{{ $inp }}"></div>
        <div><label for="td-to-duration" class="{{ $lbl }}">Czas trwania</label><input type="text" id="td-to-duration" name="type_data[duration]" value="{{ $td['duration'] ?? '' }}" placeholder="np. 16 godzin dydaktycznych" class="{{ $inp }}"></div>
        <div>
            <label for="td-to-mode" class="{{ $lbl }}">Forma</label>
            <select id="td-to-mode" name="type_data[mode]" class="{{ $inp }}">
                <option value="">— nie podano —</option>
                @foreach (['stationary' => 'Stacjonarnie', 'online' => 'Online', 'hybrid' => 'Hybrydowo'] as $modeValue => $modeLabel)
                    <option value="{{ $modeValue }}" @selected(($td['mode'] ?? '') === $modeValue)>{{ $modeLabel }}</option>
                @endforeach
            </select>
        </div>
        <div><label for="td-to-place" class="{{ $lbl }}">Miejsce</label><input type="text" id="td-to-place" name="type_data[place]" value="{{ $td['place'] ?? '' }}" placeholder="np. Kraków albo platforma online" class="{{ $inp }}"></div>
        <div><label for="td-to-group" class="{{ $lbl }}">Wielkość grupy</label><input type="text" id="td-to-group" name="type_data[group_size]" value="{{ $td['group_size'] ?? '' }}" placeholder="np. do 12 osób" class="{{ $inp }}"></div>
        <div><label for="td-to-certificate" class="{{ $lbl }}">Certyfikat / zaświadczenie</label><input type="text" id="td-to-certificate" name="type_data[certificate]" value="{{ $td['certificate'] ?? '' }}" placeholder="np. zaświadczenie o ukończeniu" class="{{ $inp }}"></div>
        <div><label for="td-to-price" class="{{ $lbl }}">Cena / odpłatność</label><input type="text" id="td-to-price" name="type_data[price]" value="{{ $td['price'] ?? '' }}" placeholder="np. 480 zł brutto albo bezpłatnie" class="{{ $inp }}"></div>
        <div><label for="td-to-price-note" class="{{ $lbl }}">Uwaga do ceny</label><input type="text" id="td-to-price-note" name="type_data[price_note]" value="{{ $td['price_note'] ?? '' }}" placeholder="np. cena zawiera materiały" class="{{ $inp }}"></div>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <div><label for="td-to-level" class="{{ $lbl }}">Poziom</label><input type="text" id="td-to-level" name="type_data[level]" value="{{ $td['level'] ?? '' }}" placeholder="np. podstawowy" class="{{ $inp }}"></div>
        <div><label for="td-to-language" class="{{ $lbl }}">Język szkolenia</label><input type="text" id="td-to-language" name="type_data[language]" value="{{ $td['language'] ?? '' }}" placeholder="np. polski, PJM" class="{{ $inp }}"></div>
        <div><label for="td-to-funding" class="{{ $lbl }}">Dofinansowanie</label><input type="text" id="td-to-funding" name="type_data[funding]" value="{{ $td['funding'] ?? '' }}" placeholder="np. możliwe z BUR i KFS" class="{{ $inp }}"></div>
    </div>

    <div class="rounded-md bg-gray-50 p-4 space-y-3">
        <label class="flex items-start gap-3 text-sm text-ink">
            <input type="hidden" name="type_data[closed_available]" value="0">
            <input type="checkbox" name="type_data[closed_available]" value="1" class="mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark" @checked(($td['closed_available'] ?? '0') == '1')>
            <span><span class="font-bold">Możliwe szkolenie zamknięte</span> <span class="block text-ink">Na zamówienie dla instytucji lub firmy: na stronie pojawia się dodatkowy przycisk zapytania.</span></span>
        </label>
        <div class="grid gap-3 sm:grid-cols-2">
            <div><label for="td-to-closed-label" class="{{ $lbl }}">Przycisk zapytania — napis</label><input type="text" id="td-to-closed-label" name="type_data[closed_label]" value="{{ $td['closed_label'] ?? '' }}" placeholder="Zapytaj o szkolenie dla Twojej instytucji" class="{{ $inp }}"></div>
            <div><label for="td-to-closed-url" class="{{ $lbl }}">Przycisk zapytania — adres</label><input type="text" id="td-to-closed-url" name="type_data[closed_url]" value="{{ $td['closed_url'] ?? '' }}" placeholder="/kontakt" class="{{ $inp }}"></div>
        </div>
    </div>

    <label class="flex items-start gap-3 text-sm text-ink">
        <input type="hidden" name="type_data[featured]" value="0">
        <input type="checkbox" name="type_data[featured]" value="1" class="mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark" @checked(($td['featured'] ?? '0') == '1')>
        <span><span class="font-bold">Wyróżnij szkolenie</span> <span class="block text-ink">Pokazuje się w sliderze katalogu szkoleń i w module szkoleń na stronie głównej (motyw FEER IS).</span></span>
    </label>

        <div><label for="td-to-prereq" class="{{ $lbl }}">Wymagania wstępne</label><textarea id="td-to-prereq" name="type_data[prerequisites]" rows="2" class="{{ $inp }}">{{ $td['prerequisites'] ?? '' }}</textarea></div>

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Dla kogo</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('audience') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_auto]">
                    <input type="text" name="type_data[audience][{{ $i }}][text]" value="{{ $row['text'] ?? '' }}" placeholder="np. Pracownicy urzędów i instytucji" aria-label="Odbiorca" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj odbiorcę</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_auto]">
                    <input type="text" name="type_data[audience][__INDEX__][text]" value="" placeholder="np. Pracownicy urzędów i instytucji" aria-label="Odbiorca" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Czego się nauczysz (efekty)</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('outcomes') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_auto]">
                    <input type="text" name="type_data[outcomes][{{ $i }}][text]" value="{{ $row['text'] ?? '' }}" placeholder="np. Rozpoznasz najczęstsze bariery w dostępie do usług" aria-label="Efekt" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj efekt</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_auto]">
                    <input type="text" name="type_data[outcomes][__INDEX__][text]" value="" placeholder="np. Rozpoznasz najczęstsze bariery w dostępie do usług" aria-label="Efekt" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Program (moduły)</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('program') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_5rem_2fr_auto]">
                    <input type="text" name="type_data[program][{{ $i }}][title]" value="{{ $row['title'] ?? '' }}" placeholder="Tytuł modułu" aria-label="Tytuł modułu" class="{{ $inp }}">
                    <input type="text" name="type_data[program][{{ $i }}][hours]" value="{{ $row['hours'] ?? '' }}" placeholder="Godz." aria-label="Liczba godzin" class="{{ $inp }}">
                    <input type="text" name="type_data[program][{{ $i }}][text]" value="{{ $row['text'] ?? '' }}" placeholder="Opis modułu" aria-label="Opis modułu" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj moduł</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_5rem_2fr_auto]">
                    <input type="text" name="type_data[program][__INDEX__][title]" value="" placeholder="Tytuł modułu" aria-label="Tytuł modułu" class="{{ $inp }}">
                    <input type="text" name="type_data[program][__INDEX__][hours]" value="" placeholder="Godz." aria-label="Liczba godzin" class="{{ $inp }}">
                    <input type="text" name="type_data[program][__INDEX__][text]" value="" placeholder="Opis modułu" aria-label="Opis modułu" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Trenerzy</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('trainers') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_1fr_1fr_auto]">
                    <input type="text" name="type_data[trainers][{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" placeholder="Imię i nazwisko" aria-label="Imię i nazwisko" class="{{ $inp }}">
                    <input type="text" name="type_data[trainers][{{ $i }}][role]" value="{{ $row['role'] ?? '' }}" placeholder="Rola / specjalizacja" aria-label="Rola" class="{{ $inp }}">
                    <input type="text" name="type_data[trainers][{{ $i }}][url]" value="{{ $row['url'] ?? '' }}" placeholder="Adres profilu (np. /trener-jan-kowalski)" aria-label="Adres profilu" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj trenera</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_1fr_1fr_auto]">
                    <input type="text" name="type_data[trainers][__INDEX__][name]" value="" placeholder="Imię i nazwisko" aria-label="Imię i nazwisko" class="{{ $inp }}">
                    <input type="text" name="type_data[trainers][__INDEX__][role]" value="" placeholder="Rola / specjalizacja" aria-label="Rola" class="{{ $inp }}">
                    <input type="text" name="type_data[trainers][__INDEX__][url]" value="" placeholder="Adres profilu (np. /trener-jan-kowalski)" aria-label="Adres profilu" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Najbliższe terminy</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('editions') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[9rem_1fr_11rem_1fr_auto]">
                    <input type="date" name="type_data[editions][{{ $i }}][date]" value="{{ $row['date'] ?? '' }}" aria-label="Data rozpoczęcia" class="{{ $inp }}">
                    <input type="text" name="type_data[editions][{{ $i }}][place]" value="{{ $row['place'] ?? '' }}" placeholder="Miejsce" aria-label="Miejsce" class="{{ $inp }}">
                    <select name="type_data[editions][{{ $i }}][status]" aria-label="Dostępność miejsc" class="{{ $inp }}"><option value="open" @selected(($row['status'] ?? '') === 'open')>Zapisy otwarte</option><option value="few" @selected(($row['status'] ?? '') === 'few')>Ostatnie miejsca</option><option value="full" @selected(($row['status'] ?? '') === 'full')>Brak miejsc</option><option value="planned" @selected(($row['status'] ?? '') === 'planned')>Planowany</option></select>
                    <input type="text" name="type_data[editions][{{ $i }}][url]" value="{{ $row['url'] ?? '' }}" placeholder="Adres zapisów (puste = przycisk zapisów szkolenia)" aria-label="Adres zapisów" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj termin</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[9rem_1fr_11rem_1fr_auto]">
                    <input type="date" name="type_data[editions][__INDEX__][date]" value="" aria-label="Data rozpoczęcia" class="{{ $inp }}">
                    <input type="text" name="type_data[editions][__INDEX__][place]" value="" placeholder="Miejsce" aria-label="Miejsce" class="{{ $inp }}">
                    <select name="type_data[editions][__INDEX__][status]" aria-label="Dostępność miejsc" class="{{ $inp }}"><option value="open">Zapisy otwarte</option><option value="few">Ostatnie miejsca</option><option value="full">Brak miejsc</option><option value="planned">Planowany</option></select>
                    <input type="text" name="type_data[editions][__INDEX__][url]" value="" placeholder="Adres zapisów (puste = przycisk zapisów szkolenia)" aria-label="Adres zapisów" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Opinie uczestników</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('testimonials') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[2fr_1fr_auto]">
                    <textarea name="type_data[testimonials][{{ $i }}][text]" rows="2" placeholder="Treść opinii" aria-label="Treść opinii" class="{{ $inp }}">{{ $row['text'] ?? '' }}</textarea>
                    <input type="text" name="type_data[testimonials][{{ $i }}][author]" value="{{ $row['author'] ?? '' }}" placeholder="Autor (np. Anna, uczestniczka)" aria-label="Autor" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj opinię</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[2fr_1fr_auto]">
                    <textarea name="type_data[testimonials][__INDEX__][text]" rows="2" placeholder="Treść opinii" aria-label="Treść opinii" class="{{ $inp }}"></textarea>
                    <input type="text" name="type_data[testimonials][__INDEX__][author]" value="" placeholder="Autor (np. Anna, uczestniczka)" aria-label="Autor" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>
    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Najczęstsze pytania</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('faq') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_2fr_auto]">
                    <input type="text" name="type_data[faq][{{ $i }}][question]" value="{{ $row['question'] ?? '' }}" placeholder="Pytanie" aria-label="Pytanie" class="{{ $inp }}">
                    <textarea name="type_data[faq][{{ $i }}][answer]" rows="2" placeholder="Odpowiedź" aria-label="Odpowiedź" class="{{ $inp }}">{{ $row['answer'] ?? '' }}</textarea>
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj pytanie</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_2fr_auto]">
                    <input type="text" name="type_data[faq][__INDEX__][question]" value="" placeholder="Pytanie" aria-label="Pytanie" class="{{ $inp }}">
                    <textarea name="type_data[faq][__INDEX__][answer]" rows="2" placeholder="Odpowiedź" aria-label="Odpowiedź" class="{{ $inp }}"></textarea>
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
        <div><label for="td-to-cta-label" class="{{ $lbl }}">Przycisk zapisów — etykieta</label><input type="text" id="td-to-cta-label" name="type_data[cta_label]" value="{{ $td['cta_label'] ?? '' }}" placeholder="np. Zapisz się" class="{{ $inp }}"></div>
        <div><label for="td-to-cta-url" class="{{ $lbl }}">Przycisk zapisów — adres</label><input type="text" id="td-to-cta-url" name="type_data[cta_url]" value="{{ $td['cta_url'] ?? '' }}" placeholder="/formularz/zapisy" class="{{ $inp }}"></div>
        <div><label for="td-to-contact-name" class="{{ $lbl }}">Osoba do kontaktu</label><input type="text" id="td-to-contact-name" name="type_data[contact_name]" value="{{ $td['contact_name'] ?? '' }}" class="{{ $inp }}"></div>
        <div><label for="td-to-contact-email" class="{{ $lbl }}">E-mail</label><input type="email" id="td-to-contact-email" name="type_data[contact_email]" value="{{ $td['contact_email'] ?? '' }}" class="{{ $inp }}"></div>
        <div><label for="td-to-contact-phone" class="{{ $lbl }}">Telefon</label><input type="text" id="td-to-contact-phone" name="type_data[contact_phone]" value="{{ $td['contact_phone'] ?? '' }}" class="{{ $inp }}"></div>
    </div>
    <p class="text-xs text-muted">Treść główna strony (edytor powyżej) wyświetla się pod programem jako dodatkowe informacje. Zdjęcie strony pojawia się w sliderze katalogu.</p>
</div>
