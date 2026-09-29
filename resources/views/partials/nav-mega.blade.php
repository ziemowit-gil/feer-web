{{--
    Mega menu — panel na całą szerokość paska nawigacji (desktop).

    Renderowane przez partials/main-nav-items dla pozycji z flagą `is_mega`:
      • „Rozwijane menu": kolumny = ręcznie dodane podpozycje (ikona + opis),
      • link do strony: kolumny = podpozycje + opublikowane podstrony tej strony.
    Na mobile pozycja wraca do zwykłego rozwijanego menu (main-nav-items).

    Wymaga, by najbliższy przodek `<nav>` miał `position: relative` — panel
    jest pozycjonowany względem paska, nie pozycji.

    WCAG: nagłówek pozycji to link (klik prowadzi pod adres), osobny przycisk
    rozwija panel (aria-expanded/aria-controls), Escape zamyka i oddaje fokus,
    panel zamyka się też po opuszczeniu go fokusem (2.1.1, 2.1.2, 1.4.13).
--}}
@php
    $ob      = $onBrand ?? false;
    $hoverW  = $ob && ($siteSettings->wide_mission_nav_hover_white  ?? true);
    $activeW = $ob && ($siteSettings->wide_mission_nav_active_white ?? true);
    $iconsW  = $ob && ($siteSettings->wide_mission_nav_icons_white  ?? false);
    $hoverTxtCls = $hoverW ? 'hover:text-white hover:underline' : 'hover:text-brand';
    $activeBdr   = $ob ? ($activeW ? 'border-white' : 'border-brand text-brand') : 'border-brand text-brand';
    $iconCls     = $iconsW ? 'text-white hover:text-white/80' : 'text-brand hover:text-brand';

    $linkedPage   = $item->type === 'link' ? $item->linkedPage() : null;
    $pageChildren = $linkedPage ? $linkedPage->publishedChildren : collect();
    $routePage    = request()->route('page');
    $currentPageId = request()->routeIs('page.show') && is_object($routePage) ? $routePage->id : null;

    // Wpisy panelu: [url, label, description, icon, current]
    $entries = [];
    foreach ($item->children as $child) {
        $entries[] = [$child->url, $child->label, $child->description, $child->icon, $child->isCurrent()];
    }
    foreach ($pageChildren as $child) {
        $entries[] = [$child->publicUrl(), $child->title, null, null, $currentPageId === $child->id];
    }

    $isCurrent = $item->isCurrent() || collect($entries)->contains(fn ($e) => $e[4]);
    $hasTarget = $item->url && $item->url !== '#';
    $columns   = max(2, min(4, (int) ceil(count($entries) / 4)));
    $navIcons  = $iconsNav ?? false;
@endphp

<li class="static" x-data="{ open: false }" x-id="['mega']"
    @mouseenter="if (!{{ $mobile ? 'true' : 'false' }}) open = true" @mouseleave="if (!{{ $mobile ? 'true' : 'false' }}) open = false"
    @focusout="if (! $el.contains($event.relatedTarget)) open = false"
    @keydown.escape="open = false; $refs.megaTrigger.focus()"
    @click.outside="open = false">

    <div class="flex items-center gap-1 border-b-2 transition-colors {{ $isCurrent ? $activeBdr : 'border-transparent' }} pb-1"
         :class="open ? '{{ $activeBdr }}' : ''">
        @if ($hasTarget)
            <a href="{{ $item->url }}" x-ref="megaTrigger"
               @if ($item->isCurrent()) aria-current="page" @endif
               class="flex items-center gap-2 pt-2 uppercase transition-colors {{ $hoverTxtCls }} focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-current">
                @if ($navIcons && $item->icon)<i class="bi {{ $item->icon }} nav-item-icon" aria-hidden="true"></i>@endif
                <span>{{ $item->label }}</span>
            </a>
            <button type="button" @click="open = ! open"
                    :aria-expanded="open.toString()" :aria-controls="$id('mega')" aria-label="Rozwiń mega menu: {{ $item->label }}"
                    class="flex min-h-8 min-w-8 items-center justify-center rounded px-1 pt-2 {{ $iconCls }} focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
                <i class="fa-solid fa-chevron-down text-[10px] transition-transform" :class="open ? 'rotate-180' : ''" aria-hidden="true"></i>
            </button>
        @else
            <button type="button" x-ref="megaTrigger" @click="open = ! open"
                    :aria-expanded="open.toString()" :aria-controls="$id('mega')"
                    class="flex items-center gap-2 pt-2 uppercase transition-colors {{ $hoverTxtCls }} focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-current">
                @if ($navIcons && $item->icon)<i class="bi {{ $item->icon }} nav-item-icon" aria-hidden="true"></i>@endif
                <span>{{ $item->label }}</span>
                <i class="fa-solid fa-chevron-down text-[10px] transition-transform" :class="open ? 'rotate-180' : ''" aria-hidden="true"></i>
            </button>
        @endif
    </div>

    <div :id="$id('mega')" x-show="open" x-cloak x-transition.opacity.duration.150ms
         class="nav-mega-panel absolute inset-x-0 top-full z-50 border-t border-gray-200 bg-white normal-case tracking-normal shadow-xl"
         role="region" aria-label="{{ $item->label }} — podmenu">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-6 lg:grid-cols-[1fr_16rem]">

            <ul role="list" class="grid gap-x-6 gap-y-1 sm:grid-cols-2 lg:grid-cols-{{ $columns }}">
                @foreach ($entries as [$url, $label, $description, $icon, $current])
                    <li>
                        <a href="{{ $url }}" @if ($current) aria-current="page" @endif
                           class="group flex min-h-11 items-start gap-3 rounded-lg px-3 py-2 transition hover:bg-gray-50 focus-visible:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand {{ $current ? 'bg-brand-light' : '' }}">
                            <span class="mt-0.5 flex h-8 w-8 flex-none items-center justify-center rounded-md {{ $current ? 'bg-brand text-white' : 'bg-brand-light text-brand' }}" aria-hidden="true">
                                <i class="{{ $icon ? 'bi ' . $icon : 'fa-solid fa-arrow-right' }} text-sm"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-bold {{ $current ? 'text-brand' : 'text-ink group-hover:text-brand' }}">{{ $label }}</span>
                                @if ($description)
                                    <span class="block text-xs leading-snug text-muted">{{ $description }}</span>
                                @endif
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- Kolumna boczna: opis pozycji + link „Zobacz wszystko" --}}
            <div class="hidden flex-col justify-between rounded-xl bg-gray-50 p-5 lg:flex">
                <div>
                    <p class="text-base font-bold text-ink">{{ $item->label }}</p>
                    @if ($item->description)
                        <p class="mt-1 text-sm leading-snug text-muted">{{ $item->description }}</p>
                    @elseif ($linkedPage && $linkedPage->meta_description)
                        <p class="mt-1 text-sm leading-snug text-muted">{{ $linkedPage->meta_description }}</p>
                    @endif
                </div>
                @if ($hasTarget)
                    <a href="{{ $item->url }}"
                       class="mt-4 inline-flex min-h-10 items-center gap-2 self-start rounded-full bg-brand px-4 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                        Zobacz wszystko <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                    </a>
                @endif
            </div>
        </div>
    </div>
</li>
