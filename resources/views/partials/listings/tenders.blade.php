{{--
    Shortcode [zapytania-ofertowe]: lista zapytań ofertowych bieżącej witryny
    z filtrem aktualne / zakończone / wszystkie i archiwum. Płasko: szare karty
    bez obramowań, status zwykłym tekstem, tylko kolory firmowe.
    Zmienne z ShortcodeParser::renderListing(): $items, $filter, $counts, $currentPage, $lastPage.
--}}
@php
    $anchor = 'lista-zapytan-ofertowych';
    $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    $fmt = function (?string $raw) {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        try {
            $d = \Illuminate\Support\Carbon::parse($raw)->locale('pl');
        } catch (\Throwable) {
            return null;
        }

        return $d->translatedFormat(str_contains($raw, 'T') || str_contains($raw, ':') ? 'j F Y, \g\o\d\z. H:i' : 'j F Y');
    };
    $empty = [
        'aktualne' => 'Obecnie nie prowadzimy postępowań. Zajrzyj do zakończonych albo wróć do nas wkrótce.',
        'zakonczone' => 'Nie ma jeszcze zakończonych postępowań.',
        'wszystkie' => 'Nie ma jeszcze żadnych zapytań ofertowych.',
    ][$filter];
@endphp

<section id="{{ $anchor }}" class="not-prose my-8 scroll-mt-24" aria-labelledby="{{ $anchor }}-h">
    <h2 id="{{ $anchor }}-h" class="sr-only">Zapytania ofertowe — {{ mb_strtolower(\App\Support\ShortcodeParser::LISTING_FILTERS[$filter]) }}</h2>

    @include('partials.listings._filter', ['label' => 'Pokaż zapytania ofertowe', 'anchor' => $anchor])

    @if ($items->isEmpty())
        <p class="rounded-md bg-gray-50 p-6 text-ink">{{ $empty }}</p>
    @else
        <ul class="grid gap-4" role="list">
            @foreach ($items as $item)
                @php
                    $td = $item->typeData();
                    $status = $item->tenderStatus();
                    $facts = array_filter([
                        'Status' => \App\Models\Page::TENDER_STATUSES[$status],
                        'Znak sprawy' => $td['tender_number'] ?? null,
                        'Termin składania ofert' => $fmt($td['tender_deadline'] ?? null),
                        'Data rozstrzygnięcia' => in_array($status, ['awarded', 'cancelled'], true) ? $fmt($td['tender_result_at'] ?? null) : null,
                    ], fn ($v) => filled($v));
                @endphp
                <li class="rounded-md {{ in_array($status, ['awarded', 'cancelled'], true) ? 'bg-gray-50' : 'bg-brand-light' }} p-6">
                    <article aria-labelledby="zapytanie-{{ $item->id }}">
                        <h3 id="zapytanie-{{ $item->id }}" class="text-xl font-bold leading-snug text-ink">
                            <a href="{{ site_route('page.show', $item) }}" class="{{ $link }}">{{ $item->title }}</a>
                        </h3>
                        <dl class="mt-3 grid gap-x-8 gap-y-2 sm:grid-cols-2">
                            @foreach ($facts as $label => $value)
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-muted">{{ $label }}</dt>
                                    <dd class="font-semibold text-ink">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                        @if (filled($td['lead'] ?? null))
                            <p class="mt-3 leading-relaxed text-ink">{{ $td['lead'] }}</p>
                        @endif
                    </article>
                </li>
            @endforeach
        </ul>
    @endif

    @include('partials.listings._pagination', ['label' => 'Strony listy zapytań ofertowych', 'anchor' => $anchor])
</section>
