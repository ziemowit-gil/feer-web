@php
    $items ??= [];
    $isFederation = ($siteSettings->site_template ?? 'default') === 'federation';
@endphp

@unless ($isFederation)
<div class="border-b border-gray-200 bg-gray-50">
@endunless
<nav aria-label="Ścieżka nawigacyjna" class="mx-auto max-w-[1400px] px-4 {{ $isFederation ? 'py-4' : '' }}">
    <ol class="flex flex-wrap items-center gap-1.5 text-xs text-muted {{ $isFederation ? '' : 'py-2.5' }}">
        <li class="flex items-center gap-2">
            <a href="{{ site_route('home') }}" class="flex min-h-6 items-center gap-1.5 hover:text-brand">
                @unless ($isFederation)
                    <i class="fa-solid fa-house text-xs" aria-hidden="true"></i>
                @endunless
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
@unless ($isFederation)
</div>
@endunless
