{{--
    „Najbliższe szkolenia” w wariancie FEER IS: nadchodzące terminy z oferty szkoleń (moduł courses) jako lista usług
    w tym samym układzie co zakładki (ikona, link, opis). Płasko, tylko klasy już obecne w zbudowanym CSS.
    Zmienna $courseEditions: kolekcja CourseEdition z załadowanym course.
--}}
@if (($courseEditions ?? collect())->isNotEmpty())
    <section class="pb-12" aria-labelledby="feer-is-courses-heading">
        <div class="mx-auto max-w-6xl px-4">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
                <h2 id="feer-is-courses-heading" class="text-2xl font-bold text-ink">Najbliższe szkolenia</h2>
                <a href="{{ route('courses.index') }}" class="text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Wszystkie szkolenia i zapisy →</a>
            </div>
            <ul class="grid gap-x-8 gap-y-8 sm:grid-cols-2 lg:grid-cols-3" role="list">
                @foreach ($courseEditions as $edition)
                    <li class="flex gap-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-brand-light text-brand-dark" aria-hidden="true">
                            <i class="fa-solid fa-chalkboard-user text-xl"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm text-ink">
                                <time datetime="{{ $edition->starts_on->toDateString() }}">{{ $edition->datesLabel() }}</time>
                                <span aria-hidden="true"> | </span>{{ $edition->course->formatLabel() }}
                            </p>
                            <h3 class="mt-1 text-base font-bold leading-snug">
                                <a href="{{ route('courses.show', [$edition->course, 'termin' => $edition->id]) }}#zapis" class="text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $edition->course->title }}</a>
                            </h3>
                            <p class="mt-1 text-sm leading-relaxed text-ink">
                                {{ $edition->isFull() ? 'Brak miejsc, zapisy na listę rezerwową' : 'Wolne miejsca: '.$edition->free_seats }}
                            </p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
