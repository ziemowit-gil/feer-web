{{-- Opcje strony kontaktowej — wspólne pola formularza strony typu „Kontakt” (zmienna: $settings). --}}
            <p class="mb-4 text-xs text-muted">Wyświetlane w sekcji „Kontakt" na dole strony głównej oraz na podstronie <a href="{{ route('contact.show') }}" target="_blank" rel="noopener" class="text-brand underline">/kontakt</a>.</p>

            <div class="mb-5">
                <p class="mb-2 text-sm font-bold">Wygląd strony kontaktowej</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach (\App\Models\SiteSetting::CONTACT_LAYOUTS as $clValue => $clLabel)
                        @continue($settings->isOptionBlocked('contact_layouts', $clValue) && old('contact_layout', $layoutDefault ?? $settings->contactLayoutValue()) !== $clValue)
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition has-[:checked]:border-brand has-[:checked]:bg-brand-light">
                            <input type="radio" name="contact_layout" value="{{ $clValue }}"
                                {{ old('contact_layout', $layoutDefault ?? $settings->contactLayoutValue()) === $clValue ? 'checked' : '' }}
                                class="mt-0.5 border-gray-300 text-brand focus:ring-brand">
                            <span class="text-sm leading-snug">{{ $clLabel }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-muted">Oba warianty pokazują te same sekcje (formularz, spotkania, przesyłki, rachunki) — różni je układ i sposób podania danych kontaktowych.</p>
                @error('contact_layout') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label for="editor-contact_intro" class="mb-1 block text-sm font-bold">Wstęp na stronie kontakt <span class="font-normal text-muted">(opcjonalnie)</span></label>
                @include('admin.partials.editor', ['name' => 'contact_intro', 'value' => old('contact_intro', $settings->contact_intro)])
                @error('contact_intro') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <label class="mb-4 flex items-start gap-2 rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm">
                <input type="checkbox" name="show_coordinators" value="1" @checked(old('show_coordinators', $settings->show_coordinators)) class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                <span>
                    <span class="font-bold">Pokazuj koordynatorów działań</span>
                    <span class="block text-xs text-muted">Wyłącza dane koordynatorów na stronach projektów. Poszczególne działania mają dodatkowo własny przełącznik.</span>
                </span>
            </label>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="contact_address" class="mb-1 block text-sm font-bold">Adres rejestrowy (ulica i numer)</label>
                    <input type="text" id="contact_address" name="contact_address" value="{{ old('contact_address', $settings->contact_address) }}" required
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('contact_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="contact_city" class="mb-1 block text-sm font-bold">Adres rejestrowy — kod pocztowy i miasto</label>
                    <input type="text" id="contact_city" name="contact_city" value="{{ old('contact_city', $settings->contact_city) }}" required
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('contact_city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2 mt-2 rounded-xl border border-gray-200 bg-gray-50/70 p-4">
                    <p class="text-sm font-bold text-ink">Biuro / korespondencja</p>
                    <p class="mb-3 text-xs text-muted">
                        Osobny adres, pod który realnie trafia poczta. Wypełnij, jeśli różni się od rejestrowego —
                        wtedy na stronie kontaktowej pojawią się dwie pozycje: „Adres rejestrowy" i „Biuro / korespondencja".
                    </p>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="contact_office_address" class="mb-1 block text-sm font-bold">Adres biura (ulica i numer)</label>
                            <input type="text" id="contact_office_address" name="contact_office_address"
                                value="{{ old('contact_office_address', $settings->contact_office_address) }}"
                                placeholder="np. ul. Przykładowa 10"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('contact_office_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="contact_office_city" class="mb-1 block text-sm font-bold">Kod pocztowy i miasto</label>
                            <input type="text" id="contact_office_city" name="contact_office_city"
                                value="{{ old('contact_office_city', $settings->contact_office_city) }}"
                                placeholder="np. 30-001 Kraków"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('contact_office_city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="contact_office_building" class="mb-1 block text-sm font-bold">Nazwa budynku <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <input type="text" id="contact_office_building" name="contact_office_building"
                                value="{{ old('contact_office_building', $settings->contact_office_building) }}"
                                placeholder="np. Biurowiec HEXAGON"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            <p class="mt-1 text-xs text-muted">Pokazujemy nad adresem — ułatwia trafienie do właściwego budynku.</p>
                            @error('contact_office_building') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="contact_office_note" class="mb-1 block text-sm font-bold">Wskazówka dojścia <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <textarea id="contact_office_note" name="contact_office_note" rows="3"
                                placeholder="np. Biuro dzielimy z HubKolektyw sp. z o.o. — na domofonie i recepcji szukaj tej nazwy. Nasz zespół pracuje zdalnie, więc wizytę umów wcześniej."
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('contact_office_note', $settings->contact_office_note) }}</textarea>
                            <p class="mt-1 text-xs text-muted">Z kim dzielicie biuro, czego szukać na domofonie, czy trzeba się umówić. Enter tworzy nowy akapit.</p>
                            @error('contact_office_note') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <p class="mb-1 text-sm font-bold">Zdjęcie biura / wejścia</p>
                            @if ($settings->officePhotoUrl())
                                <div class="mb-2 flex items-center gap-3">
                                    <img src="{{ $settings->officePhotoUrl() }}" alt="Aktualne zdjęcie biura"
                                        class="h-20 w-auto rounded object-cover ring-1 ring-gray-200">
                                    <label class="flex items-center gap-2 text-sm text-muted">
                                        <input type="checkbox" name="remove_office_photo" value="1" class="rounded border-gray-300 text-brand focus:ring-brand">
                                        Usuń zdjęcie
                                    </label>
                                </div>
                            @endif
                            <input type="file" name="office_photo" accept="image/*"
                                class="block w-full cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
                            <p class="mt-1 text-xs text-muted">Zdjęcie wejścia albo budynku — pomaga trafić na miejsce. Maks. 4 MB.</p>

                            <label class="mt-3 flex items-start gap-2 rounded-lg border border-gray-200 bg-white p-3">
                                <input type="checkbox" name="contact_hero_photo" value="1"
                                    {{ old('contact_hero_photo', $settings->contact_hero_photo ?? true) ? 'checked' : '' }}
                                    class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                                <span class="text-sm">
                                    <span class="font-bold">Pokaż zdjęcie w tle nagłówka strony kontaktowej</span>
                                    <span class="block text-xs text-muted">Dotyczy wariantów „Instytucjonalny" i „Wizytówka". Po wyłączeniu nagłówek jest jednolity, a zdjęcie zostaje przy danych biura.</span>
                                </span>
                            </label>
                            @error('office_photo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="contact_office_photo_alt" class="mb-1 block text-sm font-bold">Opis alternatywny zdjęcia</label>
                            <input type="text" id="contact_office_photo_alt" name="contact_office_photo_alt"
                                value="{{ old('contact_office_photo_alt', $settings->contact_office_photo_alt) }}"
                                placeholder="np. Szklane wejście do biurowca HEXAGON od strony ulicy"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            <p class="mt-1 text-xs text-muted">Opis dla osób korzystających z czytnika ekranu (WCAG 1.1.1). Puste = zdjęcie dekoracyjne.</p>
                            @error('contact_office_photo_alt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <label for="contact_email" class="mb-1 block text-sm font-bold">E-mail kontaktowy</label>
                    <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $settings->contact_email) }}" required
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('contact_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="contact_phone" class="mb-1 block text-sm font-bold">Telefon <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <input type="text" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $settings->contact_phone) }}" placeholder="np. +48 123 456 789"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('contact_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-4">
                <label for="contact_office_hours" class="mb-1 block text-sm font-bold">Godziny pracy <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <input type="text" id="contact_office_hours" name="contact_office_hours"
                    value="{{ old('contact_office_hours', $settings->contact_office_hours) }}"
                    placeholder="np. Poniedziałek – piątek: 9:00–17:00"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                <p class="mt-1 text-xs text-muted">Pojawi się w bocznym panelu danych kontaktowych. Zostaw puste, aby ukryć.</p>
                @error('contact_office_hours') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="mt-4">
                <label for="contact_edelivery_address" class="mb-1 block text-sm font-bold">Adres do e-Doręczeń <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <input type="text" id="contact_edelivery_address" name="contact_edelivery_address" value="{{ old('contact_edelivery_address', $settings->contact_edelivery_address) }}"
                    placeholder="AE:PL-12345-67890-ABCDE-12"
                    class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                <p class="mt-1 text-xs text-muted">Adres do doręczeń elektronicznych (ADE). Pokaże się w danych kontaktowych na podstronie <a href="{{ route('contact.show') }}" target="_blank" rel="noopener" class="text-brand underline">/kontakt</a>. Zostaw puste, aby ukryć.</p>
                @error('contact_edelivery_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Wyróżniona uwaga o kierowaniu korespondencji --}}
            <div class="mt-8 space-y-4 rounded-lg border border-amber-200 bg-amber-50 p-5">
                <div>
                    <h3 class="text-sm font-bold text-ink">Uwaga o kierowaniu korespondencji</h3>
                    <p class="mt-0.5 text-xs text-muted">Wyróżniona ramka na samej górze podstrony <a href="{{ route('contact.show') }}" target="_blank" rel="noopener" class="text-brand underline">/kontakt</a>, nad formularzem — np. „Pisma urzędowe kierujcie na adres e-Doręczeń, a nie na adres biura". Zostaw treść pustą, aby ukryć ramkę.</p>
                </div>

                <div>
                    <label for="contact_correspondence_title" class="mb-1 block text-sm font-bold">Nagłówek uwagi <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <input type="text" id="contact_correspondence_title" name="contact_correspondence_title"
                        value="{{ old('contact_correspondence_title', $settings->contact_correspondence_title) }}"
                        placeholder="Ważne: kierowanie korespondencji"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Puste = „Ważne: kierowanie korespondencji".</p>
                    @error('contact_correspondence_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="contact_correspondence_note" class="mb-1 block text-sm font-bold">Treść uwagi</label>
                    <textarea id="contact_correspondence_note" name="contact_correspondence_note" rows="4"
                        placeholder="Całą korespondencję urzędową prosimy kierować na adres e-Doręczeń. Listy wysłane na adres biura mogą dotrzeć z opóźnieniem."
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('contact_correspondence_note', $settings->contact_correspondence_note) }}</textarea>
                    <p class="mt-1 text-xs text-muted">Zwykły tekst — przejścia do nowej linii zostaną zachowane. Maksymalnie 1000 znaków.</p>
                    @error('contact_correspondence_note') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Przesyłki: paczka / list / paczkomat --}}
            <div class="mt-8 space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-5">
                <div>
                    <p class="text-sm font-bold text-ink">Przesyłki (paczka, list, paczkomat)</p>
                    <p class="mt-0.5 text-xs text-muted">Sekcja pojawia się na podstronie <a href="{{ route('contact.show') }}" target="_blank" rel="noopener" class="text-brand underline">/kontakt</a> automatycznie, gdy którekolwiek pole poniżej jest wypełnione. Zostaw wszystkie puste, aby ukryć blok.</p>
                </div>

                <div>
                    <label for="contact_shipping_note" class="mb-1 block text-sm font-bold">Tekst wstępny <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <input type="text" id="contact_shipping_note" name="contact_shipping_note" value="{{ old('contact_shipping_note', $settings->contact_shipping_note) }}"
                        placeholder="np. Możesz nadać do nas paczkę lub list — również na paczkomat."
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('contact_shipping_note') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="rounded border border-gray-200 bg-white p-4">
                    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-muted">Paczkomat InPost</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="contact_paczkomat_code" class="mb-1 block text-sm font-bold">Kod paczkomatu</label>
                            <input type="text" id="contact_paczkomat_code" name="contact_paczkomat_code" value="{{ old('contact_paczkomat_code', $settings->contact_paczkomat_code) }}"
                                placeholder="np. NSA22M" class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                            @error('contact_paczkomat_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="contact_shipping_phone" class="mb-1 block text-sm font-bold">Telefon do przesyłki</label>
                            <input type="text" id="contact_shipping_phone" name="contact_shipping_phone" value="{{ old('contact_shipping_phone', $settings->contact_shipping_phone) }}"
                                placeholder="np. 601 350 487" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            @error('contact_shipping_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="contact_paczkomat_address" class="mb-1 block text-sm font-bold">Adres paczkomatu</label>
                            <input type="text" id="contact_paczkomat_address" name="contact_paczkomat_address" value="{{ old('contact_paczkomat_address', $settings->contact_paczkomat_address) }}"
                                placeholder="np. Barbackiego 81, 33-300 Nowy Sącz" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            @error('contact_paczkomat_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="contact_paczkomat_location" class="mb-1 block text-sm font-bold">Lokalizacja / wskazówka dojścia <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <input type="text" id="contact_paczkomat_location" name="contact_paczkomat_location" value="{{ old('contact_paczkomat_location', $settings->contact_paczkomat_location) }}"
                                placeholder="np. Boczna ściana sklepu przy parkingu" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            @error('contact_paczkomat_location') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Rachunki bankowe pokazywane na podstronie /kontakt --}}
            @php
                $bankAccounts = old('contact_bank_accounts', $settings->contact_bank_accounts ?: []);
                if (empty($bankAccounts)) {
                    $bankAccounts = [['number' => '', 'purpose' => '']];
                }
            @endphp
            <div class="mt-8 space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-5"
                x-data="{ accounts: {{ \Illuminate\Support\Js::from(array_values($bankAccounts)) }} }">
                <div>
                    <p class="text-sm font-bold text-ink">Numery rachunków bankowych</p>
                    <p class="mt-0.5 text-xs text-muted">Lista rachunków pokazywana na podstronie <a href="{{ route('contact.show') }}" target="_blank" rel="noopener" class="text-brand underline">/kontakt</a>. Przy każdym rachunku podaj opis — do czego służy i co można na niego wpłacić. Zostaw numer pusty, aby usunąć wiersz przy zapisie.</p>
                </div>

                <template x-for="(account, index) in accounts" :key="index">
                    <div class="rounded border border-gray-200 bg-white p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-bold uppercase tracking-wide text-muted" x-text="'Rachunek ' + (index + 1)"></p>
                            <button type="button" @click="accounts.splice(index, 1)"
                                class="inline-flex items-center gap-1 text-xs font-bold text-red-600 hover:text-red-700">
                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i> Usuń
                            </button>
                        </div>
                        <div class="mt-3 space-y-3">
                            <div>
                                <label :for="'contact_bank_account_number_' + index" class="mb-1 block text-sm font-bold">Numer konta</label>
                                <input type="text" :id="'contact_bank_account_number_' + index"
                                    :name="'contact_bank_accounts[' + index + '][number]'" x-model="account.number"
                                    placeholder="PL00 0000 0000 0000 0000 0000 0000"
                                    class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                            </div>
                            <div>
                                <label :for="'contact_bank_account_purpose_' + index" class="mb-1 block text-sm font-bold">Do czego służy / co można wpłacić</label>
                                <textarea :id="'contact_bank_account_purpose_' + index"
                                    :name="'contact_bank_accounts[' + index + '][purpose]'" x-model="account.purpose" rows="2"
                                    placeholder="np. Darowizny na cele statutowe — wsparcie bieżących działań fundacji"
                                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand"></textarea>
                            </div>
                        </div>
                    </div>
                </template>

                <button type="button" @click="accounts.push({ number: '', purpose: '' })"
                    class="inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand hover:text-white">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj rachunek
                </button>
                @error('contact_bank_accounts') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                <div class="border-t border-gray-200 pt-4">
                    <label for="contact_bank_accounts_note" class="mb-1 block text-sm font-bold">Dodatkowa notatka (dowolny tekst)</label>
                    <textarea id="contact_bank_accounts_note" name="contact_bank_accounts_note" rows="3"
                        placeholder="np. W sprawie faktur lub większych wpłat prosimy o kontakt mailowy przed przelewem."
                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">{{ old('contact_bank_accounts_note', $settings->contact_bank_accounts_note) }}</textarea>
                    <p class="mt-1 text-xs text-muted">Pokazuje się nad listą rachunków na podstronie /kontakt. Można zostawić puste albo wpisać coś, nawet jeśli nie dodałeś żadnego rachunku powyżej.</p>
                    @error('contact_bank_accounts_note') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="border-t border-gray-200 pt-4">
                    <p class="mb-2 text-sm font-bold">Układ listy rachunków</p>
                    @php $bankLayout = old('contact_bank_accounts_layout', $settings->contact_bank_accounts_layout ?: 'cards'); @endphp
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach (\App\Models\SiteSetting::BANK_ACCOUNTS_LAYOUTS as $blValue => $blLabel)
                            <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2.5 has-[:checked]:border-brand has-[:checked]:bg-brand-light">
                                <input type="radio" name="contact_bank_accounts_layout" value="{{ $blValue }}" {{ $bankLayout === $blValue ? 'checked' : '' }} class="mt-0.5 text-brand focus:ring-brand">
                                <span class="text-sm"><span class="font-bold">{{ \Illuminate\Support\Str::before($blLabel, ' (') }}</span><span class="block text-xs text-muted">{{ \Illuminate\Support\Str::of($blLabel)->after('(')->before(')') }}</span></span>
                            </label>
                        @endforeach
                    </div>
                    @error('contact_bank_accounts_layout') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Sekcja „Spotkajmy się": online (zalecane) + harmonogram stacjonarny --}}
            @php
                $schedule = old('contact_schedule', $settings->contact_schedule ?: []);
                if (empty($schedule)) {
                    $schedule = [['type' => 'date', 'date' => '', 'weekday' => 1, 'time' => '', 'where' => '', 'note' => '']];
                }
                $weekdayOptions = [1 => 'Poniedziałek', 2 => 'Wtorek', 3 => 'Środa', 4 => 'Czwartek', 5 => 'Piątek', 6 => 'Sobota', 7 => 'Niedziela'];
            @endphp
            <div class="mt-8 space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-5">
                <div>
                    <p class="text-sm font-bold text-ink">Spotkajmy się (online i stacjonarnie)</p>
                    <p class="mt-0.5 text-xs text-muted">Sekcja na podstronie <a href="{{ route('contact.show') }}" target="_blank" rel="noopener" class="text-brand underline">/kontakt</a>: spotkanie online (opcja zalecana, z linkiem do rezerwacji terminu) oraz harmonogram stacjonarny (kiedy i gdzie jesteśmy, np. w Krakowie). Puste pola = dana część sekcji się nie pokaże.</p>
                </div>

                <div>
                    <label for="contact_meeting_title" class="mb-1 block text-sm font-bold">Tytuł sekcji</label>
                    <input type="text" id="contact_meeting_title" name="contact_meeting_title" value="{{ old('contact_meeting_title', $settings->contact_meeting_title) }}"
                        placeholder="Spotkajmy się" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('contact_meeting_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="contact_remote_note" class="mb-1 block text-sm font-bold">Informacja „na co dzień działamy zdalnie" <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <input type="text" id="contact_remote_note" name="contact_remote_note" value="{{ old('contact_remote_note', $settings->contact_remote_note) }}"
                        placeholder="np. Na co dzień działamy zdalnie — dlatego najszybciej złapiesz nas online."
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Krótka ciekawostka/informacja nad opcjami spotkania. Zostaw puste, aby ukryć.</p>
                    @error('contact_remote_note') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="rounded border border-gray-200 bg-white p-4">
                    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-muted">Spotkanie online <span class="ml-1 rounded-full bg-brand-light px-2 py-0.5 text-[0.65rem] normal-case text-brand">opcja zalecana</span></p>
                    <div class="space-y-3">
                        <div>
                            <label for="contact_online_meeting_text" class="mb-1 block text-sm font-bold">Tekst zachęty <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <input type="text" id="contact_online_meeting_text" name="contact_online_meeting_text" value="{{ old('contact_online_meeting_text', $settings->contact_online_meeting_text) }}"
                                placeholder="np. Najwygodniej spotkać się online — wybierz dogodny termin."
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('contact_online_meeting_text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label for="contact_online_meeting_url" class="mb-1 block text-sm font-bold">Link do rezerwacji terminu</label>
                                <input type="text" id="contact_online_meeting_url" name="contact_online_meeting_url" value="{{ old('contact_online_meeting_url', $settings->contact_online_meeting_url) }}"
                                    placeholder="np. https://calendly.com/…"
                                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                <p class="mt-1 text-xs text-muted">Bez linku przycisk się nie pokaże.</p>
                                @error('contact_online_meeting_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="contact_online_meeting_label" class="mb-1 block text-sm font-bold">Tekst przycisku</label>
                                <input type="text" id="contact_online_meeting_label" name="contact_online_meeting_label" value="{{ old('contact_online_meeting_label', $settings->contact_online_meeting_label) }}"
                                    placeholder="Wybierz dogodny termin"
                                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                @error('contact_online_meeting_label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="contact_meeting_notify_email" class="mb-1 block text-sm font-bold">Adres do zgłoszeń „Daj znać, że przyjdziesz" <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <input type="email" id="contact_meeting_notify_email" name="contact_meeting_notify_email" value="{{ old('contact_meeting_notify_email', $settings->contact_meeting_notify_email) }}"
                        placeholder="{{ $settings->contact_email ?: 'np. kontakt@feer.org.pl' }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    <p class="mt-1 text-xs text-muted">Tu trafiają zgłoszenia z formularza oraz kopia (DW) powiadomień o zmianie terminu. Puste = adres kontaktowy z sekcji wyżej.</p>
                    @error('contact_meeting_notify_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="rounded border border-gray-200 bg-white p-4" x-data="{ items: {{ \Illuminate\Support\Js::from(array_values($schedule)) }}, scheduleOn: {{ old('contact_schedule_enabled', $settings->contact_schedule_enabled) ? 'true' : 'false' }} }">
                    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-muted">Harmonogram stacjonarny (kiedy i gdzie jesteśmy)</p>

                    <label class="mb-3 flex items-start gap-2">
                        <input type="checkbox" name="contact_schedule_enabled" value="1" x-model="scheduleOn"
                            class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                        <span>
                            <span class="text-sm font-bold">Umożliw wybór terminu spotkania</span>
                            <span class="block text-xs text-muted">Gdy wyłączone, na stronie zapisów zamiast harmonogramu pokażemy komunikat poniżej.</span>
                        </span>
                    </label>

                    <div class="mb-3" x-show="! scheduleOn" x-cloak>
                        <label for="contact_no_schedule_note" class="mb-1 block text-sm font-bold">Komunikat, gdy brak terminów</label>
                        <input type="text" id="contact_no_schedule_note" name="contact_no_schedule_note" value="{{ old('contact_no_schedule_note', $settings->contact_no_schedule_note) }}"
                            placeholder="Jeszcze nie ustaliliśmy żadnych terminów."
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('contact_no_schedule_note') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-3" x-show="scheduleOn" x-cloak>
                        <label for="contact_schedule_title" class="mb-1 block text-sm font-bold">Tytuł harmonogramu</label>
                        <input type="text" id="contact_schedule_title" name="contact_schedule_title" value="{{ old('contact_schedule_title', $settings->contact_schedule_title) }}"
                            placeholder="Kiedy i gdzie jesteśmy w Krakowie"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('contact_schedule_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <template x-for="(item, index) in items" :key="index">
                        <div class="mb-3 rounded border border-gray-200 p-3">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-bold uppercase tracking-wide text-muted" x-text="'Termin ' + (index + 1)"></p>
                                <button type="button" @click="items.splice(index, 1)"
                                    class="inline-flex items-center gap-1 text-xs font-bold text-red-600 hover:text-red-700">
                                    <i class="fa-solid fa-trash-can" aria-hidden="true"></i> Usuń
                                </button>
                            </div>

                            <input type="hidden" :name="'contact_schedule[' + index + '][type]'" x-model="item.type">

                            <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label :for="'contact_schedule_type_' + index" class="mb-1 block text-sm font-bold">Rodzaj</label>
                                    <select :id="'contact_schedule_type_' + index" x-model="item.type"
                                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                        <option value="date">Konkretna data</option>
                                        <option value="weekly">Co tydzień (dzień tygodnia)</option>
                                    </select>
                                </div>
                                <div x-show="item.type === 'date'">
                                    <label :for="'contact_schedule_date_' + index" class="mb-1 block text-sm font-bold">Data</label>
                                    <input type="date" :id="'contact_schedule_date_' + index"
                                        :name="'contact_schedule[' + index + '][date]'" x-model="item.date"
                                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                                <div x-show="item.type === 'weekly'" x-cloak>
                                    <label :for="'contact_schedule_weekday_' + index" class="mb-1 block text-sm font-bold">Dzień tygodnia</label>
                                    <select :id="'contact_schedule_weekday_' + index"
                                        :name="'contact_schedule[' + index + '][weekday]'" x-model="item.weekday"
                                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                        @foreach ($weekdayOptions as $num => $name)
                                            <option value="{{ $num }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label :for="'contact_schedule_time_' + index" class="mb-1 block text-sm font-bold">Godziny <span class="font-normal text-muted">(opcjonalnie)</span></label>
                                    <input type="text" :id="'contact_schedule_time_' + index"
                                        :name="'contact_schedule[' + index + '][time]'" x-model="item.time"
                                        placeholder="np. 10:00–14:00"
                                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                                <div>
                                    <label :for="'contact_schedule_where_' + index" class="mb-1 block text-sm font-bold">Gdzie</label>
                                    <input type="text" :id="'contact_schedule_where_' + index"
                                        :name="'contact_schedule[' + index + '][where]'" x-model="item.where"
                                        placeholder="np. Kraków, ul. Przykładowa 1"
                                        class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                            </div>
                            <div class="mt-2">
                                <label :for="'contact_schedule_note_' + index" class="mb-1 block text-sm font-bold">Dopisek <span class="font-normal text-muted">(opcjonalnie)</span></label>
                                <input type="text" :id="'contact_schedule_note_' + index"
                                    :name="'contact_schedule[' + index + '][note]'" x-model="item.note"
                                    placeholder="np. wejście od podwórza, zapisy mailowo"
                                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            </div>
                        </div>
                    </template>

                    <button type="button" @click="items.push({ type: 'date', date: '', weekday: 1, time: '', where: '', note: '' })"
                        class="inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand hover:text-white">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj termin
                    </button>
                    @error('contact_schedule') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-start gap-2 rounded-lg border border-gray-200 bg-white p-3 text-sm">
                    <input type="checkbox" name="notify_schedule_change" value="1" @checked(old('notify_schedule_change')) class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                    <span>
                        <span class="font-bold">Powiadom zapisanych o zmianie terminu</span>
                        <span class="block text-xs text-muted">Po zapisaniu ustawień wyśle e-mail z aktualnym harmonogramem do osób, które wypełniły „Daj znać, że przyjdziesz", z kopią (DW) na adres powyżej. Zaznacz tylko, gdy termin faktycznie się zmienił.</span>
                    </span>
                </label>
            </div>

            {{-- Box informacyjny pod danymi kontaktowymi (np. „zmiany w kontakcie") --}}
            <div class="mt-8 space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-5">
                <div>
                    <p class="text-sm font-bold text-ink">Box informacyjny pod danymi kontaktowymi</p>
                    <p class="mt-0.5 text-xs text-muted">Wyróżniony box z tekstem i opcjonalnym linkiem (jak przycisk CTA), pokazywany pod danymi kontaktowymi na podstronie <a href="{{ route('contact.show') }}" target="_blank" rel="noopener" class="text-brand underline">/kontakt</a> — np. gdy zmienia się adres lub godziny. Zostaw tekst pusty, aby ukryć box.</p>
                </div>

                <div>
                    <label for="contact_box_text" class="mb-1 block text-sm font-bold">Treść boxa</label>
                    <textarea id="contact_box_text" name="contact_box_text" rows="3"
                        placeholder="np. Od 1 września zmieniamy adres biura."
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('contact_box_text', $settings->contact_box_text) }}</textarea>
                    @error('contact_box_text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="contact_box_link_label" class="mb-1 block text-sm font-bold">Tekst linku <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input type="text" id="contact_box_link_label" name="contact_box_link_label" value="{{ old('contact_box_link_label', $settings->contact_box_link_label) }}"
                            placeholder="np. Zobacz szczegóły"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('contact_box_link_label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="contact_box_link_url" class="mb-1 block text-sm font-bold">Adres linku <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input type="text" id="contact_box_link_url" name="contact_box_link_url" value="{{ old('contact_box_link_url', $settings->contact_box_link_url) }}"
                            placeholder="np. /aktualnosci lub https://…"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('contact_box_link_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="contact_box_visible_from" class="mb-1 block text-sm font-bold">Pokaż od <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input type="datetime-local" id="contact_box_visible_from" name="contact_box_visible_from"
                            value="{{ old('contact_box_visible_from', $settings->contact_box_visible_from?->format('Y-m-d\TH:i')) }}"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        <p class="mt-1 text-xs text-muted">Pusto = box widoczny od razu.</p>
                        @error('contact_box_visible_from') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="contact_box_visible_until" class="mb-1 block text-sm font-bold">Ukryj po <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input type="datetime-local" id="contact_box_visible_until" name="contact_box_visible_until"
                            value="{{ old('contact_box_visible_until', $settings->contact_box_visible_until?->format('Y-m-d\TH:i')) }}"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        <p class="mt-1 text-xs text-muted">Pusto = box widoczny bezterminowo.</p>
                        @error('contact_box_visible_until') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
