{{-- Aktualności szablonu "vm": cztery karty (zdjęcie, data, tytuł wersalikami) i przycisk „Czytaj więcej". --}}
@if ($newsItems->isNotEmpty())
<section class="py-14" aria-labelledby="vm-news-heading">
    <div class="mx-auto max-w-[1200px] px-4">
        <h2 id="vm-news-heading" class="vm-display vm-section-title mb-10 text-2xl text-ink sm:text-3xl lg:text-4xl">Aktualności</h2>

        <ul class="grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-4" role="list">
            @foreach ($newsItems as $item)
                @php $image = $item->imageUrlOrDefault(); @endphp
                <li>
                    <article class="group relative flex h-full flex-col">
                        <div class="mb-3 aspect-[4/3] overflow-hidden bg-gray-100">
                            @if ($image)
                                <img src="{{ $image }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            @else
                                <div class="flex h-full w-full items-center justify-center" aria-hidden="true">
                                    <i class="fa-solid fa-newspaper text-4xl text-gray-300"></i>
                                </div>
                            @endif
                        </div>
                        @if ($item->published_at)
                            <p class="mb-2 text-xs font-semibold uppercase text-muted">
                                <time datetime="{{ $item->published_at->toDateString() }}">{{ $item->published_at->translatedFormat('d F Y') }}</time>
                            </p>
                        @endif
                        <h3 class="vm-display text-lg leading-snug text-ink">
                            <a href="{{ route('news.show', $item) }}"
                               class="rounded after:absolute after:inset-0 group-hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">{{ $item->title }}</a>
                        </h3>
                    </article>
                </li>
            @endforeach
        </ul>

        <div class="mt-10 text-center">
            @include('templates.vm.partials.home.more-button', ['url' => route('news.index'), 'label' => 'Czytaj więcej', 'context' => 'aktualności'])
        </div>
    </div>
</section>
@endif
