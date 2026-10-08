{{--
    Zawartość zakładki będącej podstroną projektu. Gdy podstrona ma potomków, po lewej pojawia się menu boczne
    (szare pole, tytuł zakładki, pozycje z kreską i strzałką) — dowolna głębokość; każda pozycja otwiera
    treść swojej podstrony. Zmienna: $root (Page z relacją tree_children).
--}}
<style>@media (min-width: 1024px) { .proj-tab-cols { grid-template-columns: minmax(0, 1fr) 16rem; } .proj-tab-cols > .proj-tab-side { grid-column: 2; grid-row: 1; } }</style>
@php
    $nodes = [];
    $collect = function ($n) use (&$collect, &$nodes) { $nodes[] = $n; foreach ($n->tree_children as $c) { $collect($c); } };
    $collect($root);
    $sidebarMode = $sidebarMode ?? false;
    $hasKids = $root->tree_children->isNotEmpty();
    $hasExtras = filled($project->sidebar_note) || ! empty($project->sidebar_buttons);
    $localMenu = ($hasKids || $hasExtras) && ! $sidebarMode;
@endphp
{{-- W trybie menu bocznego strony stan (node) trzyma wspólny x-data projektu, menu jest poza panelem. --}}
<div @unless ($sidebarMode) x-data="{ node: {{ $root->id }}, openIds: [] }" @endunless @class(['grid items-start gap-8', 'proj-tab-cols' => $localMenu])>
    @if ($localMenu && ! $hasKids)
        {{-- Zakładka bez podstron: tylko elementy dodatkowe (przyciski, notka) w prawej kolumnie --}}
        <div class="proj-tab-side -mt-5">@include('projects.partials.sidebar-extras', ['project' => $project])</div>
    @elseif ($localMenu)
        <nav aria-label="Menu: {{ $root->title }}" class="proj-tab-side relative bg-gray-100 p-6 lg:sticky lg:top-6">
            <span class="absolute block bg-brand" style="left:0;top:0;height:.25rem;width:4rem" aria-hidden="true"></span>
            <p class="mb-4 border-b border-gray-900 pb-3 text-lg font-bold text-ink">{{ $root->title }}</p>
            <ul role="list" class="text-ink">
                <li class="border-b border-gray-300 last:border-0">
                    <button type="button" @click="node = {{ $root->id }}" :aria-current="node === {{ $root->id }} ? 'page' : null"
                        class="flex w-full items-center justify-between py-3 text-left text-base font-normal hover:text-brand focus-visible:outline-2 focus-visible:outline-brand"
                        :class="node === {{ $root->id }} ? 'text-brand' : ''">Przegląd</button>
                </li>
                @foreach ($root->tree_children as $child)
                    @include('projects.partials.tab-page-nav', ['item' => $child, 'depth' => 0])
                @endforeach
            </ul>
    @include('projects.partials.sidebar-extras', ['project' => $project])
        </nav>
    @endif

    <div class="min-w-0">
        @foreach ($nodes as $n)
            <div @if ($sidebarMode && $loop->first) x-show="node === null || node === {{ $n->id }}" @else x-show="node === {{ $n->id }}" @endif @unless ($loop->first) x-cloak @endunless>
                @if (! $loop->first || $hasKids)
                    <h2 class="mb-3 text-xl font-bold text-ink">{{ $n->title }}</h2>
                @endif
                @if ($n->content)
                    <div class="prose max-w-none text-ink">{!! $n->content !!}</div>
                @endif
                @if ($n->isSchedule())
                    @include('partials.schedule', ['page' => $n, 'showHeading' => false])
                @elseif ($n->isFaq())
                    @include('partials.faq', ['page' => $n])
                @endif
                <a href="{{ route('page.show', $n) }}" class="mt-3 inline-flex items-center gap-2 text-sm font-bold text-brand hover:text-brand-dark">Otwórz jako osobną stronę</a>
            </div>
        @endforeach
    </div>
</div>
