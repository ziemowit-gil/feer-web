{{--
    Płaska, dostępna paginacja list aktualności i archiwum (bez ramek i „pigułek").
    Parametry: $paginator (LengthAwarePaginator, z dopiętymi parametrami zapytania),
               $label (etykieta nawigacji, np. „Strony aktualności").
    Bieżąca strona: aria-current="page"; linki „Poprzednia/Następna" z rel=prev/next;
    okno ±2 strony wokół bieżącej, z pierwszą i ostatnią oraz wielokropkiem.
--}}
@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $pages = collect([1, $last])->merge(range(max(1, $current - 2), min($last, $current + 2)))->unique()->sort()->values();
        $link = 'inline-flex min-h-11 min-w-11 items-center justify-center gap-2 rounded-md px-2 font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    @endphp
    <nav aria-label="{{ $label ?? 'Strony listy' }}" class="mt-10 flex flex-wrap items-center justify-between gap-4 rounded-md bg-gray-50 px-4 py-3">
        <div class="min-w-32">
            @if ($paginator->onFirstPage())
                <span class="sr-only">To jest pierwsza strona.</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $link }}">
                    <i class="fa-solid fa-arrow-left text-sm" aria-hidden="true"></i> Poprzednia<span class="sr-only"> strona</span>
                </a>
            @endif
        </div>

        <ul class="flex flex-wrap items-center gap-1" role="list">
            @foreach ($pages as $i => $p)
                @if ($i > 0 && $p - $pages[$i - 1] > 1)
                    <li aria-hidden="true" class="px-1 text-muted">…</li>
                @endif
                <li>
                    @if ($p === $current)
                        <span aria-current="page" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-md bg-ink px-2 font-bold text-white">
                            <span class="sr-only">Strona </span>{{ $p }}
                        </span>
                    @else
                        <a href="{{ $paginator->url($p) }}" class="{{ $link }}"><span class="sr-only">Strona </span>{{ $p }}</a>
                    @endif
                </li>
            @endforeach
        </ul>

        <div class="min-w-32 text-right">
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $link }}">
                    Następna<span class="sr-only"> strona</span> <i class="fa-solid fa-arrow-right text-sm" aria-hidden="true"></i>
                </a>
            @else
                <span class="sr-only">To jest ostatnia strona.</span>
            @endif
        </div>
    </nav>
@endif
