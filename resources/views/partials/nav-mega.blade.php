{{--
    Mega menu — panel na całą szerokość paska nawigacji (desktop).

    Renderowane przez partials/main-nav-items dla pozycji z flagą `is_mega`:
      • „Rozwijane menu": kolumny = ręcznie dodane podpozycje (ikona + opis),
      • link do strony: kolumny = podpozycje + opublikowane podstrony tej strony,
      • „Menu projektów": kolumny = kategorie projektów z listą projektów.
    Kolumna boczna: grafika promocyjna (mega_image), opis i „Zobacz wszystko".
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
    $navIcons    = $iconsNav ?? false;
    $isProjects  = $item->type === 'projects';

    $routePage     = request()->route('page');
    $currentPageId = request()->routeIs('page.show') && is_object($routePage) ? $routePage->id : null;

    // Płaskie wpisy (dropdown / link): [url, label, description, icon, current]
    $entries = [];
    $linkedPage = null;
    if (! $isProjects) {
        $linkedPage   = $item->type === 'link' ? $item->linkedPage() : null;
        foreach ($item->children as $child) {
            $entries[] = [$child->url, $child->label, $child->description, $child->icon, $child->isCurrent()];
        }
        foreach ($linkedPage ? $linkedPage->publishedChildren : [] as $child) {
            $entries[] = [$child->publicUrl(), $child->title, null, null, $currentPageId === $child->id];
        }
    }

    // Grupy (projekty): kategoria → projekty; do tego projekt wyróżniony w kolumnie
    // bocznej (najnowszy aktywny ze zdjęciem, gdy nie ustawiono grafiki promocyjnej)
    // i najbliższe szkolenie z modułu wydarzeń.
    $groups = $isProjects ? collect($navCategories ?? [])->filter(fn ($c) => $c->publishedProjects->isNotEmpty())->values() : collect();
    $groupCurrentId = request()->routeIs('projects.show') ? request()->route('project')?->id : null;
    $catCurrentId   = request()->routeIs('categories.show') ? request()->route('category')?->id : null;
    $allProjects    = $groups->flatMap(fn ($c) => $c->publishedProjects);
    $featuredProject = null;
    $nextEvent = null;
    if ($isProjects) {
        $featuredProject = $allProjects->first(fn ($p) => ! $p->is_completed && $p->image_url)
            ?? $allProjects->first(fn ($p) => $p->image_url);
        if ($siteSettings->isModuleEnabled('events') && class_exists(\App\Models\Event::class)) {
            try {
                $nextEvent = \App\Models\Event::upcoming()->orderBy('starts_at')->first();
            } catch (\Throwable) {
                $nextEvent = null;
            }
        }
    }

    $isCurrent = $item->isCurrent() || collect($entries)->contains(fn ($e) => $e[4]);
    $hasTarget = $isProjects ? true : ($item->url && $item->url !== '#');
    $targetUrl = $isProjects ? route('projects.index') : $item->url;
    $hasImage  = filled($item->mega_image);

    // Wielkość pozycji (NavItem::MEGA_SIZES): liczba kolumn i skala wpisów.
    $size = $item->megaSize();
    $columns = match ($size) {
        'sm' => $isProjects ? max(3, min(5, $groups->count())) : max(3, min(5, (int) ceil(count($entries) / 3))),
        'lg' => 2,
        default => $isProjects ? max(2, min(4, $groups->count())) : max(2, min(4, (int) ceil(count($entries) / 4))),
    };
    $sz = match ($size) {
        'sm' => ['row' => 'min-h-9 gap-2 px-2 py-1', 'icon' => 'h-6 w-6 text-[11px]', 'title' => 'text-xs', 'desc' => null, 'proj' => 'text-xs', 'projExcerpt' => null, 'projTake' => 6, 'cat' => 'text-xs', 'gapY' => 'gap-y-0.5'],
        'lg' => ['row' => 'min-h-14 gap-4 px-4 py-3', 'icon' => 'h-11 w-11 text-base', 'title' => 'text-base', 'desc' => 'text-sm', 'proj' => 'text-base', 'projExcerpt' => 'text-sm', 'projTake' => 4, 'cat' => 'text-base', 'gapY' => 'gap-y-2'],
        default => ['row' => 'min-h-11 gap-3 px-3 py-2', 'icon' => 'h-8 w-8 text-sm', 'title' => 'text-sm', 'desc' => 'text-xs', 'proj' => 'text-sm', 'projExcerpt' => 'text-xs', 'projTake' => 5, 'cat' => 'text-sm', 'gapY' => 'gap-y-1'],
    };
@endphp

<li class="static" x-data="{ open: false }" x-id="['mega']"
    @mouseenter="if (!{{ $mobile ? 'true' : 'false' }}) open = true" @mouseleave="if (!{{ $mobile ? 'true' : 'false' }}) open = false"
    @focusout="if (! $el.contains($event.relatedTarget)) open = false"
    @keydown.escape="open = false; $refs.megaTrigger.focus()"
    @click.outside="open = false">

    <div class="flex items-center gap-1 border-b-2 transition-colors {{ $isCurrent ? $activeBdr : 'border-transparent' }} pb-1"
         :class="open ? '{{ $activeBdr }}' : ''">
        @if ($hasTarget)
            <a href="{{ $targetUrl }}" x-ref="megaTrigger"
               @if ($item->isCurrent() && ! $isProjects) aria-current="page" @endif
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

            @if ($isProjects)
                {{-- Kolumny: kategorie projektów (nazwa + liczba, projekty z opisem i statusem) --}}
                @if ($groups->isEmpty())
                    <p class="text-sm text-muted">Brak kategorii projektów.</p>
                @else
                    <div>
                        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-{{ $columns }}">
                            @foreach ($groups as $category)
                                <div>
                                    <a href="{{ route('categories.show', $category) }}" @if ($catCurrentId === $category->id) aria-current="page" @endif
                                       class="mb-2 flex items-center justify-between gap-2 rounded-md px-2 py-1.5 font-bold {{ $sz['cat'] }} hover:bg-gray-50 hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand {{ $catCurrentId === $category->id ? 'text-brand' : 'text-ink' }}">
                                        <span class="flex items-center gap-2"><i class="fa-solid fa-folder-open text-brand" aria-hidden="true"></i>{{ $category->name }}</span>
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-bold text-muted" aria-label="{{ $category->publishedProjects->count() }} projektów">{{ $category->publishedProjects->count() }}</span>
                                    </a>
                                    <ul role="list" class="space-y-0.5 border-l border-gray-100 pl-3">
                                        @foreach ($category->publishedProjects->take($sz['projTake']) as $project)
                                            <li>
                                                <a href="{{ route('projects.show', $project) }}" @if ($groupCurrentId === $project->id) aria-current="page" @endif
                                                   class="group/p block rounded-md px-2 py-1.5 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                                    <span class="flex items-center gap-2 {{ $sz['proj'] }} {{ $groupCurrentId === $project->id ? 'font-semibold text-brand' : 'text-ink group-hover/p:text-brand' }}">
                                                        <span class="h-2 w-2 flex-none rounded-full" style="background: {{ \App\Support\Color::isValid($project->accent_color ?? null) ? $project->accent_color : 'var(--color-brand)' }}" aria-hidden="true"></span>
                                                        <span class="truncate">{{ $project->title }}</span>
                                                        @if ($project->is_completed)
                                                            <span class="ml-auto flex-none rounded bg-emerald-50 px-1.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700">zrealizowany</span>
                                                        @endif
                                                    </span>
                                                    @if ($project->excerpt && $sz['projExcerpt'])
                                                        <span class="mt-0.5 block truncate pl-4 text-muted {{ $sz['projExcerpt'] }}">{{ $project->excerpt }}</span>
                                                    @endif
                                                </a>
                                            </li>
                                        @endforeach
                                        @if ($category->publishedProjects->count() > $sz['projTake'])
                                            <li>
                                                <a href="{{ route('categories.show', $category) }}" class="block rounded-md px-2 py-1.5 text-xs font-bold text-brand hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                                    Wszystkie w tej kategorii ({{ $category->publishedProjects->count() }}) →
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            @endforeach
                        </div>

                        {{-- Stopka panelu: podsumowanie + skróty --}}
                        <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-gray-100 pt-3 text-xs text-muted">
                            <span><i class="fa-solid fa-diagram-project mr-1 text-brand" aria-hidden="true"></i>{{ $allProjects->count() }} {{ trans_choice('projekt|projekty|projektów', $allProjects->count()) }} w {{ $groups->count() }} {{ trans_choice('kategorii|kategoriach|kategoriach', $groups->count()) }}</span>
                            @if ($allProjects->where('is_completed', true)->isNotEmpty())
                                <span><i class="fa-solid fa-circle-check mr-1 text-emerald-600" aria-hidden="true"></i>{{ $allProjects->where('is_completed', true)->count() }} zrealizowanych</span>
                            @endif
                            @if ($navHasProjectArchive ?? false)
                                <a href="{{ route('projects.archive') }}" class="ml-auto font-bold text-brand underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">To już zrobiliśmy →</a>
                            @endif
                        </div>
                    </div>
                @endif
            @else
                <ul role="list" class="grid gap-x-6 {{ $sz['gapY'] }} sm:grid-cols-2 lg:grid-cols-{{ $columns }}">
                    @foreach ($entries as [$url, $label, $description, $icon, $current])
                        <li>
                            <a href="{{ $url }}" @if ($current) aria-current="page" @endif
                               class="group flex items-start rounded-lg transition hover:bg-gray-50 focus-visible:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand {{ $sz['row'] }} {{ $current ? 'bg-brand-light' : '' }}">
                                <span class="mt-0.5 flex flex-none items-center justify-center rounded-md {{ $sz['icon'] }} {{ $current ? 'bg-brand text-white' : 'bg-brand-light text-brand' }}" aria-hidden="true">
                                    <i class="{{ $icon ? 'bi ' . $icon : 'fa-solid fa-arrow-right' }}"></i>
                                </span>
                                <span class="min-w-0 self-center">
                                    <span class="block font-bold {{ $sz['title'] }} {{ $current ? 'text-brand' : 'text-ink group-hover:text-brand' }}">{{ $label }}</span>
                                    @if ($description && $sz['desc'])
                                        <span class="block leading-snug text-muted {{ $sz['desc'] }}">{{ $description }}</span>
                                    @endif
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            {{-- Kolumna boczna: grafika promocyjna (lub wyróżniony projekt), opis, najbliższe szkolenie, CTA --}}
            <div class="hidden flex-col overflow-hidden rounded-xl bg-gray-50 lg:flex">
                @if ($hasImage)
                    <img src="{{ $item->mega_image }}" alt="{{ $item->mega_image_alt ?? '' }}" class="aspect-video w-full object-cover" loading="lazy">
                @elseif ($isProjects && $featuredProject)
                    <a href="{{ route('projects.show', $featuredProject) }}" class="group/f block focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand">
                        <img src="{{ $featuredProject->image_url }}" alt="{{ $featuredProject->image_alt ?: '' }}" class="aspect-video w-full object-cover" loading="lazy">
                        <span class="block px-5 pt-4">
                            <span class="block text-[10px] font-bold uppercase tracking-wide text-brand">Polecany projekt</span>
                            <span class="block text-base font-bold text-ink group-hover/f:text-brand">{{ $featuredProject->title }}</span>
                            @if ($featuredProject->excerpt)
                                <span class="mt-1 line-clamp-2 block text-xs leading-snug text-muted">{{ $featuredProject->excerpt }}</span>
                            @endif
                        </span>
                    </a>
                @endif
                <div class="flex flex-1 flex-col justify-between p-5">
                    @if (! ($isProjects && $featuredProject && ! $hasImage))
                        <div>
                            <p class="text-base font-bold text-ink">{{ $item->label }}</p>
                            @if ($item->description)
                                <p class="mt-1 text-sm leading-snug text-muted">{{ $item->description }}</p>
                            @elseif ($linkedPage && $linkedPage->meta_description)
                                <p class="mt-1 text-sm leading-snug text-muted">{{ $linkedPage->meta_description }}</p>
                            @endif
                        </div>
                    @elseif ($item->description)
                        <p class="text-sm leading-snug text-muted">{{ $item->description }}</p>
                    @else
                        <span></span>
                    @endif

                    @if ($isProjects && $nextEvent)
                        <a href="{{ site_route('events.show', $nextEvent) }}" class="mt-4 flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-3 text-sm transition hover:border-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                            <span class="flex h-11 w-11 flex-none flex-col items-center justify-center rounded-md bg-brand text-white" aria-hidden="true">
                                <span class="text-base font-bold leading-none">{{ $nextEvent->starts_at->format('d') }}</span>
                                <span class="text-[10px] uppercase leading-none">{{ $nextEvent->starts_at->translatedFormat('M') }}</span>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-[10px] font-bold uppercase tracking-wide text-muted">Najbliższe szkolenie</span>
                                <span class="line-clamp-2 block font-semibold leading-snug text-ink">{{ $nextEvent->title }}</span>
                                <span class="sr-only">{{ $nextEvent->starts_at->translatedFormat('j F Y') }}</span>
                            </span>
                        </a>
                    @endif

                    <div class="mt-4 flex flex-col items-start gap-2">
                        @if ($hasTarget)
                            <a href="{{ $targetUrl }}"
                               class="inline-flex min-h-10 items-center gap-2 rounded-full bg-brand px-4 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                {{ $isProjects ? 'Wszystkie projekty' : 'Zobacz wszystko' }} <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                            </a>
                        @endif
                        @if ($isProjects && $siteSettings->isModuleEnabled('events'))
                            <a href="{{ site_route('events.index') }}" class="inline-flex min-h-9 items-center gap-1.5 rounded-md px-2 text-sm font-bold text-brand underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                <i class="fa-solid fa-calendar-days" aria-hidden="true"></i> Wszystkie szkolenia
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</li>
