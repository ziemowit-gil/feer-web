{{--
    „Zobacz także w dziale" pod treścią domyślnego widoku strony (page/show):
    do 4 kart rodzeństwa strony — tytuł (link) i krótki fragment treści.
    Płasko: szare karty bez ramek, cała karta klikalna (stretched-link), fokus pierścieniem.
    Zmienna: $items (kolekcja Page, bez bieżącej strony).
--}}
@php
    $seeAlsoExcerpt = fn (\App\Models\Page $p): string => \Illuminate\Support\Str::limit(
        trim(preg_replace('/\s+/u', ' ', strip_tags(str_replace('<', ' <', (string) $p->content)))),
        120
    );
@endphp

<section class="mt-16 print:hidden" aria-labelledby="page-see-also-heading">
    <h2 id="page-see-also-heading" class="mb-6 text-2xl font-bold text-ink">Zobacz także w dziale</h2>
    <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4" role="list">
        @foreach ($items as $item)
            @php $excerpt = $seeAlsoExcerpt($item); @endphp
            <li>
                <article class="group relative flex h-full flex-col rounded-md bg-gray-50 p-4 transition hover:bg-gray-100 focus-within:ring-2 focus-within:ring-brand-dark">
                    <h3 class="text-base font-bold leading-snug">
                        <a href="{{ $item->publicUrl() }}" class="stretched-link text-brand-dark underline underline-offset-4 group-hover:no-underline focus-visible:outline-none">{{ $item->title }}</a>
                    </h3>
                    @if ($excerpt !== '')
                        <p class="mt-2 text-sm leading-relaxed text-muted">{{ $excerpt }}</p>
                    @endif
                </article>
            </li>
        @endforeach
    </ul>
</section>
