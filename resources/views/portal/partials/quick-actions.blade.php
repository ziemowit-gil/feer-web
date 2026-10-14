{{-- „Na skróty” jako kafle szybkich akcji (płasko: jasne tło, ikona w kolorze marki). Expects: $shortcuts, $shortcutsTitle. --}}
<nav aria-labelledby="portal-shortcuts" class="mt-8">
    <h2 id="portal-shortcuts" class="text-base font-bold text-ink">{{ $shortcutsTitle }}</h2>
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
</nav>
