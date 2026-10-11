{{-- ═══ SPRAWOZDANIA ROCZNE ═══ --}}
<div data-type-fields="reports" class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'reports' ? '' : 'hidden' }}">
    <p class="text-sm font-bold uppercase tracking-wide text-muted">Sprawozdania roczne</p>
    <p class="text-sm text-muted">Strona pokazuje tabelę sprawozdań merytorycznych i finansowych z modułu <strong>Sprawozdania</strong> (lata, pliki PDF, załączniki). Treść strony wyświetla się nad tabelą. Dodaj tę stronę do menu BIP, aby sprawozdania były dostępne z poziomu BIP.</p>
    <div>
        <label for="td-reports-lead" class="{{ $lbl }}">Lead (1–2 zdania pod tytułem)</label>
        <textarea id="td-reports-lead" name="type_data[lead]" rows="2" class="{{ $inp }}">{{ $td['lead'] ?? '' }}</textarea>
    </div>
</div>
