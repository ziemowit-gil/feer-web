{{--
    Własne linki i przyciski karty bocznej mega menu (NavItem::megaSideLinks())
    w wersji dla zwykłych rozwijanych menu — mobile i pozycje bez mega panelu —
    żeby konfigurowalne odnośniki nie znikały poza desktopowym mega menu.

    Zmienne: $item, $asListItem (true → <li> wewnątrz <ul>, domyślnie <div>).
    WCAG: dopisek „(link otwiera się w nowej karcie)” dla target=_blank dodaje globalnie resources/js/app.js (3.2.5).
--}}
@php
    $sideLinks = $item->megaSideLinks();
    $asListItem = $asListItem ?? false;
    $tag = $asListItem ? 'li' : 'div';
@endphp
@if ($sideLinks)
    <{{ $tag }} class="mt-1 space-y-1 border-t border-gray-100 px-2 py-2 normal-case tracking-normal">
        @if ($item->mega_side_title)
            <p class="px-2 text-xs font-bold uppercase tracking-wide text-muted">{{ $item->mega_side_title }}</p>
        @endif
        @foreach ($sideLinks as $sl)
            <a href="{{ $sl['url'] }}" @if ($sl['new_tab']) target="_blank" rel="noopener" @endif
               class="{{ $sl['style'] === 'button'
                    ? 'flex min-h-10 items-center justify-center gap-2 rounded-full bg-brand px-4 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2'
                    : 'flex min-h-10 items-center gap-2 rounded-md px-2 text-sm font-bold text-brand hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand' }}">
                <span>{{ $sl['label'] }}</span>
                <i class="fa-solid {{ $sl['new_tab'] ? 'fa-arrow-up-right-from-square' : 'fa-arrow-right' }} text-xs" aria-hidden="true"></i>
            </a>
        @endforeach
    </{{ $tag }}>
@endif
