{{-- ═══ OFERTA / USŁUGA ═══ --}}
<div data-type-fields="service" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'service' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Oferta / usługa</p>

    <div>
        <label for="td-service-lead" class="{{ $lbl }}">Lead (1–2 zdania pod tytułem)</label>
        <textarea id="td-service-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Dla kogo</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('audience') as $i => $row)
                <div data-td-row class="flex gap-2">
                    <input type="text" name="type_data[audience][{{ $i }}][text]" value="{{ $row['text'] ?? '' }}" placeholder="np. Urzędy i instytucje publiczne" aria-label="Grupa odbiorców" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj grupę odbiorców</button>
        <template data-td-template>
            <div data-td-row class="flex gap-2">
                <input type="text" name="type_data[audience][__INDEX__][text]" placeholder="np. Urzędy i instytucje publiczne" aria-label="Grupa odbiorców" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Korzyści (ikona Font Awesome, tytuł, opis)</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('benefits') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[10rem_1fr_2fr_auto]">
                    <input type="text" name="type_data[benefits][{{ $i }}][icon]" value="{{ $row['icon'] ?? '' }}" placeholder="fa-solid fa-check" data-icon-picker data-icon-format="class" aria-label="Ikona" class="{{ $inp }} font-mono text-xs">
                    <input type="text" name="type_data[benefits][{{ $i }}][title]" value="{{ $row['title'] ?? '' }}" placeholder="Tytuł korzyści" aria-label="Tytuł korzyści" class="{{ $inp }}">
                    <input type="text" name="type_data[benefits][{{ $i }}][text]" value="{{ $row['text'] ?? '' }}" placeholder="Krótki opis" aria-label="Opis korzyści" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń korzyść"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj korzyść</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[10rem_1fr_2fr_auto]">
                <input type="text" name="type_data[benefits][__INDEX__][icon]" placeholder="fa-solid fa-check" data-icon-picker data-icon-format="class" aria-label="Ikona" class="{{ $inp }} font-mono text-xs">
                <input type="text" name="type_data[benefits][__INDEX__][title]" placeholder="Tytuł korzyści" aria-label="Tytuł korzyści" class="{{ $inp }}">
                <input type="text" name="type_data[benefits][__INDEX__][text]" placeholder="Krótki opis" aria-label="Opis korzyści" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń korzyść"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Jak działamy — etapy współpracy</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('steps') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_2fr_auto]">
                    <input type="text" name="type_data[steps][{{ $i }}][title]" value="{{ $row['title'] ?? '' }}" placeholder="np. Rozmowa o potrzebach" aria-label="Nazwa etapu" class="{{ $inp }}">
                    <input type="text" name="type_data[steps][{{ $i }}][text]" value="{{ $row['text'] ?? '' }}" placeholder="Co się dzieje na tym etapie" aria-label="Opis etapu" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń etap"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj etap</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_2fr_auto]">
                <input type="text" name="type_data[steps][__INDEX__][title]" placeholder="np. Rozmowa o potrzebach" aria-label="Nazwa etapu" class="{{ $inp }}">
                <input type="text" name="type_data[steps][__INDEX__][text]" placeholder="Co się dzieje na tym etapie" aria-label="Opis etapu" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń etap"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div><label for="td-service-cta-label" class="{{ $lbl }}">Przycisk CTA — etykieta</label><input type="text" id="td-service-cta-label" name="type_data[cta_label]" value="{{ $td['cta_label'] ?? '' }}" placeholder="np. Zapytaj o wycenę" class="{{ $inp }}"></div>
        <div><label for="td-service-cta-url" class="{{ $lbl }}">Przycisk CTA — adres</label><input type="text" id="td-service-cta-url" name="type_data[cta_url]" value="{{ $td['cta_url'] ?? '' }}" placeholder="/kontakt" class="{{ $inp }}"></div>
        <div><label for="td-service-contact-name" class="{{ $lbl }}">Osoba kontaktowa</label><input type="text" id="td-service-contact-name" name="type_data[contact_name]" value="{{ $td['contact_name'] ?? '' }}" class="{{ $inp }}"></div>
        <div><label for="td-service-contact-email" class="{{ $lbl }}">E-mail kontaktowy</label><input type="email" id="td-service-contact-email" name="type_data[contact_email]" value="{{ $td['contact_email'] ?? '' }}" class="{{ $inp }}"></div>
        <div><label for="td-service-contact-phone" class="{{ $lbl }}">Telefon kontaktowy</label><input type="text" id="td-service-contact-phone" name="type_data[contact_phone]" value="{{ $td['contact_phone'] ?? '' }}" class="{{ $inp }}"></div>
    </div>
    <p class="text-xs text-muted">Treść główna strony (edytor powyżej) wyświetla się między leadem a korzyściami.</p>
</div>
