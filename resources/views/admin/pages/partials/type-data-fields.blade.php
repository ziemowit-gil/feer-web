{{--
    Pola nowych typów treści (kolumna JSON `type_data`): oferta/usługa, poradnik,
    słownik pojęć, studium przypadku. Każda sekcja ma `data-type-fields="<typ>"`
    i jest przełączana przez selekt typu strony (JS w form.blade.php).

    Listy (korzyści, kroki, hasła…) używają własnego, prostego repeatera
    `data-td-repeater` — bez przeliczania indeksów, bo nazwy pól są zagnieżdżone
    (type_data[lista][i][pole]) i generyczny reindex z form.blade.php by je popsuł.
--}}
@php
    $td = old('type_data', $page->type_data ?? []);
    $tdRows = fn (string $key) => array_values(array_filter(is_array($td[$key] ?? null) ? $td[$key] : [], 'is_array'));
    $inp = 'w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand';
    $lbl = 'mb-1 block text-xs font-bold text-muted';
    $box = 'space-y-3 rounded-lg border border-gray-100 bg-gray-50 p-4';
    $addBtn = 'mt-2 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand';
    $rmBtn = 'rounded p-2 text-muted hover:bg-red-50 hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400';
@endphp

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

{{-- ═══ PORADNIK KROK PO KROKU ═══ --}}
<div data-type-fields="guide" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'guide' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Poradnik krok po kroku</p>

    <div>
        <label for="td-guide-lead" class="{{ $lbl }}">Lead (czego dotyczy poradnik)</label>
        <textarea id="td-guide-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
        <div><label for="td-guide-time" class="{{ $lbl }}">Szacowany czas</label><input type="text" id="td-guide-time" name="type_data[time_estimate]" value="{{ $td['time_estimate'] ?? '' }}" placeholder="np. 15 minut" class="{{ $inp }}"></div>
        <div>
            <label for="td-guide-level" class="{{ $lbl }}">Poziom</label>
            <select id="td-guide-level" name="type_data[level]" class="{{ $inp }}">
                @foreach (\App\Models\Page::GUIDE_LEVELS as $lv => $ll)
                    <option value="{{ $lv }}" @selected(($td['level'] ?? 'basic') === $lv)>{{ $ll }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Zanim zaczniesz — wymagania</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('requirements') as $i => $row)
                <div data-td-row class="flex gap-2">
                    <input type="text" name="type_data[requirements][{{ $i }}][text]" value="{{ $row['text'] ?? '' }}" placeholder="np. Konto w systemie" aria-label="Wymaganie" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wymaganie"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj wymaganie</button>
        <template data-td-template>
            <div data-td-row class="flex gap-2">
                <input type="text" name="type_data[requirements][__INDEX__][text]" placeholder="np. Konto w systemie" aria-label="Wymaganie" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wymaganie"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Kroki (kolejność = kolejność na stronie)</p>
        <div data-td-rows class="space-y-3">
            @foreach ($tdRows('steps') as $i => $row)
                <div data-td-row class="rounded border border-gray-200 bg-white p-3">
                    <div class="flex gap-2">
                        <input type="text" name="type_data[steps][{{ $i }}][title]" value="{{ $row['title'] ?? '' }}" placeholder="Tytuł kroku" aria-label="Tytuł kroku" class="{{ $inp }} font-bold">
                        <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń krok"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                    </div>
                    <textarea name="type_data[steps][{{ $i }}][text]" rows="3" placeholder="Opis kroku (można używać akapitów)" aria-label="Opis kroku" class="{{ $inp }} mt-2">{{ $row['text'] ?? '' }}</textarea>
                    <input type="text" name="type_data[steps][{{ $i }}][tip]" value="{{ $row['tip'] ?? '' }}" placeholder="Wskazówka (opcjonalnie)" aria-label="Wskazówka" class="{{ $inp }} mt-2">
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj krok</button>
        <template data-td-template>
            <div data-td-row class="rounded border border-gray-200 bg-white p-3">
                <div class="flex gap-2">
                    <input type="text" name="type_data[steps][__INDEX__][title]" placeholder="Tytuł kroku" aria-label="Tytuł kroku" class="{{ $inp }} font-bold">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń krok"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
                <textarea name="type_data[steps][__INDEX__][text]" rows="3" placeholder="Opis kroku (można używać akapitów)" aria-label="Opis kroku" class="{{ $inp }} mt-2"></textarea>
                <input type="text" name="type_data[steps][__INDEX__][tip]" placeholder="Wskazówka (opcjonalnie)" aria-label="Wskazówka" class="{{ $inp }} mt-2">
            </div>
        </template>
    </div>

    <div>
        <label for="td-guide-summary" class="{{ $lbl }}">Podsumowanie / co dalej</label>
        <textarea id="td-guide-summary" name="type_data[summary]" rows="3" class="{{ $inp }}">{{ $td['summary'] ?? '' }}</textarea>
    </div>
</div>

{{-- ═══ SŁOWNIK POJĘĆ ═══ --}}
<div data-type-fields="glossary" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'glossary' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Słownik pojęć</p>

    <div>
        <label for="td-glossary-lead" class="{{ $lbl }}">Lead (o czym jest słownik)</label>
        <textarea id="td-glossary-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Hasła (na stronie sortowane alfabetycznie, z indeksem liter i wyszukiwarką)</p>
        <div data-td-rows class="space-y-3">
            @foreach ($tdRows('terms') as $i => $row)
                <div data-td-row class="rounded border border-gray-200 bg-white p-3">
                    <div class="flex gap-2">
                        <input type="text" name="type_data[terms][{{ $i }}][term]" value="{{ $row['term'] ?? '' }}" placeholder="Hasło, np. WCAG" aria-label="Hasło" class="{{ $inp }} font-bold">
                        <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń hasło"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                    </div>
                    <textarea name="type_data[terms][{{ $i }}][definition]" rows="3" placeholder="Definicja" aria-label="Definicja" class="{{ $inp }} mt-2">{{ $row['definition'] ?? '' }}</textarea>
                    <input type="text" name="type_data[terms][{{ $i }}][related]" value="{{ $row['related'] ?? '' }}" placeholder="Hasła powiązane, po przecinku (opcjonalnie)" aria-label="Hasła powiązane" class="{{ $inp }} mt-2">
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj hasło</button>
        <template data-td-template>
            <div data-td-row class="rounded border border-gray-200 bg-white p-3">
                <div class="flex gap-2">
                    <input type="text" name="type_data[terms][__INDEX__][term]" placeholder="Hasło, np. WCAG" aria-label="Hasło" class="{{ $inp }} font-bold">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń hasło"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
                <textarea name="type_data[terms][__INDEX__][definition]" rows="3" placeholder="Definicja" aria-label="Definicja" class="{{ $inp }} mt-2"></textarea>
                <input type="text" name="type_data[terms][__INDEX__][related]" placeholder="Hasła powiązane, po przecinku (opcjonalnie)" aria-label="Hasła powiązane" class="{{ $inp }} mt-2">
            </div>
        </template>
    </div>
</div>

{{-- ═══ STUDIUM PRZYPADKU ═══ --}}
<div data-type-fields="case_study" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'case_study' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Studium przypadku</p>

    <div class="grid gap-3 sm:grid-cols-3">
        <div><label for="td-cs-client" class="{{ $lbl }}">Klient / partner</label><input type="text" id="td-cs-client" name="type_data[client]" value="{{ $td['client'] ?? '' }}" class="{{ $inp }}"></div>
        <div><label for="td-cs-sector" class="{{ $lbl }}">Sektor</label><input type="text" id="td-cs-sector" name="type_data[sector]" value="{{ $td['sector'] ?? '' }}" placeholder="np. Administracja publiczna" class="{{ $inp }}"></div>
        <div><label for="td-cs-period" class="{{ $lbl }}">Okres realizacji</label><input type="text" id="td-cs-period" name="type_data[period]" value="{{ $td['period'] ?? '' }}" placeholder="np. 2025–2026" class="{{ $inp }}"></div>
    </div>
    <div>
        <label for="td-cs-lead" class="{{ $lbl }}">Lead</label>
        <textarea id="td-cs-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
        <div><label for="td-cs-challenge" class="{{ $lbl }}">Wyzwanie</label><textarea id="td-cs-challenge" name="type_data[challenge]" rows="5" class="{{ $inp }}">{{ $td['challenge'] ?? '' }}</textarea></div>
        <div><label for="td-cs-solution" class="{{ $lbl }}">Rozwiązanie</label><textarea id="td-cs-solution" name="type_data[solution]" rows="5" class="{{ $inp }}">{{ $td['solution'] ?? '' }}</textarea></div>
    </div>

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Efekty w liczbach</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('results') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[8rem_1fr_auto]">
                    <input type="text" name="type_data[results][{{ $i }}][value]" value="{{ $row['value'] ?? '' }}" placeholder="np. 98%" aria-label="Wartość" class="{{ $inp }} font-bold">
                    <input type="text" name="type_data[results][{{ $i }}][label]" value="{{ $row['label'] ?? '' }}" placeholder="np. kryteriów WCAG spełnionych" aria-label="Opis wartości" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń efekt"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj efekt</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[8rem_1fr_auto]">
                <input type="text" name="type_data[results][__INDEX__][value]" placeholder="np. 98%" aria-label="Wartość" class="{{ $inp }} font-bold">
                <input type="text" name="type_data[results][__INDEX__][label]" placeholder="np. kryteriów WCAG spełnionych" aria-label="Opis wartości" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń efekt"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <div class="grid gap-3 sm:grid-cols-[2fr_1fr]">
        <div><label for="td-cs-quote" class="{{ $lbl }}">Cytat klienta</label><textarea id="td-cs-quote" name="type_data[quote]" rows="2" class="{{ $inp }}">{{ $td['quote'] ?? '' }}</textarea></div>
        <div><label for="td-cs-quote-author" class="{{ $lbl }}">Autor cytatu</label><input type="text" id="td-cs-quote-author" name="type_data[quote_author]" value="{{ $td['quote_author'] ?? '' }}" class="{{ $inp }}"></div>
        <div><label for="td-cs-cta-label" class="{{ $lbl }}">Przycisk CTA — etykieta</label><input type="text" id="td-cs-cta-label" name="type_data[cta_label]" value="{{ $td['cta_label'] ?? '' }}" placeholder="np. Porozmawiajmy o Twoim projekcie" class="{{ $inp }}"></div>
        <div><label for="td-cs-cta-url" class="{{ $lbl }}">Przycisk CTA — adres</label><input type="text" id="td-cs-cta-url" name="type_data[cta_url]" value="{{ $td['cta_url'] ?? '' }}" placeholder="/kontakt" class="{{ $inp }}"></div>
    </div>
    <p class="text-xs text-muted">Treść główna strony (edytor powyżej) wyświetla się po sekcji „Rozwiązanie" jako rozwinięcie.</p>
</div>


{{-- ═══ CENNIK I OPŁATY ═══ --}}
<div data-type-fields="pricing" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'pricing' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Cennik i opłaty</p>
    <div>
        <label for="td-pricing-lead" class="{{ $lbl }}">Lead (1–2 zdania pod tytułem)</label>
        <textarea id="td-pricing-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>
    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Pozycje cennika (grupa, nazwa, cena, opis)</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('price_rows') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_2fr_1fr_2fr_auto]">
                    <input type="text" name="type_data[price_rows][{{ $i }}][group]" value="{{ $row['group'] ?? '' }}" placeholder="Grupa (opcjonalnie)" aria-label="Grupa" class="{{ $inp }}"> <input type="text" name="type_data[price_rows][{{ $i }}][item]" value="{{ $row['item'] ?? '' }}" placeholder="Nazwa usługi" aria-label="Nazwa usługi" class="{{ $inp }}"> <input type="text" name="type_data[price_rows][{{ $i }}][price]" value="{{ $row['price'] ?? '' }}" placeholder="np. 85 zł" aria-label="Cena" class="{{ $inp }}"> <input type="text" name="type_data[price_rows][{{ $i }}][note]" value="{{ $row['note'] ?? '' }}" placeholder="Krótki opis" aria-label="Opis" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj pozycję</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_2fr_1fr_2fr_auto]">
                <input type="text" name="type_data[price_rows][__INDEX__][group]" value="" placeholder="Grupa (opcjonalnie)" aria-label="Grupa" class="{{ $inp }}"> <input type="text" name="type_data[price_rows][__INDEX__][item]" value="" placeholder="Nazwa usługi" aria-label="Nazwa usługi" class="{{ $inp }}"> <input type="text" name="type_data[price_rows][__INDEX__][price]" value="" placeholder="np. 85 zł" aria-label="Cena" class="{{ $inp }}"> <input type="text" name="type_data[price_rows][__INDEX__][note]" value="" placeholder="Krótki opis" aria-label="Opis" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <div>
        <label for="td-pricing-pay" class="{{ $lbl }}">Zasady płatności (prostym językiem)</label>
        <textarea id="td-pricing-pay" name="type_data[payment_info]" rows="5" class="{{ $inp }}" placeholder="np. Płać po otrzymaniu faktury. Nie rób przedpłat.">{{ $td['payment_info'] ?? '' }}</textarea>
        <p class="mt-1 text-xs text-muted">Akapity rozdzielaj pustą linią. Nie wpisuj numerów kont — podawaj je w mailu z fakturą.</p>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
        <div><label for="td-pr-cta-label" class="{{ $lbl }}">Przycisk — etykieta</label><input type="text" id="td-pr-cta-label" name="type_data[cta_label]" value="{{ $td['cta_label'] ?? '' }}" class="{{ $inp }}"></div>
        <div><label for="td-pr-cta-url" class="{{ $lbl }}">Przycisk — adres</label><input type="text" id="td-pr-cta-url" name="type_data[cta_url]" value="{{ $td['cta_url'] ?? '' }}" class="{{ $inp }}"></div>
    </div>
    <p class="text-xs text-muted">Treść główna strony (edytor powyżej) wyświetla się nad cennikiem.</p>
</div>

{{-- ═══ ZESPÓŁ ═══ --}}
<div data-type-fields="team" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'team' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Zespół</p>
    <div>
        <label for="td-team-lead" class="{{ $lbl }}">Lead (1–2 zdania pod tytułem)</label>
        <textarea id="td-team-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>
    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Osoby (imię i nazwisko, rola, opis, e-mail)</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('members') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[1.2fr_1.2fr_2fr_1.4fr_auto]">
                    <input type="text" name="type_data[members][{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" placeholder="Imię i nazwisko" aria-label="Imię i nazwisko" class="{{ $inp }}"> <input type="text" name="type_data[members][{{ $i }}][role]" value="{{ $row['role'] ?? '' }}" placeholder="Rola" aria-label="Rola" class="{{ $inp }}"> <input type="text" name="type_data[members][{{ $i }}][text]" value="{{ $row['text'] ?? '' }}" placeholder="Krótki opis" aria-label="Opis" class="{{ $inp }}"> <input type="text" name="type_data[members][{{ $i }}][email]" value="{{ $row['email'] ?? '' }}" placeholder="E-mail (opcjonalnie)" aria-label="E-mail" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj osobę</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[1.2fr_1.2fr_2fr_1.4fr_auto]">
                <input type="text" name="type_data[members][__INDEX__][name]" value="" placeholder="Imię i nazwisko" aria-label="Imię i nazwisko" class="{{ $inp }}"> <input type="text" name="type_data[members][__INDEX__][role]" value="" placeholder="Rola" aria-label="Rola" class="{{ $inp }}"> <input type="text" name="type_data[members][__INDEX__][text]" value="" placeholder="Krótki opis" aria-label="Opis" class="{{ $inp }}"> <input type="text" name="type_data[members][__INDEX__][email]" value="" placeholder="E-mail (opcjonalnie)" aria-label="E-mail" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <p class="text-xs text-muted">Treść główna strony (edytor powyżej) wyświetla się nad listą osób.</p>
</div>

{{-- ═══ DOKUMENTY ═══ --}}
<div data-type-fields="documents" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'documents' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Dokumenty</p>
    <div>
        <label for="td-documents-lead" class="{{ $lbl }}">Lead (1–2 zdania pod tytułem)</label>
        <textarea id="td-documents-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>
    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Dokumenty (grupa, tytuł, adres pliku lub strony, opis)</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('docs') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_2fr_2fr_2fr_auto]">
                    <input type="text" name="type_data[docs][{{ $i }}][group]" value="{{ $row['group'] ?? '' }}" placeholder="Grupa, np. Statut" aria-label="Grupa" class="{{ $inp }}"> <input type="text" name="type_data[docs][{{ $i }}][title]" value="{{ $row['title'] ?? '' }}" placeholder="Tytuł dokumentu" aria-label="Tytuł" class="{{ $inp }}"> <input type="text" name="type_data[docs][{{ $i }}][url]" value="{{ $row['url'] ?? '' }}" placeholder="https://… albo /storage/plik.pdf" aria-label="Adres" class="{{ $inp }}"> <input type="text" name="type_data[docs][{{ $i }}][note]" value="{{ $row['note'] ?? '' }}" placeholder="Opis (opcjonalnie)" aria-label="Opis" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj dokument</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_2fr_2fr_2fr_auto]">
                <input type="text" name="type_data[docs][__INDEX__][group]" value="" placeholder="Grupa, np. Statut" aria-label="Grupa" class="{{ $inp }}"> <input type="text" name="type_data[docs][__INDEX__][title]" value="" placeholder="Tytuł dokumentu" aria-label="Tytuł" class="{{ $inp }}"> <input type="text" name="type_data[docs][__INDEX__][url]" value="" placeholder="https://… albo /storage/plik.pdf" aria-label="Adres" class="{{ $inp }}"> <input type="text" name="type_data[docs][__INDEX__][note]" value="" placeholder="Opis (opcjonalnie)" aria-label="Opis" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <p class="text-xs text-muted">Pliki wgrane w zakładce „Pliki” tej strony pokazują się dodatkowo jako karty „Pliki do pobrania”.</p>
</div>

{{-- ═══ SPRAWOZDANIA ROCZNE ═══ --}}
<div data-type-fields="reports" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'reports' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Sprawozdania roczne</p>
    <p class="text-sm text-muted">Strona pokazuje tabelę sprawozdań merytorycznych i finansowych z modułu <strong>Sprawozdania</strong> (lata, pliki PDF, załączniki). Treść strony wyświetla się nad tabelą. Dodaj tę stronę do menu BIP, aby sprawozdania były dostępne z poziomu BIP.</p>
    <div>
        <label for="td-reports-lead" class="{{ $lbl }}">Lead (1–2 zdania pod tytułem)</label>
        <textarea id="td-reports-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>
</div>

{{-- ═══ PRZEKIEROWANIE ═══ --}}
<div data-type-fields="redirect" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'redirect' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Przekierowanie</p>
    <div>
        <label for="td-redirect-url" class="{{ $lbl }}">Adres docelowy</label>
        <input type="text" id="td-redirect-url" name="type_data[target_url]" value="{{ $td['target_url'] ?? '' }}" placeholder="/nowy-adres lub https://…" class="{{ $inp }}">
        <p class="mt-1 text-xs text-muted">Ścieżka na tej stronie (zaczyna się od /) albo pełny adres http(s).</p>
    </div>
    <fieldset>
        <legend class="{{ $lbl }}">Co ma się stać po wejściu na tę stronę</legend>
        @php $rmode = $td['redirect_mode'] ?? 'notice'; @endphp
        <label class="mb-1 flex items-start gap-2 text-sm"><input type="radio" name="type_data[redirect_mode]" value="notice" @checked($rmode !== 'auto') class="mt-0.5 border-gray-300 text-brand focus:ring-brand"> <span><span class="font-bold">Pokaż stronę z informacją</span> i przycisk do nowego adresu (przyjazne dla osób korzystających z czytników ekranu).</span></label>
        <label class="flex items-start gap-2 text-sm"><input type="radio" name="type_data[redirect_mode]" value="auto" @checked($rmode === 'auto') class="mt-0.5 border-gray-300 text-brand focus:ring-brand"> <span><span class="font-bold">Przekieruj od razu</span> (301). W podglądzie z panelu zobaczysz stronę z informacją.</span></label>
    </fieldset>
    <div>
        <label for="td-redirect-msg" class="{{ $lbl }}">Komunikat <span class="font-normal text-muted">(opcjonalnie)</span></label>
        <textarea id="td-redirect-msg" name="type_data[redirect_message]" rows="2" placeholder="np. Ta treść znajduje się teraz w innym miejscu." class="{{ $inp }}">{{ $td['redirect_message'] ?? '' }}</textarea>
    </div>
    <div>
        <label for="td-redirect-label" class="{{ $lbl }}">Napis na przycisku <span class="font-normal text-muted">(opcjonalnie)</span></label>
        <input type="text" id="td-redirect-label" name="type_data[redirect_label]" value="{{ $td['redirect_label'] ?? '' }}" placeholder="Przejdź do nowej strony" class="{{ $inp }}">
    </div>
</div>

{{-- ═══ REGULAMIN / DOKUMENT ═══ --}}
<div data-type-fields="regulation" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'regulation' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Regulamin lub dokument</p>
    <div>
        <label for="td-regulation-lead" class="{{ $lbl }}">Lead (1–2 zdania pod tytułem)</label>
        <textarea id="td-regulation-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
        <div><label for="td-reg-from" class="{{ $lbl }}">Obowiązuje od</label><input type="text" id="td-reg-from" name="type_data[valid_from]" value="{{ $td['valid_from'] ?? '' }}" placeholder="np. 1 stycznia 2026" class="{{ $inp }}"></div>
        <div><label for="td-reg-ver" class="{{ $lbl }}">Wersja</label><input type="text" id="td-reg-ver" name="type_data[version]" value="{{ $td['version'] ?? '' }}" placeholder="np. 2.1" class="{{ $inp }}"></div>
    </div>
    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Poprzednie wersje (nazwa, adres)</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('versions') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[2fr_3fr_auto]">
                    <input type="text" name="type_data[versions][{{ $i }}][label]" value="{{ $row['label'] ?? '' }}" placeholder="np. Wersja 2.0 (do 31.12.2025)" aria-label="Nazwa wersji" class="{{ $inp }}"> <input type="text" name="type_data[versions][{{ $i }}][url]" value="{{ $row['url'] ?? '' }}" placeholder="https://… albo /storage/plik.pdf" aria-label="Adres" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj poprzednią wersję</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[2fr_3fr_auto]">
                <input type="text" name="type_data[versions][__INDEX__][label]" value="" placeholder="np. Wersja 2.0 (do 31.12.2025)" aria-label="Nazwa wersji" class="{{ $inp }}"> <input type="text" name="type_data[versions][__INDEX__][url]" value="" placeholder="https://… albo /storage/plik.pdf" aria-label="Adres" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>

    <p class="text-xs text-muted">Treść główna strony (edytor powyżej) to tekst regulaminu — nagłówki H2 i H3 tworzą automatyczny spis treści.</p>
</div>

<script>
    // Repeater dla pól type_data: klonuje <template>, podstawia kolejny indeks,
    // usuwa wiersze. Bez przeliczania nazw — kolejność w JSON = kolejność w DOM.
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-td-repeater]').forEach(function (rep) {
            var rows = rep.querySelector('[data-td-rows]');
            var template = rep.querySelector('[data-td-template]');
            var addBtn = rep.querySelector('[data-td-add]');
            if (!rows || !template || !addBtn) return;
            var next = rows.querySelectorAll('[data-td-row]').length;
            addBtn.addEventListener('click', function () {
                var wrap = document.createElement('div');
                wrap.innerHTML = template.innerHTML.replace(/__INDEX__/g, String(next++)).trim();
                var row = wrap.firstElementChild;
                rows.appendChild(row);
                row.querySelector('input, textarea')?.focus();
            });
            rep.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-td-remove]');
                if (btn && rep.contains(btn)) { btn.closest('[data-td-row]')?.remove(); addBtn.focus(); }
            });
        });
    });
</script>
