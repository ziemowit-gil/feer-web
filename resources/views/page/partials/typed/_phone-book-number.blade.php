{{--
    Numer osoby w książce telefonicznej: zwykły tekst (bez linku tel: i bez „Kopiuj numer" — książka służy do
    rozpoznania, kto dzwoni, a kontakt idzie przez numer ogólny), albo przycisk „Pokaż numer", gdy numery są ukryte,
    oraz przycisk „Zapisz w telefonie". Używany w kartach i w tabeli (typed/phone-book).
    Zmienne: $p (osoba), $hideNumbers, $link, $act (klasy), $vcard (closure), $wrap (klasy kontenera).
--}}
@php $who = $p['name'] ?: 'tej osoby'; @endphp
@if ($p['phone'] !== '')
    <div class="{{ $wrap ?? '' }} {{ $hideNumbers ? 'phone-reveal phone-reveal--inline' : '' }}" @if ($hideNumbers) data-phone-reveal @endif>
        @if ($hideNumbers)
            <button type="button" class="phone-reveal-btn" aria-expanded="false"><span>Pokaż numer: {{ $who }}</span></button>
        @endif
        <span class="{{ $hideNumbers ? 'phone-reveal-number' : 'text-xl font-bold text-ink' }}"><i class="fa-solid fa-phone mr-2" aria-hidden="true"></i><span class="sr-only">Numer telefonu {{ $who }}: </span>{{ $p['phone'] }}</span>
        <div class="phone-reveal-actions mt-2 flex flex-wrap gap-2 no-print">
            <button type="button" class="{{ $act }}" @click="save(@js($vcard($p)), @js(\Illuminate\Support\Str::slug($p['name'] ?: $p['phone']).'.vcf'))"><i class="fa-solid fa-address-card" aria-hidden="true"></i> Zapisz w telefonie<span class="sr-only"> ({{ $who }})</span></button>
        </div>
    </div>
@endif
