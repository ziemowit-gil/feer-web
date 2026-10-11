{{-- Wiersz archiwum aktualności: data · kategoria · pochodzenie zwykłym tekstem, tytuł-link (cały wiersz klikalny), zajawka. Płasko, na szarym tle. --}}
<li>
    <article class="group relative rounded-md bg-gray-50 p-4 hover:bg-gray-100 md:p-5">
        <p class="text-sm text-muted">
            <time datetime="{{ $item->published_at?->toDateString() }}">{{ $item->published_at?->locale('pl')->translatedFormat('j F Y') }}</time>
            @if ($item->category) · {{ $item->category->name }}@endif
            @if ($item->is_archived) · Archiwalne @endif
            @if ($item->is_legacy) · Ze starej strony @endif
        </p>
        <h2 class="mt-1 text-lg font-bold leading-snug text-ink">
            <a href="{{ site_route('news.show', $item) }}" class="stretched-link hover:underline hover:underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $item->title }}</a>
        </h2>
        @if ($item->excerpt)
            <p class="mt-1 line-clamp-2 text-sm leading-relaxed text-ink">{{ $item->excerpt }}</p>
        @endif
    </article>
</li>
