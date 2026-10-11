{{--
    Zawartość zakładki będącej podstroną projektu. Gdy podstrona ma potomków, po lewej pojawia się menu boczne
    (szare pole, tytuł zakładki, pozycje ze strzałką — bez kresek) — dowolna głębokość; każda pozycja otwiera
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
    // Po wysłaniu formularza z błędami wracamy na stronę działania — otwieramy podstronę z tym formularzem.
    $openNodeId = collect($nodes)->contains(fn ($n) => $n->id === ($openNodeId ?? null)) ? $openNodeId : $root->id;
@endphp
{{-- W trybie menu bocznego strony stan (node) trzyma wspólny x-data działania, menu jest poza panelem. --}}
<div @unless ($sidebarMode) x-data="{ node: {{ $openNodeId }}, openIds: [] }" @endunless @class(['grid items-start gap-8', 'proj-tab-cols' => $localMenu])>
    @if ($localMenu && ! $hasKids)
        {{-- Zakładka bez podstron: tylko elementy dodatkowe (przyciski, notka) w prawej kolumnie --}}
        <div class="proj-tab-side -mt-5">@include('projects.partials.sidebar-extras', ['project' => $project])</div>
    @elseif ($localMenu)
        <nav aria-label="Menu: {{ $root->title }}" class="proj-tab-side rounded-md bg-gray-50 p-6 lg:sticky lg:top-24">
            <p class="mb-2 text-lg font-bold text-ink">{{ $root->title }}</p>
            <ul role="list" class="text-ink">
                <li>
                    <button type="button" @click="node = {{ $root->id }}" :aria-current="node === {{ $root->id }} ? 'page' : null"
                        class="flex w-full items-center justify-between py-3 text-left text-base hover:text-brand-dark hover:underline hover:underline-offset-4 focus-visible:outline-2 focus-visible:outline-brand-dark"
                        :class="node === {{ $root->id }} ? 'font-bold text-brand-dark' : ''">Przegląd</button>
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
                    <div class="prose max-w-none text-ink">@shortcodes($n->content)</div>
                @endif
                @if ($n->type === 'form_page')
                    {{-- Typ „Formularz”: formularz z modułu Formularze pod treścią podstrony (jak na samodzielnej stronie). --}}
                    @php
                        $tabFormSlug = (string) ($n->typeData()['fp_form'] ?? '');
                        $tabForm = filled($tabFormSlug) && $siteSettings->isModuleEnabled('forms')
                            ? \App\Models\FormDefinition::where('slug', $tabFormSlug)->where('is_active', true)->first()
                            : null;
                        $tabFormHeading = $n->typeData()['fp_heading'] ?? null;
                    @endphp
                    @if (filled($tabFormHeading))
                        <h3 class="mt-8 text-xl font-bold text-ink">{{ $tabFormHeading }}</h3>
                    @endif
                    @if ($tabForm)
                        @include('formularz._embed', ['form' => $tabForm, 'headingTag' => (! $loop->first || $hasKids || filled($tabFormHeading)) ? 'h3' : 'h2'])
                    @else
                        <p class="mt-4 rounded-md bg-gray-50 p-5 text-ink">Formularz jest chwilowo niedostępny. Skontaktuj się z nami, a przyjmiemy zgłoszenie w inny sposób.</p>
                    @endif
                @endif
                @if ($n->isSchedule())
                    @include('partials.schedule', ['page' => $n, 'showHeading' => false])
                @elseif ($n->isFaq())
                    @include('partials.faq', ['page' => $n])
                @endif
            </div>
        @endforeach
    </div>
</div>
