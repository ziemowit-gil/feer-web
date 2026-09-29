{{--
    Lista klauzul informacyjnych RODO zaimportowanych z SZO.
    Wstawiana shortcodem [klauzule-rodo] (albo [klauzule-rodo:en]) — App\Support\ShortcodeParser.
    Zmienne: $clauses (kolekcja App\Models\GdprClause), $lang.

    Dostępność: natywne <details>/<summary> (klawiatura, czytniki), nagłówek
    klauzuli h3 pod h2 sekcji, nagłówki treści obniżone o 2 poziomy (SafeHtml::demoteHeadings).
    Dopisek „(link otwiera się w nowej karcie)” dodaje globalnie resources/js/app.js.

    Shortcode renderuje się wewnątrz `.prose` treści strony: treść klauzuli dziedziczy
    tę typografię, a elementy interfejsu (nagłówek sekcji, pasek klauzuli, przyciski)
    mają `not-prose`. Nie zakładamy `not-prose` na całą sekcję — wyłączyłoby to
    typografię także w treści klauzuli.
--}}
@php
    $en = ($lang ?? 'pl') === 'en';
    $t = $en
        ? ['heading' => 'Information clauses', 'updated' => 'Updated', 'version' => 'version', 'open' => 'Full text', 'pdf' => 'Download PDF']
        : ['heading' => 'Klauzule informacyjne', 'updated' => 'Aktualizacja', 'version' => 'wersja', 'open' => 'Pełna treść', 'pdf' => 'Pobierz PDF'];
@endphp

@if ($clauses->isNotEmpty())
    <section class="mt-10" aria-labelledby="gdpr-clauses-h" lang="{{ $lang }}">
        <h2 id="gdpr-clauses-h" class="not-prose mb-4 text-xl font-bold text-ink">{{ $t['heading'] }}</h2>

        <div class="divide-y divide-gray-200 rounded-lg border border-gray-200 bg-white">
            @foreach ($clauses as $clause)
                <details class="group" id="klauzula-{{ $clause->slug }}">
                    <summary class="not-prose flex cursor-pointer list-none items-start gap-3 p-4 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand [&::-webkit-details-marker]:hidden">
                        <i class="fa-solid fa-chevron-right mt-1.5 flex-none text-xs text-brand transition-transform duration-150 group-open:rotate-90 motion-reduce:transition-none" aria-hidden="true"></i>
                        <h3 class="min-w-0 flex-1 text-base font-bold text-ink">
                            {{ $clause->title }}
                            @if ($clause->remote_updated_at || $clause->version)
                                <span class="mt-0.5 block text-sm font-normal text-muted">
                                    @if ($clause->remote_updated_at)
                                        {{ $t['updated'] }}: <time datetime="{{ $clause->remote_updated_at->toDateString() }}">{{ $clause->remote_updated_at->format('d.m.Y') }}</time>
                                    @endif
                                    @if ($clause->version)
                                        @if ($clause->remote_updated_at) · @endif{{ $t['version'] }} {{ $clause->version }}
                                    @endif
                                </span>
                            @endif
                        </h3>
                    </summary>

                    <div class="px-4 pb-5 sm:pl-11">
                        <div class="prose prose-sm max-w-none text-ink">{!! \App\Support\SafeHtml::demoteHeadings($clause->html, 2) !!}</div>

                        @if ($clause->url || $clause->pdf_url)
                            <div class="not-prose mt-4 flex flex-wrap gap-3">
                                @if ($clause->url)
                                    <a href="{{ $clause->url }}" target="_blank" rel="noopener"
                                        class="no-external-icon inline-flex items-center gap-2 rounded border border-brand px-4 py-2 text-sm font-bold text-brand hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                        <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                                        {{ $t['open'] }} <span class="sr-only">— {{ $clause->title }}</span>
                                    </a>
                                @endif
                                @if ($clause->pdf_url)
                                    <a href="{{ $clause->pdf_url }}"
                                        class="inline-flex items-center gap-2 rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                        <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                                        {{ $t['pdf'] }} <span class="sr-only">— {{ $clause->title }}</span>
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>
    </section>
@endif
