{{-- Widżet portalu: aktualności z działań kategorii — wyróżniona po lewej, pozostałe w kolumnie obok (odstępy zamiast linii). --}}
@php $lead = $news->first(); $rest = $news->slice(1); @endphp
@if ($lead)
    <section aria-labelledby="portal-news">
        <h2 id="portal-news" class="text-xl font-bold leading-tight text-ink md:text-2xl">{{ $category->portalWidgetTitle('news') }}</h2>
        <span class="mt-2 block h-1 w-12 bg-brand" aria-hidden="true"></span>
        <div class="mt-6 grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <article class="grid gap-6 md:grid-cols-2">
                @if ($img = $lead->imageUrlOrDefault())
                    <img src="{{ $img }}" alt="{{ $lead->image_alt ?? '' }}" class="aspect-[4/3] w-full rounded-md object-cover" loading="lazy">
                @endif
                <div>
                    <p class="text-sm text-ink"><time datetime="{{ $lead->published_at->toDateString() }}">{{ $lead->published_at->translatedFormat('j F Y') }}</time></p>
                    <h3 class="mt-1 text-xl font-bold leading-snug text-ink md:text-2xl">
                        <a href="{{ route('news.show', $lead) }}" class="hover:text-brand-dark hover:underline hover:underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $lead->title }}</a>
                    </h3>
                    @php $excerpt = trim(html_entity_decode(strip_tags((string) ($lead->excerpt ?: $lead->content)), ENT_QUOTES | ENT_HTML5)); @endphp
                    @if ($excerpt !== '')
                        <p class="mt-3 line-clamp-5 leading-relaxed text-ink">{{ \Illuminate\Support\Str::limit($excerpt, 360) }}</p>
                    @endif
                    <p class="mt-3"><a href="{{ route('news.show', $lead) }}" class="{{ $more }}">Pełna treść<span class="sr-only">: {{ $lead->title }}</span> <span aria-hidden="true">→</span></a></p>
                </div>
            </article>

            <div>
                @if ($rest->isNotEmpty())
                    <ul role="list" class="space-y-5">
                        @foreach ($rest as $item)
                            <li>
                                <a href="{{ route('news.show', $item) }}" class="group block rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                    <span class="block text-sm text-ink"><time datetime="{{ $item->published_at->toDateString() }}">{{ $item->published_at->translatedFormat('j F Y') }}</time></span>
                                    <span class="mt-0.5 block font-bold leading-snug text-ink group-hover:text-brand-dark group-hover:underline group-hover:underline-offset-4">{{ $item->title }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
                @if (Route::has('news.index'))
                    <p class="mt-4"><a href="{{ route('news.index') }}" class="{{ $more }}">Wszystkie aktualności <span aria-hidden="true">→</span></a></p>
                @endif
            </div>
        </div>
    </section>
@endif
