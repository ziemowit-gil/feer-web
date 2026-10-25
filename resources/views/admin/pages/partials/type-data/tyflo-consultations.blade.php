{{-- ═══ KONSULTACJE TYFLO ═══ --}}
@php
    $tcForms = \App\Models\SiteSetting::current()->isModuleEnabled('forms')
        ? \App\Models\FormDefinition::where('is_active', true)->orderBy('title')->get(['slug', 'title'])
        : collect();

    // Wiersz terminu harmonogramu: widoczne etykiety (label owija pole), bez placeholderów jako jedynych opisów.
    $slotRow = function (string|int $idx, array $row = []) use ($inp, $lbl, $rmBtn) {
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
            .$field('Data', 'date', 'date')
            .$field('Godzina od', 'time_from', 'time')
            .$field('Godzina do', 'time_to', 'time')
            .$select('Forma', 'mode', \App\Support\TyfloSchedule::MODES)
            .$field('Miejsce lub sposób połączenia', 'place', 'text', 'placeholder="np. Biuro FEER, ul. Długa 5, sala 2"')
            .$field('Konsultant', 'consultant', 'text')
            .$field('Liczba miejsc', 'seats', 'text', 'placeholder="np. 4 lub brak limitu"')
            .$select('Status', 'status', \App\Support\TyfloSchedule::STATUSES, 'open')
            .'</div>'
            .'<div class="mt-2 flex items-end gap-2">'
            .'<label class="block flex-1"><span class="'.$lbl.'">Uwaga do terminu (opcjonalnie)</span><input type="text" name="'.$name('note').'" value="'.e($row['note'] ?? '').'" class="'.$inp.'" placeholder="np. Prosimy o zgłoszenie z 2-dniowym wyprzedzeniem"></label>'
            .'<button type="button" data-td-remove class="'.$rmBtn.'" aria-label="Usuń termin"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>'
            .'</div></div>';
    };
@endphp
<div data-type-fields="tyflo_consultations" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'tyflo_consultations' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Konsultacje tyflo</p>

    <div>
        <label for="td-tc-lead" class="{{ $lbl }}">Lead (1–2 zdania: czym są konsultacje i dla kogo)</label>
        <textarea id="td-tc-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Dla kogo są konsultacje</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('audience') as $i => $row)
                {!! $recRow('audience', ['text' => ['label' => 'Grupa odbiorców', 'ph' => 'np. Osoby niewidome i słabowidzące']], 'Usuń grupę', $i, $row) !!}
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj grupę</button>
        <template data-td-template>{!! $recRow('audience', ['text' => ['label' => 'Grupa odbiorców', 'ph' => 'np. Osoby niewidome i słabowidzące']], 'Usuń grupę') !!}</template>
    </div>

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Konsultanci (tyflopedagodzy, instruktorzy)</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('consultants') as $i => $row)
                {!! $recRow('consultants', ['name' => ['label' => 'Imię i nazwisko', 'ph' => 'Imię i nazwisko'], 'role' => ['label' => 'Rola', 'ph' => 'np. tyflopedagog'], 'text' => ['label' => 'Opis', 'ph' => 'W czym pomaga'], 'email' => ['label' => 'E-mail', 'ph' => 'E-mail (opcjonalnie)']], 'Usuń konsultanta', $i, $row, 'grid gap-2 sm:grid-cols-[1.2fr_1.2fr_2fr_1.4fr_auto]') !!}
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj konsultanta</button>
        <template data-td-template>{!! $recRow('consultants', ['name' => ['label' => 'Imię i nazwisko', 'ph' => 'Imię i nazwisko'], 'role' => ['label' => 'Rola', 'ph' => 'np. tyflopedagog'], 'text' => ['label' => 'Opis', 'ph' => 'W czym pomaga'], 'email' => ['label' => 'E-mail', 'ph' => 'E-mail (opcjonalnie)']], 'Usuń konsultanta', null, [], 'grid gap-2 sm:grid-cols-[1.2fr_1.2fr_2fr_1.4fr_auto]') !!}</template>
    </div>

    <fieldset class="{{ $box }}">
        <legend class="text-xs font-bold uppercase text-muted">Jak się zgłosić</legend>
        <div>
            <label for="td-tc-form" class="{{ $lbl }}">Formularz zgłoszeniowy</label>
            <select id="td-tc-form" name="type_data[tc_form]" class="{{ $inp }}" aria-describedby="td-tc-form-help">
                <option value="">— bez formularza (telefon, e-mail lub przycisk) —</option>
                @foreach ($tcForms as $f)
                    <option value="{{ $f->slug }}" @selected(($td['tc_form'] ?? '') === $f->slug)>{{ $f->title }}</option>
                @endforeach
            </select>
            <p id="td-tc-form-help" class="mt-1 text-xs text-muted">Formularz z modułu Formularze — pojawia się pod harmonogramem, a przy terminach z wolnymi miejscami jest link „Zapisz się”.</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-3">
            <div><label for="td-tc-contact-name" class="{{ $lbl }}">Osoba do kontaktu</label><input type="text" id="td-tc-contact-name" name="type_data[contact_name]" value="{{ $td['contact_name'] ?? '' }}" class="{{ $inp }}"></div>
            <div><label for="td-tc-contact-phone" class="{{ $lbl }}">Telefon</label><input type="text" id="td-tc-contact-phone" name="type_data[contact_phone]" value="{{ $td['contact_phone'] ?? '' }}" class="{{ $inp }}"></div>
            <div><label for="td-tc-contact-email" class="{{ $lbl }}">E-mail</label><input type="email" id="td-tc-contact-email" name="type_data[contact_email]" value="{{ $td['contact_email'] ?? '' }}" class="{{ $inp }}">@error('type_data.contact_email') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror</div>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div><label for="td-tc-cta-label" class="{{ $lbl }}">Przycisk zgłoszenia — etykieta (opcjonalnie)</label><input type="text" id="td-tc-cta-label" name="type_data[cta_label]" value="{{ $td['cta_label'] ?? '' }}" placeholder="np. Zapisz się na konsultacje" class="{{ $inp }}"></div>
            <div><label for="td-tc-cta-url" class="{{ $lbl }}">Przycisk zgłoszenia — adres</label><input type="text" id="td-tc-cta-url" name="type_data[cta_url]" value="{{ $td['cta_url'] ?? '' }}" placeholder="pusty = przewija do formularza" class="{{ $inp }}"></div>
        </div>
        <div>
            <label for="td-tc-signup-note" class="{{ $lbl }}">Zasady zgłoszenia</label>
            <textarea id="td-tc-signup-note" name="type_data[tc_signup_note]" rows="2" placeholder="np. Zgłoś się co najmniej 2 dni przed terminem. Zgłoszenie możesz też przekazać telefonicznie." class="{{ $inp }}">{{ $td['tc_signup_note'] ?? '' }}</textarea>
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
            <label for="td-tc-schedule-note" class="{{ $lbl }}">Informacja nad harmonogramem (np. o zmianie terminów)</label>
            <textarea id="td-tc-schedule-note" name="type_data[tc_schedule_note]" rows="2" class="{{ $inp }}">{{ $td['tc_schedule_note'] ?? '' }}</textarea>
        </div>
    </fieldset>

    <div>
        <label for="td-tc-a11y" class="{{ $lbl }}">Dostępność konsultacji (dojazd, pies przewodnik, materiały w alternatywnych formatach, PJM)</label>
        <textarea id="td-tc-a11y" name="type_data[tc_accessibility]" rows="3" class="{{ $inp }}">{{ $td['tc_accessibility'] ?? '' }}</textarea>
    </div>

    <p class="text-xs text-muted">Treść główna strony (edytor powyżej) wyświetla się w sekcji „O konsultacjach”. Wersję łatwą do czytania (ETR) i film PJM dodajesz jak na innych stronach — dotyczą tej samej treści.</p>
</div>
