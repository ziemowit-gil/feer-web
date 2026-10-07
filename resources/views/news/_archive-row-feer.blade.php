{{-- Wiersz archiwum aktualności (FEER): data po lewej, kategoria/tytuł/zajawka, cały wiersz klikalny. Bez ramek i kresek. --}}
<li>
    <article class="feer-card group relative flex items-start gap-5 rounded-md bg-gray-50 p-5 hover:bg-gray-100 focus-within:ring-2 focus-within:ring-brand">
        <div class="hidden w-16 shrink-0 text-center sm:block" aria-hidden="true">
            <span class="block text-3xl font-extrabold leading-none text-ink">{{ $item->published_at?->format('d') }}</span>
            <span class="mt-1 block text-xs font-bold uppercase tracking-wide text-muted">{{ $item->published_at?->locale('pl')->isoFormat('MMM') }} {{ $item->published_at?->format('Y') }}</span>
        </div>

        <div class="min-w-0 flex-1">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-dark">
                {{ $item->category?->name ?? 'Aktualności' }}
                @if ($item->is_archived)<span class="ml-2 rounded-sm bg-gray-200 px-1.5 py-0.5 text-ink">Archiwalne</span>@endif
                @if ($item->is_legacy)<span class="ml-2 rounded-sm bg-amber-100 px-1.5 py-0.5 text-amber-900">Stara strona</span>@endif
                <time datetime="{{ $item->published_at?->toDateString() }}" class="ml-2 font-medium normal-case tracking-normal text-muted sm:hidden">{{ $item->published_at?->format('d.m.Y') }}</time>
            </p>
            <h2 class="mt-1 text-lg font-bold leading-snug text-ink group-hover:text-brand-dark">
                <a href="{{ site_route('news.show', $item) }}" class="stretched-link focus-visible:outline-none">{{ $item->title }}</a>
            </h2>
            @if ($item->excerpt)
                <p class="mt-1 line-clamp-2 text-sm leading-relaxed text-muted">{{ $item->excerpt }}</p>
            @endif
        </div>
        <span class="mt-1 flex-none text-lg text-brand-dark transition group-hover:translate-x-1" aria-hidden="true">→</span>
    </article>
</li>
