{{--
    Stronicowanie listy Wiem FEER — płasko: bez ramek, bieżąca strona na ciemnym
    tle marki z aria-current="page" (nie tylko kolorem: pogrubienie + opis dla
    czytników ekranu). Cele ≥ 44 px. Zmienne Laravela: $paginator, $elements.
--}}
@if ($paginator->hasPages())
    @php
        $pgItem = 'inline-flex min-h-11 min-w-11 items-center justify-center rounded-md px-3 text-sm font-bold';
        $pgLink = $pgItem . ' bg-gray-50 text-ink transition hover:bg-brand-light hover:text-brand-dark hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    @endphp
    <nav aria-label="Stronicowanie wpisów" class="mt-10">
        <ul class="flex flex-wrap items-center gap-2">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="{{ $pgItem }} text-muted" aria-disabled="true"><i class="fa-solid fa-chevron-left mr-2 text-xs" aria-hidden="true"></i>Poprzednia</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $pgLink }}"><i class="fa-solid fa-chevron-left mr-2 text-xs" aria-hidden="true"></i>Poprzednia<span class="sr-only"> strona</span></a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="{{ $pgItem }} text-muted" aria-hidden="true">{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="{{ $pgItem }} bg-brand-dark text-white" aria-current="page"><span class="sr-only">Strona </span>{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="{{ $pgLink }}"><span class="sr-only">Strona </span>{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $pgLink }}">Następna<span class="sr-only"> strona</span><i class="fa-solid fa-chevron-right ml-2 text-xs" aria-hidden="true"></i></a>
                @else
                    <span class="{{ $pgItem }} text-muted" aria-disabled="true">Następna<i class="fa-solid fa-chevron-right ml-2 text-xs" aria-hidden="true"></i></span>
                @endif
            </li>
        </ul>
    </nav>
@endif
