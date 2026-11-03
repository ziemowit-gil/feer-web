{{--
    Wskazówka na stronach kontaktu: numer centrali (z ustawień → Kontakt), informacja, że często oddzwaniamy
    z numerów komórkowych, i link do podstrony typu „Książka telefoniczna” (jeśli jest opublikowana).
    Numer centrali jest nad informacją i linkiem. $class — odstęp (domyślnie pod spodem). Płasko; tekst ink na szarym tle ≥ 14:1, cel dotyku ≥ 44 px.
--}}
@php
    $switchboard = trim((string) $siteSettings->contact_phone);
    $phoneBookPage = \App\Models\Page::forCurrentSite()->where('type', 'phone_book')->where('is_published', true)->orderBy('id')->first();
    $pbLink = 'inline-flex min-h-11 items-center gap-2 font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp
@if ($switchboard !== '' || $phoneBookPage)
    <div class="phone-book-note {{ $class ?? 'mb-6' }} rounded-md bg-gray-50 p-5 text-ink" role="note" aria-label="Telefon i oddzwanianie">
        @if ($switchboard !== '')
            <p class="text-sm font-bold uppercase tracking-widest">Centrala</p>
            <p class="mt-1"><a href="tel:{{ preg_replace('/[^0-9+]/', '', $switchboard) }}" class="{{ $pbLink }} text-2xl"><i class="fa-solid fa-phone" aria-hidden="true"></i><span class="sr-only">Zadzwoń na centralę: </span>{{ $switchboard }}</a></p>
        @endif
        <p class="{{ $switchboard !== '' ? 'mt-3 ' : '' }}leading-relaxed">Często oddzwaniamy z numerów komórkowych. Jeśli zobaczysz nieznany numer, to możemy być my.</p>
        @if ($phoneBookPage)
            <p class="mt-1"><a href="{{ $phoneBookPage->publicUrl() }}" class="{{ $pbLink }}"><i class="fa-solid fa-address-book" aria-hidden="true"></i>{{ $phoneBookPage->title }}: sprawdź, kto i w jakiej sprawie dzwoni</a></p>
        @endif
    </div>
@endif
