{{--
    Numer osoby w książce telefonicznej: link tel: (albo przycisk „Pokaż numer", gdy numery są ukryte) i przyciski
    „Kopiuj numer" / „Zapisz w telefonie". Używany w kartach i w tabeli (typed/phone-book).
    Zmienne: $p (osoba), $hideNumbers, $link, $act (klasy), $vcard (closure), $wrap (klasy kontenera).
--}}
@php $who = $p['name'] ?: 'tej osoby'; @endphp
@if ($p['phone'] !== '')
    <div class="{{ $wrap ?? '' }} {{ $hideNumbers ? 'phone-reveal phone-reveal--inline' : '' }}" @if ($hideNumbers) data-phone-reveal @endif>
        @if ($hideNumbers)
            <button type="button" class="phone-reveal-btn" aria-expanded="false"><span>Pokaż numer: {{ $who }}</span></button>
        @endif
        <a href="tel:{{ $p['tel'] }}" class="{{ $hideNumbers ? 'phone-reveal-number' : $link.' text-xl' }}"><i class="fa-solid fa-phone mr-2" aria-hidden="true"></i><span class="sr-only">Zadzwoń do {{ $who }}: </span>{{ $p['phone'] }}</a>
        <div class="phone-reveal-actions mt-2 flex flex-wrap gap-2 no-print">
            <button type="button" class="{{ $act }}" @click="copy(@js($p['phone']), @js($p['name']))"><i class="fa-regular fa-copy" aria-hidden="true"></i> Kopiuj numer<span class="sr-only"> ({{ $who }})</span></button>
            <button type="button" class="{{ $act }}" @click="save(@js($vcard($p)), @js(\Illuminate\Support\Str::slug($p['name'] ?: $p['phone']).'.vcf'))"><i class="fa-solid fa-address-card" aria-hidden="true"></i> Zapisz w telefonie<span class="sr-only"> ({{ $who }})</span></button>
        </div>
    </div>
@endif
