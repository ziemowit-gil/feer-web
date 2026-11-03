{{--
    Wskazówka na stronach kontaktu: numer telefonu (z ustawień → Kontakt), informacja, że często oddzwaniamy
    z numerów komórkowych, i link do podstrony typu „Książka telefoniczna” (jeśli jest opublikowana).
    Numer telefonu jest nad informacją i linkiem. $class — odstęp (domyślnie pod spodem). Płasko; tekst ink na szarym tle ≥ 14:1, cel dotyku ≥ 44 px.
--}}
@php
    $switchboard = trim((string) $siteSettings->contact_phone);
    // Link do książki: moduł „Książka telefoniczna” (gdy ma wpisy), a w drugiej kolejności opublikowana strona tego typu.
    $phoneBookUrl = null;
    $phoneBookTitle = 'Książka telefoniczna';
    if ($siteSettings->isModuleEnabled('phone_book') && \App\Models\PhoneBookEntry::forCurrentSite()->active()->exists()) {
        $phoneBookUrl = url('/'.\App\Http\Controllers\PhoneBookController::SLUG);
        $phoneBookTitle = filled($siteSettings->phonebook_title) ? $siteSettings->phonebook_title : $phoneBookTitle;
    } elseif ($phoneBookPage = \App\Models\Page::forCurrentSite()->where('type', 'phone_book')->where('is_published', true)->orderBy('id')->first()) {
        $phoneBookUrl = $phoneBookPage->publicUrl();
        $phoneBookTitle = $phoneBookPage->title;
    }
    // Szablon FEER pokazuje dane kontaktowe jako zwykły tekst (bez linków tel:), więc numer telefonu też.
    $plainPhone = $siteSettings->contactLayoutValue() === 'feer';
    // Godziny wpisane po swojemu („czw-pt 10:00 - 16”): myślniki bez spacji i jednolity półpauz, np. „czw–pt 10:00–16”.
    $phoneHours = preg_replace('/\s*[-–—]\s*/u', '–', trim((string) $siteSettings->contact_phone_hours));
    $pbLink = 'inline-flex min-h-11 items-center gap-2 font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp
@if ($switchboard !== '' || $phoneBookUrl)
    <div class="phone-book-note {{ $class ?? 'mb-6' }} rounded-md bg-gray-50 p-5 text-ink" role="note" aria-label="Numer ogólny i telefony pracowników">
        @if ($switchboard !== '')
            <p class="text-sm font-bold uppercase tracking-widest">Numer ogólny</p>
            @if ($plainPhone)
                <p class="mt-1 text-2xl font-bold"><span class="sr-only">Numer ogólny: </span>{{ $switchboard }}</p>
            @else
                <p class="mt-1"><a href="tel:{{ preg_replace('/[^0-9+]/', '', $switchboard) }}" class="{{ $pbLink }} text-2xl"><i class="fa-solid fa-phone" aria-hidden="true"></i><span class="sr-only">Zadzwoń na numer ogólny: </span>{{ $switchboard }}</a></p>
            @endif
        @endif
        @if ($switchboard !== '' && $phoneHours !== '')
            <p class="mt-1"><span class="font-bold">Kiedy dzwonić:</span> {{ $phoneHours }}</p>
        @endif
        <p class="{{ $switchboard !== '' ? 'mt-3 ' : '' }}leading-relaxed">Pracujemy zdalnie, dlatego koordynatorzy i inni pracownicy często dzwonią z własnych komórek.</p>
        @if ($phoneBookUrl)
            <p class="mt-1"><a href="{{ $phoneBookUrl }}" class="{{ $pbLink }}"><i class="fa-solid fa-address-book" aria-hidden="true"></i>{{ $phoneBookTitle }}: sprawdź, kto i w jakiej sprawie dzwoni</a></p>
        @endif
    </div>
@endif
