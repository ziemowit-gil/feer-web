{{--
    Sekcja szkoleń — moduł „Szkolenia i wydarzenia" na stronie głównej szablonu
    mieszanego. Korzysta z tych samych danych ($events) co sekcja wydarzeń
    w szablonie rozbudowanym, ale nazywa rzecz po imieniu i prowadzi do kalendarza.
--}}
@if ($events->isNotEmpty())
    <section class="bg-gray-50 py-14" aria-labelledby="mix-trainings-heading">
        <div class="mx-auto max-w-[1400px] px-4">

            <div class="mb-8 flex items-end justify-between gap-4">
                <div>
                    <h2 id="mix-trainings-heading" class="text-2xl font-extrabold text-gray-900 md:text-3xl">
                        Szkolenia i wydarzenia
                    </h2>
                    <p class="mt-1 text-sm text-muted">Najbliższe terminy — zapisy przez stronę wydarzenia.</p>
                </div>
                <a href="{{ site_route('events.index') }}"
                    class="shrink-0 text-sm font-semibold text-brand hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    Pełny kalendarz &rarr;
                </a>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($events as $event)
                    <article class="flex h-full flex-col rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 transition hover:shadow-md">
                        <p class="text-xs font-bold uppercase tracking-wide text-brand">
                            <i class="fa-regular fa-calendar mr-1" aria-hidden="true"></i>
                            {{ $event->starts_at?->translatedFormat('j F Y, H:i') }}
                        </p>

                        <h3 class="mt-2 text-lg font-bold text-ink">
                            <a href="{{ site_route('events.show', $event) }}"
                               class="hover:text-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                {{ $event->title }}
                            </a>
                        </h3>

                        @if (filled($event->excerpt ?? null))
                            <p class="mt-2 line-clamp-3 text-sm text-muted">{{ $event->excerpt }}</p>
                        @endif

                        <p class="mt-auto pt-3 text-sm text-muted">
                            <i class="fa-solid {{ $event->is_remote ? 'fa-video' : 'fa-location-dot' }} mr-1" aria-hidden="true"></i>
                            {{ $event->is_remote ? 'Online' : ($event->location ?: 'Miejsce podamy wkrótce') }}
                        </p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif
