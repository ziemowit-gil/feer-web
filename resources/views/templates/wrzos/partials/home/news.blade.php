@if ($newsItems->isNotEmpty())
<section class="py-14" aria-labelledby="wrzos-news-heading">
    <div class="mx-auto max-w-[1400px] px-4">
        <div class="mb-6 flex items-end justify-between border-b border-gray-200 pb-3">
            <h2 id="wrzos-news-heading" class="border-t-2 border-brand pt-2 text-xl font-extrabold text-ink">
                Aktualności
            </h2>
            @if ($siteSettings->isModuleEnabled('news'))
                <a href="{{ route('news.index') }}" class="hidden text-sm font-bold text-brand hover:text-brand-dark sm:block">
                    Więcej aktualności
                </a>
            @endif
        </div>

        <div class="grid gap-x-6 gap-y-8 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($newsItems as $item)
                <article>
                    <a href="{{ route('news.show', $item) }}" class="group block focus-visible:outline-none">
                        <div class="mb-3 aspect-[4/3] overflow-hidden rounded-md bg-gray-100">
                            @if ($item->image_url ?? null)
                                <img src="{{ $item->image_url }}" alt="" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            @else
                                <div class="flex h-full w-full items-center justify-center">
                                    <i class="fa-solid fa-newspaper text-3xl text-gray-300" aria-hidden="true"></i>
                                </div>
                            @endif
                        </div>
                        <h3 class="mb-1.5 text-base font-bold leading-snug text-ink group-hover:text-brand group-focus-visible:text-brand">
                            {{ $item->title }}
                        </h3>
                        @if ($item->excerpt)
                            <p class="text-sm leading-relaxed text-muted">{{ \Illuminate\Support\Str::limit($item->excerpt, 110) }}</p>
                        @endif
                    </a>
                </article>
            @endforeach
        </div>

        @if ($siteSettings->isModuleEnabled('news'))
            <div class="mt-8 text-center sm:hidden">
                <a href="{{ route('news.index') }}" class="text-sm font-bold text-brand hover:text-brand-dark">Więcej aktualności</a>
            </div>
        @endif
    </div>
</section>
@endif
