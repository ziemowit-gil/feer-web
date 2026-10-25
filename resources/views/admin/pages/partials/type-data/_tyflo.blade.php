{{-- ═══ KONSULTACJE / SZKOLENIA TYFLO ═══ --}}
{{-- Wspólna sekcja pól obu typów; zmienna $kind: 'consultations' | 'trainings'. Klucze type_data wspólne (consultants = osoby prowadzące). --}}
@php
    $trainings = $kind === 'trainings';
    $tyfloKey = $trainings ? 'tyflo_trainings' : 'tyflo_consultations';
    $tcForms = \App\Models\SiteSetting::current()->isModuleEnabled('forms')
        ? \App\Models\FormDefinition::where('is_active', true)->orderBy('title')->get(['slug', 'title'])
        : collect();

    // Wiersz terminu harmonogramu: widoczne etykiety (label owija pole), bez placeholderów jako jedynych opisów.
    $slotRow = function (string|int $idx, array $row = []) use ($inp, $lbl, $rmBtn, $trainings) {
        $name = fn (string $f) => 'type_data[slots]['.$idx.']['.$f.']';
        $field = fn (string $label, string $f, string $type = 'text', string $extra = '') => '<label class="block"><span class="'.$lbl.'">'.e($label).'</span><input type="'.$type.'" name="'.$name($f).'" value="'.e($row[$f] ?? '').'" class="'.$inp.'" '.$extra.'></label>';
        $select = function (string $label, string $f, array $options, string $default = '') use ($name, $inp, $lbl, $row) {
            $cur = $row[$f] ?? $default;
            $html = '<label class="block"><span class="'.$lbl.'">'.e($label).'</span><select name="'.$name($f).'" class="'.$inp.'">';
            if ($default === '') {
                $html .= '<option value="">—</option>';
            }
            foreach ($options as $value => $text) {
                $html .= '<option value="'.e($value).'"'.($cur === $value ? ' selected' : '').'>'.e($text).'</option>';
            }

            return $html.'</select></label>';
        };

        return '<div data-td-row class="rounded-md bg-white p-3">'
            .'<div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">'
            .$field($trainings ? 'Data rozpoczęcia' : 'Data', 'date', 'date')
            .($trainings ? $field('Data zakończenia (szkolenie wielodniowe)', 'date_to', 'date') : '')
            .$field('Godzina od', 'time_from', 'time')
            .$field('Godzina do', 'time_to', 'time')
            .$select('Forma', 'mode', \App\Support\TyfloSchedule::MODES)
            .$field('Miejsce lub sposób połączenia', 'place', 'text', 'placeholder="np. Biuro FEER, ul. Długa 5, sala 2"')
            .$field($trainings ? 'Prowadzący' : 'Konsultant', 'consultant', 'text')
            .$field('Liczba miejsc', 'seats', 'text', 'placeholder="np. 4 lub brak limitu"')
            .$select('Status', 'status', \App\Support\TyfloSchedule::STATUSES, 'open')
            .'</div>'
            .'<div class="mt-2 flex items-end gap-2">'
            .'<label class="block flex-1"><span class="'.$lbl.'">Uwaga do terminu (opcjonalnie)</span><input type="text" name="'.$name('note').'" value="'.e($row['note'] ?? '').'" class="'.$inp.'" placeholder="np. Prosimy o zgłoszenie z 2-dniowym wyprzedzeniem"></label>'
            .'<button type="button" data-td-remove class="'.$rmBtn.'" aria-label="Usuń termin"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>'
            .'</div></div>';
    };
@endphp
<div data-type-fields="{{ $tyfloKey }}" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === $tyfloKey ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">{{ $trainings ? 'Szkolenia tyflo' : 'Konsultacje tyflo' }}</p>

    <div>
        <label for="td-tc-lead-{{ $kind }}" class="{{ $lbl }}">Lead (1–2 zdania: {{ $trainings ? 'czego dotyczy szkolenie i dla kogo' : 'czym są konsultacje i dla kogo' }})</label>
        <textarea id="td-tc-lead-{{ $kind }}" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">{{ $trainings ? 'Dla kogo jest szkolenie' : 'Dla kogo są konsultacje' }}</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('audience') as $i => $row)
                {!! $recRow('audience', ['text' => ['label' => 'Grupa odbiorców', 'ph' => 'np. Osoby niewidome i słabowidzące']], 'Usuń grupę', $i, $row) !!}
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj grupę</button>
        <template data-td-template>{!! $recRow('audience', ['text' => ['label' => 'Grupa odbiorców', 'ph' => 'np. Osoby niewidome i słabowidzące']], 'Usuń grupę') !!}</template>
    </div>

    @if ($trainings)
        <fieldset class="{{ $box }}">
            <legend class="text-xs font-bold uppercase text-muted">Najważniejsze informacje o szkoleniu</legend>
            <div class="grid gap-3 sm:grid-cols-2">
                <div><label for="td-tt-duration" class="{{ $lbl }}">Czas trwania</label><input type="text" id="td-tt-duration" name="type_data[tt_duration]" value="{{ $td['tt_duration'] ?? '' }}" placeholder="np. 16 godzin, 2 dni" class="{{ $inp }}"></div>
                <div><label for="td-tt-cost" class="{{ $lbl }}">Koszt</label><input type="text" id="td-tt-cost" name="type_data[tt_cost]" value="{{ $td['tt_cost'] ?? '' }}" placeholder="np. Bezpłatnie" class="{{ $inp }}"></div>
                <div><label for="td-tt-group" class="{{ $lbl }}">Wielkość grupy</label><input type="text" id="td-tt-group" name="type_data[tt_group]" value="{{ $td['tt_group'] ?? '' }}" placeholder="np. do 6 osób" class="{{ $inp }}"></div>
                <div><label for="td-tt-certificate" class="{{ $lbl }}">Po szkoleniu (certyfikat, zaświadczenie)</label><input type="text" id="td-tt-certificate" name="type_data[tt_certificate]" value="{{ $td['tt_certificate'] ?? '' }}" placeholder="np. Zaświadczenie o ukończeniu" class="{{ $inp }}"></div>
            </div>
        </fieldset>

        <div class="{{ $box }}" data-td-repeater>
            <p class="text-xs font-bold uppercase text-muted">Program szkolenia (moduły, w kolejności)</p>
            <div data-td-rows class="space-y-2">
                @foreach ($tdRows('tt_program') as $i => $row)
                    {!! $recRow('tt_program', ['title' => ['label' => 'Moduł', 'ph' => 'np. Obsługa czytnika ekranu NVDA'], 'text' => ['label' => 'Opis modułu', 'ph' => 'Czego uczestnicy się nauczą']], 'Usuń moduł', $i, $row, 'grid gap-2 sm:grid-cols-[1fr_2fr_auto]') !!}
                @endforeach
            </div>
            <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj moduł</button>
            <template data-td-template>{!! $recRow('tt_program', ['title' => ['label' => 'Moduł', 'ph' => 'np. Obsługa czytnika ekranu NVDA'], 'text' => ['label' => 'Opis modułu', 'ph' => 'Czego uczestnicy się nauczą']], 'Usuń moduł', null, [], 'grid gap-2 sm:grid-cols-[1fr_2fr_auto]') !!}</template>
        </div>

        <div class="{{ $box }}" data-td-repeater>
            <p class="text-xs font-bold uppercase text-muted">Wymagania i co przygotować</p>
            <div data-td-rows class="space-y-2">
                @foreach ($tdRows('requirements') as $i => $row)
                    {!! $recRow('requirements', ['text' => ['label' => 'Wymaganie', 'ph' => 'np. Własny laptop z czytnikiem ekranu']], 'Usuń wymaganie', $i, $row) !!}
                @endforeach
            </div>
            <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj wymaganie</button>
            <template data-td-template>{!! $recRow('requirements', ['text' => ['label' => 'Wymaganie', 'ph' => 'np. Własny laptop z czytnikiem ekranu']], 'Usuń wymaganie') !!}</template>
        </div>
    @endif

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">{{ $trainings ? 'Prowadzący (tyflopedagodzy, trenerzy, instruktorzy)' : 'Konsultanci (tyflopedagodzy, instruktorzy)' }}</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('consultants') as $i => $row)
                {!! $recRow('consultants', ['name' => ['label' => 'Imię i nazwisko', 'ph' => 'Imię i nazwisko'], 'role' => ['label' => 'Rola', 'ph' => 'np. tyflopedagog'], 'text' => ['label' => 'Opis', 'ph' => 'W czym pomaga'], 'email' => ['label' => 'E-mail', 'ph' => 'E-mail (opcjonalnie)']], ($trainings ? 'Usuń prowadzącego' : 'Usuń konsultanta'), $i, $row, 'grid gap-2 sm:grid-cols-[1.2fr_1.2fr_2fr_1.4fr_auto]') !!}
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> {{ $trainings ? 'Dodaj prowadzącego' : 'Dodaj konsultanta' }}</button>
        <template data-td-template>{!! $recRow('consultants', ['name' => ['label' => 'Imię i nazwisko', 'ph' => 'Imię i nazwisko'], 'role' => ['label' => 'Rola', 'ph' => 'np. tyflopedagog'], 'text' => ['label' => 'Opis', 'ph' => 'W czym pomaga'], 'email' => ['label' => 'E-mail', 'ph' => 'E-mail (opcjonalnie)']], ($trainings ? 'Usuń prowadzącego' : 'Usuń konsultanta'), null, [], 'grid gap-2 sm:grid-cols-[1.2fr_1.2fr_2fr_1.4fr_auto]') !!}</template>
    </div>

    <fieldset class="{{ $box }}">
        <legend class="text-xs font-bold uppercase text-muted">Jak się zgłosić</legend>
        <div>
            <label for="td-tc-form-{{ $kind }}" class="{{ $lbl }}">Formularz zgłoszeniowy</label>
            <select id="td-tc-form-{{ $kind }}" name="type_data[tc_form]" class="{{ $inp }}" aria-describedby="td-tc-form-help-{{ $kind }}">
                <option value="">— bez formularza (telefon, e-mail lub przycisk) —</option>
                @foreach ($tcForms as $f)
                    <option value="{{ $f->slug }}" @selected(($td['tc_form'] ?? '') === $f->slug)>{{ $f->title }}</option>
                @endforeach
            </select>
            <p id="td-tc-form-help-{{ $kind }}" class="mt-1 text-xs text-muted">Formularz z modułu Formularze — pojawia się pod harmonogramem, a przy terminach z wolnymi miejscami jest link „Zapisz się”.</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-3">
            <div><label for="td-tc-contact-name-{{ $kind }}" class="{{ $lbl }}">Osoba do kontaktu</label><input type="text" id="td-tc-contact-name-{{ $kind }}" name="type_data[contact_name]" value="{{ $td['contact_name'] ?? '' }}" class="{{ $inp }}"></div>
            <div><label for="td-tc-contact-phone-{{ $kind }}" class="{{ $lbl }}">Telefon</label><input type="text" id="td-tc-contact-phone-{{ $kind }}" name="type_data[contact_phone]" value="{{ $td['contact_phone'] ?? '' }}" class="{{ $inp }}"></div>
            <div><label for="td-tc-contact-email-{{ $kind }}" class="{{ $lbl }}">E-mail</label><input type="email" id="td-tc-contact-email-{{ $kind }}" name="type_data[contact_email]" value="{{ $td['contact_email'] ?? '' }}" class="{{ $inp }}">@error('type_data.contact_email') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror</div>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div><label for="td-tc-cta-label-{{ $kind }}" class="{{ $lbl }}">Przycisk zgłoszenia — etykieta (opcjonalnie)</label><input type="text" id="td-tc-cta-label-{{ $kind }}" name="type_data[cta_label]" value="{{ $td['cta_label'] ?? '' }}" placeholder="{{ $trainings ? 'np. Zapisz się na szkolenie' : 'np. Zapisz się na konsultacje' }}" class="{{ $inp }}"></div>
            <div><label for="td-tc-cta-url-{{ $kind }}" class="{{ $lbl }}">Przycisk zgłoszenia — adres</label><input type="text" id="td-tc-cta-url-{{ $kind }}" name="type_data[cta_url]" value="{{ $td['cta_url'] ?? '' }}" placeholder="pusty = przewija do formularza" class="{{ $inp }}"></div>
        </div>
        <div>
            <label for="td-tc-signup-note-{{ $kind }}" class="{{ $lbl }}">Zasady zgłoszenia</label>
            <textarea id="td-tc-signup-note-{{ $kind }}" name="type_data[tc_signup_note]" rows="2" placeholder="np. Zgłoś się co najmniej 2 dni przed terminem. Zgłoszenie możesz też przekazać telefonicznie." class="{{ $inp }}">{{ $td['tc_signup_note'] ?? '' }}</textarea>
        </div>
    </fieldset>

    <fieldset class="{{ $box }}">
        <legend class="text-xs font-bold uppercase text-muted">Harmonogram terminów</legend>
        <p class="text-xs text-muted">Na stronie terminy są sortowane po dacie; zakończone trafiają do zwijanej listy. Status i forma są wyświetlane tekstem, nie samym kolorem.</p>
        <div class="space-y-3" data-td-repeater>
            <div data-td-rows class="space-y-3">
                @foreach ($tdRows('slots') as $i => $row)
                    {!! $slotRow($i, $row) !!}
                @endforeach
            </div>
            <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj termin</button>
            <template data-td-template>{!! $slotRow('__INDEX__') !!}</template>
        </div>
        @php $slotErrors = collect($errors->getMessages())->filter(fn ($m, $k) => str_starts_with($k, 'type_data.slots'))->flatten()->unique(); @endphp
        @if ($slotErrors->isNotEmpty())
            <p class="text-sm font-bold text-red-700" role="alert">Popraw terminy w harmonogramie: {{ $slotErrors->implode(' ') }}</p>
        @endif
        <div>
            <label for="td-tc-schedule-note-{{ $kind }}" class="{{ $lbl }}">Informacja nad harmonogramem (np. o zmianie terminów)</label>
            <textarea id="td-tc-schedule-note-{{ $kind }}" name="type_data[tc_schedule_note]" rows="2" class="{{ $inp }}">{{ $td['tc_schedule_note'] ?? '' }}</textarea>
        </div>
    </fieldset>

    <div>
        <label for="td-tc-a11y-{{ $kind }}" class="{{ $lbl }}">{{ $trainings ? 'Dostępność szkolenia' : 'Dostępność konsultacji' }} (dojazd, pies przewodnik, materiały w alternatywnych formatach, PJM)</label>
        <textarea id="td-tc-a11y-{{ $kind }}" name="type_data[tc_accessibility]" rows="3" class="{{ $inp }}">{{ $td['tc_accessibility'] ?? '' }}</textarea>
    </div>

    <p class="text-xs text-muted">Treść główna strony (edytor powyżej) wyświetla się w sekcji „{{ $trainings ? 'O szkoleniu' : 'O konsultacjach' }}”. Wersję łatwą do czytania (ETR) i film PJM dodajesz jak na innych stronach — dotyczą tej samej treści.</p>
</div>
