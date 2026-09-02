{{--
    Sekcja: Siatka aktualności (duże karty + lista).
    Dane: $newsGrid (kolekcja News, max 8).
--}}
@if ($siteSettings->isModuleEnabled('news') && $newsGrid->isNotEmpty())
<section id="aktualnosci" class="bg-gray-50 py-10" aria-label="Aktualności">
    <div class="mx-auto max-w-[1400px] px-4">

        <h2 class="mb-8 text-center text-3xl font-extrabold text-ink">Aktualności</h2>

        {{-- Górna sekcja: max 4 duże karty z obrazkiem --}}
        @php $featured = $newsGrid->take(4); $rest = $newsGrid->skip(4); @endphp

        @if ($featured->isNotEmpty())
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($featured as $item)
                    <a href="{{ site_route('news.show', $item) }}"
                        class="group block overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100 transition hover:shadow-md hover:ring-brand/20">
                        @if ($img = $item->imageUrlOrDefault())
                            <div class="aspect-[4/3] overflow-hidden bg-gray-100">
                                <img src="{{ $img }}" alt="{{ $item->title }}"
                                    class="h-full w-full object-cover transition group-hover:scale-105">
                            </div>
                        @else
                            <div class="aspect-[4/3] bg-brand/10 flex items-center justify-center">
                                <i class="fa-solid fa-newspaper text-3xl text-brand/40" aria-hidden="true"></i>
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Dolna sekcja: lista (4 dalsze aktualności) --}}
        @if ($rest->isNotEmpty())
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($rest as $item)
                    <a href="{{ site_route('news.show', $item) }}"
                        class="group block overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100 transition hover:shadow-md hover:ring-brand/20">
                        @if ($img = $item->imageUrlOrDefault())
                            <div class="h-36 overflow-hidden bg-gray-100">
                                <img src="{{ $img }}" alt="" class="h-full w-full object-cover group-hover:scale-105 transition">
                            </div>
                        @endif
                        <div class="p-3">
                            <div class="flex items-center gap-1 text-[0.65rem] text-muted">
                                <i class="bi bi-clock" aria-hidden="true"></i>
                                <time datetime="{{ $item->published_at->toDateString() }}">
                                    {{ $item->published_at->format('d - m - Y') }}
                                </time>
                            </div>
                            <p class="mt-1 text-sm font-bold text-ink line-clamp-2 group-hover:text-brand transition">
                                {{ $item->title }}
                            </p>
                            <span class="mt-1 block text-xs font-bold text-brand" aria-hidden="true">&rsaquo;</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

    </div>
</section>
@endif
