{{-- ═══ KSIĄŻKA TELEFONICZNA ═══ --}}
@php
    $phoneBookFields = function (string $i, array $row = []) use ($inp) {
        $v = fn (string $k) => e($row[$k] ?? '');

        return '<div class="grid gap-2 md:grid-cols-2">'
            .'<input type="text" name="type_data[contacts]['.$i.'][name]" value="'.$v('name').'" placeholder="Imię i nazwisko" aria-label="Imię i nazwisko" class="'.$inp.' font-bold">'
            .'<input type="text" name="type_data[contacts]['.$i.'][role]" value="'.$v('role').'" placeholder="Stanowisko lub rola, np. koordynatorka szkoleń" aria-label="Stanowisko lub rola" class="'.$inp.'">'
            .'<input type="tel" name="type_data[contacts]['.$i.'][phone]" value="'.$v('phone').'" placeholder="Numer komórkowy, np. 601 350 487" aria-label="Numer komórkowy" inputmode="tel" class="'.$inp.'">'
            .'<input type="email" name="type_data[contacts]['.$i.'][email]" value="'.$v('email').'" placeholder="E-mail (opcjonalnie)" aria-label="E-mail" class="'.$inp.'">'
            .'</div>'
            .'<input type="text" name="type_data[contacts]['.$i.'][hours]" value="'.$v('hours').'" placeholder="Kiedy dzwonić (opcjonalnie), np. pon.–śr. 10:00–12:00" aria-label="Kiedy dzwonić" class="'.$inp.' mt-2">'
            .'<textarea name="type_data[contacts]['.$i.'][topic]" rows="2" placeholder="W jakiej sprawie się kontaktować, np. zapisy na szkolenia, faktury, media" aria-label="W jakiej sprawie się kontaktować" class="'.$inp.' mt-2">'.$v('topic').'</textarea>'
            .'<input type="text" name="type_data[contacts]['.$i.'][group]" value="'.$v('group').'" placeholder="Dział lub grupa (opcjonalnie), np. Biuro" aria-label="Dział lub grupa" class="'.$inp.' mt-2">';
    };
@endphp
<div data-type-fields="phone_book" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'phone_book' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Książka telefoniczna</p>

    <div>
        <label for="td-phonebook-lead" class="{{ $lbl }}">Lead (np. kiedy dzwonić, a kiedy pisać)</label>
        <textarea id="td-phonebook-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>

    <div class="{{ $box }}">
        <p class="text-xs font-bold uppercase text-muted">Ustawienia książki</p>
        <div>
            <label for="td-phonebook-sort" class="{{ $lbl }}">Kolejność osób</label>
            <select id="td-phonebook-sort" name="type_data[sort]" class="{{ $inp }}">
                <option value="manual" @selected(($td['sort'] ?? 'manual') !== 'alpha')>Jak w panelu (ręcznie)</option>
                <option value="alpha" @selected(($td['sort'] ?? '') === 'alpha')>Alfabetycznie (działy i osoby)</option>
            </select>
        </div>
        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="type_data[hide_numbers]" value="1" @checked(! empty($td['hide_numbers'])) class="mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
            <span><span class="font-bold">Ukryj numery za przyciskiem</span>
                <span class="block text-xs text-muted">Odwiedzający odsłania numer najechaniem, klawiaturą albo kliknięciem. To tylko ukrycie wizualne (utrudnia przypadkowe kopiowanie): numer nadal jest w kodzie strony, więc nie chroni przed botami.</span></span>
        </label>
    </div>

    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Osoby (kto, numer komórkowy, w jakiej sprawie; osoby z tego samego działu pokażą się razem)</p>
        <div data-td-rows class="space-y-3">
            @foreach ($tdRows('contacts') as $i => $row)
                <div data-td-row class="rounded-md bg-white p-3">
                    <div class="flex justify-end"><button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń osobę"><i class="fa-solid fa-trash" aria-hidden="true"></i></button></div>
                    {!! $phoneBookFields((string) $i, $row) !!}
                </div>
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj osobę</button>
        <template data-td-template>
            <div data-td-row class="rounded-md bg-white p-3">
                <div class="flex justify-end"><button type="button" data-td-remove class="{{ $rmBtn }}" aria-label="Usuń osobę"><i class="fa-solid fa-trash" aria-hidden="true"></i></button></div>
                {!! $phoneBookFields('__INDEX__') !!}
            </div>
        </template>
    </div>
</div>
