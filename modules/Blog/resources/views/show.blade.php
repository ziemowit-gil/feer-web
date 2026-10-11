@extends('layouts.site')

{{--
    Wpis Wiem FEER w układzie „portal" (jak news/_portal): lewa kolumna (3/4) — kategoria,
    tytuł, pasek metryczki (data, aktualizacja, czas czytania, autorzy, rozmiar tekstu A A A),
    pogrubiona zajawka, zdjęcie 16:9, treść, tagi, udostępnianie (bez skryptów śledzących),
    autorzy, poprzedni/następny; prawa (1/4, przyklejona na desktopie, pod artykułem na
    telefonie) — „Na tej stronie", „Więcej w kategorii", wyszukiwarka, newsletter. Pod spodem
    na całą szerokość „Zobacz także" (4 karty) i komentarze. Płasko, kolory firmowe.
    Dane: BlogController::show().
--}}

@php
    $blogSite = (bool) request()->attributes->get('blog_site');
    $blogName = $blogSite ? $siteSettings->site_name : 'Wiem FEER';
    $articleUrl = route('blog.show', $article);
    $image = $article->imageUrl();
    $date = $article->publishedDate();
    $profiles = $article->authorProfiles();
    $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    $tool = 'inline-flex min-h-11 items-center gap-2 rounded-md bg-gray-100 px-4 text-sm font-bold text-ink transition hover:bg-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
    $field = 'w-full rounded-t-md rounded-b-none border-0 border-b-2 border-gray-500 bg-gray-100 text-base text-ink focus:border-brand-dark focus:bg-white focus:outline-none focus:ring-0';
    $navLink = 'flex min-h-11 items-start gap-2 rounded-md px-3 py-2 text-sm font-bold leading-snug text-ink transition hover:bg-white hover:text-brand-dark hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    $sizeBtn = 'inline-flex h-11 w-11 items-center justify-center rounded-md font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
    $sizes = [
        'normal' => ['label' => 'Tekst normalny', 'class' => 'text-sm'],
        'large'  => ['label' => 'Większy tekst', 'class' => 'text-base'],
        'larger' => ['label' => 'Największy tekst', 'class' => 'text-xl'],
    ];
    $fmt = fn ($d) => $d?->locale('pl')->translatedFormat('j F Y');
@endphp

@section('title', $article->metaTitle() . ' — ' . $blogName)
@section('meta_description', $article->metaDescription())
@if ($image)
    @section('og_image', url($image))
@endif

@include('blog::partials.feed-link')

@unless ($article->showsPlaceholder())
    @push('structured_data')
        <script type="application/ld+json">
            {!! json_encode(array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'BlogPosting',
                'headline' => \Illuminate\Support\Str::limit($article->title, 110, ''),
                'description' => $article->metaDescription(),
                'url' => $articleUrl,
                'mainEntityOfPage' => $articleUrl,
                'image' => $image ? url($image) : null,
                'datePublished' => $date?->toIso8601String(),
                'dateModified' => $article->updated_at?->toIso8601String(),
                'articleSection' => $article->category?->name,
                'keywords' => $article->tags->isNotEmpty() ? $article->tags->pluck('name')->join(', ') : null,
                'author' => $profiles->isNotEmpty()
                    ? $profiles->map(fn ($p) => array_filter(['@type' => 'Person', 'name' => $p->name, 'url' => $p->url(), 'jobTitle' => $p->role]))->values()->all()
                    : (filled($article->author_name) ? ['@type' => 'Person', 'name' => $article->author_name] : ['@type' => 'Organization', 'name' => $siteSettings->site_name]),
                'publisher' => array_filter(['@type' => 'Organization', 'name' => $siteSettings->site_name, 'logo' => $siteSettings->logoUrl() ? ['@type' => 'ImageObject', 'url' => $siteSettings->logoUrl()] : null]),
            ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
        </script>
    @endpush
@endunless

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => array_values(array_filter([
        ['label' => $blogName, 'url' => route('blog.index')],
        $article->category ? ['label' => $article->category->name, 'url' => $article->category->url()] : null,
        ['label' => $article->title, 'url' => null],
    ]))])
@endsection

@section('content')
    @if ($article->showsPlaceholder())
        @include('partials.unavailable-notice', ['entity' => $article, 'backUrl' => route('blog.index'), 'backLabel' => 'Wróć do wszystkich artykułów'])
    @else
    <style>
        /* Rozmiar tekstu wpisu (przyciski A A A) — skaluje tylko zajawkę i treść, nie tytuł ani menu. */
        .blog-portal-article { --bp-scale: 1; }
        .blog-portal-article.bp-size-large { --bp-scale: 1.2; }
        .blog-portal-article.bp-size-larger { --bp-scale: 1.4; }
        .blog-portal-article .blog-portal-lead { font-size: calc(1.25rem * var(--bp-scale)); }
        .blog-portal-article .blog-portal-body { font-size: calc(1.125rem * var(--bp-scale)); }
    </style>

    <div class="mx-auto max-w-6xl px-4 py-10">
        <div class="grid gap-10 lg:grid-cols-4">
            {{-- ── Lewa kolumna (3/4): artykuł ── --}}
            <article class="blog-portal-article bp-size-normal min-w-0 lg:col-span-3" aria-labelledby="blog-article-title"
                x-data="{
                    size: 'normal',
                    init() {
                        try {
                            const s = localStorage.getItem('feer-blog-text-size');
                            if (['normal', 'large', 'larger'].includes(s)) this.size = s;
                        } catch (e) {}
                    },
                    setSize(s) {
                        this.size = s;
                        try { localStorage.setItem('feer-blog-text-size', s); } catch (e) {}
                    },
                }"
                :class="'bp-size-' + size">
                @if ($article->wipIsNotice())
                    @include('partials.page-wip-notice', ['message' => $article->wipMessage(), 'heading' => 'Artykuł w przygotowaniu'])
                @endif

                <header>
                    @if ($article->category)
                        <p class="text-sm font-bold uppercase tracking-widest">
                            <a href="{{ $article->category->url() }}" class="text-brand-dark hover:underline hover:underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $article->category->name }}</a>
                        </p>
                    @endif
                    <h1 id="blog-article-title" class="mt-3 text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-4xl">{{ $article->title }}</h1>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-4 rounded-md bg-gray-50 px-4 py-3">
                        <div class="text-sm text-muted">
                            <p>
                                @if ($date)<time datetime="{{ $date->toDateString() }}" class="font-bold text-ink">{{ $fmt($date) }}</time>@endif
                                @if ($article->wasUpdated())
                                    · zaktualizowano <time datetime="{{ $article->updated_at->toDateString() }}">{{ $fmt($article->updated_at) }}</time>
                                @endif
                                · {{ $article->readingMinutes() }} min czytania
                            </p>
                            @if ($profiles->isNotEmpty() || filled($article->author_name))
                                <p class="mt-1 text-ink"><span class="text-muted">{{ $profiles->count() > 1 ? 'Autorzy:' : 'Autor:' }}</span> @include('blog::partials.authors-inline', ['article' => $article])</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-1 print:hidden" x-cloak role="group" aria-label="Rozmiar tekstu artykułu">
                            @foreach ($sizes as $key => $opt)
                                <button type="button" @click="setSize('{{ $key }}')"
                                    aria-pressed="{{ $key === 'normal' ? 'true' : 'false' }}"
                                    :aria-pressed="(size === '{{ $key }}').toString()"
                                    :class="size === '{{ $key }}' ? 'bg-ink text-white hover:bg-ink' : 'bg-white text-ink hover:bg-gray-200'"
                                    class="{{ $sizeBtn }} {{ $opt['class'] }}"
                                    title="{{ $opt['label'] }}">
                                    <span aria-hidden="true">A</span><span class="sr-only">{{ $opt['label'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    @if ($article->excerpt)
                        <p class="blog-portal-lead mt-6 font-bold leading-relaxed text-ink">{{ $article->excerpt }}</p>
                    @endif
                </header>

                @if ($image)
                    <figure class="mt-6">
                        <img src="{{ $image }}" alt="{{ $article->imageAlt() }}" class="aspect-[16/9] w-full rounded-md object-cover">
                    </figure>
                @endif

                @if (! empty($toc))
                    <div class="mt-6">@include('partials.page-toc', ['toc' => $toc, 'variant' => 'mobile'])</div>
                @endif

                <div class="blog-portal-body prose prose-lg mt-8 max-w-none text-ink">{!! $body !!}</div>

                @if ($article->tags->isNotEmpty())
                    <p class="mt-8 text-sm text-ink">
                        <span class="font-bold">Tagi:</span>
                        @foreach ($article->tags as $t)<a href="{{ $t->url() }}" class="{{ $link }}">{{ $t->name }}</a>@if (! $loop->last), @endif @endforeach
                    </p>
                @endif

                {{-- ===== UDOSTĘPNIJ — zwykłe odnośniki, bez skryptów serwisów społecznościowych ===== --}}
                <section class="mt-10 print:hidden" aria-labelledby="blog-share-heading"
                    x-data="{ copied: false, copy() { navigator.clipboard?.writeText(@js($articleUrl)).then(() => { this.copied = true; setTimeout(() => this.copied = false, 4000); }); } }">
                    <h2 id="blog-share-heading" class="mb-3 text-lg font-bold text-ink">Udostępnij</h2>
                    <ul class="flex flex-wrap gap-2">
                        <li>
                            <a href="mailto:?subject={{ rawurlencode($article->title) }}&amp;body={{ rawurlencode($articleUrl) }}" class="{{ $tool }}">
                                <i class="fa-solid fa-envelope" aria-hidden="true"></i> E-mail
                            </a>
                        </li>
                        <li>
                            <button type="button" x-cloak x-show="navigator.clipboard" @click="copy()" class="{{ $tool }}">
                                <i class="fa-solid fa-link" aria-hidden="true"></i> Kopiuj link
                            </button>
                        </li>
                        <li>
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($articleUrl) }}" target="_blank" rel="noopener noreferrer" class="{{ $tool }}">
                                <i class="fa-brands fa-facebook-f" aria-hidden="true"></i> Facebook<span class="sr-only"> (otwiera się w nowej karcie)</span>
                            </a>
                        </li>
                        <li>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ rawurlencode($articleUrl) }}" target="_blank" rel="noopener noreferrer" class="{{ $tool }}">
                                <i class="fa-brands fa-linkedin-in" aria-hidden="true"></i> LinkedIn<span class="sr-only"> (otwiera się w nowej karcie)</span>
                            </a>
                        </li>
                        <li>
                            <a href="https://x.com/intent/post?url={{ rawurlencode($articleUrl) }}&amp;text={{ rawurlencode($article->title) }}" target="_blank" rel="noopener noreferrer" class="{{ $tool }}">
                                <i class="fa-brands fa-x-twitter" aria-hidden="true"></i> X<span class="sr-only"> (otwiera się w nowej karcie)</span>
                            </a>
                        </li>
                    </ul>
                    <p class="mt-2 min-h-5 text-sm font-bold text-ink" aria-live="polite" x-text="copied ? 'Skopiowano link do schowka.' : ''"></p>
                </section>

                {{-- ===== AUTORZY ===== --}}
                @if ($profiles->isNotEmpty() || filled($article->author_name))
                    <section class="mt-10" aria-labelledby="blog-authors-heading">
                        <h2 id="blog-authors-heading" class="mb-4 text-lg font-bold text-ink">{{ $profiles->count() > 1 ? 'O autorach' : 'O autorze' }}</h2>
                        <div class="space-y-4">
                            @forelse ($profiles as $profile)
                                @include('blog::partials.author-box', ['author' => $profile, 'headingLevel' => 3])
                            @empty
                                <div class="rounded-md bg-gray-50 p-5">
                                    <h3 class="text-lg font-bold text-ink">{{ $article->author_name }}</h3>
                                    @if ($article->author_bio)
                                        <p class="mt-2 text-sm leading-relaxed text-ink">{{ $article->author_bio }}</p>
                                    @endif
                                </div>
                            @endforelse
                        </div>
                    </section>
                @endif

                {{-- ===== POPRZEDNI / NASTĘPNY ===== --}}
                @if ($previous || $next)
                    <nav class="mt-10 grid gap-4 sm:grid-cols-2 print:hidden" aria-label="Sąsiednie wpisy">
                        @if ($previous)
                            <a href="{{ route('blog.show', $previous) }}" rel="prev" class="group block rounded-md bg-gray-50 p-4 transition hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                <span class="block text-sm text-muted"><i class="fa-solid fa-arrow-left mr-1" aria-hidden="true"></i>Poprzedni wpis</span>
                                <span class="mt-1 block font-bold text-ink group-hover:underline group-hover:underline-offset-4">{{ $previous->title }}</span>
                            </a>
                        @else
                            <span></span>
                        @endif
                        @if ($next)
                            <a href="{{ route('blog.show', $next) }}" rel="next" class="group block rounded-md bg-gray-50 p-4 text-right transition hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                <span class="block text-sm text-muted">Następny wpis<i class="fa-solid fa-arrow-right ml-1" aria-hidden="true"></i></span>
                                <span class="mt-1 block font-bold text-ink group-hover:underline group-hover:underline-offset-4">{{ $next->title }}</span>
                            </a>
                        @endif
                    </nav>
                @endif
            </article>

            {{-- ── Prawa kolumna (1/4, przyklejona na desktopie, pod artykułem na telefonie) ── --}}
            <aside class="space-y-6 print:hidden lg:sticky lg:top-24 lg:self-start" aria-label="Kolumna boczna wpisu">
                @if (! empty($toc))
                    @include('partials.page-toc', ['toc' => $toc, 'variant' => 'desktop'])
                @endif

                @if ($article->category)
                    <nav class="rounded-md bg-gray-50 p-5" aria-labelledby="blog-category-more-heading">
                        <h2 id="blog-category-more-heading" class="mb-3 text-lg font-bold text-ink">Więcej w kategorii {{ $article->category->name }}</h2>
                        @if ($categoryArticles->isNotEmpty())
                            <ul class="space-y-1" role="list">
                                @foreach ($categoryArticles as $item)
                                    <li>
                                        <a href="{{ route('blog.show', $item) }}" class="{{ $navLink }}">
                                            <i class="fa-solid fa-chevron-right mt-1 text-xs text-brand-dark" aria-hidden="true"></i>
                                            <span>{{ $item->title }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-sm text-muted">Brak innych wpisów w tej kategorii.</p>
                        @endif
                        <p class="mt-4 text-sm">
                            <a href="{{ $article->category->url() }}" class="{{ $link }}">Wszystkie w kategorii {{ $article->category->name }}</a>
                        </p>
                    </nav>
                @endif

                @include('blog::partials.search-form', ['formId' => 'blog-search-article', 'q' => ''])

                @if (! empty($newsletter))
                    @include('blog::partials.newsletter', ['newsletter' => $newsletter, 'headingId' => 'blog-article-newsletter-heading'])
                @endif

                <p class="px-1 text-sm"><a href="{{ route('blog.index') }}" class="{{ $link }}">Wszystkie wpisy {{ $blogName }}</a></p>
            </aside>
        </div>

        {{-- ── Zobacz także: pełna szerokość pod obiema kolumnami ── --}}
        @if ($related->isNotEmpty())
            <section class="mt-16 print:hidden" aria-labelledby="blog-related-heading">
                <h2 id="blog-related-heading" class="mb-6 text-2xl font-bold text-ink">Zobacz także</h2>
                <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4" role="list">
                    @foreach ($related as $item)
                        @php $itemImg = $item->imageUrl(); $itemDate = $item->publishedDate(); @endphp
                        <li>
                            <article class="group relative flex h-full flex-col overflow-hidden rounded-md bg-gray-50 transition hover:bg-gray-100 focus-within:ring-2 focus-within:ring-brand-dark">
                                @if ($itemImg)
                                    <img src="{{ $itemImg }}" alt="" loading="lazy" class="aspect-[16/10] w-full object-cover">
                                @endif
                                <div class="flex flex-1 flex-col p-4">
                                    <h3 class="text-base font-bold leading-snug text-ink">
                                        <a href="{{ route('blog.show', $item) }}" class="stretched-link group-hover:text-brand-dark group-hover:underline focus-visible:outline-none">{{ $item->title }}</a>
                                    </h3>
                                    @if ($itemDate)
                                        <p class="mt-auto pt-3 text-sm text-muted">
                                            <time datetime="{{ $itemDate->toDateString() }}">{{ $itemDate->format('d.m.Y') }}</time>
                                        </p>
                                    @endif
                                </div>
                            </article>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- ── Komentarze: pełna szerokość ── --}}
        <div class="max-w-3xl">
            {{-- ===== KOMENTARZE ===== --}}
            @php $commentCount = $article->approvedComments->count(); @endphp
            <section id="komentarze" class="mt-12" aria-labelledby="blog-comments-heading">
                <h2 id="blog-comments-heading" class="mb-6 text-2xl font-bold text-ink">
                    Komentarze <span class="text-base font-normal text-muted">({{ $commentCount }})</span>
                </h2>

                @if (session('comment_status'))
                    <p class="mb-6 flex items-start gap-2 rounded-md bg-green-50 px-4 py-3 text-sm text-green-900" role="status">
                        <i class="fa-solid fa-circle-check mt-0.5" aria-hidden="true"></i>
                        <span>{{ session('comment_status') }}</span>
                    </p>
                @endif

                @if ($commentCount === 0)
                    <p class="mb-8 text-sm text-ink">Brak komentarzy. Bądź pierwszą osobą, która skomentuje ten artykuł.</p>
                @else
                    <ul class="mb-10 space-y-5">
                        @foreach ($article->approvedComments as $comment)
                            <li class="rounded-md bg-gray-50 p-4">
                                <p class="mb-1 flex flex-wrap items-baseline justify-between gap-2">
                                    <span class="font-bold text-ink">{{ $comment->author_name }}</span>
                                    <time datetime="{{ $comment->created_at->toIso8601String() }}" class="text-xs text-muted">{{ $comment->created_at->locale('pl')->translatedFormat('j F Y, H:i') }}</time>
                                </p>
                                <p class="whitespace-pre-line text-sm text-ink">{{ $comment->body }}</p>

                                @if ($comment->replies->isNotEmpty())
                                    <ul class="mt-4 space-y-3 sm:ml-6">
                                        @foreach ($comment->replies as $reply)
                                            <li class="rounded-md bg-brand-light p-4">
                                                <p class="mb-1 flex flex-wrap items-baseline justify-between gap-2">
                                                    <span class="font-bold text-ink">
                                                        @if ($reply->is_staff_reply)<i class="fa-solid fa-reply mr-1 text-brand-dark" aria-hidden="true"></i>{{ \Modules\Blog\Models\BlogComment::STAFF_LABEL }}@else{{ $reply->author_name }}@endif
                                                    </span>
                                                    <time datetime="{{ $reply->created_at->toIso8601String() }}" class="text-xs text-ink">{{ $reply->created_at->locale('pl')->translatedFormat('j F Y, H:i') }}</time>
                                                </p>
                                                <p class="whitespace-pre-line text-sm text-ink">{{ $reply->body }}</p>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                {{-- Formularz dodania komentarza --}}
                <div id="dodaj-komentarz" class="rounded-md bg-gray-50 p-6">
                    <h3 class="mb-2 text-lg font-bold text-ink">Dodaj komentarz</h3>
                    <p class="mb-4 text-sm text-ink">Komentarz pojawi się po zatwierdzeniu przez moderatora. Pola oznaczone gwiazdką są wymagane.</p>

                    <form method="POST" action="{{ route('blog.comments.store', $article) }}" class="space-y-4" novalidate>
                        @csrf

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="author_name" class="mb-1 block text-sm font-bold text-ink">Imię lub podpis <span aria-hidden="true">*</span></label>
                                <input type="text" id="author_name" name="author_name" value="{{ old('author_name') }}" required aria-required="true" maxlength="100" autocomplete="name"
                                    @error('author_name') aria-invalid="true" aria-describedby="author_name-error" @enderror class="{{ $field }}">
                                @error('author_name') <p id="author_name-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="email" class="mb-1 block text-sm font-bold text-ink">E-mail <span class="font-normal">(opcjonalnie, nie publikujemy)</span></label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email"
                                    @error('email') aria-invalid="true" aria-describedby="email-error" @enderror class="{{ $field }}">
                                @error('email') <p id="email-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="body" class="mb-1 block text-sm font-bold text-ink">Komentarz <span aria-hidden="true">*</span></label>
                            <textarea id="body" name="body" rows="4" required aria-required="true" maxlength="3000"
                                @error('body') aria-invalid="true" aria-describedby="body-error" @enderror class="{{ $field }}">{{ old('body') }}</textarea>
                            @error('body') <p id="body-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                        </div>

                        @include('partials.spam-protection', ['spamScope' => 'blog-comment'])

                        <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-md bg-brand-dark px-5 text-sm font-bold text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                            Wyślij komentarz
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>
    @endif
@endsection
