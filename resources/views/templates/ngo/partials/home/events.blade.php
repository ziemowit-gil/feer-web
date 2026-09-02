@if ($events->isNotEmpty())
<section class="py-14" aria-labelledby="ngo-events-heading">
    <div class="mx-auto max-w-[1400px] px-4">

        <div class="mb-8 flex items-end justify-between gap-4">
            <h2 id="ngo-events-heading" class="text-2xl font-extrabold text-gray-900 md:text-3xl">
                Nadchodzące wydarzenia
            </h2>
            <a href="{{ site_route('events.index') }}"
                class="shrink-0 text-sm font-semibold text-brand hover:underline"
                aria-label="Wszystkie wydarzenia">
                Kalendarz &rarr;
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($events as $event)
            @php
                $color = $siteSettings->events_home_color ?: 'var(--color-brand)';
            @endphp
            <article class="group relative flex gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 transition hover:shadow-md">

                {{-- Date block --}}
                <div class="flex-none rounded-xl px-3 py-2 text-center text-white"
                    style="background-color: {{ $color }}">
                    <div class="text-xl font-extrabold leading-none">
                        {{ $event->starts_at->format('j') }}
                    </div>
                    <div class="text-[10px] font-semibold uppercase tracking-wide">
                        {{ $event->starts_at->translatedFormat('M') }}
                    </div>
                </div>

                <div class="flex flex-1 flex-col gap-1 overflow-hidden">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                        {{ $event->typeLabel() }}
                        @if ($event->mode === 'online')
                            &middot; online
                        @endif
                    </span>
                    <h3 class="text-sm font-bold leading-snug text-gray-900 group-hover:text-brand line-clamp-2">
                        <a href="{{ site_route('events.show', $event) }}" class="stretched-link">
                            {{ $event->title }}
                        </a>
                    </h3>
                    <time datetime="{{ $event->starts_at->toDateString() }}"
                        class="mt-auto text-xs text-gray-400 truncate">
                        {{ $event->dateRangeLabel() }}
                    </time>
                </div>

            </article>
            @endforeach
        </div>

    </div>
</section>
@endif
