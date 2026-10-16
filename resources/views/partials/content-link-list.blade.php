{{--
    Lista linków w treści: podstrony ([podstrony…], App\Support\SubpageList) i aktualności ([aktualnosci…], App\Support\NewsList).
    Zmienne: $items — lista ['title', 'url', 'excerpt' (?string), 'date' (?Carbon), 'children' (lista ['title', 'url'])],
    $layout (lista|zajawki|kafle), $label (aria-label nawigacji).
    Płasko, bez ramek; style .content-links* w resources/css/app.css. not-prose: typografia treści nie dokłada punktorów i marginesów.
--}}
<nav class="content-links content-links--{{ $layout }} not-prose" aria-label="{{ $label }}">
    <ul class="content-links-list" role="list">
        @foreach ($items as $item)
            <li class="content-links-item">
                <a href="{{ $item['url'] }}" class="content-links-link">{{ $item['title'] }}</a>
                @if (! empty($item['date']) && $layout !== 'lista')
                    <time class="content-links-date" datetime="{{ $item['date']->toDateString() }}">{{ $item['date']->translatedFormat('j F Y') }}</time>
                @endif
                @if ($layout !== 'lista' && filled($item['excerpt'] ?? null))
                    <p class="content-links-excerpt">{{ $item['excerpt'] }}</p>
                @endif
                @if (! empty($item['children']))
                    <ul class="content-links-sub" role="list" aria-label="Podstrony: {{ $item['title'] }}">
                        @foreach ($item['children'] as $child)
                            <li><a href="{{ $child['url'] }}" class="content-links-sublink">{{ $child['title'] }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
