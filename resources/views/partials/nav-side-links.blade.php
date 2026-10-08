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
            @include('partials.nav-side-link', ['sl' => $sl, 'block' => true])
        @endforeach
    </{{ $tag }}>
@endif
