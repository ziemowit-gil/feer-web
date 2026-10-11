{{--
    Karta wpisu w siatce Wiem FEER: zdjęcie (gdy jest), tytuł, data · kategoria, autorzy;
    bez zdjęcia — szare tło i zajawka. Płasko, bez ramek. Zdjęcie ma alt="" —
    tytuł obok niesie tę samą informację. Zdjęcia autorów (32 px) — partials.card-authors. Zmienne: $article, opcjonalnie $headingLevel (3).
--}}
@php
    $hl = $headingLevel ?? 3;
    $img = $article->imageUrl();
    $d = $article->publishedDate();
@endphp
<article class="{{ $img ? '' : 'h-full rounded-md bg-gray-50 p-4' }}">
    @if ($img)
        <img src="{{ $img }}" alt="" loading="lazy" class="mb-3 aspect-[3/2] w-full rounded-md object-cover">
    @endif
    <h{{ $hl }} class="font-bold leading-snug text-ink">
        <a href="{{ route('blog.show', $article) }}" class="hover:underline hover:underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $article->title }}</a>
    </h{{ $hl }}>
    <p class="mt-1 text-sm text-muted">
        @if ($d)<time datetime="{{ $d->toDateString() }}">{{ $d->locale('pl')->translatedFormat('j F Y') }}</time>@endif
        @if ($article->category) · {{ $article->category->name }}@endif
    </p>
    @include('blog::partials.card-authors', ['article' => $article, 'class' => 'mt-1'])
    @if (! $img && $article->excerpt)
        <p class="mt-2 text-sm leading-relaxed text-ink">{{ \Illuminate\Support\Str::limit($article->excerpt, 140) }}</p>
    @endif
</article>
