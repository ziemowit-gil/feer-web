@extends('layouts.site')

@section('title', 'Aktualności — ' . $siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Aktualności', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-12">
        <h1 class="mb-8 text-3xl font-bold text-ink">Aktualności</h1>

        <div class="grid gap-8 md:grid-cols-[14rem_1fr] lg:grid-cols-[16rem_1fr]">
            <aside class="flex flex-col gap-4 md:sticky md:top-4 md:self-start">
                <div class="rounded-xl border border-gray-200 bg-white p-3">
                    @include('partials.news-category-picker', ['categories' => $categories, 'active' => $activeCategory, 'baseUrl' => route('news.index')])
                </div>
                <x-banner-zone name="sidebar" />
            </aside>

            <div>
                @if (($siteSettings->news_layout ?? 'grid') === 'list')
                    {{-- ── Widok lista ── --}}
                    <ul class="flex flex-col divide-y divide-gray-100" role="list">
                        @forelse ($news as $item)
                            @php
                                $ngoAccent = $item->accent_color ?: (($item->audience ?? 'brand') === 'ngo' ? $siteSettings->audienceColor('ngo') : null);
                                $img = $item->imageUrlOrDefault();
                            @endphp
                            <li @class([
                                'group py-5 first:pt-0 last:pb-0',
                                'rounded-xl border-l-4 border-amber-400 bg-amber-50/50 px-4 py-5' => $item->is_featured,
                            ])>
                                <article class="flex items-start gap-4">
                                    <div class="hidden w-16 shrink-0 text-center sm:block" aria-hidden="true">
                                        <span class="block text-2xl font-bold leading-none text-ink">{{ $item->published_at->format('d') }}</span>
                                        <span class="block text-xs uppercase tracking-wide text-muted">{{ $item->published_at->locale('pl')->isoFormat('MMM') }}</span>
                                        <span class="block text-xs text-muted">{{ $item->published_at->format('Y') }}</span>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <div class="mb-1 flex flex-wrap items-center gap-2">
                                            @if ($item->category)
                                                <span class="inline-block rounded px-2 py-0.5 text-xs font-bold uppercase text-white"
                                                    style="background-color: {{ $item->category->badgeColor() }}">
                                                    {{ $item->category->name }}
                                                </span>
                                            @endif
                                            @if ($item->is_featured)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-400/20 px-2 py-0.5 text-xs font-bold text-amber-700">
                                                    <i class="fa-solid fa-star" aria-hidden="true"></i> Wyróżnione
                                                </span>
                                            @endif
                                            <time datetime="{{ $item->published_at->toDateString() }}"
                                                class="text-xs text-muted sm:hidden">
                                                {{ $item->published_at->format('d.m.Y') }}
                                            </time>
                                        </div>

                                        <h2 class="font-bold text-ink">
                                            <a href="{{ route('news.show', $item) }}"
                                                @if ($ngoAccent) style="text-decoration-color: {{ $ngoAccent }}" @endif
                                                class="hover:underline focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current {{ $ngoAccent ? '' : 'hover:text-brand' }}">
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

                                    @if ($img)
                                        <a href="{{ route('news.show', $item) }}" class="hidden shrink-0 overflow-hidden rounded-lg lg:block" tabindex="-1" aria-hidden="true">
                                            <img src="{{ $img }}" alt=""
                                                class="h-16 w-24 object-cover transition group-hover:scale-105">
                                        </a>
                                    @endif
                                </article>
                            </li>
                        @empty
                            @if ($activeCategory)
                                <li class="py-5 text-muted">
                                    Brak aktualności w kategorii „{{ $activeCategory->name }}".
                                    <a href="{{ route('news.index') }}" class="font-bold text-brand hover:text-brand-dark">Zobacz wszystkie →</a>
                                </li>
                            @else
                                <li class="py-5 text-muted">Brak opublikowanych newsów.</li>
                            @endif
                        @endforelse
                    </ul>
                @elseif (($siteSettings->news_layout ?? 'grid') === 'cards')
                    {{-- ── Widok karty 3-kolumnowe (magazynowy) ── --}}
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @forelse ($news as $item)
                            @php $img = $item->imageUrlOrDefault(); @endphp
                            <article class="group flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
                                <a href="{{ route('news.show', $item) }}" class="block overflow-hidden" tabindex="-1" aria-hidden="true">
                                    @if ($img)
                                        <img src="{{ $img }}" alt="" class="h-48 w-full object-cover transition group-hover:scale-105">
                                    @else
                                        <div class="h-48 w-full bg-brand-light"></div>
                                    @endif
                                </a>

                                <div class="flex flex-1 flex-col p-4">
                                    <div class="mb-2 flex items-center justify-between text-xs text-muted">
                                        <span class="flex items-center gap-1">
                                            <i class="fa-regular fa-clock text-brand" aria-hidden="true"></i>
                                            <time datetime="{{ $item->published_at->toDateString() }}">
                                                {{ $item->published_at->format('d - m - Y') }}
                                            </time>
                                        </span>
                                        <i class="fa-solid fa-rss opacity-40" aria-hidden="true"></i>
                                    </div>

                                    <h3 class="mb-2 font-bold leading-snug text-ink">
                                        <a href="{{ route('news.show', $item) }}"
                                            class="hover:text-brand focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                            {{ $item->title }}
                                        </a>
                                    </h3>

                                    @if ($item->excerpt)
                                        <p class="mb-4 line-clamp-3 text-sm text-muted">{{ $item->excerpt }}</p>
                                    @endif

                                    <div class="mt-auto">
                                        <a href="{{ route('news.show', $item) }}"
                                            aria-label="Czytaj więcej: {{ $item->title }}"
                                            class="inline-block rounded border border-gray-300 px-5 py-2 text-xs font-bold uppercase tracking-wide text-ink transition hover:border-brand hover:text-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                                            Czytaj więcej
                                        </a>
                                    </div>
                                </div>
                            </article>
                        @empty
                            @if ($activeCategory)
                                <p class="text-muted sm:col-span-3">Brak aktualności w kategorii „{{ $activeCategory->name }}". <a href="{{ route('news.index') }}" class="font-bold text-brand hover:text-brand-dark">Zobacz wszystkie →</a></p>
                            @else
                                <p class="text-muted sm:col-span-3">Brak opublikowanych newsów.</p>
                            @endif
                        @endforelse
                    </div>
                @else
                    {{-- ── Widok siatka (domyślny) ── --}}
                    <div class="grid gap-8 sm:grid-cols-2">
                        @forelse ($news as $item)
                            @php $ngoAccent = $item->accent_color ?: (($item->audience ?? 'brand') === 'ngo' ? $siteSettings->audienceColor('ngo') : null); @endphp
                            <a href="{{ route('news.show', $item) }}"
                                @class([
                                    'group block',
                                    'rounded-xl border-2 border-amber-400 bg-amber-50/50 p-3' => $item->is_featured,
                                    'rounded-xl border-2 p-3' => $ngoAccent && ! $item->is_featured,
                                ])
                                @if ($ngoAccent && ! $item->is_featured) style="border-color: {{ $ngoAccent }};" @endif>
                                @php $img = $item->imageUrlOrDefault(); @endphp
                                @if ($img)
                                    <div class="relative mb-3 h-44 overflow-hidden rounded-lg bg-gray-100">
                                        <img src="{{ $img }}" alt="" class="h-full w-full object-cover transition group-hover:scale-105">
                                        @if ($item->category)
                                            <span class="absolute left-3 top-3 rounded px-2 py-1 text-xs font-bold uppercase text-white" style="background-color: {{ $item->category->badgeColor() }}">{{ $item->category->name }}</span>
                                        @endif
                                    </div>
                                @endif
                                @if ($item->is_featured)
                                    <span class="mb-1 inline-flex items-center gap-1 rounded-full bg-amber-400/20 px-2 py-0.5 text-xs font-bold text-amber-700">
                                        <i class="fa-solid fa-star" aria-hidden="true"></i> Wyróżnione
                                    </span>
                                @endif
                                <div class="text-xs font-medium text-muted">{{ $item->published_at->format('d.m.Y') }}</div>
                                <h3 class="mt-1 font-bold text-ink group-hover:text-brand">{{ $item->title }}</h3>
                                @if ($item->excerpt)
                                    <p class="mt-1 text-sm text-muted">{{ $item->excerpt }}</p>
                                @endif
                                <span class="mt-2 inline-block text-sm font-bold text-brand" aria-hidden="true">Czytaj więcej →</span>
                            </a>
                        @empty
                            @if ($activeCategory)
                                <p class="text-muted sm:col-span-2">Brak aktualności w kategorii „{{ $activeCategory->name }}". <a href="{{ route('news.index') }}" class="font-bold text-brand hover:text-brand-dark">Zobacz wszystkie →</a></p>
                            @else
                                <p class="text-muted sm:col-span-2">Brak opublikowanych newsów.</p>
                            @endif
                        @endforelse
                    </div>
                @endif

                <div class="mt-10">
                    {{ $news->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
