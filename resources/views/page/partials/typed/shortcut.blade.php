{{--
    Typ „Skrót” — widok tylko w podglądzie z panelu (na żywo adres przenosi od razu do celu, 302).
    Płasko: jedna kolumna, ikona bez tła i obramowania, tylko kolory firmowe.
--}}
@php
    $target = $page->shortcutTarget();
    $mode = $page->typeData()['shortcut_mode'] ?? 'first_child';
@endphp
<section class="mx-auto max-w-2xl px-4 py-16 text-center" aria-labelledby="shortcut-h" data-page-type="shortcut">
    <i class="fa-solid fa-diamond-turn-right mb-5 block text-4xl text-brand-dark" aria-hidden="true"></i>
    <h1 id="shortcut-h" class="mb-4 text-3xl font-bold text-ink md:text-4xl">{{ $page->title }}</h1>
    <p class="mb-6 text-lg text-ink">Ta strona to skrót: {{ \Illuminate\Support\Str::lower(\App\Models\Page::SHORTCUT_MODES[$mode] ?? \App\Models\Page::SHORTCUT_MODES['first_child']) }}. Odwiedzający trafiają od razu na stronę docelową.</p>
    @if ($target)
        <a href="{{ $target->navUrl() }}"
            class="inline-flex min-h-12 items-center gap-2 rounded-md bg-brand-dark px-6 py-3 text-base font-bold text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
            Przejdź do: {{ $target->navLabel() }}<i class="fa-solid fa-arrow-right text-sm" aria-hidden="true"></i>
        </a>
    @else
        <p class="text-ink">Skrót nie ma jeszcze celu (brak opublikowanej podstrony albo wskazanej strony) — odwiedzający trafią na stronę nadrzędną.</p>
    @endif
</section>
