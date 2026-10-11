@php $substackPosts ??= []; @endphp

@if ($siteSettings->substack_url && ! empty($substackPosts))
    <section class="mx-auto max-w-6xl px-4 py-12" aria-labelledby="home-substack-heading">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 id="home-substack-heading" class="text-2xl font-bold text-ink">O tym piszemy</h2>
                <p class="mt-1 text-sm text-muted">Najnowsze wpisy z naszego bloga na Substacku.</p>
            </div>
            <a href="{{ $siteSettings->substack_url }}" target="_blank" rel="noopener"
                class="inline-flex items-center gap-2 text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                Zobacz wszystkie <i class="fa-solid fa-arrow-right" aria-hidden="true"></i><span class="sr-only"> (otwiera się w nowej karcie)</span>
            </a>
        </div>

        {{-- Płaskie kafle na szarym tle (bez ramek, cieni i unoszenia). --}}
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($substackPosts as $post)
                <a href="{{ $post['url'] }}" target="_blank" rel="noopener"
                    class="group flex flex-col overflow-hidden rounded-md bg-gray-50 transition-colors hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                    <div class="aspect-video overflow-hidden bg-gray-100">
                        @if ($post['image'])
                            <img src="{{ $post['image'] }}" alt="" loading="lazy" class="h-full w-full object-cover">
                        @else
                            <span class="flex h-full w-full items-center justify-center text-4xl text-muted" aria-hidden="true"><i class="fa-solid fa-pen-nib"></i></span>
                        @endif
                    </div>
                    <div class="flex flex-1 flex-col p-4">
                        @if ($post['date'])
                            <p class="mb-1 text-xs font-bold uppercase tracking-wide text-muted">{{ $post['date']->translatedFormat('d MMMM Y') }}</p>
                        @endif
                        <h3 class="mb-1 font-bold text-ink group-hover:text-brand-dark group-hover:underline group-hover:underline-offset-4">{{ $post['title'] }}</h3>
                        @if ($post['excerpt'])
                            <p class="line-clamp-3 text-sm text-muted">{{ $post['excerpt'] }}</p>
                        @endif
                        <span class="sr-only">(otwiera się w nowej karcie)</span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif
