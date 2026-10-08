@extends('layouts.site')

@section('title', 'Instrukcja korzystania z BIP — ' . $siteSettings->site_name)
@section('meta_description', 'Jak korzystać z Biuletynu Informacji Publicznej ' . $siteSettings->siteNameGenitive() . ': nawigacja, wyszukiwarka, rejestr zmian i kontakt z redakcją.')

@section('content')
    @php $feer = ($siteSettings->site_template ?? 'default') === 'feer'; @endphp
    @include('bip._head', ['bipTitle' => 'Instrukcja korzystania z BIP', 'bipSub' => null])
    <div class="bip-wrap">
            <aside class="{{ $feer ? '' : 'lg:border-r lg:border-gray-100 lg:pr-6' }}">@include('bip._sidebar')</aside>
            <article aria-labelledby="bip-instr-h" class="prose max-w-none text-ink [&_h2]:text-ink [&_a]:text-brand-dark [&_a:hover]:text-ink">
                <h1 id="bip-instr-h" class="!mb-2 text-2xl font-extrabold text-ink sm:text-3xl">Jak korzystać z BIP</h1>
                <p class="lead">Biuletyn Informacji Publicznej to strona, na której publikujemy dokumenty i informacje o działalności {{ $siteSettings->siteNameGenitive() }}. Poniżej wyjaśniamy prostym językiem, jak z niej korzystać.</p>

                <h2>Jak się poruszać po BIP</h2>
                <ul>
                    <li>Menu po lewej stronie (na telefonie — nad treścią) prowadzi do działów BIP, rejestru zmian, tej instrukcji i deklaracji dostępności.</li>
                    <li>Dokumenty są pogrupowane w kategorie. Kliknij tytuł dokumentu, aby zobaczyć jego treść i pobrać załączniki.</li>
                    <li>Do strony głównej organizacji wracasz przyciskiem „Strona główna organizacji” u góry.</li>
                </ul>

                @unless ($isExternal)
                    <h2>Wyszukiwarka</h2>
                    <p>Na <a href="{{ route('bip') }}">stronie głównej BIP</a> wpisz słowo lub fragment tytułu w pole „Szukaj w BIP” i wybierz „Szukaj”. Wyszukiwarka sprawdza tytuły, streszczenia i treść dokumentów.</p>

                    <h2>Informacje przy każdym dokumencie</h2>
                    <p>Przy dokumencie podajemy: kto go wytworzył i udostępnił, datę udostępnienia i datę ostatniej zmiany. Pełną historię zmian znajdziesz w <a href="{{ route('bip.changelog') }}">rejestrze zmian</a>.</p>
                @endunless

                <h2>Dostępność</h2>
                <p>Strona jest przygotowana tak, by korzystały z niej także osoby z niepełnosprawnościami (czytniki ekranu, obsługa klawiaturą, zmiana kontrastu i wielkości tekstu w panelu „Dostępność”). Szczegóły: <a href="{{ route('accessibility.show') }}">deklaracja dostępności</a>.</p>

                <h2>Kontakt z redakcją BIP</h2>
                <p>
                    @if ($siteSettings->bip_editor_name || $siteSettings->bip_editor_email)
                        Za publikację w BIP odpowiada: <strong>{{ $siteSettings->bip_editor_name ?: 'redakcja BIP' }}</strong>@if ($siteSettings->bip_editor_email), e-mail: <a href="mailto:{{ $siteSettings->bip_editor_email }}">{{ $siteSettings->bip_editor_email }}</a>@endif.
                    @else
                        W sprawach związanych z BIP skorzystaj ze strony <a href="{{ route('contact.show') }}">Kontakt</a>.
                    @endif
                    Jeśli nie możesz znaleźć informacji lub potrzebujesz dokumentu w innej formie, napisz do nas — udzielimy pomocy.
                </p>

                <h2>Główna strona BIP</h2>
                <p>Ogólnopolski portal Biuletynu Informacji Publicznej: <a href="{{ $siteSettings->bip_gov_url ?: 'https://www.gov.pl/web/bip' }}" target="_blank" rel="noopener">gov.pl/bip<span class="sr-only"> (otwiera się w nowej karcie)</span></a>.</p>
            </article>
    </div>
@endsection
