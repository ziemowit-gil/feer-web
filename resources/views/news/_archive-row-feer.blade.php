{{-- Wiersz archiwum aktualności (FEER): duża data po lewej, data/kategoria zwykłym tekstem, tytuł-link (cały wiersz klikalny). Bez ramek i etykiet. --}}
<li>
    <article class="feer-card group relative flex items-start gap-5 rounded-md bg-gray-50 p-5 hover:bg-gray-100">
        <div class="hidden w-16 shrink-0 text-center sm:block" aria-hidden="true">
            <span class="block text-3xl font-extrabold leading-none text-ink">{{ $item->published_at?->format('d') }}</span>
            <span class="mt-1 block text-xs font-bold uppercase tracking-wide text-muted">{{ $item->published_at?->locale('pl')->isoFormat('MMM') }} {{ $item->published_at?->format('Y') }}</span>
        </div>

        <div class="min-w-0 flex-1">
            <p class="text-sm text-muted">
                <time datetime="{{ $item->published_at?->toDateString() }}" class="sm:sr-only">{{ $item->published_at?->format('d.m.Y') }}</time><span class="sm:hidden" aria-hidden="true"> · </span>
                {{ $item->category?->name ?? 'Aktualności' }}
                @if ($item->is_archived) · Archiwalne @endif
                @if ($item->is_legacy) · Ze starej strony @endif
            </p>
            <h2 class="mt-1 text-lg font-bold leading-snug text-ink">
                <a href="{{ site_route('news.show', $item) }}" class="stretched-link hover:underline hover:underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $item->title }}</a>
            </h2>
            @if ($item->excerpt)
                <p class="mt-1 line-clamp-2 text-sm leading-relaxed text-ink">{{ $item->excerpt }}</p>
            @endif
        </div>
    </article>
</li>
