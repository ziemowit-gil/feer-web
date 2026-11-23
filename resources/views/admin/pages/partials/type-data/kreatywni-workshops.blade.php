{{-- ═══ KALENDARZ WARSZTATÓW (szablon „kreatywni”) ═══ --}}
<div data-type-fields="kreatywni_workshops" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'kreatywni_workshops' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Kalendarz warsztatów</p>
    <p class="rounded-md bg-brand-light px-3 py-2 text-sm text-ink">
        <i class="fa-solid fa-calendar-check mr-1" aria-hidden="true"></i>
        Terminy dodajesz w
        @if (\Illuminate\Support\Facades\Route::has('admin.kreatywni-warsztaty.index'))
            <a href="{{ route('admin.kreatywni-warsztaty.index') }}" class="font-bold text-brand-dark underline underline-offset-2">Treści → Strona główna → Warsztaty – Kreatywni</a>
        @else
            Treści → Strona główna → Warsztaty – Kreatywni
        @endif
        (miejsce, data, liczba uczestników, opis, grafika). Strona pokazuje je od najbliższego, pogrupowane w miesiące.
    </p>
    <div>
        <label for="td-kw-lead" class="{{ $lbl }}">Wstęp (1–2 zdania nad kalendarzem)</label>
        <textarea id="td-kw-lead" name="type_data[lead]" rows="2" class="{{ $inp }}" placeholder="np. Zapraszamy na warsztaty otwarte. Liczba miejsc jest ograniczona — zapytaj o wolne miejsca.">{{ $td['lead'] ?? '' }}</textarea>
    </div>
    <label class="flex items-start gap-2 text-sm text-ink">
        <input type="checkbox" name="type_data[show_past]" value="1" @checked(! empty($td['show_past'])) class="mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
        <span><span class="font-bold">Pokaż minione warsztaty</span> <span class="block text-xs text-muted">Pod kalendarzem lista ostatnich 12 terminów, które już się odbyły.</span></span>
    </label>
</div>
