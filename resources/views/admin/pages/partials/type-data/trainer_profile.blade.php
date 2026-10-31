{{-- ═══ TRENER ═══ --}}
<div data-type-fields="trainer_profile" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'trainer_profile' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Trener</p>
    <div class="grid gap-3 sm:grid-cols-2">
        <div><label for="td-tp-role" class="{{ $lbl }}">Rola / tytuł</label><input type="text" id="td-tp-role" name="type_data[role]" value="{{ $td['role'] ?? '' }}" placeholder="np. trener dostępności cyfrowej" class="{{ $inp }}"></div>
        <div><label for="td-tp-lang" class="{{ $lbl }}">Języki</label><input type="text" id="td-tp-lang" name="type_data[languages]" value="{{ $td['languages'] ?? '' }}" placeholder="np. polski, polski język migowy" class="{{ $inp }}"></div>
    </div>
    <div>
        <label for="td-tp-lead" class="{{ $lbl }}">Krótki opis (lead)</label>
        <textarea id="td-tp-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>
    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Specjalizacje</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('specializations') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_auto]">
                    <input type="text" name="type_data[specializations][{{ $i }}][text]" value="{{ $row['text'] ?? '' }}" placeholder="np. Dostępność dokumentów PDF" aria-label="Specjalizacja" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj specjalizację</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_auto]">
                    <input type="text" name="type_data[specializations][__INDEX__][text]" value="" placeholder="np. Dostępność dokumentów PDF" aria-label="Specjalizacja" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Kwalifikacje i doświadczenie</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('qualifications') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_auto]">
                    <input type="text" name="type_data[qualifications][{{ $i }}][text]" value="{{ $row['text'] ?? '' }}" placeholder="np. Certyfikowany audytor WCAG" aria-label="Kwalifikacja" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj kwalifikację</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_auto]">
                    <input type="text" name="type_data[qualifications][__INDEX__][text]" value="" placeholder="np. Certyfikowany audytor WCAG" aria-label="Kwalifikacja" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <div><label for="td-tp-contact-name" class="{{ $lbl }}">Osoba do kontaktu</label><input type="text" id="td-tp-contact-name" name="type_data[contact_name]" value="{{ $td['contact_name'] ?? '' }}" class="{{ $inp }}"></div>
        <div><label for="td-tp-contact-email" class="{{ $lbl }}">E-mail</label><input type="email" id="td-tp-contact-email" name="type_data[contact_email]" value="{{ $td['contact_email'] ?? '' }}" class="{{ $inp }}"></div>
        <div><label for="td-tp-contact-phone" class="{{ $lbl }}">Telefon</label><input type="text" id="td-tp-contact-phone" name="type_data[contact_phone]" value="{{ $td['contact_phone'] ?? '' }}" class="{{ $inp }}"></div>
    </div>
    <p class="text-xs text-muted">Tytuł strony to imię i nazwisko trenera; treść główna to jego biogram. Lista prowadzonych szkoleń buduje się sama ze szkoleń, w których wpisano tego trenera z tym samym imieniem i nazwiskiem. Zdjęcie strony pokazuje się przy biogramie.</p>
</div>
