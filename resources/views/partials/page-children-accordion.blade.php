{{--
    Podstrony działu jako rozwijane sekcje (akordeon) pod treścią strony nadrzędnej.
    Natywne <details>/<summary> — obsługa klawiatury i czytników ekranu bez JS. Zmienne: $children (kolekcja Page), $page (strona nadrzędna).
--}}
@php $accId = 'acc-pages-'.$page->id; @endphp
<section class="not-prose my-8" aria-label="Podstrony: {{ $page->title }}">
    <div class="divide-y divide-gray-300 border-y border-gray-300">
        @foreach ($children as $child)
            <details class="group" name="{{ $accId }}">
                <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-4 py-3 text-lg font-bold text-ink hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 [&::-webkit-details-marker]:hidden">
                    <span>{{ $child->title }}</span>
                    <i class="fa-solid fa-chevron-down flex-none text-sm transition-transform motion-reduce:transition-none group-open:rotate-180" aria-hidden="true"></i>
                </summary>
                <div class="pb-5 pr-8 text-base leading-relaxed text-ink">
                    <div class="prose max-w-none text-ink">{!! \App\Support\ShortcodeParser::render($child->content) !!}</div>
                    <a href="{{ $child->publicUrl() }}" class="mt-3 inline-flex items-center gap-2 text-sm font-bold text-brand hover:text-brand-dark">Otwórz jako osobną stronę <span class="sr-only">„{{ $child->title }}”</span></a>
                </div>
            </details>
        @endforeach
    </div>
</section>
