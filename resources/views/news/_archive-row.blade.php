<li class="group py-5 first:pt-0 last:pb-0">
    <article class="flex items-start gap-4">
        {{-- Data --}}
        <div class="hidden w-14 shrink-0 text-center sm:block" aria-hidden="true">
            <span class="block text-xl font-bold leading-none text-ink">{{ $item->published_at?->format('d') }}</span>
            <span class="block text-xs uppercase tracking-wide text-muted">{{ $item->published_at?->locale('pl')->isoFormat('MMM') }}</span>
            <span class="block text-xs text-muted">{{ $item->published_at?->format('Y') }}</span>
        </div>

        <div class="flex-1 min-w-0">
            <div class="mb-1 flex flex-wrap items-center gap-2">
                @if ($item->is_archived)
                    <span class="inline-block rounded bg-gray-200 px-2 py-0.5 text-xs font-bold uppercase text-gray-600">Archiwalne</span>
                @endif
                @if ($item->is_legacy)
                    <span class="inline-block rounded bg-amber-100 px-2 py-0.5 text-xs font-bold uppercase text-amber-700">Stara strona</span>
                @endif
                <time datetime="{{ $item->published_at?->toDateString() }}" class="text-xs text-muted sm:hidden">
                    {{ $item->published_at?->format('d.m.Y') }}
                </time>
            </div>

            <h2 class="font-bold text-ink">
                <a href="{{ route('news.show', $item) }}"
                    class="hover:underline hover:text-brand focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    {{ $item->title }}
                </a>
            </h2>

            @if ($item->excerpt)
                <p class="mt-1 text-sm text-muted line-clamp-2">{{ $item->excerpt }}</p>
            @endif

            <a href="{{ route('news.show', $item) }}"
                aria-label="Czytaj więcej: {{ $item->title }}"
                class="mt-2 inline-block text-sm font-bold text-brand hover:text-brand-dark focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                Czytaj więcej →
            </a>
        </div>
    </article>
</li>
