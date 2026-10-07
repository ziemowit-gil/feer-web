@extends('layouts.site')

@section('title', 'Aktualności — ' . $siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Aktualności', 'url' => null],
    ]])
@endsection

{{--
    Aktualności w układzie FEER: jasny nagłówek, poziomy pasek kategorii (kwadratowe „pigułki", aktywna ciemna),
    na pierwszej stronie wyróżniony najnowszy wpis (zdjęcie + tekst obok), pod nim płaskie karty w trzech kolumnach.
    Bez ramek i cieni. Kontrast: aktywna kategoria biała na #1D1D1A (16,9:1), kategorie wpisów brand-dark na szarym/białym.
--}}
@section('content')
    @php
        $featured = $news->onFirstPage() ? $news->first() : null;
        $rest = $featured ? $news->slice(1) : $news;
        $pill = 'inline-flex min-h-11 items-center rounded-md px-4 text-sm font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2';
    @endphp

    <section class="bg-gray-50">
        <div class="mx-auto max-w-6xl px-4 py-12 md:py-16">
            <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-5xl">Aktualności</h1>
            @if ($activeCategory)
                <p class="mt-3 text-lg text-ink">Kategoria: <strong>{{ $activeCategory->name }}</strong></p>
            @endif
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-10">
        <nav aria-label="Kategorie aktualności" class="mb-10">
            <ul class="flex flex-wrap gap-2" role="list">
                <li>
                    <a href="{{ site_route('news.index') }}" @unless ($activeCategory) aria-current="page" @endunless
                       class="{{ $pill }} {{ $activeCategory ? 'bg-gray-100 text-ink hover:bg-gray-200' : 'bg-ink text-white' }}">Wszystkie</a>
                </li>
                @foreach ($categories as $category)
                    @php $isActive = $activeCategory && $activeCategory->id === $category->id; @endphp
                    <li>
                        <a href="{{ site_route('news.index') }}?kategoria={{ $category->slug }}" @if ($isActive) aria-current="page" @endif
                           class="{{ $pill }} {{ $isActive ? 'bg-ink text-white' : 'bg-gray-100 text-ink hover:bg-gray-200' }}">{{ $category->name }}</a>
                    </li>
                @endforeach
                <li><a href="{{ route('news.archiwum') }}" class="{{ $pill }} text-brand-dark underline underline-offset-4 hover:no-underline">Archiwum →</a></li>
            </ul>
        </nav>

        @if ($news->isEmpty())
            <p class="text-muted">
                @if ($activeCategory)
                    Brak aktualności w kategorii „{{ $activeCategory->name }}".
                    <a href="{{ site_route('news.index') }}" class="font-bold text-brand-dark underline underline-offset-4 hover:no-underline">Zobacz wszystkie →</a>
                @else
                    Brak opublikowanych newsów.
                @endif
            </p>
        @else
            {{-- Wyróżniony najnowszy wpis --}}
            @if ($featured)
                @php $fImg = $featured->imageUrlOrDefault(); @endphp
                <article class="group relative mb-12 grid items-center gap-8 {{ $fImg ? 'lg:grid-cols-2' : '' }}">
                    @if ($fImg)
                        <img src="{{ $fImg }}" alt="" class="aspect-[16/10] w-full rounded-lg object-cover">
                    @endif
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-widest text-brand-dark">
                            {{ $featured->category?->name ?? 'Aktualności' }}
                            <span class="font-medium text-muted"> · <time datetime="{{ $featured->published_at->toDateString() }}">{{ $featured->published_at->translatedFormat('j F Y') }}</time></span>
                        </p>
                        <h2 class="mt-3 text-2xl font-extrabold leading-tight text-ink md:text-4xl">
                            <a href="{{ site_route('news.show', $featured) }}" class="stretched-link group-hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">{{ $featured->title }}</a>
                        </h2>
                        @if ($featured->excerpt)
                            <p class="mt-4 line-clamp-4 text-lg leading-relaxed text-muted">{{ $featured->excerpt }}</p>
                        @endif
                        <p class="mt-5 text-sm font-bold text-brand-dark" aria-hidden="true">Czytaj więcej →</p>
                        @include('news._legacy-badge', ['item' => $featured])
                    </div>
                </article>
            @endif

            {{-- Pozostałe wpisy --}}
            @if ($rest->isNotEmpty())
                <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" role="list">
                    @foreach ($rest as $item)
                        @php $img = $item->imageUrlOrDefault(); @endphp
                        <li>
                            <article class="group relative flex h-full flex-col overflow-hidden rounded-lg bg-gray-50 focus-within:ring-2 focus-within:ring-brand">
                                @if ($img)
                                    <img src="{{ $img }}" alt="" loading="lazy" class="aspect-[16/10] w-full object-cover transition duration-500 group-hover:scale-[1.03]">
                                @endif
                                <div class="flex flex-1 flex-col p-5">
                                    <p class="text-xs font-bold uppercase tracking-widest text-brand-dark">
                                        {{ $item->category?->name ?? 'Aktualności' }}
                                        <span class="font-medium text-muted"> · <time datetime="{{ $item->published_at->toDateString() }}">{{ $item->published_at->format('d.m.Y') }}</time></span>
                                    </p>
                                    <h3 class="mt-2 text-lg font-bold leading-snug text-ink group-hover:text-brand-dark">
                                        <a href="{{ site_route('news.show', $item) }}" class="stretched-link focus-visible:outline-none">{{ $item->title }}</a>
                                    </h3>
                                    @if ($item->excerpt)
                                        <p class="mt-2 line-clamp-3 flex-1 text-sm leading-relaxed text-muted">{{ $item->excerpt }}</p>
                                    @endif
                                    @include('news._legacy-badge', ['item' => $item])
                                </div>
                            </article>
                        </li>
                    @endforeach
                </ul>
            @endif
        @endif

        <div class="mt-10">{{ $news->links() }}</div>
    </div>
@endsection
