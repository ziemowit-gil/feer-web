@extends('layouts.site')

@section('title', 'Organizacje członkowskie — ' . $siteSettings->site_name)
@section('meta_description', 'Poznaj organizacje członkowskie ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Organizacje członkowskie', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-[1400px] px-4 py-12 lg:py-16">
        <p class="mb-3 text-sm font-extrabold uppercase tracking-widest text-brand">Organizacje</p>
        <h1 class="mb-4 max-w-3xl text-3xl font-extrabold leading-tight tracking-tight text-ink sm:text-4xl">
            Poznaj organizacje członkowskie {{ $siteSettings->siteNameGenitive() }}
        </h1>
        <p class="mb-8 max-w-2xl text-base leading-relaxed text-muted">
            Organizacje zrzeszone w {{ $siteSettings->site_name }} w swojej codziennej działalności na pierwszym
            miejscu stawiają pomoc człowiekowi. Rozwijają też pasje i zainteresowania w środowiskach dzieci i
            młodzieży, jak również wspierają edukację i system pomocy zdrowotnej.
        </p>

        <div class="relative mb-12 flex flex-col items-center gap-5 overflow-hidden rounded-lg p-8 text-center sm:flex-row sm:justify-between sm:text-left"
            style="background:{{ $siteSettings->brandColorN(2) }}">
            <i class="fa-solid fa-people-group pointer-events-none absolute -right-4 -top-4 text-[8rem] text-white/10" aria-hidden="true"></i>
            <div class="relative">
                <p class="text-xl font-extrabold leading-snug text-white">Chcesz dołączyć do federacji?</p>
                <p class="mt-1 text-base text-white">Sprawdź, jakie dokumenty są potrzebne i jak wygląda proces przystąpienia.</p>
            </div>
            <a href="{{ route('federation.join') }}"
                class="relative flex-none rounded-md bg-white px-6 py-3 text-sm font-extrabold text-ink transition hover:bg-white/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2"
                style="--tw-ring-offset-color:{{ $siteSettings->brandColorN(2) }}">
                Dołącz do nas
            </a>
        </div>

        {{-- Krótkie statystyki katalogu --}}
        <dl class="mb-8 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-lg border border-gray-100 bg-white p-4">
                <dt class="text-xs font-bold uppercase tracking-wide text-muted">Organizacje {{ $siteSettings->siteNameGenitive() }}</dt>
                <dd class="mt-1 text-2xl font-extrabold text-ink">{{ $stats['total'] }}</dd>
            </div>
            @if ($stats['topTown'])
                <div class="rounded-lg border border-gray-100 bg-white p-4">
                    <dt class="text-xs font-bold uppercase tracking-wide text-muted">Najwięcej organizacji jest z</dt>
                    <dd class="mt-1 text-lg font-extrabold text-ink">
                        {{ $stats['topTown'] }} <span class="text-sm font-semibold text-muted">({{ $stats['topTownCount'] }})</span>
                    </dd>
                </div>
            @endif
            <div class="rounded-lg border border-gray-100 bg-white p-4">
                <dt class="text-xs font-bold uppercase tracking-wide text-muted">Działamy w</dt>
                <dd class="mt-1 text-2xl font-extrabold text-ink">
                    {{ $stats['townsCount'] }} {{ $stats['townsCount'] === 1 ? 'miejscowości' : 'miejscowościach' }}
                </dd>
            </div>
        </dl>

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-4">
                <h2 class="text-2xl font-extrabold tracking-tight text-ink">Organizacje członkowskie</h2>

                {{-- "Jak korzystać z katalogu?" jako modal, otwierany na żądanie. --}}
                <div x-data="{ guideOpen: false }" @keydown.escape.window="if (guideOpen) { guideOpen = false; $refs.guideToggle.focus() }">
                    <button type="button" x-ref="guideToggle" @click="guideOpen = true" aria-haspopup="dialog" :aria-expanded="guideOpen"
                        class="inline-flex items-center gap-1.5 rounded-full border border-brand/30 bg-brand-light/50 px-3 py-1 text-sm font-bold text-brand hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        Jak korzystać z katalogu?
                    </button>

                    <div x-show="guideOpen" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-ink/60 p-4">
                        <div @click.outside="guideOpen = false; $refs.guideToggle.focus()" x-transition
                            x-init="$watch('guideOpen', (open) => { if (open) $nextTick(() => $refs.guideCloseBtn.focus()) })"
                            role="dialog" aria-modal="true" aria-labelledby="org-guide-heading"
                            class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
                            <div class="mb-3 flex items-center justify-between gap-4">
                                <h2 id="org-guide-heading" class="flex items-center gap-2 text-lg font-extrabold text-ink">
                                    <i class="fa-solid fa-circle-info text-brand" aria-hidden="true"></i>
                                    Jak korzystać z katalogu?
                                </h2>
                                <button type="button" x-ref="guideCloseBtn" @click="guideOpen = false; $refs.guideToggle.focus()"
                                    class="flex h-9 w-9 flex-none items-center justify-center rounded-full text-muted hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                    aria-label="Zamknij instrukcję">
                                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                </button>
                            </div>
                            <ul class="space-y-2 text-sm text-ink/80">
                                <li><strong class="text-ink">1.</strong> Wpisz nazwę w wyszukiwarkę albo filtruj po miejscowości i formie prawnej.</li>
                                <li><strong class="text-ink">2.</strong> Przełącz widok — kafelki albo tabela, jak Ci wygodniej.</li>
                                <li><strong class="text-ink">3.</strong> Kliknij kartę organizacji, aby zobaczyć jej pełną wizytówkę.</li>
                                <li><strong class="text-ink">4.</strong> „Mapa" pokazuje przybliżoną lokalizację w nowej karcie.</li>
                                <li><strong class="text-ink">5.</strong> Jesteś przedstawicielem organizacji? Zaloguj się i edytuj swoje dane.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <a href="{{ route('organization.login') }}"
                class="inline-flex items-center gap-1.5 text-sm font-bold text-brand hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                Jesteś przedstawicielem organizacji? Zaloguj się i edytuj wizytówkę
            </a>
        </div>

        <div
            x-data="{
                query: '',
                town: '',
                type: '',
                searchOpen: false,
                view: (function () { try { return localStorage.getItem('org-catalog-view') || 'grid' } catch (e) { return 'grid' } })(),
                orgs: {{ \Illuminate\Support\Js::from($organizations) }},
                towns: [{ value: '', label: 'Wszystkie miejscowości' }, ...{{ \Illuminate\Support\Js::from($towns->map(fn ($t) => ['value' => $t, 'label' => $t])) }}],
                types: [{ value: '', label: 'Wszystkie formy prawne' }, ...{{ \Illuminate\Support\Js::from($types->map(fn ($t) => ['value' => $t, 'label' => $t])) }}],
                get filtered() {
                    const q = this.query.trim().toLowerCase();
                    return this.orgs.filter((o) =>
                        (! q || o.name.toLowerCase().includes(q))
                        && (! this.town || o.town === this.town)
                        && (! this.type || o.type === this.type)
                    );
                },
                setView(v) { this.view = v; try { localStorage.setItem('org-catalog-view', v) } catch (e) {} },
            }"
            @keydown.escape.window="if (searchOpen) { searchOpen = false; $refs.orgSearchToggle.focus() }"
        >
            {{-- Wyszukiwarka, filtry i przełącznik widoku katalogu --}}
            <div class="mb-6 grid gap-3 sm:grid-cols-[auto_auto_auto_auto] sm:justify-start">
                <div>
                    <button type="button" x-ref="orgSearchToggle" @click="searchOpen = true" aria-haspopup="dialog" :aria-expanded="searchOpen"
                        class="flex h-full w-full items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-muted transition hover:border-brand/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand sm:w-auto">
                        <i class="fa-solid fa-magnifying-glass" style="color:{{ $siteSettings->brandColorN(1) }}" aria-hidden="true"></i>
                        <span x-text="query ? query : 'Szukaj po nazwie…'" :class="query ? 'text-ink font-semibold' : ''"></span>
                    </button>

                    <div x-show="searchOpen" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-start justify-center bg-ink/60 p-4 pt-24 sm:pt-32">
                        <div @click.outside="searchOpen = false; $refs.orgSearchToggle.focus()" x-transition
                            x-init="$watch('searchOpen', (open) => { if (open) $nextTick(() => $refs.orgSearchInput.focus()) })"
                            role="dialog" aria-modal="true" aria-labelledby="org-search-modal-heading"
                            class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
                            <div class="mb-4 flex items-center justify-between gap-4">
                                <h2 id="org-search-modal-heading" class="flex items-center gap-3 text-lg font-extrabold text-ink">
                                    <i class="fa-solid fa-magnifying-glass text-2xl" style="color:{{ $siteSettings->brandColorN(1) }}" aria-hidden="true"></i>
                                    Szukaj organizacji
                                </h2>
                                <button type="button" @click="searchOpen = false; $refs.orgSearchToggle.focus()"
                                    class="flex h-9 w-9 flex-none items-center justify-center rounded-full text-muted hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                    aria-label="Zamknij wyszukiwarkę">
                                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                </button>
                            </div>
                            <label for="org-search-modal-input" class="sr-only">Szukaj organizacji po nazwie</label>
                            <div class="flex items-center overflow-hidden rounded-full border-2 focus-within:ring-2"
                                style="border-color:{{ $siteSettings->brandColorN(1) }}; --tw-ring-color:{{ $siteSettings->brandColorN(1) }}">
                                <input id="org-search-modal-input" x-ref="orgSearchInput" type="search" x-model="query"
                                    placeholder="Wpisz nazwę organizacji…" autocomplete="off"
                                    class="w-full border-none bg-transparent px-4 py-3 text-base focus:outline-none focus:ring-0">
                                <span class="flex h-11 w-11 flex-none items-center justify-center text-white" style="background:{{ $siteSettings->brandColorN(1) }}" aria-hidden="true">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </span>
                            </div>
                            <p class="mt-3 text-sm text-muted" aria-live="polite">
                                <span x-text="filtered.length"></span> <span x-text="filtered.length === 1 ? 'wynik' : 'wyników'"></span>
                            </p>
                            <button type="button" @click="searchOpen = false; $refs.orgSearchToggle.focus()"
                                class="mt-2 w-full rounded-md px-5 py-2.5 text-sm font-extrabold text-white transition hover:brightness-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                                style="background:{{ $siteSettings->brandColorN(1) }}; --tw-ring-color:{{ $siteSettings->brandColorN(1) }}">
                                Pokaż wyniki
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Filtr miejscowości: niestandardowa, stylowana listbox (wzorzec WAI-ARIA "combobox select-only"). --}}
                <div class="relative" x-data="{ open: false, active: -1 }" @keydown.escape="open = false" @click.outside="open = false">
                    <span id="org-town-label" class="sr-only">Filtruj po miejscowości</span>
                    <button type="button" role="combobox" aria-haspopup="listbox" aria-labelledby="org-town-label"
                        :aria-expanded="open" :aria-activedescendant="open && active >= 0 ? 'org-town-opt-' + active : null"
                        @click="open = !open; active = towns.findIndex(t => t.value === town)"
                        @keydown.arrow-down.prevent="open = true; active = Math.min(active + 1, towns.length - 1)"
                        @keydown.arrow-up.prevent="open = true; active = Math.max(active - 1, 0)"
                        @keydown.home.prevent="open = true; active = 0"
                        @keydown.end.prevent="open = true; active = towns.length - 1"
                        @keydown.enter.prevent="if (open && active >= 0) { town = towns[active].value; open = false }"
                        class="flex w-full items-center justify-between gap-2 rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm transition hover:border-brand/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand sm:w-auto sm:min-w-[11rem]">
                        <span x-text="towns.find(t => t.value === town)?.label"></span>
                        <i class="fa-solid fa-chevron-down text-xs text-muted transition-transform duration-200" :class="{ 'rotate-180': open }" aria-hidden="true"></i>
                    </button>
                    <ul x-show="open" x-cloak id="org-town-listbox" role="listbox" aria-labelledby="org-town-label" tabindex="-1"
                        class="absolute z-20 mt-1 max-h-64 w-56 overflow-y-auto rounded-lg border border-gray-200 bg-white p-1 shadow-lg">
                        <template x-for="(t, i) in towns" :key="t.value">
                            <li :id="'org-town-opt-' + i" role="option" :aria-selected="town === t.value" @click="town = t.value; open = false" @mouseenter="active = i"
                                class="flex cursor-pointer items-center justify-between rounded px-3 py-2 text-sm"
                                :class="[town === t.value ? 'font-semibold text-brand' : 'text-ink', active === i ? 'bg-gray-50' : '']">
                                <span x-text="t.label"></span>
                                <i class="fa-solid fa-check text-xs" x-show="town === t.value" aria-hidden="true"></i>
                            </li>
                        </template>
                    </ul>
                </div>

                {{-- Filtr formy prawnej: ten sam wzorzec co miejscowość. --}}
                <div class="relative" x-data="{ open: false, active: -1 }" @keydown.escape="open = false" @click.outside="open = false">
                    <span id="org-type-label" class="sr-only">Filtruj po formie prawnej</span>
                    <button type="button" role="combobox" aria-haspopup="listbox" aria-labelledby="org-type-label"
                        :aria-expanded="open" :aria-activedescendant="open && active >= 0 ? 'org-type-opt-' + active : null"
                        @click="open = !open; active = types.findIndex(t => t.value === type)"
                        @keydown.arrow-down.prevent="open = true; active = Math.min(active + 1, types.length - 1)"
                        @keydown.arrow-up.prevent="open = true; active = Math.max(active - 1, 0)"
                        @keydown.home.prevent="open = true; active = 0"
                        @keydown.end.prevent="open = true; active = types.length - 1"
                        @keydown.enter.prevent="if (open && active >= 0) { type = types[active].value; open = false }"
                        class="flex w-full items-center justify-between gap-2 rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm transition hover:border-brand/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand sm:w-auto sm:min-w-[11rem]">
                        <span x-text="types.find(t => t.value === type)?.label"></span>
                        <i class="fa-solid fa-chevron-down text-xs text-muted transition-transform duration-200" :class="{ 'rotate-180': open }" aria-hidden="true"></i>
                    </button>
                    <ul x-show="open" x-cloak id="org-type-listbox" role="listbox" aria-labelledby="org-type-label" tabindex="-1"
                        class="absolute z-20 mt-1 max-h-64 w-60 overflow-y-auto rounded-lg border border-gray-200 bg-white p-1 shadow-lg">
                        <template x-for="(t, i) in types" :key="t.value">
                            <li :id="'org-type-opt-' + i" role="option" :aria-selected="type === t.value" @click="type = t.value; open = false" @mouseenter="active = i"
                                class="flex cursor-pointer items-center justify-between rounded px-3 py-2 text-sm"
                                :class="[type === t.value ? 'font-semibold text-brand' : 'text-ink', active === i ? 'bg-gray-50' : '']">
                                <span x-text="t.label"></span>
                                <i class="fa-solid fa-check text-xs" x-show="type === t.value" aria-hidden="true"></i>
                            </li>
                        </template>
                    </ul>
                </div>

                <div class="flex items-center gap-1 rounded-md border border-gray-300 bg-white p-1" role="group" aria-label="Widok katalogu">
                    <button type="button" @click="setView('grid')" :aria-pressed="view === 'grid'"
                        class="flex h-8 w-9 items-center justify-center rounded transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                        :class="view === 'grid' ? 'bg-brand-light text-brand' : 'text-muted hover:text-brand'">
                        <i class="fa-solid fa-table-cells-large text-xs" aria-hidden="true"></i>
                        <span class="sr-only">Widok kafelków</span>
                    </button>
                    <button type="button" @click="setView('table')" :aria-pressed="view === 'table'"
                        class="flex h-8 w-9 items-center justify-center rounded transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                        :class="view === 'table' ? 'bg-brand-light text-brand' : 'text-muted hover:text-brand'">
                        <i class="fa-solid fa-table-list text-xs" aria-hidden="true"></i>
                        <span class="sr-only">Widok tabeli</span>
                    </button>
                </div>
            </div>

            <p class="sr-only" role="status" aria-live="polite" x-text="`Znaleziono ${filtered.length} ${filtered.length === 1 ? 'organizację' : 'organizacji'}`"></p>
            <p class="mb-4 text-sm text-muted" aria-hidden="true">
                <span x-text="filtered.length"></span> z {{ $organizations->count() }} organizacji
            </p>

            {{-- Widok kafelków --}}
            <ul x-show="view === 'grid'" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" role="list">
                <template x-for="org in filtered" :key="org.name">
                    <li class="relative flex flex-col gap-3 rounded-lg border border-gray-100 bg-white p-5 shadow-sm transition hover:border-brand/40 hover:shadow-md">
                        <div class="flex items-start gap-3">
                            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                                <i class="fa-solid fa-people-roof text-sm"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-bold leading-snug text-ink" x-text="org.name"></p>
                                <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted">
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 font-semibold text-ink/70" x-text="org.type"></span>
                                    <span><i class="fa-solid fa-location-dot mr-1" aria-hidden="true"></i><span x-text="org.town"></span></span>
                                </p>
                                <p class="mt-1 flex flex-wrap gap-1" x-show="org.spheres.length">
                                    <template x-for="(sphere, i) in org.spheres" :key="sphere">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-brand-light px-2 py-0.5 text-xs font-semibold text-brand">
                                            <i class="fa-solid" :class="org.sphereIcons[i] || 'fa-circle-info'" aria-hidden="true"></i>
                                            <span x-text="sphere"></span>
                                        </span>
                                    </template>
                                </p>
                            </div>
                        </div>
                        <p class="text-sm leading-relaxed text-muted" x-text="org.description"></p>

                        {{-- Cała karta prowadzi do wizytówki organizacji ("stretched link"); przycisk „Mapa" pozostaje osobno klikalny. --}}
                        <a :href="org.showUrl" class="absolute inset-0 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                            <span class="sr-only" x-text="`Zobacz wizytówkę: ${org.name}`"></span>
                        </a>
                        <a :href="org.mapUrl" target="_blank" rel="noopener"
                            class="relative z-10 mt-auto inline-flex items-center gap-1.5 self-start text-sm font-bold text-brand hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                            <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
                            Mapa
                            <span class="sr-only" x-text="`: ${org.name} (otwiera się w nowej karcie)`"></span>
                        </a>
                    </li>
                </template>
            </ul>

            {{-- Widok tabeli (styl katalogu organizacji pozarządowych ngo.krakow.pl) --}}
            <div x-show="view === 'table'" class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">Lista organizacji członkowskich z formą prawną, miejscowością i linkiem do mapy</caption>
                    <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wide text-muted">
                        <tr>
                            <th scope="col" class="px-4 py-3">Nazwa</th>
                            <th scope="col" class="px-4 py-3">Forma prawna</th>
                            <th scope="col" class="px-4 py-3">Miejscowość</th>
                            <th scope="col" class="px-4 py-3">Mapa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <template x-for="org in filtered" :key="org.name">
                            <tr>
                                <td class="px-4 py-3 font-semibold text-ink">
                                    <a :href="org.showUrl" class="hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" x-text="org.name"></a>
                                </td>
                                <td class="px-4 py-3 text-muted" x-text="org.type"></td>
                                <td class="px-4 py-3 text-muted" x-text="org.town"></td>
                                <td class="px-4 py-3">
                                    <a :href="org.mapUrl" target="_blank" rel="noopener"
                                        class="inline-flex items-center gap-1 font-bold text-brand hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                        <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> Mapa
                                        <span class="sr-only" x-text="`: ${org.name} (otwiera się w nowej karcie)`"></span>
                                    </a>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <p class="mt-6 rounded-lg border border-dashed border-gray-200 p-6 text-center text-sm text-muted" x-show="filtered.length === 0" x-cloak>
                Brak organizacji spełniających kryteria wyszukiwania.
            </p>
        </div>
    </section>

    @include('templates.federation.partials.home.cta-banner')
@endsection
