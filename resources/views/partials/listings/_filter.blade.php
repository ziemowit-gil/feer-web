{{--
    Filtr list [nabory] / [zapytania-ofertowe]: zwykłe linki (?status=…), bez JS.
    Bieżący filtr ma aria-current i wyróżnienie tłem (nie tylko kolorem — bez podkreślenia, pogrubiony).
    Zmienne: $filter, $counts, $label (np. „Filtr naborów"), $anchor (id sekcji listy).
--}}
<nav aria-label="{{ $label }}" class="mb-6">
    <ul class="flex flex-wrap gap-2" role="list">
        @foreach (\App\Support\ShortcodeParser::LISTING_FILTERS as $key => $name)
            <li>
                @if ($key === $filter)
                    <a href="{{ request()->fullUrlWithQuery(['status' => $key, 'strona' => null]) }}#{{ $anchor }}" aria-current="page"
                       class="inline-flex min-h-11 items-center rounded-md bg-brand-dark px-4 font-bold text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                        {{ $name }} ({{ $counts[$key] }})
                    </a>
                @else
                    <a href="{{ request()->fullUrlWithQuery(['status' => $key, 'strona' => null]) }}#{{ $anchor }}"
                       class="inline-flex min-h-11 items-center rounded-md bg-gray-50 px-4 font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                        {{ $name }} ({{ $counts[$key] }})
                    </a>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
