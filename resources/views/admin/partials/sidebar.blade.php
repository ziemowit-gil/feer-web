{{--
    Menu boczne panelu administracyjnego.

    Dane menu: App\Support\AdminMenu (maks. 6 grup głównych → bloki → pozycje → podpozycje), układ jak moduły TYPO3.
    Stan (zwinięcie na desktopie, otwarcie szuflady na mobile) trzyma Alpine store
    `adminNav`, zdefiniowany w admin/layout.blade.php. Styl `.admin-sidebar`: resources/css/app.css.

    Tryby:
      • desktop (≥ lg) rozwinięty  — pełne etykiety, zwijane sekcje (stan w localStorage),
      • desktop (≥ lg) zwinięty    — sama „szyna" ikon; etykieta i podpozycje w wysuwanym panelu po najechaniu/fokusie,
      • mobile (< lg)              — szuflada wysuwana hamburgerem w nagłówku.
--}}
@php
    $authUser   = auth()->user();
    $menu       = \App\Support\AdminMenu::for($authUser);
    $adminSites = $authUser?->isAdmin()
        ? \App\Models\SiteSetting::orderBy('parent_site_id')->orderBy('id')->get()
        : collect();
    $initial    = mb_strtoupper(mb_substr($authUser?->name ?: $authUser?->email, 0, 1));
    $roleLabel  = \App\Models\User::ROLES[$authUser?->role] ?? 'Edytor';
@endphp

{{-- Tło szuflady na mobile --}}
<div x-show="$store.adminNav.mobileOpen" x-cloak x-transition.opacity
     @click="$store.adminNav.close()"
     class="fixed inset-0 z-30 bg-ink/50 lg:hidden" aria-hidden="true"></div>

<aside id="admin-sidebar"
       class="admin-sidebar fixed inset-y-0 left-0 z-40 flex flex-col border-r border-gray-200 bg-white lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
       :class="{ 'is-collapsed': $store.adminNav.collapsed, 'is-open': $store.adminNav.mobileOpen }"
       @keydown.escape.window="$store.adminNav.close()"
       aria-label="Panel administracyjny">

    {{-- ── Marka + przełączniki ─────────────────────────────────── --}}
    <div class="nav-brand flex h-16 flex-none items-center gap-3 border-b border-gray-200 px-4">
        <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 flex-1 items-center gap-3 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" title="Dashboard">
            @if ($siteSettings->logoUrl())
                <img src="{{ $siteSettings->logoUrl() }}" alt="" class="h-9 w-9 flex-none rounded-lg object-contain">
            @else
                <span class="flex h-9 w-9 flex-none items-center justify-center rounded-lg bg-brand text-sm font-bold text-white" aria-hidden="true">{{ mb_substr($siteSettings->site_name, 0, 1) }}</span>
            @endif
            <span class="nav-label min-w-0 leading-tight">
                <span class="block truncate text-base">
                    <span style="font-family:'Pacifico',cursive;color:var(--color-brand)">We</span><span style="font-family:'Pacifico',cursive;font-weight:300">CMS</span>
                </span>
                <span class="block truncate text-[11px] text-muted">{{ $siteSettings->site_name }}</span>
            </span>
        </a>

        {{-- Zwiń/rozwiń (desktop) --}}
        <button type="button" @click="$store.adminNav.toggleCollapsed()"
                class="nav-collapse-btn hidden h-8 w-8 flex-none items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand lg:flex"
                :aria-expanded="(! $store.adminNav.collapsed).toString()"
                :aria-label="$store.adminNav.collapsed ? 'Rozwiń menu boczne' : 'Zwiń menu boczne'"
                :title="$store.adminNav.collapsed ? 'Rozwiń menu' : 'Zwiń menu'">
            <i class="fa-solid text-xs" :class="$store.adminNav.collapsed ? 'fa-angles-right' : 'fa-angles-left'" aria-hidden="true"></i>
        </button>

        {{-- Zamknij (mobile) --}}
        <button type="button" @click="$store.adminNav.close()"
                class="flex h-8 w-8 flex-none items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand lg:hidden"
                aria-label="Zamknij menu">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    </div>

    {{-- ── Przełącznik witryny (multisite, tylko admin) ─────────── --}}
    @if ($adminSites->count() > 1)
        <div class="nav-site flex-none border-b border-gray-200 px-3 py-2" x-data="{ open: false }">
            <div class="relative">
                <button type="button" @click="open = ! open" :aria-expanded="open.toString()"
                        class="nav-link flex w-full items-center gap-3 rounded-lg px-2 py-1.5 text-left hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                        title="Witryna: {{ $siteSettings->site_name }}">
                    <span class="flex h-7 w-7 flex-none items-center justify-center rounded-md bg-gray-100 text-xs text-gray-600" aria-hidden="true">
                        <i class="fa-solid fa-sitemap"></i>
                    </span>
                    <span class="nav-label min-w-0 flex-1 leading-tight">
                        <span class="block text-[10px] font-semibold uppercase tracking-wider text-muted">Witryna</span>
                        <span class="block truncate text-sm font-semibold text-ink">{{ $siteSettings->site_name }}</span>
                    </span>
                    <i class="nav-label fa-solid fa-chevron-down text-[0.6rem] text-gray-400 transition-transform" :class="{ 'rotate-180': open }" aria-hidden="true"></i>
                </button>
                <div x-show="open" x-cloak @click.outside="open = false" @keydown.escape.stop="open = false"
                     class="absolute left-0 top-full z-50 mt-1 w-64 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-xl"
                     role="menu" aria-label="Wybierz witrynę">
                    @foreach ($adminSites as $adminSite)
                        <form method="POST" action="{{ route('admin.witryny.przelacz', $adminSite) }}">
                            @csrf
                            <button type="submit" role="menuitem"
                                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-gray-50 focus-visible:bg-gray-50 focus-visible:outline-none {{ $adminSite->is($siteSettings) ? 'font-bold text-brand' : 'text-ink' }}">
                                @if ($adminSite->parent_site_id)
                                    <i class="fa-solid fa-arrow-turn-up fa-rotate-90 ml-2 text-[0.65rem] text-gray-300" aria-hidden="true"></i>
                                @else
                                    <i class="fa-solid fa-sitemap text-[0.7rem] text-gray-300" aria-hidden="true"></i>
                                @endif
                                <span class="truncate">{{ $adminSite->site_name }}</span>
                                @if ($adminSite->is($siteSettings))
                                    <i class="fa-solid fa-check ml-auto text-xs" aria-hidden="true"></i>
                                    <span class="sr-only">(bieżąca)</span>
                                @endif
                            </button>
                        </form>
                    @endforeach
                    <a href="{{ route('admin.witryny.index') }}" role="menuitem"
                       class="mt-1 flex items-center gap-2 border-t border-gray-100 px-3 py-2 text-sm text-muted hover:bg-gray-50 hover:text-brand">
                        <i class="fa-solid fa-gear text-[0.7rem]" aria-hidden="true"></i> Zarządzaj witrynami
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Nawigacja: moduły jak w TYPO3 — najwyżej sześć grup głównych ──────────────
         Grupy rozwijają się pojedynczo (akordeon); aktywna grupa jest otwarta od razu. W zwężonej szynie
         widać tylko ikony grup, a lista pozycji wysuwa się po najechaniu lub fokusie. Przycisk grupy
         w szynie rozwija całe menu — to dostęp z klawiatury i dotyku. --}}
    <style>
        .tm-head { display: flex; width: 100%; align-items: center; gap: .75rem; min-height: 2.75rem; padding: .375rem .625rem; border-radius: .625rem; text-align: left; font-weight: 700; font-size: .875rem; color: #1f2937; transition: background-color .15s; }
        .tm-head:hover { background: #f3f4f6; }
        .tm-head:focus-visible { outline: 2px solid var(--color-brand); outline-offset: 1px; }
        .tm-icon { display: inline-flex; flex: none; align-items: center; justify-content: center; width: 2rem; height: 2rem; border-radius: .5rem; background: #f3f4f6; color: #6b7280; font-size: .9rem; transition: background-color .15s, color .15s; }
        .tm-group.is-active > .tm-head, .tm-group.is-active .tm-head { color: var(--color-brand); }
        .tm-group.is-active .tm-icon { background: var(--color-brand); color: #fff; }
        .tm-panel { margin: .25rem 0 .5rem 1.05rem; padding-left: .75rem; border-left: 2px solid #e5e7eb; }
        .tm-sub { margin: .625rem .5rem .25rem; font-size: .6875rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #6b7280; }
        .tm-sub:first-child { margin-top: .25rem; }
        .admin-sidebar.is-collapsed .tm-panel { display: none !important; }
        .admin-sidebar.is-collapsed .tm-head { justify-content: center; padding-left: .25rem; padding-right: .25rem; }
        .admin-sidebar.is-collapsed .tm-head .tm-badge { position: absolute; top: .125rem; right: .25rem; }
    </style>

    <nav class="nav-scroll flex-1 overflow-y-auto overscroll-contain px-3 py-3 text-sm" aria-label="Menu panelu"
         x-data="navAccordion(@js(collect($menu)->firstWhere('active', true)['key'] ?? ''), @js($menu[0]['key'] ?? ''))">
        <ul class="space-y-1" role="list">
            @foreach ($menu as $group)
                @php
                    $gid = 'tm-panel-' . $group['key'];
                @endphp
                <li class="tm-group relative {{ $group['active'] ? 'is-active' : '' }}" x-data="navItem(false)"
                    @mouseenter="flyIn($el)" @mouseleave="flyOut()" @focusin="flyIn($el)" @focusout="flyOut($event)">

                    <button type="button" class="tm-head nav-link relative" @click="toggle(@js($group['key']))"
                            :aria-expanded="($store.adminNav.collapsed ? false : openKey === @js($group['key'])).toString()" aria-controls="{{ $gid }}"
                            title="{{ $group['label'] }}">
                        <span class="tm-icon" aria-hidden="true"><i class="fa-solid {{ $group['icon'] }}"></i></span>
                        <span class="nav-label min-w-0 flex-1 truncate">{{ $group['label'] }}</span>
                        @if ($group['badge'])
                            <span class="tm-badge nav-badge inline-flex h-5 min-w-5 flex-none items-center justify-center rounded-full bg-brand px-1.5 text-[11px] font-bold leading-none text-white"
                                  aria-label="{{ $group['badge'] }} do obsłużenia">{{ $group['badge'] > 99 ? '99+' : $group['badge'] }}</span>
                        @endif
                        <i class="nav-label fa-solid fa-chevron-down text-[0.6rem] text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': openKey === @js($group['key']) }" aria-hidden="true"></i>
                    </button>

                    {{-- Lista pozycji grupy --}}
                    <div id="{{ $gid }}" class="tm-panel" x-show="openKey === @js($group['key'])" @if (! $group['active']) style="display:none" @endif>
                        @foreach ($group['blocks'] as $block)
                            @if ($block['heading'])<p class="tm-sub" id="tm-sub-{{ $group['key'] }}-{{ $loop->index }}">{{ $block['heading'] }}</p>@endif
                            <ul class="space-y-0.5" role="list" @if ($block['heading']) aria-labelledby="tm-sub-{{ $group['key'] }}-{{ $loop->index }}" @endif>
                                @foreach ($block['items'] as $item)
                                    @php $hasChildren = $item['children'] !== []; $childrenId = 'nav-children-' . $item['key']; @endphp
                                    <li x-data="{ open: {{ $hasChildren && $item['active'] ? 'true' : 'false' }} }">
                                        <div class="relative flex items-center gap-2 rounded-lg px-2 {{ $item['active'] ? 'bg-brand-light text-brand' : 'text-ink hover:bg-gray-100 hover:text-brand' }}" style="min-height: 2.25rem">
                                            <a href="{{ $item['url'] }}" class="flex min-w-0 flex-1 items-center gap-2.5 py-1.5 font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-1 rounded"
                                               @if ($item['active'] && ! $hasChildren) aria-current="page" @endif>
                                                <i class="fa-solid {{ $item['icon'] }} w-4 flex-none text-center text-[0.8rem] {{ $item['active'] ? 'text-brand' : 'text-gray-400' }}" aria-hidden="true"></i>
                                                <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                                            </a>
                                            @if ($item['badge'])
                                                <span class="inline-flex h-5 min-w-5 flex-none items-center justify-center rounded-full px-1.5 text-[11px] font-bold leading-none {{ $item['badge_tone'] === 'muted' ? 'bg-gray-200 text-gray-700' : 'bg-brand text-white' }}"
                                                      aria-label="{{ $item['badge'] }} do obsłużenia">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                                            @endif
                                            @if ($hasChildren)
                                                <button type="button" @click.stop="open = ! open" :aria-expanded="open.toString()" aria-controls="{{ $childrenId }}"
                                                        class="-mr-1 flex h-7 w-7 flex-none items-center justify-center rounded text-gray-400 hover:bg-white/60 hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                                    <span class="sr-only">Pokaż podpozycje: {{ $item['label'] }}</span>
                                                    <i class="fa-solid fa-chevron-down text-[0.55rem] transition-transform duration-200" :class="{ 'rotate-180': open }" aria-hidden="true"></i>
                                                </button>
                                            @endif
                                        </div>
                                        @if ($hasChildren)
                                            <ul id="{{ $childrenId }}" class="ml-4 mt-0.5 space-y-0.5 border-l border-gray-200 pl-2.5" role="list" x-show="open" @unless ($item['active']) style="display:none" @endunless>
                                                @php $prevGroup = null; @endphp
                                                @foreach ($item['children'] as $child)
                                                    @if (($child['group'] ?? null) && $child['group'] !== $prevGroup)
                                                        <li class="tm-sub" role="presentation" style="margin: .5rem .5rem .125rem">{{ $child['group'] }}</li>
                                                        @php $prevGroup = $child['group']; @endphp
                                                    @endif
                                                    <li>
                                                        <a href="{{ $child['url'] }}"
                                                           class="flex items-center gap-2 rounded-md px-2 py-1 text-[13px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand {{ $child['active'] ? 'bg-brand-light font-semibold text-brand' : 'text-muted hover:bg-gray-100 hover:text-brand' }}"
                                                           @if ($child['active']) aria-current="page" @endif>
                                                            @if ($child['icon'])<i class="fa-solid {{ $child['icon'] }} text-[0.7rem]" aria-hidden="true"></i>@endif
                                                            <span class="truncate">{{ $child['label'] }}</span>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endforeach
                    </div>

                    {{-- Wysuwany panel grupy w zwężonej szynie (desktop): prawdziwe, osiągalne klawiaturą linki --}}
                    <div x-show="fly && $store.adminNav.collapsed" x-cloak :style="'top:' + flyTop + 'px'"
                         class="nav-flyout fixed z-50 hidden w-64 overflow-y-auto rounded-lg border border-gray-200 bg-white py-2 shadow-xl lg:block"
                         style="left: 4.25rem; max-height: calc(100vh - 2rem)">
                        <p class="px-3 pb-1 text-sm font-bold text-ink"><i class="fa-solid {{ $group['icon'] }} mr-1.5 text-brand" aria-hidden="true"></i>{{ $group['label'] }}</p>
                        @foreach ($group['blocks'] as $block)
                            @if ($block['heading'])<p class="px-3 pt-2 text-[10px] font-bold uppercase tracking-wider text-muted">{{ $block['heading'] }}</p>@endif
                            @foreach ($block['items'] as $item)
                                <a href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif
                                   class="flex items-center gap-2 px-3 py-1.5 text-[13px] hover:bg-gray-50 focus-visible:bg-gray-50 focus-visible:outline-none {{ $item['active'] ? 'font-semibold text-brand' : 'text-ink hover:text-brand' }}">
                                    <i class="fa-solid {{ $item['icon'] }} w-4 flex-none text-center text-[0.75rem] text-gray-400" aria-hidden="true"></i>
                                    <span class="truncate">{{ $item['label'] }}</span>
                                    @if ($item['badge'])<span class="ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[11px] font-bold leading-none {{ $item['badge_tone'] === 'muted' ? 'bg-gray-200 text-gray-700' : 'bg-brand text-white' }}">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>@endif
                                </a>
                            @endforeach
                        @endforeach
                    </div>
                </li>
            @endforeach
        </ul>
    </nav>

    {{-- ── Użytkownik ────────────────────────────────────────────── --}}
    <div class="nav-user relative flex-none border-t border-gray-200 p-2" x-data="{ open: false }">
        <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-controls="nav-user-menu"
                class="nav-link flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                title="{{ $authUser?->name ?: $authUser?->email }}">
            <span class="flex h-8 w-8 flex-none items-center justify-center rounded-full bg-brand-light text-sm font-bold text-brand" aria-hidden="true">{{ $initial }}</span>
            <span class="nav-label min-w-0 flex-1 leading-tight">
                <span class="block truncate text-sm font-semibold text-ink">{{ $authUser?->name ?: $authUser?->email }}</span>
                <span class="block truncate text-[11px] text-muted">{{ $roleLabel }}</span>
            </span>
            <i class="nav-label fa-solid fa-ellipsis-vertical text-gray-400" aria-hidden="true"></i>
            <span class="sr-only">Menu użytkownika</span>
        </button>

        <div id="nav-user-menu" x-show="open" x-cloak @click.outside="open = false" @keydown.escape.stop="open = false"
             class="absolute bottom-full left-2 z-50 mb-1 w-64 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 text-sm shadow-xl"
             role="menu" aria-label="Menu użytkownika">
            <div class="border-b border-gray-100 px-3 py-2">
                <p class="truncate font-semibold text-ink">{{ $authUser?->name ?: $authUser?->email }}</p>
                <p class="truncate text-xs text-muted">{{ $authUser?->email }}</p>
                <span class="mt-1 inline-block rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $authUser?->isAdmin() ? 'bg-brand/10 text-brand' : 'bg-gray-100 text-muted' }}">{{ $roleLabel }}</span>
            </div>
            <a href="{{ route('profile.edit') }}" role="menuitem" class="flex items-center gap-3 px-3 py-2 text-ink hover:bg-gray-50 hover:text-brand">
                <i class="fa-solid fa-key w-4 text-center text-gray-400" aria-hidden="true"></i> Profil i hasło
            </a>
            <a href="{{ route('profile.edit') }}#powiadomienia" role="menuitem" class="flex items-center gap-3 px-3 py-2 text-ink hover:bg-gray-50 hover:text-brand">
                <i class="fa-solid fa-bell w-4 text-center text-gray-400" aria-hidden="true"></i> Powiadomienia
            </a>
            @if ($authUser?->isAdmin())
                <a href="{{ route('admin.dokumentacja') }}" target="_blank" rel="noopener" role="menuitem" class="flex items-center gap-3 px-3 py-2 text-ink hover:bg-gray-50 hover:text-brand">
                    <i class="fa-solid fa-book w-4 text-center text-gray-400" aria-hidden="true"></i> Dokumentacja
                    <i class="fa-solid fa-arrow-up-right-from-square ml-auto text-[0.65rem] text-gray-300" aria-hidden="true"></i>
                    <span class="sr-only">(otwiera się w nowej karcie)</span>
                </a>
            @endif
            <a href="{{ route('home') }}" role="menuitem" class="flex items-center gap-3 border-t border-gray-100 px-3 py-2 text-ink hover:bg-gray-50 hover:text-brand">
                <i class="fa-solid fa-arrow-left w-4 text-center text-gray-400" aria-hidden="true"></i> Wróć do strony
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" role="menuitem" class="flex w-full items-center gap-3 px-3 py-2 text-left text-ink hover:bg-gray-50 hover:text-brand">
                    <i class="fa-solid fa-right-from-bracket w-4 text-center text-gray-400" aria-hidden="true"></i> Wyloguj
                </button>
            </form>
            <p class="border-t border-gray-100 px-3 py-2 text-[11px] text-gray-400">
                Napędzane przez <span class="font-bold">weCMS</span> · <a href="mailto:ziemowit.gil@gmail.com" class="hover:text-ink">Ziemowit Gil</a>
            </p>
        </div>
    </div>
</aside>
