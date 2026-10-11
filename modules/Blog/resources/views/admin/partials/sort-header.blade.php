{{--
    Nagłówek sortowalnej kolumny listy artykułów: link przełącza kierunek, aktywna kolumna
    ma aria-sort i strzałkę (ukrytą dla czytników — kierunek mówi aria-sort i tekst sr-only).
    Zmienne: $filters (ArticleListFilters), $col, $text.
--}}
@php
    $aria = $filters->ariaSort($col);
    $next = $aria === 'ascending' ? 'malejąco' : ($aria === 'descending' ? 'rosnąco' : (in_array($col, ['publikacja', 'zmiana', 'komentarze'], true) ? 'malejąco' : 'rosnąco'));
@endphp
<th scope="col" class="px-3 py-3" @if ($aria) aria-sort="{{ $aria }}" @endif>
    <a href="{{ $filters->sortUrl($col) }}" class="inline-flex items-center gap-1 text-ink hover:text-brand-dark hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
        {{ $text }}
        <i class="fa-solid {{ $aria === 'ascending' ? 'fa-arrow-up' : ($aria === 'descending' ? 'fa-arrow-down' : 'fa-sort text-muted') }}" aria-hidden="true"></i>
        <span class="sr-only">— sortuj {{ $next }}</span>
    </a>
</th>
