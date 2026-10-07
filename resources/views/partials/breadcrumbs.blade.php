@php
    $items ??= [];
    $isFederation = ($siteSettings->site_template ?? 'default') === 'federation';
    $isFeer = ($siteSettings->site_template ?? 'default') === 'feer';
@endphp

@if ($isFeer)
{{-- FEER: bez szarego paska — okruszki w szerokości treści, niebieskie szewrony, bieżąca strona jako kafelek. Kontrast: ink/muted na bieli ≥ 7:1. --}}
<nav aria-label="Ścieżka nawigacyjna" class="mx-auto max-w-6xl px-4 pt-5">
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
        <li class="flex items-center gap-2">
            <a href="{{ site_route('home') }}" class="inline-flex min-h-8 items-center rounded-md px-2 font-bold text-ink underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">Strona główna</a>
        </li>
        @foreach ($items as $item)
            <li class="flex items-center gap-2">
                <i class="fa-solid fa-chevron-right text-[0.65rem] text-brand" aria-hidden="true"></i>
                @if ($loop->last)
                    <span aria-current="page" class="inline-flex min-h-8 items-center rounded-md bg-brand-light px-3 font-bold text-ink">{{ $item['label'] }}</span>
                @elseif (! empty($item['url']))
                    <a href="{{ $item['url'] }}" class="inline-flex min-h-8 items-center rounded-md px-2 font-bold text-ink underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">{{ $item['label'] }}</a>
                @else
                    <span class="px-2 text-muted">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
@else

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
@endif
