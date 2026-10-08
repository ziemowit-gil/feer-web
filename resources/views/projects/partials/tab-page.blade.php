{{--
    Zawartość zakładki będącej podstroną projektu. Gdy podstrona ma potomków, po lewej pojawia się menu boczne
    (szare pole, tytuł zakładki, pozycje z kreską i strzałką) — dowolna głębokość; każda pozycja otwiera
    treść swojej podstrony. Zmienna: $root (Page z relacją tree_children).
--}}
@php
    $nodes = [];
    $collect = function ($n) use (&$collect, &$nodes) { $nodes[] = $n; foreach ($n->tree_children as $c) { $collect($c); } };
    $collect($root);
    $sidebarMode = $sidebarMode ?? false;
    $hasKids = $root->tree_children->isNotEmpty();
    $localMenu = $hasKids && ! $sidebarMode;
@endphp
{{-- W trybie menu bocznego strony stan (node) trzyma wspólny x-data projektu, menu jest poza panelem. --}}
<div @unless ($sidebarMode) x-data="{ node: {{ $root->id }}, openIds: [] }" @endunless @class(['grid items-start gap-8', 'lg:grid-cols-[16rem_minmax(0,1fr)]' => $localMenu])>
    @if ($localMenu)
        <nav aria-label="Menu: {{ $root->title }}" class="relative bg-gray-100 p-6 lg:sticky lg:top-6">
            <span class="absolute -left-2 -top-2 h-6 w-6 bg-brand" aria-hidden="true"></span>
            <p class="mb-4 border-b border-gray-900 pb-3 text-lg font-bold text-ink">{{ $root->title }}</p>
            <ul role="list" class="text-ink">
                <li class="border-b border-gray-300 last:border-0">
                    <button type="button" @click="node = {{ $root->id }}" :aria-current="node === {{ $root->id }} ? 'page' : null"
                        class="flex w-full items-center justify-between py-3 text-left font-bold hover:text-brand focus-visible:outline-2 focus-visible:outline-brand"
                        :class="node === {{ $root->id }} ? 'text-brand' : ''">Przegląd</button>
                </li>
                @foreach ($root->tree_children as $child)
                    @include('projects.partials.tab-page-nav', ['item' => $child, 'depth' => 0])
                @endforeach
            </ul>
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
