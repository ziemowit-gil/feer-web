@php $items ??= []; @endphp

<nav aria-label="Ścieżka nawigacyjna" class="mx-auto max-w-6xl px-4 pt-6">
    <ol class="flex flex-wrap items-center gap-2 text-sm text-muted">
        <li class="flex items-center gap-2">
            <a href="{{ route('home') }}" class="flex min-h-6 items-center gap-1.5 hover:text-brand">
                <i class="fa-solid fa-house text-xs" aria-hidden="true"></i>
                Strona główna
            </a>
        </li>
        @foreach ($items as $item)
            <li class="flex items-center gap-2">
                <span aria-hidden="true">/</span>
                @if ($loop->last)
                    <span aria-current="page" class="font-bold text-ink">{{ $item['label'] }}</span>
                @elseif (! empty($item['url']))
                    <a href="{{ $item['url'] }}" class="hover:text-brand">{{ $item['label'] }}</a>
                @else
                    <span>{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
