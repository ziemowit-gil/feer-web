@if ($newsItems->isNotEmpty())
<section class="py-16" aria-labelledby="federation-news-heading">
    <div class="mx-auto max-w-[1400px] px-4">
        <h2 id="federation-news-heading" class="mb-10 text-center text-3xl font-extrabold tracking-tight text-ink">
            Aktualności
        </h2>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($newsItems as $item)
                <article class="flex flex-col overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-100 transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex h-40 items-center justify-center bg-gray-100">
                        @if ($item->image_url ?? null)
                            <img src="{{ $item->image_url }}" alt="" class="h-full w-full object-cover">
                        @else
                            <i class="fa-solid fa-newspaper text-3xl text-gray-300" aria-hidden="true"></i>
                        @endif
                    </div>
                    <div class="flex flex-1 flex-col gap-3 p-6">
                        <time datetime="{{ $item->published_at?->toDateString() }}" class="text-xs font-bold uppercase tracking-wide text-muted">
                            {{ $item->published_at?->translatedFormat('d.m.Y') }}
                        </time>
                        <h3 class="text-base font-bold leading-snug text-ink">{{ $item->title }}</h3>
                        @if ($item->excerpt)
                            <p class="flex-1 text-sm leading-relaxed text-muted">{{ \Illuminate\Support\Str::limit($item->excerpt, 140) }}</p>
                        @endif
                        <a href="{{ route('news.show', $item) }}"
                            class="mt-2 inline-flex w-fit items-center gap-1.5 rounded-md bg-brand px-4 py-2 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                            Więcej
                        </a>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-10 text-center">
            <a href="{{ route('news.index') }}"
                class="inline-flex items-center gap-2 rounded-md border-2 border-ink px-6 py-2.5 text-sm font-bold text-ink transition hover:bg-ink hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2">
                Wszystkie aktualności
            </a>
        </div>
    </div>
</section>
@endif
