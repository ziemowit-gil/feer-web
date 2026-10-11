{{-- ═══ FORMULARZ (wstęp + formularz) ═══ --}}
@php
    $fpForms = \App\Models\SiteSetting::current()->isModuleEnabled('forms')
        ? \App\Models\FormDefinition::where('is_active', true)->orderBy('title')->get(['slug', 'title'])
        : collect();
    $fpReqFields = ['text' => ['label' => 'Rzecz do przygotowania', 'ph' => 'np. Numer KRS organizacji']];
@endphp
<div data-type-fields="form_page" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'form_page' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Formularz</p>
    <p class="text-sm text-muted">Wstęp to treść strony (edytor powyżej). Pod nim wyświetla się wybrany formularz, a obok — informacje z pól poniżej.</p>

    <div>
        <label for="td-fp-lead" class="{{ $lbl }}">Lead (1–2 zdania pod tytułem: czego dotyczy formularz)</label>
        <textarea id="td-fp-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>

    <fieldset class="{{ $box }}">
        <legend class="text-xs font-bold uppercase text-muted">Formularz</legend>
        <div>
            <label for="td-fp-form" class="{{ $lbl }}">Formularz na stronie</label>
            <select id="td-fp-form" name="type_data[fp_form]" class="{{ $inp }}" aria-describedby="td-fp-form-help">
                <option value="">— wybierz formularz —</option>
                @foreach ($fpForms as $f)
                    <option value="{{ $f->slug }}" @selected(($td['fp_form'] ?? '') === $f->slug)>{{ $f->title }}</option>
                @endforeach
            </select>
            <p id="td-fp-form-help" class="mt-1 text-xs text-muted">
                Formularz z modułu Formularze — pola, termin i limit zgłoszeń ustawiasz w nim.
                @if (Route::has('admin.formularze.create'))
                    <a href="{{ route('admin.formularze.create') }}" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline">Utwórz nowy formularz</a>
                @endif
            </p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div><label for="td-fp-heading" class="{{ $lbl }}">Nagłówek nad formularzem</label><input type="text" id="td-fp-heading" name="type_data[fp_heading]" value="{{ $td['fp_heading'] ?? '' }}" placeholder="pusty = nazwa formularza" class="{{ $inp }}"></div>
            <div><label for="td-fp-time" class="{{ $lbl }}">Czas wypełnienia</label><input type="text" id="td-fp-time" name="type_data[fp_time]" value="{{ $td['fp_time'] ?? '' }}" placeholder="np. ok. 5 minut" class="{{ $inp }}"></div>
        </div>
    </fieldset>

    {{-- Co przygotować --}}
    <div class="{{ $box }}" data-td-repeater>
        <p class="text-xs font-bold uppercase text-muted">Co przygotować przed wypełnieniem</p>
        <div data-td-rows class="space-y-2">
            @foreach ($tdRows('requirements') as $i => $row)
                {!! $recRow('requirements', $fpReqFields, 'Usuń pozycję', $i, $row) !!}
            @endforeach
        </div>
        <button type="button" data-td-add class="{{ $addBtn }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj pozycję</button>
        <template data-td-template>{!! $recRow('requirements', $fpReqFields, 'Usuń pozycję') !!}</template>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <label for="td-fp-after" class="{{ $lbl }}">Co dalej po wysłaniu</label>
            <textarea id="td-fp-after" name="type_data[fp_after]" rows="3" class="{{ $inp }}" placeholder="np. Odpowiemy w ciągu 5 dni roboczych.">{{ $td['fp_after'] ?? '' }}</textarea>
        </div>
        <div>
            <label for="td-fp-help" class="{{ $lbl }}">Pomoc w wypełnieniu (dostępność)</label>
            <textarea id="td-fp-help" name="type_data[fp_help]" rows="3" class="{{ $inp }}" placeholder="np. Możesz też zadzwonić lub przyjść do biura — wypełnimy formularz razem.">{{ $td['fp_help'] ?? '' }}</textarea>
        </div>
    </div>

    <fieldset class="{{ $box }}">
        <legend class="text-xs font-bold uppercase text-muted">Kontakt w sprawie formularza</legend>
        <div class="grid gap-3 sm:grid-cols-3">
            <div><label for="td-fp-cname" class="{{ $lbl }}">Osoba</label><input type="text" id="td-fp-cname" name="type_data[contact_name]" value="{{ $td['contact_name'] ?? '' }}" class="{{ $inp }}"></div>
            <div><label for="td-fp-cmail" class="{{ $lbl }}">E-mail</label><input type="email" id="td-fp-cmail" name="type_data[contact_email]" value="{{ $td['contact_email'] ?? '' }}" class="{{ $inp }}"></div>
            <div><label for="td-fp-cphone" class="{{ $lbl }}">Telefon</label><input type="text" id="td-fp-cphone" name="type_data[contact_phone]" value="{{ $td['contact_phone'] ?? '' }}" class="{{ $inp }}"></div>
        </div>
    </fieldset>
</div>
