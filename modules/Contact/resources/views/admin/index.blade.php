{{--
    Komunikacja → Kontakt (moduł Kontakt): stan strony kontaktu, formularz kontaktowy, podstrony w menu bocznym,
    „Gdzie działamy”, dojazd i dostępność, komunikat o zmianie godzin i wizytówka vCard. Jeden formularz, jeden zapis.
    Miejsca „Gdzie działamy” są renderowane po stronie serwera (bez JS): istniejące + dwa puste wiersze; pusta nazwa = usunięcie.
    Dostępność: etykiety przy każdym polu, fieldset/legend dla grup, opisy pól przez aria-describedby, cele ≥ 44 px.
--}}
@extends('admin.layout')

@section('title', 'Kontakt')

@section('content')
    @php
        $inp = 'w-full rounded-md border-gray-300 text-sm focus:border-brand-dark focus:ring-brand-dark';
        $lbl = 'mb-1 block text-sm font-bold text-ink';
        $hint = 'mt-1 text-xs text-muted';
        $card = 'rounded-lg bg-white p-5 sm:p-6';
        $h2 = 'text-base font-bold text-ink';
        $chk = 'mt-0.5 size-5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark';
        $link = 'inline-flex min-h-9 items-center gap-1.5 font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
        $o = old('options', $options);
        $shown = old('options.shown_sections', array_values(array_diff(\Modules\Contact\Support\ContactSections::SLUGS, $options['hidden_sections'] ?? [])));
        $places = array_values(old('options.places', $options['places'] ?? []));
        $places = array_merge($places, array_fill(0, 2, []));
        $fs = $form?->settings ?? [];
        $fv = fn (string $k, $d = '') => old('form.'.$k, $fs[$k] ?? $d);
    @endphp

    <div class="mb-6">
        <h1 class="text-lg font-bold text-ink">Kontakt</h1>
        <p class="max-w-3xl text-sm text-muted">Moduł Kontakt: strona kontaktu, formularz, podstrony w menu bocznym i dodatki. Adresy, telefon, rachunki i spotkania uzupełniasz w <a href="{{ route('admin.ustawienia.edit', ['tab' => 'contact']) }}" class="font-bold text-brand-dark underline">Ustawienia → Kontakt</a>.</p>
    </div>

    @unless ($optionsColumn)
        <div class="mb-5 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-950" role="status">Baza nie ma jeszcze pola na opcje modułu — uruchom migracje (<code>php artisan migrate</code>), a potem zapisz opcje.</div>
    @endunless

    {{-- Stan: strona, formularz, książka --}}
    <div class="mb-6 grid gap-4 lg:grid-cols-3">
        <section class="{{ $card }}" aria-labelledby="kt-page-h">
            <h2 id="kt-page-h" class="{{ $h2 }}"><i class="fa-solid fa-envelope mr-1.5 text-brand-dark" aria-hidden="true"></i>Strona kontaktu</h2>
            @if ($contactPage)
                <p class="mt-2 text-sm text-ink">{{ $contactPage->title }} — {{ $contactPage->is_published ? 'opublikowana' : 'szkic' }}.</p>
                <p class="mt-2 flex flex-wrap gap-x-5 text-sm">
                    <a href="{{ route('admin.podstrony.edit', $contactPage) }}" class="{{ $link }}">Edytuj stronę</a>
                    <a href="{{ $contactPage->publicUrl() }}" target="_blank" rel="noopener" class="{{ $link }}">Zobacz<span class="sr-only"> stronę kontaktu (nowa karta)</span></a>
                </p>
            @else
                <p class="mt-2 text-sm text-ink">Strona /kontakt działa, ale nie ma jeszcze strony typu „Kontakt” do edycji w drzewie stron.</p>
                <p class="mt-2 text-sm"><a href="{{ route('admin.podstrony.create', ['type' => 'contact']) }}" class="{{ $link }}"><i class="fa-solid fa-plus" aria-hidden="true"></i>Utwórz stronę Kontakt</a></p>
            @endif
        </section>

        <section class="{{ $card }}" aria-labelledby="kt-inbox-h">
            <h2 id="kt-inbox-h" class="{{ $h2 }}"><i class="fa-solid fa-inbox mr-1.5 text-brand-dark" aria-hidden="true"></i>Wiadomości</h2>
            @if ($form)
                <p class="mt-2 text-sm text-ink">Nieprzeczytane z formularza: <strong>{{ $formUnread }}</strong>.</p>
                <p class="mt-2 flex flex-wrap gap-x-5 text-sm">
                    <a href="{{ route('admin.formularze.zgloszenia', $form) }}" class="{{ $link }}">Zgłoszenia</a>
                    <a href="{{ route('admin.formularze.edit', $form) }}" class="{{ $link }}">Pola formularza</a>
                </p>
            @else
                <p class="mt-2 text-sm text-ink">Formularz działa w wersji wbudowanej (moduł „Kreator formularzy” jest wyłączony). Nieprzeczytane: <strong>{{ $legacyUnread }}</strong>.</p>
            @endif
            @if ($legacyUnread > 0 && $form)
                <p class="mt-1 text-xs text-muted">W archiwum dawnego formularza są jeszcze nieprzeczytane wiadomości: {{ $legacyUnread }}.</p>
            @endif
            <p class="mt-2 text-xs text-muted">Ochrona przed spamem: wbudowana (pułapka, czas wypełnienia, zadanie){{ $cleanTalk ? ' i CleanTalk' : '' }}.</p>
        </section>

        <section class="{{ $card }}" aria-labelledby="kt-book-h">
            <h2 id="kt-book-h" class="{{ $h2 }}"><i class="fa-solid fa-address-book mr-1.5 text-brand-dark" aria-hidden="true"></i>Książka telefoniczna</h2>
            <p class="mt-2 text-sm text-ink">Osoby: <strong>{{ $bookCount }}</strong>.{{ $bookPage ? ' Strona: '.$bookPage->title.'.' : '' }}</p>
            <p class="mt-2 flex flex-wrap gap-x-5 text-sm">
                @if ($settings->isModuleEnabled('phone_book') && \Illuminate\Support\Facades\Route::has('admin.ksiazka-telefoniczna.index'))
                    <a href="{{ route('admin.ksiazka-telefoniczna.index') }}" class="{{ $link }}">Osoby i numery</a>
                @endif
                @if ($bookPage)
                    <a href="{{ route('admin.podstrony.edit', $bookPage) }}" class="{{ $link }}">Edytuj stronę książki</a>
                @elseif ($contactPage)
                    <a href="{{ route('admin.podstrony.create', ['type' => 'contact_book', 'parent_id' => $contactPage->id]) }}" class="{{ $link }}"><i class="fa-solid fa-plus" aria-hidden="true"></i>Dodaj jako podstronę kontaktu</a>
                @endif
            </p>
        </section>
    </div>

    <form method="POST" action="{{ route('admin.kontakt.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Formularz kontaktowy --}}
        <section class="{{ $card }} space-y-5" aria-labelledby="kt-form-h">
            <div>
                <h2 id="kt-form-h" class="{{ $h2 }}">Formularz kontaktowy</h2>
                <p class="{{ $hint }}">Pola formularza (imię, temat, „Do kogo piszesz”, zgoda…) zmieniasz w kreatorze{{ $form ? '' : ' (po włączeniu modułu „Kreator formularzy”)' }}. Tu są najczęściej zmieniane ustawienia.</p>
            </div>

            <label class="flex items-start gap-3 text-sm">
                <input type="hidden" name="options[form_enabled]" value="0">
                <input type="checkbox" name="options[form_enabled]" value="1" class="{{ $chk }}" @checked(filter_var($o['form_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN))>
                <span><span class="font-bold text-ink">Pokazuj formularz na stronie kontaktu</span>
                    <span class="block text-xs text-muted">Wyłączony: zamiast formularza jest zdanie z adresem e-mail i telefonem.</span></span>
            </label>

            <div>
                <label for="kt-response" class="{{ $lbl }}">Czas odpowiedzi (zdanie pod „Napisz do nas”)</label>
                <input id="kt-response" type="text" name="options[response_time]" maxlength="200" value="{{ $o['response_time'] ?? '' }}" class="{{ $inp }}" aria-describedby="kt-response-hint">
                <p id="kt-response-hint" class="{{ $hint }}">Puste pole = bez zdania.</p>
            </div>

            @if ($form)
                <input type="hidden" name="form[present]" value="1">
                <label class="flex items-start gap-3 text-sm">
                    <input type="hidden" name="form[is_active]" value="0">
                    <input type="checkbox" name="form[is_active]" value="1" class="{{ $chk }}" @checked(filter_var(old('form.is_active', $form->is_active), FILTER_VALIDATE_BOOLEAN))>
                    <span><span class="font-bold text-ink">Formularz przyjmuje wiadomości</span>
                        <span class="block text-xs text-muted">Ustawienie z kreatora formularzy (Formularze → Kontakt).</span></span>
                </label>
                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="kt-notify" class="{{ $lbl }}">Wiadomości trafiają na adres</label>
                        <input id="kt-notify" type="text" name="form[notification_email]" value="{{ $fv('notification_email') }}" maxlength="500" class="{{ $inp }}" aria-describedby="kt-notify-hint" autocomplete="off">
                        <p id="kt-notify-hint" class="{{ $hint }}">Kilka adresów oddziel przecinkami. Wybór osoby w polu „Do kogo piszesz” kieruje wiadomość do niej.</p>
                    </div>
                    <div>
                        <label for="kt-cc" class="{{ $lbl }}">Kopia (DW)</label>
                        <input id="kt-cc" type="text" name="form[notification_cc]" value="{{ $fv('notification_cc') }}" maxlength="500" class="{{ $inp }}" autocomplete="off">
                    </div>
                    <div>
                        <label for="kt-subject" class="{{ $lbl }}">Temat powiadomienia</label>
                        <input id="kt-subject" type="text" name="form[notification_subject]" value="{{ $fv('notification_subject') }}" maxlength="200" class="{{ $inp }}">
                    </div>
                    <div>
                        <label for="kt-submit" class="{{ $lbl }}">Napis na przycisku</label>
                        <input id="kt-submit" type="text" name="form[submit_label]" value="{{ $fv('submit_label') }}" maxlength="60" class="{{ $inp }}" placeholder="Wyślij wiadomość">
                    </div>
                </div>
                <div>
                    <label for="kt-confirm" class="{{ $lbl }}">Komunikat po wysłaniu</label>
                    <textarea id="kt-confirm" name="form[confirmation_message]" rows="2" maxlength="1000" class="{{ $inp }}">{{ $fv('confirmation_message') }}</textarea>
                </div>
                <fieldset class="space-y-3">
                    <legend class="{{ $lbl }}">Odpowiedź i kopia</legend>
                    <label class="flex items-start gap-3 text-sm">
                        <input type="hidden" name="form[reply_to_submitter]" value="0">
                        <input type="checkbox" name="form[reply_to_submitter]" value="1" class="{{ $chk }}" @checked(filter_var($fv('reply_to_submitter', true), FILTER_VALIDATE_BOOLEAN))>
                        <span><span class="font-bold text-ink">„Odpowiedz” w poczcie trafia do nadawcy</span>
                            <span class="block text-xs text-muted">Odpowiadasz wprost z powiadomienia, bez przepisywania adresu.</span></span>
                    </label>
                    <label class="flex items-start gap-3 text-sm">
                        <input type="hidden" name="form[send_copy_to_submitter]" value="0">
                        <input type="checkbox" name="form[send_copy_to_submitter]" value="1" class="{{ $chk }}" @checked(filter_var($fv('send_copy_to_submitter', false), FILTER_VALIDATE_BOOLEAN))>
                        <span><span class="font-bold text-ink">Wyślij nadawcy potwierdzenie z kopią wiadomości</span>
                            <span class="block text-xs text-muted">Nadawca wie, że wiadomość doszła, i ma jej treść u siebie.</span></span>
                    </label>
                    <div>
                        <label for="kt-copy" class="{{ $lbl }}">Tekst potwierdzenia dla nadawcy</label>
                        <textarea id="kt-copy" name="form[submitter_copy_message]" rows="2" maxlength="1000" class="{{ $inp }}" placeholder="Dziękujemy za wiadomość. Odpowiemy zwykle w ciągu jednego dnia roboczego. Poniżej kopia.">{{ $fv('submitter_copy_message') }}</textarea>
                    </div>
                </fieldset>
            @endif
        </section>

        {{-- Podstrony w menu bocznym --}}
        <section class="{{ $card }}" aria-labelledby="kt-sections-h">
            <h2 id="kt-sections-h" class="{{ $h2 }}">Podstrony w menu bocznym kontaktu</h2>
            <p class="{{ $hint }}">Podstrona pokazuje się, gdy ma dane i jest zaznaczona. Odznacz, żeby ją ukryć (dane zostają, np. w stopce).</p>
            <fieldset class="mt-4">
                <legend class="sr-only">Podstrony pokazywane w menu</legend>
                <ul class="grid gap-x-6 gap-y-2 sm:grid-cols-2" role="list">
                    @foreach ($sectionLabels as $slug => $label)
                        @php $hasData = array_key_exists($slug, $sectionsWithData); @endphp
                        <li>
                            <label class="flex min-h-11 items-start gap-3 text-sm">
                                <input type="checkbox" name="options[shown_sections][]" value="{{ $slug }}" class="{{ $chk }}" @checked(in_array($slug, $shown, true))>
                                <span><span class="font-bold text-ink">{{ $label }}</span>
                                    <span class="block text-xs text-muted">/kontakt/{{ $slug }} · {{ $hasData ? 'ma dane' : 'brak danych — nie pokaże się' }}</span></span>
                            </label>
                        </li>
                    @endforeach
                </ul>
            </fieldset>
        </section>

        {{-- Gdzie działamy --}}
        <section class="{{ $card }} space-y-5" aria-labelledby="kt-places-h">
            <div>
                <h2 id="kt-places-h" class="{{ $h2 }}">Gdzie działamy</h2>
                <p class="{{ $hint }}">Miejsca z opisem, co tam robimy — podstrona /kontakt/gdzie-dzialamy w menu bocznym kontaktu. Pusta nazwa = miejsce zostanie usunięte. Po zapisie pojawią się kolejne puste wiersze.</p>
            </div>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="kt-places-title" class="{{ $lbl }}">Nazwa podstrony</label>
                    <input id="kt-places-title" type="text" name="options[places_title]" maxlength="80" value="{{ $o['places_title'] ?? \Modules\Contact\Support\ContactOptions::PLACES_TITLE_DEFAULT }}" class="{{ $inp }}">
                </div>
                <div>
                    <label for="kt-places-intro" class="{{ $lbl }}">Wstęp (opcjonalnie)</label>
                    <textarea id="kt-places-intro" name="options[places_intro]" rows="2" maxlength="1000" class="{{ $inp }}" placeholder="Np. Działamy głównie online, a spotkania i zajęcia prowadzimy w tych miejscach.">{{ $o['places_intro'] ?? '' }}</textarea>
                </div>
            </div>
            <ol class="space-y-4" role="list">
                @foreach ($places as $i => $place)
                    <li>
                        <fieldset class="space-y-3 rounded-md bg-gray-50 p-4">
                            <legend class="text-sm font-bold text-ink">{{ filled($place['name'] ?? null) ? 'Miejsce '.($i + 1).': '.$place['name'] : 'Nowe miejsce' }}</legend>
                            <div class="grid gap-3 md:grid-cols-2">
                                <div>
                                    <label for="kt-pl-{{ $i }}-name" class="{{ $lbl }}">Nazwa</label>
                                    <input id="kt-pl-{{ $i }}-name" type="text" name="options[places][{{ $i }}][name]" value="{{ $place['name'] ?? '' }}" maxlength="160" class="{{ $inp }}" placeholder="Np. Klub Cyfrowy w Nowym Sączu">
                                </div>
                                <div>
                                    <label for="kt-pl-{{ $i }}-area" class="{{ $lbl }}">Miejscowość lub obszar</label>
                                    <input id="kt-pl-{{ $i }}-area" type="text" name="options[places][{{ $i }}][area]" value="{{ $place['area'] ?? '' }}" maxlength="160" class="{{ $inp }}" placeholder="Np. Nowy Sącz, Małopolska albo online">
                                </div>
                            </div>
                            <div>
                                <label for="kt-pl-{{ $i }}-desc" class="{{ $lbl }}">Co tam robimy</label>
                                <textarea id="kt-pl-{{ $i }}-desc" name="options[places][{{ $i }}][description]" rows="3" maxlength="1500" class="{{ $inp }}" placeholder="Np. Zajęcia komputerowe dla osób 60+, we wtorki o 16:00.">{{ $place['description'] ?? '' }}</textarea>
                            </div>
                            <div class="grid gap-3 md:grid-cols-3">
                                <div>
                                    <label for="kt-pl-{{ $i }}-address" class="{{ $lbl }}">Adres (opcjonalnie)</label>
                                    <input id="kt-pl-{{ $i }}-address" type="text" name="options[places][{{ $i }}][address]" value="{{ $place['address'] ?? '' }}" maxlength="255" class="{{ $inp }}" placeholder="ul. Przykładowa 1, Nowy Sącz">
                                </div>
                                <div>
                                    <label for="kt-pl-{{ $i }}-url" class="{{ $lbl }}">Link (opcjonalnie)</label>
                                    <input id="kt-pl-{{ $i }}-url" type="text" name="options[places][{{ $i }}][url]" value="{{ $place['url'] ?? '' }}" maxlength="500" class="{{ $inp }}" placeholder="/dzialania/klub-cyfrowy albo https://…">
                                </div>
                                <div>
                                    <label for="kt-pl-{{ $i }}-urllabel" class="{{ $lbl }}">Napis linku</label>
                                    <input id="kt-pl-{{ $i }}-urllabel" type="text" name="options[places][{{ $i }}][url_label]" value="{{ $place['url_label'] ?? '' }}" maxlength="80" class="{{ $inp }}" placeholder="Więcej o tym miejscu">
                                </div>
                            </div>
                        </fieldset>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- Dojazd, dostępność, mapa, vCard --}}
        <section class="{{ $card }} space-y-5" aria-labelledby="kt-visit-h">
            <div>
                <h2 id="kt-visit-h" class="{{ $h2 }}">Dojazd i dostępność biura</h2>
                <p class="{{ $hint }}">Pokazuje się pod danymi kontaktowymi na stronie kontaktu (we wszystkich układach).</p>
            </div>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="kt-directions" class="{{ $lbl }}">Jak dojechać</label>
                    <textarea id="kt-directions" name="options[directions]" rows="3" maxlength="2000" class="{{ $inp }}" placeholder="Np. Tramwaje 4, 10, 52 — przystanek Dworzec Główny; parking od ul. Pawiej.">{{ $o['directions'] ?? '' }}</textarea>
                </div>
                <div>
                    <label for="kt-access" class="{{ $lbl }}">Dostępność biura</label>
                    <textarea id="kt-access" name="options[accessibility]" rows="3" maxlength="2000" class="{{ $inp }}" placeholder="Np. Wejście bez progów, winda, toaleta dostępna, pętla indukcyjna w sali spotkań.">{{ $o['accessibility'] ?? '' }}</textarea>
                </div>
            </div>
            <label class="flex items-start gap-3 text-sm">
                <input type="hidden" name="options[map_link]" value="0">
                <input type="checkbox" name="options[map_link]" value="1" class="{{ $chk }}" @checked(filter_var($o['map_link'] ?? false, FILTER_VALIDATE_BOOLEAN))>
                <span><span class="font-bold text-ink">Link „Pokaż na mapie” przy adresie</span>
                    <span class="block text-xs text-muted">OpenStreetMap w nowej karcie — bez osadzonej mapy i bez plików cookies na stronie.</span></span>
            </label>
            <label class="flex items-start gap-3 text-sm">
                <input type="hidden" name="options[vcard]" value="0">
                <input type="checkbox" name="options[vcard]" value="1" class="{{ $chk }}" @checked(filter_var($o['vcard'] ?? false, FILTER_VALIDATE_BOOLEAN))>
                <span><span class="font-bold text-ink">Przycisk „Zapisz kontakt w telefonie”</span>
                    <span class="block text-xs text-muted">Plik wizytówki (.vcf) z nazwą, numerem kontaktowym, e-mailem i adresem.</span></span>
            </label>
        </section>

        {{-- Komunikat o zmianie godzin --}}
        <section class="{{ $card }} space-y-5" aria-labelledby="kt-closure-h">
            <div>
                <h2 id="kt-closure-h" class="{{ $h2 }}">Komunikat o zmianie godzin lub nieobecności</h2>
                <p class="{{ $hint }}">Widoczny na stronie kontaktu tylko w podanych dniach (bez dat — dopóki ma treść). Np. „Biuro nieczynne między świętami. Na wiadomości odpowiemy od 2 stycznia.”</p>
            </div>
            <div>
                <label for="kt-closure" class="{{ $lbl }}">Treść komunikatu</label>
                <textarea id="kt-closure" name="options[closure_text]" rows="2" maxlength="500" class="{{ $inp }}">{{ $o['closure_text'] ?? '' }}</textarea>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="kt-closure-from" class="{{ $lbl }}">Od (włącznie)</label>
                    <input id="kt-closure-from" type="date" name="options[closure_from]" value="{{ $o['closure_from'] ?? '' }}" class="{{ $inp }}">
                </div>
                <div>
                    <label for="kt-closure-until" class="{{ $lbl }}">Do (włącznie)</label>
                    <input id="kt-closure-until" type="date" name="options[closure_until]" value="{{ $o['closure_until'] ?? '' }}" class="{{ $inp }}">
                </div>
            </div>
        </section>

        <div class="flex flex-wrap items-center gap-4">
            <button type="submit" class="min-h-11 rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Zapisz opcje kontaktu</button>
            <a href="{{ url('/kontakt') }}" target="_blank" rel="noopener" class="{{ $link }}">Zobacz stronę kontaktu<span class="sr-only"> (nowa karta)</span></a>
        </div>
    </form>
@endsection
