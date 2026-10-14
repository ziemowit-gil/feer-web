{{-- Widżet portalu: najbliższe szkolenia i wydarzenia. --}}
@if ($events->isNotEmpty())
    <section aria-labelledby="portal-w-events">
        <h2 id="portal-w-events" class="text-xl font-bold leading-tight text-ink md:text-2xl">{{ $category->portalWidgetTitle('events') }}</h2>
        <span class="mt-2 block h-1 w-12 bg-brand" aria-hidden="true"></span>
        <ul role="list" class="mt-6 grid gap-4 {{ ($compact ?? false) ? '' : 'sm:grid-cols-2 lg:grid-cols-4' }}">
            @foreach ($events as $event)
                <li>
                    <a href="{{ route('events.show', $event) }}" class="group flex h-full gap-4 rounded-md bg-gray-50 p-4 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                        @if ($event->starts_at)
                            <span class="flex w-14 flex-none flex-col items-center justify-center rounded-md bg-white py-2 text-ink" aria-hidden="true">
                                <span class="text-2xl font-bold leading-none">{{ $event->starts_at->format('j') }}</span>
                                <span class="mt-1 text-xs font-bold uppercase">{{ $event->starts_at->translatedFormat('M') }}</span>
                            </span>
                        @endif
                        <span class="min-w-0">
                            @if ($event->starts_at)<span class="sr-only">{{ $event->starts_at->translatedFormat('j F Y') }}: </span>@endif
                            <span class="block font-bold leading-snug text-ink group-hover:text-brand-dark group-hover:underline group-hover:underline-offset-4">{{ $event->title }}</span>
                            @if ($event->location)<span class="mt-1 block text-sm text-ink">{{ $event->location }}</span>@endif
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
        @if (Route::has('events.index'))
            <p class="mt-4"><a href="{{ route('events.index') }}" class="inline-flex min-h-11 items-center text-sm font-bold text-brand-dark underline underline-offset-4 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Kalendarz wydarzeń <span aria-hidden="true" class="ml-1">→</span></a></p>
        @endif
    </section>
@endif
