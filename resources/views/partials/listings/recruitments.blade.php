{{--
    Shortcode [nabory]: lista naborów (strony typu „Rekrutacja do projektu") bieżącej
    witryny z filtrem aktualne / zakończone / wszystkie i archiwum. Płasko: szare
    karty bez obramowań, status zwykłym tekstem, tylko kolory firmowe.
    Zmienne z ShortcodeParser::renderListing(): $items, $filter, $counts, $currentPage, $lastPage.
--}}
@php
    $anchor = 'lista-naborow';
    $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    $fmt = function (?\Illuminate\Support\Carbon $d, string $raw) {
        if (! $d) {
            return null;
        }

        return $d->locale('pl')->translatedFormat(str_contains($raw, 'T') || str_contains($raw, ':') ? 'j F Y, \g\o\d\z. H:i' : 'j F Y');
    };
    $empty = [
        'aktualne' => 'Obecnie nie prowadzimy naborów. Zajrzyj do zakończonych albo wróć do nas wkrótce.',
        'zakonczone' => 'Nie ma jeszcze zakończonych naborów.',
        'wszystkie' => 'Nie ma jeszcze żadnych naborów.',
    ][$filter];
@endphp

<section id="{{ $anchor }}" class="not-prose my-8 scroll-mt-24" aria-labelledby="{{ $anchor }}-h">
    <h2 id="{{ $anchor }}-h" class="sr-only">Nabory — {{ mb_strtolower(\App\Support\ShortcodeParser::LISTING_FILTERS[$filter]) }}</h2>

    @include('partials.listings._filter', ['label' => 'Pokaż nabory', 'anchor' => $anchor])

    @if ($items->isEmpty())
        <p class="rounded-md bg-gray-50 p-6 text-ink">{{ $empty }}</p>
    @else
        <ul class="grid gap-4" role="list">
            @foreach ($items as $item)
                @php
                    $td = $item->typeData();
                    $status = $item->recruitmentStatus();
                    $startLabel = $fmt($item->recruitmentDate('start'), (string) ($td['rec_starts_at'] ?? ''));
                    $endLabel = $fmt($item->recruitmentDate('end'), (string) ($td['rec_ends_at'] ?? ''));
                    $term = match (true) {
                        $startLabel && $endLabel => $startLabel.' – '.$endLabel,
                        (bool) $endLabel => 'do '.$endLabel,
                        (bool) $startLabel => 'od '.$startLabel,
                        default => $status === 'continuous' ? 'nabór ciągły' : null,
                    };
                @endphp
                <li class="rounded-md {{ $status === 'closed' ? 'bg-gray-50' : 'bg-brand-light' }} p-6">
                    <article aria-labelledby="nabor-{{ $item->id }}">
                        <h3 id="nabor-{{ $item->id }}" class="text-xl font-bold leading-snug text-ink">
                            <a href="{{ site_route('page.show', $item) }}" class="{{ $link }}">{{ $item->title }}</a>
                        </h3>
                        <dl class="mt-3 grid gap-x-8 gap-y-2 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wide text-muted">Status</dt>
                                <dd class="font-semibold text-ink">{{ \App\Models\Page::RECRUITMENT_STATUSES[$status] }}</dd>
                            </div>
                            @if ($term)
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-muted">Termin naboru</dt>
                                    <dd class="font-semibold text-ink">{{ $term }}</dd>
                                </div>
                            @endif
                            @if (filled($td['rec_places'] ?? null))
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-muted">Liczba miejsc</dt>
                                    <dd class="font-semibold text-ink">{{ $td['rec_places'] }}</dd>
                                </div>
                            @endif
                        </dl>
                        @if (filled($td['lead'] ?? null))
                            <p class="mt-3 leading-relaxed text-ink">{{ $td['lead'] }}</p>
                        @endif
                    </article>
                </li>
            @endforeach
        </ul>
    @endif

    @include('partials.listings._pagination', ['label' => 'Strony listy naborów', 'anchor' => $anchor])
</section>
