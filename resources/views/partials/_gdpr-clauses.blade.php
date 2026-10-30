{{--
    Lista klauzul informacyjnych RODO zaimportowanych z SZO.
    Wstawiana shortcodem [klauzule-rodo] (albo [klauzule-rodo:en]) — App\Support\ShortcodeParser.
    Zmienne: $clauses (kolekcja App\Models\GdprClause), $lang.

    Wygląd: płaskie, jasnoszare karty bez ramek i linii (paleta marki). Tytuł klauzuli to h3, a data i wersja leżą
    w osobnym akapicie poza nagłówkiem, żeby spis „Na tej stronie” pokazywał same tytuły. Przyciski „Pobierz plik (PDF)”
    i „Pełna treść” są widoczne od razu, także przy zwiniętej klauzuli, i stoją poza <summary> (bez zagnieżdżonych
    elementów interaktywnych).
    Dostępność: natywne <details>/<summary> (klawiatura, czytniki), nagłówki treści obniżone o 2 poziomy
    (SafeHtml::demoteHeadings).
    Dopisek „(link otwiera się w nowej karcie)” dodaje globalnie resources/js/app.js.

    Shortcode renderuje się wewnątrz `.prose` treści strony: treść klauzuli dziedziczy
    tę typografię, a elementy interfejsu (nagłówek sekcji, pasek klauzuli, przyciski)
    mają `not-prose`. Nie zakładamy `not-prose` na całą sekcję — wyłączyłoby to
    typografię także w treści klauzuli.
--}}
@php
    $en = ($lang ?? 'pl') === 'en';
    $t = $en
        ? ['heading' => 'Information clauses', 'updated' => 'Updated', 'version' => 'version', 'open' => 'Full text', 'pdf' => 'Download file (PDF)']
        : ['heading' => 'Klauzule informacyjne', 'updated' => 'Aktualizacja', 'version' => 'wersja', 'open' => 'Pełna treść', 'pdf' => 'Pobierz plik (PDF)'];
@endphp

@if ($clauses->isNotEmpty())
    <section class="mt-10" aria-labelledby="gdpr-clauses-h" lang="{{ $lang }}">
        <h2 id="gdpr-clauses-h" class="not-prose mb-5 text-xl font-bold text-ink">{{ $t['heading'] }}</h2>

        <ul role="list" class="not-prose m-0 grid list-none gap-4 p-0">
            @foreach ($clauses as $clause)
                <li class="m-0 rounded-lg bg-gray-50 p-5" id="klauzula-{{ $clause->slug }}">
                    <details class="group">
                        <summary class="flex cursor-pointer list-none items-start gap-3 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand [&::-webkit-details-marker]:hidden">
                            <i class="fa-solid fa-chevron-right mt-2 flex-none text-xs text-brand-dark transition-transform duration-150 group-open:rotate-90 motion-reduce:transition-none" aria-hidden="true"></i>
                            <h3 class="m-0 min-w-0 flex-1 text-lg font-bold leading-snug text-ink">{{ $clause->title }}</h3>
                        </summary>

                        <div class="prose prose-sm mt-4 max-w-none pl-6 text-ink">{!! \App\Support\SafeHtml::demoteHeadings($clause->html, 2) !!}</div>
                    </details>

                    @if ($clause->remote_updated_at || $clause->version)
                        <p class="m-0 mt-1 pl-6 text-sm text-ink">
                            @if ($clause->remote_updated_at)
                                {{ $t['updated'] }}: <time datetime="{{ $clause->remote_updated_at->toDateString() }}">{{ $clause->remote_updated_at->format('d.m.Y') }}</time>
                            @endif
                            @if ($clause->version)
                                @if ($clause->remote_updated_at), @endif{{ $t['version'] }} {{ $clause->version }}
                            @endif
                        </p>
                    @endif

                    @if ($clause->url || $clause->pdf_url)
                        <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-3 pl-6">
                            @if ($clause->pdf_url)
                                <a href="{{ $clause->pdf_url }}" download
                                    class="inline-flex min-h-11 items-center gap-2 rounded-md bg-brand-dark px-5 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                    <i class="fa-solid fa-arrow-down" aria-hidden="true"></i>
                                    {{ $t['pdf'] }}<span class="sr-only">: {{ $clause->title }}</span>
                                </a>
                            @endif
                            @if ($clause->url)
                                <a href="{{ $clause->url }}" target="_blank" rel="noopener"
                                    class="no-external-icon inline-flex min-h-11 items-center gap-2 text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                    {{ $t['open'] }}<span class="sr-only">: {{ $clause->title }}</span>
                                    <i class="fa-solid fa-arrow-up-right-from-square text-xs" aria-hidden="true"></i>
                                </a>
                            @endif
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>
@endif
