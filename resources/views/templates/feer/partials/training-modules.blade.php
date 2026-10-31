{{--
    Moduły szkoleniowe strony głównej „FEER IS”: wyróżnione szkolenia (karty) i najbliższe terminy (tabela).
    Dane: $trainingOffers, $trainingEditions, $trainingCatalogUrl (HomeController::trainingModules()).
    Pusty moduł się nie pokazuje. Płasko, kolory marki, klasy już obecne w zbudowanym CSS.
--}}
@php
    $trainingOffers ??= collect();
    $trainingEditions ??= collect();
    $trainingCatalogUrl ??= null;
    $modeLabels = ['stationary' => 'Stacjonarnie', 'online' => 'Online', 'hybrid' => 'Hybrydowo'];
    $statusLabels = ['open' => 'Zapisy otwarte', 'few' => 'Ostatnie miejsca', 'full' => 'Brak miejsc', 'planned' => 'Planowany'];
@endphp

@if ($trainingOffers->isNotEmpty())
    <section class="bg-gray-50 py-12" aria-labelledby="feer-is-trainings-heading">
        <div class="mx-auto max-w-6xl px-4">
            <div class="mb-8 flex items-end justify-between gap-4">
                <h2 id="feer-is-trainings-heading" class="text-2xl font-bold text-ink md:text-3xl">{{ $siteSettings->projectsLabel() }}</h2>
                @if ($trainingCatalogUrl)
                    <a href="{{ $trainingCatalogUrl }}" class="shrink-0 text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Katalog szkoleń →</a>
                @endif
            </div>
            <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" role="list">
                @foreach ($trainingOffers as $offer)
                    <li>
                        <article class="group relative flex h-full flex-col gap-2 rounded-md bg-white p-6 focus-within:ring-2 focus-within:ring-brand-dark">
                            @if (filled($offer['category']))
                                <span class="text-xs font-bold uppercase tracking-widest text-brand-dark">{{ $offer['category'] }}</span>
                            @endif
                            <h3 class="text-lg font-bold leading-snug">
                                <a href="{{ $offer['url'] }}" class="stretched-link text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none">{{ $offer['title'] }}</a>
                            </h3>
                            @if (filled($offer['lead']))
                                <p class="text-sm leading-relaxed text-ink">{{ \Illuminate\Support\Str::limit($offer['lead'], 140) }}</p>
                            @endif
                            <p class="mt-auto pt-3 text-sm font-semibold text-ink">
                                {{ collect([$offer['duration'], $modeLabels[$offer['mode']] ?? null, $offer['price']])->filter()->implode(' · ') }}
                            </p>
                        </article>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif

@if ($trainingEditions->isNotEmpty())
    <section class="py-12" aria-labelledby="feer-is-editions-heading">
        <div class="mx-auto max-w-6xl px-4">
            <h2 id="feer-is-editions-heading" class="mb-6 text-2xl font-bold text-ink md:text-3xl">Najbliższe terminy</h2>
            <ul class="divide-y divide-gray-200 rounded-md bg-gray-50" role="list">
                @foreach ($trainingEditions as $edition)
                    <li class="flex flex-wrap items-center gap-x-6 gap-y-1 px-5 py-4">
                        <time datetime="{{ $edition['date']->toDateString() }}" class="w-32 shrink-0 font-bold text-ink">{{ $edition['date']->translatedFormat('j F Y') }}</time>
                        <a href="{{ $edition['url'] }}" class="min-w-0 flex-1 font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $edition['title'] }}</a>
                        <span class="text-sm text-ink">{{ collect([$edition['place'], $statusLabels[$edition['status']] ?? null])->filter()->implode(' · ') }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
