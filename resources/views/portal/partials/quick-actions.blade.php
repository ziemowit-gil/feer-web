{{--
    „Na skróty” pod wstępem: kafle szybkich akcji albo przyciski (pierwszy wyróżniony kolorem marki, kolejne jasne).
    Płasko, bez ramek; tekst ≥ 4,5:1 we wszystkich stanach. Expects: $shortcuts, $shortcutsTitle, $shortcutsStyle (tiles|buttons).
--}}
<nav aria-labelledby="portal-shortcuts" class="mt-8">
    <h2 id="portal-shortcuts" class="{{ $shortcutsStyle === 'buttons' ? 'sr-only' : 'text-base font-bold text-ink' }}">{{ $shortcutsTitle }}</h2>
    @if ($shortcutsStyle === 'buttons')
        <ul role="list" class="flex flex-wrap gap-3">
            @foreach ($shortcuts as [$label, $url, $icon])
                <li>
                    <a href="{{ $url }}" class="inline-flex min-h-11 items-center gap-2 rounded-md px-5 py-2 text-sm font-bold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2 motion-reduce:transition-none {{ $loop->first ? 'bg-brand-dark text-white hover:bg-ink' : 'bg-brand-light text-ink hover:bg-gray-100' }}">
                        @if ($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <ul role="list" class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($shortcuts as [$label, $url, $icon])
                <li>
                    <a href="{{ $url }}" class="group flex h-full min-h-14 items-center gap-3 rounded-md bg-gray-50 px-4 py-3 font-bold leading-snug text-ink transition-colors hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark motion-reduce:transition-none">
                        @if ($icon)<i class="{{ $icon }} w-5 flex-none text-center text-lg text-brand-dark" aria-hidden="true"></i>@endif
                        <span class="min-w-0 flex-1 group-hover:underline group-hover:underline-offset-4">{{ $label }}</span>
                        <span class="flex-none text-brand-dark" aria-hidden="true">→</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</nav>
