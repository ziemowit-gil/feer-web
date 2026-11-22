{{--
    Ustawienia szablonu „kreatywni” (Ustawienia → Szablon): które sekcje strony głównej pokazać i ich teksty.
    Pola budują się z rejestru App\Support\KreatywniTexts. Pole wypełnione jest bieżącym tekstem; puste pole albo
    tekst równy domyślnemu = tekst domyślny (nie zapisujemy go, więc poprawki w kodzie docierają do witryny).
--}}
@php
    $kcTexts = \App\Support\KreatywniTexts::for($settings);
    $kcHidden = (array) old('kreatywni_texts._shown') !== [] || old('kreatywni_texts._sections')
        ? array_diff(array_keys(\App\Support\KreatywniTexts::SECTIONS), (array) old('kreatywni_texts._shown', []))
        : $kcTexts->hidden();
@endphp
<div x-show="tpl === 'kreatywni'" x-cloak class="space-y-5 rounded-md bg-gray-50 p-5">
    <h3 class="text-sm font-bold text-ink">Ustawienia szablonu: Kreatywni na co dzień</h3>
    <p class="text-xs text-muted">
        Wizytówka fundacji na jednej stronie. Telefon i e-mail bierzemy z zakładki <span class="font-bold">Kontakt</span>,
        profile z zakładki <span class="font-bold">Media i BIP</span> (Facebook, Instagram, YouTube…), zdjęcia z modułu
        <span class="font-bold">Galeria</span>, numer KRS i konta z zakładki <span class="font-bold">Ogólne</span>.
        Menu w nagłówku to <span class="font-bold">Menu nawigacji → Menu główne</span>. Listy wpisujesz po jednej pozycji
        w wierszu, części rozdzielasz znakiem „|”. Puste pole przywraca tekst domyślny.
    </p>
    <p class="text-xs text-muted">
        Stopka: tekst praw autorskich i linki w dolnym pasku (mapa serwisu, deklaracja dostępności, cookies, strefa
        współpracownika, panel) ustawiasz w
        <a href="{{ route('admin.ustawienia.edit', ['tab' => 'general', 'karta' => 'stopka']) }}" class="font-bold text-brand-dark underline underline-offset-2">Ogólne → Stopka</a>,
        linki w kolumnie „Na skróty” w <a href="{{ route('admin.pozycje-menu.index', ['location' => 'footer']) }}" class="font-bold text-brand-dark underline underline-offset-2">Menu nawigacji → Stopka</a>.
    </p>

    <fieldset>
        <legend class="mb-1 text-sm font-bold">Sekcje strony głównej</legend>
        <p class="mb-2 text-xs text-muted">Odznaczona sekcja się nie pokazuje. Sekcja bez treści (np. galeria bez zdjęć) też się nie pokazuje.</p>
        <input type="hidden" name="kreatywni_texts[_sections]" value="1">
        <div class="grid gap-2 sm:grid-cols-2">
            @foreach (\App\Support\KreatywniTexts::SECTIONS as $kcKey => [$kcName, $kcDesc])
                <label class="flex cursor-pointer items-start gap-2 rounded border border-gray-300 bg-white p-2">
                    <input type="checkbox" name="kreatywni_texts[_shown][]" value="{{ $kcKey }}" class="mt-1" @checked(! in_array($kcKey, $kcHidden, true))>
                    <span>
                        <span class="block text-sm font-bold text-ink">{{ $kcName }}</span>
                        <span class="block text-xs text-muted">{{ $kcDesc }}</span>
                    </span>
                </label>
            @endforeach
        </div>
    </fieldset>

    {{-- Wygląd listy warsztatów w bloku oferty (strona główna i góra strony „Oferta”). --}}
    @php $kcOfferStyle = old('kreatywni_texts._offer_style', $kcTexts->offerStyle()); @endphp
    <fieldset aria-describedby="kc-offer-style-help">
        <legend class="mb-1 text-sm font-bold">Wygląd listy warsztatów</legend>
        <p id="kc-offer-style-help" class="mb-2 text-xs text-muted">Dotyczy bloku oferty na stronie głównej i na górze strony „Oferta”. Same warsztaty wpisujesz w polu „Warsztaty” w tekstach oferty.</p>
        <div class="grid gap-2 sm:grid-cols-2">
            @foreach (\App\Support\KreatywniTexts::OFFER_STYLES as $kcStyleKey => [$kcStyleName, $kcStyleDesc])
                <label class="flex cursor-pointer items-start gap-2 rounded border border-gray-300 bg-white p-2">
                    <input type="radio" name="kreatywni_texts[_offer_style]" value="{{ $kcStyleKey }}" class="mt-1" @checked($kcOfferStyle === $kcStyleKey)>
                    <span>
                        <span class="block text-sm font-bold text-ink">{{ $kcStyleName }}</span>
                        <span class="block text-xs text-muted">{{ $kcStyleDesc }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        @error('kreatywni_texts._offer_style') <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
    </fieldset>

    @foreach (\App\Support\KreatywniTexts::SECTIONS + \App\Support\KreatywniTexts::PAGE_GROUPS as $kcKey => [$kcName])
        @php $kcFields = \App\Support\KreatywniTexts::fields($kcKey); @endphp
        @continue($kcFields === [])
        <details class="rounded border border-gray-300 bg-white">
            <summary class="cursor-pointer px-3 py-2 text-sm font-bold text-ink">Teksty: {{ $kcName }}</summary>
            <div class="space-y-4 border-t border-gray-200 p-3">
                @foreach ($kcFields as $kcField => [$kcLabel, $kcType, $kcDefault])
                    @php $kcValue = old('kreatywni_texts.'.$kcField, $kcTexts->get($kcField)); @endphp
                    <div>
                        <label for="kc_{{ $kcField }}" class="mb-1 block text-sm font-bold">{{ $kcLabel }}</label>
                        @if ($kcType === 'line' || $kcType === 'url')
                            <input type="text" id="kc_{{ $kcField }}" name="kreatywni_texts[{{ $kcField }}]" maxlength="{{ $kcType === 'url' ? 500 : 255 }}"
                                value="{{ $kcValue }}" @if ($kcDefault !== '') placeholder="{{ $kcDefault }}" @endif
                                class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                        @else
                            <textarea id="kc_{{ $kcField }}" name="kreatywni_texts[{{ $kcField }}]" rows="{{ $kcType === 'list' ? min(16, max(3, substr_count($kcValue, "\n") + 2)) : 4 }}"
                                class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">{{ $kcValue }}</textarea>
                        @endif
                        @error('kreatywni_texts.'.$kcField) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>
        </details>
    @endforeach
</div>
