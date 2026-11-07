{{--
    Wskazówka na stronach kontaktu: numer telefonu (z ustawień → Kontakt), informacja, że często oddzwaniamy
    z numerów komórkowych, i link do podstrony typu „Książka telefoniczna” (jeśli jest opublikowana).
    Numer telefonu jest nad informacją i linkiem. $class — odstęp (domyślnie pod spodem). Płasko; tekst ink na szarym tle ≥ 14:1, cel dotyku ≥ 44 px.
--}}
@php
    $switchboard = trim((string) $siteSettings->contact_phone);
    // Link do książki: moduł „Książka telefoniczna” (gdy ma wpisy), a w drugiej kolejności opublikowana strona tego typu.
    $phoneBook = \App\Support\ContactSections::phoneBook($siteSettings);
    $phoneBookUrl = $phoneBook['url'] ?? null;
    $phoneBookTitle = $phoneBook['title'] ?? 'Książka telefoniczna';
    // Szablon FEER pokazuje dane kontaktowe jako zwykły tekst (bez linków tel:), więc numer telefonu też.
    $plainPhone = $siteSettings->contactLayoutValue() === 'feer';
    // Godziny wpisane po swojemu („czw-pt 10:00 - 16”): myślniki bez spacji i jednolity półpauz, np. „czw–pt 10:00–16”.
    $phoneHours = preg_replace('/\s*[-–—]\s*/u', '–', trim((string) $siteSettings->contact_phone_hours));
    // Szablon FEER ma link do książki w menu bocznym strony kontaktu, więc w bloku go nie powtarza.
    $showBookLink = $phoneBookUrl && ! $plainPhone;
    $pbLink = 'inline-flex min-h-11 items-center gap-2 font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp
@if ($switchboard !== '' || $phoneBookUrl)
    <div class="phone-book-note {{ $class ?? 'mb-6' }} rounded-md bg-gray-50 p-5 text-ink" role="note" aria-label="Numer kontaktowy i telefony pracowników">
        @if ($switchboard !== '')
            <p class="text-sm font-bold uppercase tracking-widest">Numer kontaktowy</p>
            @if ($plainPhone)
                <p class="mt-1 text-2xl font-bold"><span class="sr-only">Numer kontaktowy: </span>{{ $switchboard }}</p>
            @else
                <p class="mt-1"><a href="tel:{{ preg_replace('/[^0-9+]/', '', $switchboard) }}" class="{{ $pbLink }} text-2xl"><i class="fa-solid fa-phone" aria-hidden="true"></i><span class="sr-only">Zadzwoń na numer kontaktowy: </span>{{ $switchboard }}</a></p>
            @endif
        @endif
        @if ($switchboard !== '')
            <p class="mt-1">Informacje o naszej działalności.</p>
        @endif
        @if ($switchboard !== '' && $phoneHours !== '')
            <p class="mt-1"><span class="font-bold">Kiedy dzwonić:</span> {{ $phoneHours }}</p>
        @endif
        <p class="{{ $switchboard !== '' ? 'mt-3 ' : '' }}leading-relaxed">Pracujemy zdalnie, dlatego pracownicy dzwonią z własnych lub służbowych komórek.@if ($phoneBookUrl) Kto to był, sprawdzisz w książce telefonicznej.@endif @if ($switchboard !== '') Żeby coś u nas załatwić, dzwoń na numer kontaktowy.@endif</p>
        @if ($showBookLink)
            <p class="mt-1"><a href="{{ $phoneBookUrl }}" class="{{ $pbLink }}"><i class="fa-solid fa-address-book" aria-hidden="true"></i>{{ $phoneBookTitle }}: sprawdź, kto i w jakiej sprawie dzwoni</a></p>
        @endif
    </div>
@endif
