{{--
    Moduł Kontakt: „Gdzie działamy” — miejsca z opisem, co tam robimy (Komunikacja → Kontakt). Automatyczna podstrona
    /kontakt/gdzie-dzialamy. Płaskie karty na jasnoszarym tle; adres z linkiem do mapy (OpenStreetMap, nowa karta).
--}}
@php
    $plOptions = $contactOptions ?? \Modules\Contact\Support\ContactOptions::for($siteSettings);
    $plLink = 'inline-flex min-h-11 items-center gap-2 font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp
@if ($plOptions->places() !== [])
    <section id="gdzie-dzialamy" aria-labelledby="gdzie-dzialamy-heading" class="scroll-mt-24">
        <h2 id="gdzie-dzialamy-heading" class="{{ ($hideHeading ?? false) ? 'sr-only' : 'mb-3 text-2xl font-bold text-ink' }}">{{ $plOptions->placesTitle() }}</h2>
        @if ($plOptions->placesIntro() !== '')
            <p class="mb-6 max-w-3xl text-lg leading-relaxed text-ink">{!! nl2br(e($plOptions->placesIntro())) !!}</p>
        @endif
        <ul role="list" class="grid gap-4 {{ count($plOptions->places()) > 1 ? 'md:grid-cols-2' : '' }}">
            @foreach ($plOptions->places() as $place)
                <li class="rounded-lg bg-gray-50 p-5 text-ink">
                    <h3 class="text-xl font-bold">{{ $place['name'] }}</h3>
                    @if ($place['area'] !== '')
                        <p class="mt-1 text-sm font-bold uppercase tracking-widest text-muted">{{ $place['area'] }}</p>
                    @endif
                    @if ($place['description'] !== '')
                        <p class="mt-3 leading-relaxed">{!! nl2br(e($place['description'])) !!}</p>
                    @endif
                    @if ($place['address'] !== '' || $place['url'] !== '')
                        <p class="mt-3 flex flex-wrap items-center gap-x-6">
                            @if ($place['address'] !== '')
                                <a href="{{ \Modules\Contact\Support\ContactOptions::mapUrl($place['address']) }}" target="_blank" rel="noopener" class="{{ $plLink }}">
                                    <i class="fa-solid fa-location-dot" aria-hidden="true"></i>{{ $place['address'] }}<span class="sr-only"> — pokaż na mapie (OpenStreetMap, otwiera się w nowej karcie)</span>
                                </a>
                            @endif
                            @if ($place['url'] !== '')
                                @php $plExternal = str_starts_with($place['url'], 'http'); @endphp
                                <a href="{{ $place['url'] }}" @if ($plExternal) target="_blank" rel="noopener" @endif class="{{ $plLink }}">
                                    {{ $place['url_label'] !== '' ? $place['url_label'] : 'Więcej o tym miejscu' }}<span aria-hidden="true">→</span>@if ($plExternal)<span class="sr-only"> (otwiera się w nowej karcie)</span>@endif
                                </a>
                            @endif
                        </p>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>
@endif
