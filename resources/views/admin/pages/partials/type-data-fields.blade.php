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

{{-- ═══ PARTNERZY I SPONSORZY ═══ --}}
<div data-type-fields="partners" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'partners' ? '' : 'hidden' }}">
    @php
        $partnerOptions = \App\Models\Partner::forCurrentSite()->orderBy('order')->orderBy('name')->get(['id', 'name']);
        $partnerSelect = function (string $name, $selected = null) use ($partnerOptions, $inp) {
            $h = '<select name="'.$name.'" aria-label="Partner z modułu" class="'.$inp.'"><option value="">— własny wpis (nazwa niżej) —</option>';
            foreach ($partnerOptions as $po) { $h .= '<option value="'.$po->id.'"'.((int) $selected === $po->id ? ' selected' : '').'>'.e($po->name).'</option>'; }
            return $h.'</select>';
        };
    @endphp
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Partnerzy i sponsorzy</p>
    <div>
        <label for="td-partners-lead" class="{{ $lbl }}">Lead (1–2 zdania pod tytułem)</label>
        <textarea id="td-partners-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <label for="td-partners-layout" class="{{ $lbl }}">Układ</label>
            <select id="td-partners-layout" name="type_data[partners_layout]" class="{{ $inp }}">
                <option value="logos" @selected(($td['partners_layout'] ?? 'logos') === 'logos')>Ściana logotypów (same loga)</option>
                <option value="cards" @selected(($td['partners_layout'] ?? '') === 'cards')>Karty (logo, nazwa, opis roli)</option>
            </select>
        </div>
        <label class="flex items-start gap-2 pt-5 text-sm"><input type="hidden" name="type_data[partners_all]" value="0"><input type="checkbox" name="type_data[partners_all]" value="1" @checked(! empty($td['partners_all'])) class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand"> <span><span class="font-bold">Pokaż też wszystkich partnerów z modułu</span><span class="block text-xs text-muted">Ci, których nie przypiszesz do grupy, trafią do grupy „Pozostali partnerzy”.</span></span></label>
    </div>
    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Grupy (np. Partnerzy strategiczni, Sponsorzy, Patroni medialni) — nazwa i krótki opis</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('partner_groups') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_2fr_auto]">
                    <input type="text" name="type_data[partner_groups][{{ $i }}][title]" value="{{ $row['title'] ?? '' }}" placeholder="Nazwa grupy" aria-label="Nazwa grupy" class="{{ $inp }}"> <input type="text" name="type_data[partner_groups][{{ $i }}][text]" value="{{ $row['text'] ?? '' }}" placeholder="Opis grupy (opcjonalnie)" aria-label="Opis grupy" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj grupę</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_2fr_auto]">
                <input type="text" name="type_data[partner_groups][__INDEX__][title]" value="" placeholder="Nazwa grupy" aria-label="Nazwa grupy" class="{{ $inp }}"> <input type="text" name="type_data[partner_groups][__INDEX__][text]" value="" placeholder="Opis grupy (opcjonalnie)" aria-label="Opis grupy" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
    </div>
    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Wpisy (grupa, partner z modułu albo własna nazwa, adres, logo, opis roli)</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('partners') as $i => $row)
                <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_1.4fr_1.2fr_1.2fr_1.2fr_1.4fr_auto]">
                    <input type="text" name="type_data[partners][{{ $i }}][group]" value="{{ $row['group'] ?? '' }}" placeholder="Grupa" aria-label="Grupa" class="{{ $inp }}"> {!! $partnerSelect("type_data[partners][{$i}][partner_id]", $row['partner_id'] ?? null) !!} <input type="text" name="type_data[partners][{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" placeholder="Własna nazwa" aria-label="Własna nazwa" class="{{ $inp }}"> <input type="text" name="type_data[partners][{{ $i }}][url]" value="{{ $row['url'] ?? '' }}" placeholder="https://…" aria-label="Adres strony" class="{{ $inp }}"> <input type="text" name="type_data[partners][{{ $i }}][logo]" value="{{ $row['logo'] ?? '' }}" placeholder="Adres logo (własny wpis)" aria-label="Adres logo" class="{{ $inp }}"> <input type="text" name="type_data[partners][{{ $i }}][note]" value="{{ $row['note'] ?? '' }}" placeholder="Opis roli (np. finansuje szkolenia)" aria-label="Opis roli" class="{{ $inp }}">
                    <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj wpis</button>
        <template data-td-template>
            <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_1.4fr_1.2fr_1.2fr_1.2fr_1.4fr_auto]">
                <input type="text" name="type_data[partners][__INDEX__][group]" value="" placeholder="Grupa" aria-label="Grupa" class="{{ $inp }}"> {!! $partnerSelect('type_data[partners][__INDEX__][partner_id]') !!} <input type="text" name="type_data[partners][__INDEX__][name]" value="" placeholder="Własna nazwa" aria-label="Własna nazwa" class="{{ $inp }}"> <input type="text" name="type_data[partners][__INDEX__][url]" value="" placeholder="https://…" aria-label="Adres strony" class="{{ $inp }}"> <input type="text" name="type_data[partners][__INDEX__][logo]" value="" placeholder="Adres logo (własny wpis)" aria-label="Adres logo" class="{{ $inp }}"> <input type="text" name="type_data[partners][__INDEX__][note]" value="" placeholder="Opis roli" aria-label="Opis roli" class="{{ $inp }}">
                <button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
            </div>
        </template>
        <p class="text-xs text-muted">Logo partnera z modułu bierze się automatycznie. Adres logo własnego wpisu możesz skopiować z Biblioteki multimediów.</p>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
        <div><label for="td-partners-cta-label" class="{{ $lbl }}">Przycisk „Zostań partnerem” — napis</label><input type="text" id="td-partners-cta-label" name="type_data[partners_cta_label]" value="{{ $td['partners_cta_label'] ?? '' }}" placeholder="Zostań partnerem" class="{{ $inp }}"></div>
        <div><label for="td-partners-cta-url" class="{{ $lbl }}">Przycisk — adres</label><input type="text" id="td-partners-cta-url" name="type_data[partners_cta_url]" value="{{ $td['partners_cta_url'] ?? '' }}" placeholder="/kontakt lub mailto:…" class="{{ $inp }}"></div>
    </div>
    <div>
        <label for="td-partners-thanks" class="{{ $lbl }}">Podziękowanie (na dole strony, opcjonalnie)</label>
        <textarea id="td-partners-thanks" name="type_data[partners_thanks]" rows="2" placeholder="np. Dziękujemy wszystkim, którzy wspierają nasze działania." class="{{ $inp }}">{{ $td['partners_thanks'] ?? '' }}</textarea>
    </div>
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


{{-- ═══ REKRUTACJA DO PROJEKTU ═══ --}}
@php
    // Klasy siatki jako literały (Tailwind wykrywa tylko pełne nazwy klas w źródle).
    $recCols = [
        'audience' => 'flex gap-2', 'requirements' => 'flex gap-2',
        'bonus' => 'grid gap-2 sm:grid-cols-[3fr_8rem_auto]',
        'benefits' => 'grid gap-2 sm:grid-cols-[10rem_1fr_2fr_auto]',
        'steps' => 'grid gap-2 sm:grid-cols-[1fr_2fr_auto]',
        'docs' => 'grid gap-2 sm:grid-cols-[1fr_1fr_1fr_auto]',
        'rec_faq' => 'grid gap-2 sm:grid-cols-[1fr_2fr_auto]',
    ];
    $recRow = function (string $list, array $fields, string $removeLabel, ?int $i = null, array $row = []) use ($inp, $rmBtn, $recCols) {
        $idx = $i === null ? '__INDEX__' : $i;
        $cols = $recCols[$list];
        $html = '<div data-td-row class="'.$cols.'">';
        foreach ($fields as $key => $f) {
            $val = e($row[$key] ?? '');
            $attrs = 'name="type_data['.$list.']['.$idx.']['.$key.']" placeholder="'.e($f['ph'] ?? '').'" aria-label="'.e($f['label']).'" class="'.$inp.'"';
            $html .= ($f['textarea'] ?? false) ? '<textarea rows="2" '.$attrs.'>'.$val.'</textarea>' : '<input type="'.($f['type'] ?? 'text').'" value="'.$val.'" '.$attrs.'>';
        }

        return $html.'<button type="button" data-td-remove class="'.$rmBtn.'" aria-label="'.e($removeLabel).'"><i class="fa-solid fa-trash" aria-hidden="true"></i></button></div>';
    };
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
