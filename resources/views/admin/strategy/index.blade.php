@extends('admin.layout')

@section('title', 'Strategia organizacji')

@section('content')
{{--
    Moduł „Strategia organizacji" — planowanie działań operacyjnych.
    Cała interakcja (filtry, widoki, formularz w drawerze, słowniki)
    działa na stanie Alpine.js; dane wymieniane z backendem przez fetch/JSON.
--}}
{{-- Alpine 3 sam wywołuje metodę init() komponentu — bez dodatkowego x-init --}}
<div x-data="strategyApp()" @keydown.escape.window="closeDrawer(); dict.open = false">

    {{-- Komunikaty statusu dla czytników ekranu i wizualny toast --}}
    <div aria-live="polite" class="sr-only" x-text="statusMsg"></div>
    <div x-show="statusMsg" x-cloak x-transition.opacity
         class="fixed bottom-6 left-1/2 z-[70] -translate-x-1/2 rounded-lg bg-ink px-4 py-2 text-sm font-semibold text-white shadow-xl"
         x-text="statusMsg"></div>

    {{-- ── Pasek narzędzi: rok, widok, akcje ─────────────────────────── --}}
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <div class="flex items-center rounded-lg border border-gray-300 bg-white" role="group" aria-label="Wybór roku planowania">
            <button type="button" @click="changeYear(-1)"
                class="rounded-l-lg px-3 py-2 text-muted hover:bg-gray-100 hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                aria-label="Poprzedni rok">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            </button>
            <span class="min-w-[5rem] px-2 text-center text-lg font-bold tabular-nums" x-text="year"></span>
            <button type="button" @click="changeYear(1)"
                class="rounded-r-lg px-3 py-2 text-muted hover:bg-gray-100 hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                aria-label="Następny rok">
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </button>
        </div>

        <div class="flex rounded-lg border border-gray-300 bg-white p-1" role="group" aria-label="Sposób prezentacji planu">
            <button type="button" @click="setView('table')" :aria-pressed="(view === 'table').toString()"
                :class="view === 'table' ? 'bg-brand text-white' : 'text-muted hover:text-brand'"
                class="flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                <i class="fa-solid fa-table-list" aria-hidden="true"></i> Tabela
            </button>
            <button type="button" @click="setView('grid')" :aria-pressed="(view === 'grid').toString()"
                :class="view === 'grid' ? 'bg-brand text-white' : 'text-muted hover:text-brand'"
                class="flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                <i class="fa-solid fa-calendar-days" aria-hidden="true"></i> Siatka miesięcy
            </button>
        </div>

        <div class="ml-auto flex items-center gap-2">
            @if (auth()->user()->isAdmin())
                <button type="button" @click="dict.open = true"
                    class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-muted hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                    <i class="fa-solid fa-book" aria-hidden="true"></i> Słowniki
                </button>
            @endif
            <button type="button" @click="openCreate()"
                class="flex items-center gap-2 rounded-lg bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj działanie
            </button>
        </div>
    </div>

    {{-- ── Podsumowanie: liczby, budżet, rozkład na miesiące i grupy ── --}}
    <div class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Działania w roku</p>
            <p class="mt-1 text-3xl font-bold text-ink" x-text="summary.total_count"></p>
            <p class="mt-1 text-xs text-muted">po zastosowaniu filtrów</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Zaplanowany budżet</p>
            <p class="mt-1 text-3xl font-bold text-ink" x-text="money(summary.total_budget)"></p>
            <p class="mt-1 text-xs text-muted">suma budżetów działań</p>
        </div>

        {{-- Mini-wykres: budżet per miesiąc (dostępny też jako lista dla czytników) --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Budżet wg miesięcy</p>
            <div class="mt-3 flex h-16 items-end gap-1" aria-hidden="true">
                <template x-for="m in summary.per_month" :key="m.month">
                    <div class="group relative flex-1">
                        <div class="w-full rounded-t bg-brand-light transition-colors group-hover:bg-brand"
                             :class="m.month === highlightMonth ? 'bg-brand' : ''"
                             :style="`height:${monthBarHeight(m)}px`"></div>
                        <span class="mt-1 block text-center text-[9px] text-muted" x-text="monthShort(m.month)"></span>
                    </div>
                </template>
            </div>
            <ul class="sr-only">
                <template x-for="m in summary.per_month" :key="'sr' + m.month">
                    <li x-text="`${monthName(m.month)}: ${m.count} działań, budżet ${money(m.budget)}`"></li>
                </template>
            </ul>
        </div>

        {{-- Top grupy docelowe --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-muted">Budżet wg grup docelowych</p>
            <ul class="mt-2 space-y-1.5">
                <template x-for="g in summary.per_group.slice(0, 4)" :key="g.id">
                    <li class="flex items-center justify-between gap-2 text-sm">
                        <span class="min-w-0 truncate text-ink" x-text="g.name"></span>
                        <span class="whitespace-nowrap font-semibold tabular-nums text-ink" x-text="money(g.budget)"></span>
                    </li>
                </template>
                <li x-show="summary.per_group.length === 0" class="text-sm text-muted">Brak danych — dodaj pierwsze działanie.</li>
            </ul>
            <p x-show="summary.per_group.length > 0" class="mt-2 text-[11px] leading-snug text-muted">
                Działanie z kilkoma grupami liczone jest w każdej z nich.
            </p>
        </div>
    </div>

    {{-- ── Filtry ─────────────────────────────────────────────────────── --}}
    <form class="mb-4 grid gap-3 rounded-xl border border-gray-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-6" @submit.prevent>
        <div class="lg:col-span-2">
            <label for="f-q" class="mb-1 block text-xs font-semibold text-muted">Szukaj</label>
            <input id="f-q" type="search" x-model="filters.q" @input.debounce.400ms="load()"
                placeholder="Nazwa lub opis działania…"
                class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
        </div>
        <div>
            <label for="f-group" class="mb-1 block text-xs font-semibold text-muted">Grupa docelowa</label>
            <select id="f-group" x-model="filters.target_group_id" @change="load()"
                class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                <option value="">Wszystkie</option>
                <template x-for="g in config.targetGroups" :key="g.id">
                    <option :value="g.id" x-text="g.name"></option>
                </template>
            </select>
        </div>
        <div>
            <label for="f-type" class="mb-1 block text-xs font-semibold text-muted">Typ działania</label>
            <select id="f-type" x-model="filters.action_type_id" @change="load()"
                class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                <option value="">Wszystkie</option>
                <template x-for="t in config.actionTypes" :key="t.id">
                    <option :value="t.id" x-text="t.name"></option>
                </template>
            </select>
        </div>
        <div>
            <label for="f-funding" class="mb-1 block text-xs font-semibold text-muted">Finansowanie</label>
            <select id="f-funding" x-model="filters.funding_source_id" @change="load()"
                class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                <option value="">Wszystkie</option>
                <template x-for="s in config.fundingSources" :key="s.id">
                    <option :value="s.id" x-text="s.name"></option>
                </template>
            </select>
        </div>
        <div>
            <label for="f-status" class="mb-1 block text-xs font-semibold text-muted">Status</label>
            <select id="f-status" x-model="filters.status" @change="load()"
                class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                <option value="">Wszystkie</option>
                <template x-for="(label, key) in config.statuses" :key="key">
                    <option :value="key" x-text="label"></option>
                </template>
            </select>
        </div>
    </form>

    {{-- ── Widok: TABELA ─────────────────────────────────────────────── --}}
    <div x-show="view === 'table'" class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <caption class="sr-only">Plan działań organizacji — lista działań w wybranym roku</caption>
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Miesiąc</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Działanie</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Grupy docelowe</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Finansowanie</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-muted">Budżet</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Zasoby</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Status</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-muted">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <template x-for="plan in plans" :key="plan.id">
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-ink" x-text="monthName(plan.month)"></td>
                        <td class="max-w-xs px-4 py-3">
                            <p class="font-semibold text-ink" x-text="plan.title"></p>
                            <p class="mt-0.5 flex flex-wrap items-center gap-x-2 text-xs text-muted">
                                <span class="inline-flex items-center gap-1">
                                    <i class="fa-solid" :class="plan.action_type?.icon || 'fa-tag'" aria-hidden="true"></i>
                                    <span x-text="plan.action_type?.name"></span>
                                </span>
                                <span x-show="plan.statute_ref" class="inline-flex items-center gap-1" :title="plan.statute_ref?.title">
                                    <i class="fa-solid fa-scale-balanced" aria-hidden="true"></i>
                                    <span x-text="plan.statute_ref?.code"></span>
                                </span>
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex max-w-[14rem] flex-wrap gap-1">
                                <template x-for="g in plan.target_groups" :key="g.id">
                                    <span class="rounded-full bg-brand-light px-2 py-0.5 text-xs font-semibold text-brand" x-text="g.name"></span>
                                </template>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-muted">
                            <span x-text="plan.funding_source?.name ?? '—'"></span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-ink" x-text="money(plan.budget_planned)"></td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex items-center gap-3 text-xs text-muted" :aria-label="resourcesAria(plan)">
                                <span class="inline-flex items-center gap-1" title="Zasoby ludzkie"><i class="fa-solid fa-user-group" aria-hidden="true"></i><span x-text="resourceCount(plan, 'human')"></span></span>
                                <span class="inline-flex items-center gap-1" title="Sprzęt"><i class="fa-solid fa-toolbox" aria-hidden="true"></i><span x-text="resourceCount(plan, 'equipment')"></span></span>
                                <span class="inline-flex items-center gap-1" title="Pozycje finansowe"><i class="fa-solid fa-coins" aria-hidden="true"></i><span x-text="resourceCount(plan, 'financial')"></span></span>
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold" :class="statusClass(plan.status)" x-text="plan.status_label"></span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <button type="button" @click="openEdit(plan)"
                                class="rounded-lg p-2 text-muted hover:bg-brand-light hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                :aria-label="`Edytuj działanie: ${plan.title}`">
                                <i class="fa-solid fa-pen" aria-hidden="true"></i>
                            </button>
                            <button type="button" @click="removePlan(plan)"
                                class="rounded-lg p-2 text-muted hover:bg-red-50 hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                                :aria-label="`Usuń działanie: ${plan.title}`">
                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                </template>
                <tr x-show="!loading && plans.length === 0">
                    <td colspan="8" class="px-4 py-14 text-center">
                        <div class="mx-auto flex max-w-xs flex-col items-center gap-3">
                            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-2xl text-gray-400">
                                <i class="fa-solid fa-chess" aria-hidden="true"></i>
                            </span>
                            <p class="text-sm text-muted">Brak działań spełniających kryteria. Dodaj pierwsze działanie do planu.</p>
                            <button type="button" @click="openCreate()"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj działanie
                            </button>
                        </div>
                    </td>
                </tr>
                <tr x-show="loading">
                    <td colspan="8" class="px-4 py-10 text-center text-sm text-muted">
                        <i class="fa-solid fa-spinner fa-spin mr-2" aria-hidden="true"></i>Wczytywanie planu…
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- ── Widok: SIATKA MIESIĘCY ────────────────────────────────────── --}}
    <div x-show="view === 'grid'" x-cloak class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <template x-for="m in summary.per_month" :key="'grid' + m.month">
            <section class="flex flex-col rounded-xl border border-gray-200 bg-white"
                     :aria-label="`${monthName(m.month)} ${year}`">
                <header class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-ink" x-text="monthName(m.month)"></h2>
                    <span class="text-xs tabular-nums text-muted" x-text="`${m.count} dział. · ${money(m.budget)}`"></span>
                </header>
                <div class="flex-1 space-y-2 p-3">
                    <template x-for="plan in plansInMonth(m.month)" :key="'card' + plan.id">
                        <button type="button" @click="openEdit(plan)"
                            class="block w-full rounded-lg border border-gray-200 p-3 text-left transition-colors hover:border-brand hover:bg-brand-light/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                            :aria-label="`Edytuj działanie: ${plan.title}`">
                            <span class="flex items-start justify-between gap-2">
                                <span class="min-w-0 text-sm font-semibold leading-snug text-ink" x-text="plan.title"></span>
                                <span class="mt-1 h-2.5 w-2.5 flex-none rounded-full" :class="statusDot(plan.status)" role="img" :aria-label="plan.status_label"></span>
                            </span>
                            <span class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted">
                                <span class="inline-flex items-center gap-1">
                                    <i class="fa-solid" :class="plan.action_type?.icon || 'fa-tag'" aria-hidden="true"></i>
                                    <span x-text="plan.action_type?.name"></span>
                                </span>
                                <span class="font-semibold tabular-nums text-ink" x-text="money(plan.budget_planned)"></span>
                            </span>
                            <span class="mt-1.5 flex flex-wrap gap-1">
                                <template x-for="g in plan.target_groups" :key="'cg' + plan.id + '-' + g.id">
                                    <span class="rounded-full bg-brand-light px-2 py-0.5 text-[10px] font-semibold text-brand" x-text="g.name"></span>
                                </template>
                            </span>
                        </button>
                    </template>
                    <button type="button" @click="openCreate(m.month)"
                        class="flex w-full items-center justify-center gap-1.5 rounded-lg border border-dashed border-gray-300 px-3 py-2 text-xs font-semibold text-muted hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        <span x-text="`Dodaj w: ${monthName(m.month)}`"></span>
                    </button>
                </div>
            </section>
        </template>
    </div>

    {{-- ── DRAWER: formularz działania ───────────────────────────────── --}}
    <div x-show="drawer.open" x-cloak class="fixed inset-0 z-[60]" role="dialog" aria-modal="true" aria-labelledby="drawer-title">
        <div class="absolute inset-0 bg-black/40" @click="closeDrawer()" aria-hidden="true"></div>
        <div class="absolute inset-y-0 right-0 flex w-full max-w-2xl flex-col bg-white shadow-2xl"
             x-show="drawer.open"
             x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">

            <header class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h2 id="drawer-title" class="text-lg font-bold text-ink" x-text="drawer.form.id ? 'Edytuj działanie' : 'Nowe działanie'"></h2>
                <button type="button" @click="closeDrawer()"
                    class="rounded-lg p-2 text-muted hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                    aria-label="Zamknij formularz">
                    <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                </button>
            </header>

            <form class="flex-1 space-y-6 overflow-y-auto px-6 py-5" @submit.prevent="save()" novalidate>
                {{-- Podstawy --}}
                <div>
                    <label for="p-title" class="mb-1 block text-sm font-semibold text-ink">Nazwa działania <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="p-title" type="text" x-model="drawer.form.title" x-ref="firstField" required
                        :aria-invalid="drawer.errors.title ? 'true' : 'false'" :aria-describedby="drawer.errors.title ? 'err-title' : null"
                        class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
                    <p x-show="drawer.errors.title" id="err-title" class="mt-1 text-sm text-red-600" x-text="drawer.errors.title"></p>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="p-month" class="mb-1 block text-sm font-semibold text-ink">Miesiąc <span class="text-red-600" aria-hidden="true">*</span></label>
                        <select id="p-month" x-model.number="drawer.form.month" class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
                            <template x-for="(name, num) in config.months" :key="num">
                                <option :value="Number(num)" x-text="name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label for="p-year" class="mb-1 block text-sm font-semibold text-ink">Rok <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input id="p-year" type="number" min="2020" max="2100" x-model.number="drawer.form.year"
                            class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
                        <p x-show="drawer.errors.year" class="mt-1 text-sm text-red-600" x-text="drawer.errors.year"></p>
                    </div>
                    <div>
                        <label for="p-status" class="mb-1 block text-sm font-semibold text-ink">Status</label>
                        <select id="p-status" x-model="drawer.form.status" class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
                            <template x-for="(label, key) in config.statuses" :key="'st' + key">
                                <option :value="key" x-text="label"></option>
                            </template>
                        </select>
                    </div>
                </div>

                {{-- Grupy docelowe jako dostępne „chipy" checkboxów --}}
                <fieldset>
                    <legend class="mb-1 text-sm font-semibold text-ink">Grupy docelowe <span class="text-red-600" aria-hidden="true">*</span></legend>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="g in config.targetGroups" :key="'tg' + g.id">
                            <label class="cursor-pointer">
                                <input type="checkbox" class="peer sr-only" :value="g.id"
                                    :checked="drawer.form.target_groups.includes(g.id)" @change="toggleGroup(g.id)">
                                <span class="inline-block rounded-full border px-3 py-1.5 text-sm font-semibold transition-colors
                                             peer-focus-visible:ring-2 peer-focus-visible:ring-brand peer-focus-visible:ring-offset-1"
                                      :class="drawer.form.target_groups.includes(g.id)
                                        ? 'border-brand bg-brand text-white'
                                        : 'border-gray-300 text-muted hover:border-brand hover:text-brand'"
                                      x-text="g.name"></span>
                            </label>
                        </template>
                    </div>
                    <p x-show="drawer.errors.target_groups" class="mt-1 text-sm text-red-600" x-text="drawer.errors.target_groups"></p>
                </fieldset>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="p-type" class="mb-1 block text-sm font-semibold text-ink">Typ działania <span class="text-red-600" aria-hidden="true">*</span></label>
                        <select id="p-type" x-model.number="drawer.form.action_type_id" class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
                            <option :value="null">— wybierz —</option>
                            <template x-for="t in config.actionTypes" :key="'at' + t.id">
                                <option :value="t.id" x-text="t.name"></option>
                            </template>
                        </select>
                        <p x-show="drawer.errors.action_type_id" class="mt-1 text-sm text-red-600" x-text="drawer.errors.action_type_id"></p>
                    </div>
                    <div>
                        <label for="p-statute" class="mb-1 block text-sm font-semibold text-ink">Powiązanie statutowe</label>
                        <select id="p-statute" x-model.number="drawer.form.statute_ref_id" class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
                            <option :value="null">— brak —</option>
                            <template x-for="s in config.statuteRefs" :key="'sr' + s.id">
                                <option :value="s.id" x-text="`${s.code} — ${s.title}`"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label for="p-funding" class="mb-1 block text-sm font-semibold text-ink">Źródło finansowania</label>
                        <select id="p-funding" x-model.number="drawer.form.funding_source_id" class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
                            <option :value="null">— nie wybrano —</option>
                            <template x-for="s in config.fundingSources" :key="'fs' + s.id">
                                <option :value="s.id" x-text="`${s.name} (${config.fundingKinds[s.kind] ?? s.kind})`"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label for="p-budget" class="mb-1 block text-sm font-semibold text-ink">Budżet działania (PLN) <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input id="p-budget" type="number" min="0" step="0.01" x-model.number="drawer.form.budget_planned"
                            class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand"
                            aria-describedby="budget-hint">
                        <p id="budget-hint" class="mt-1 text-xs text-muted">
                            Suma pozycji finansowych poniżej: <strong class="tabular-nums" x-text="money(financialSum())"></strong>
                            <button type="button" x-show="financialSum() > 0 && financialSum() !== Number(drawer.form.budget_planned)"
                                @click="drawer.form.budget_planned = financialSum()"
                                class="ml-1 font-semibold text-brand underline hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                przepisz do budżetu
                            </button>
                        </p>
                        <p x-show="drawer.errors.budget_planned" class="mt-1 text-sm text-red-600" x-text="drawer.errors.budget_planned"></p>
                    </div>
                </div>

                <div>
                    <label for="p-desc" class="mb-1 block text-sm font-semibold text-ink">Opis / uwagi</label>
                    <textarea id="p-desc" rows="3" x-model="drawer.form.description"
                        class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand"></textarea>
                </div>

                {{-- Alokacja zasobów --}}
                <fieldset class="rounded-xl border border-gray-200 p-4">
                    <legend class="px-1 text-sm font-bold text-ink">Podział zasobów</legend>
                    <div class="mb-3 flex flex-wrap gap-2">
                        <button type="button" @click="addResource('human')"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-muted hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                            <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Osoba / zespół
                        </button>
                        <button type="button" @click="addResource('equipment')"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-muted hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                            <i class="fa-solid fa-toolbox" aria-hidden="true"></i> Sprzęt
                        </button>
                        <button type="button" @click="addResource('financial')"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-muted hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                            <i class="fa-solid fa-coins" aria-hidden="true"></i> Pozycja finansowa
                        </button>
                    </div>

                    <p x-show="drawer.form.resources.length === 0" class="text-sm text-muted">Brak przypisanych zasobów — dodaj pozycje przyciskami powyżej.</p>

                    <ul class="space-y-3">
                        <template x-for="(res, idx) in drawer.form.resources" :key="res._key">
                            <li class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-muted">
                                        <i class="fa-solid" :class="resourceIcon(res.type)" aria-hidden="true"></i>
                                        <span x-text="config.resourceTypes[res.type]"></span>
                                    </span>
                                    <button type="button" @click="drawer.form.resources.splice(idx, 1)"
                                        class="rounded p-1.5 text-muted hover:bg-red-50 hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                                        :aria-label="`Usuń pozycję zasobów nr ${idx + 1}`">
                                        <i class="fa-solid fa-trash-can text-sm" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div class="grid gap-2 sm:grid-cols-12">
                                    {{-- Zasób ludzki: opcjonalny wybór osoby z panelu --}}
                                    <div class="sm:col-span-4" x-show="res.type === 'human'">
                                        <label :for="`r-user-${idx}`" class="mb-0.5 block text-xs font-semibold text-muted">Osoba z zespołu</label>
                                        <select :id="`r-user-${idx}`" x-model.number="res.user_id" @change="onResourceUserChange(res)"
                                            class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                            <option :value="null">— spoza panelu —</option>
                                            <template x-for="u in config.users" :key="`u${idx}-${u.id}`">
                                                <option :value="u.id" x-text="u.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div :class="res.type === 'human' ? 'sm:col-span-8' : 'sm:col-span-6'">
                                        <label :for="`r-name-${idx}`" class="mb-0.5 block text-xs font-semibold text-muted">
                                            <span x-text="res.type === 'human' ? 'Rola / imię i nazwisko *' : (res.type === 'equipment' ? 'Nazwa sprzętu *' : 'Nazwa pozycji *')"></span>
                                        </label>
                                        <input :id="`r-name-${idx}`" type="text" x-model="res.name"
                                            class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                    </div>
                                    <div class="sm:col-span-3" x-show="res.type !== 'financial'">
                                        <label :for="`r-qty-${idx}`" class="mb-0.5 block text-xs font-semibold text-muted">Ilość</label>
                                        <input :id="`r-qty-${idx}`" type="number" min="0" step="0.5" x-model.number="res.quantity"
                                            class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                    </div>
                                    <div class="sm:col-span-3" x-show="res.type !== 'financial'">
                                        <label :for="`r-unit-${idx}`" class="mb-0.5 block text-xs font-semibold text-muted">Jednostka</label>
                                        <input :id="`r-unit-${idx}`" type="text" placeholder="np. godz., szt." x-model="res.unit"
                                            class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                    </div>
                                    <div class="sm:col-span-3" :class="res.type === 'financial' ? 'sm:col-span-6' : ''">
                                        <label :for="`r-cost-${idx}`" class="mb-0.5 block text-xs font-semibold text-muted">Koszt (PLN)</label>
                                        <input :id="`r-cost-${idx}`" type="number" min="0" step="0.01" x-model.number="res.cost"
                                            class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                    </div>
                                </div>
                                <p x-show="drawer.errors[`resources.${idx}.name`]" class="mt-1 text-sm text-red-600" x-text="drawer.errors[`resources.${idx}.name`]"></p>
                            </li>
                        </template>
                    </ul>
                </fieldset>

                <p x-show="drawer.errors._general" class="rounded-lg bg-red-50 px-4 py-3 text-sm font-semibold text-red-700" x-text="drawer.errors._general"></p>
            </form>

            <footer class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4">
                <button type="button" @click="closeDrawer()"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-muted hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                    Anuluj
                </button>
                <button type="button" @click="save()" :disabled="drawer.saving"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark disabled:opacity-60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    <i class="fa-solid" :class="drawer.saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'" aria-hidden="true"></i>
                    <span x-text="drawer.form.id ? 'Zapisz zmiany' : 'Dodaj działanie'"></span>
                </button>
            </footer>
        </div>
    </div>

    {{-- ── MODAL: słowniki (tylko admin) ─────────────────────────────── --}}
    @if (auth()->user()->isAdmin())
    <div x-show="dict.open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="dict-title">
        <div class="absolute inset-0 bg-black/40" @click="dict.open = false" aria-hidden="true"></div>
        <div class="relative flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
            <header class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h2 id="dict-title" class="text-lg font-bold text-ink">Słowniki modułu strategii</h2>
                <button type="button" @click="dict.open = false"
                    class="rounded-lg p-2 text-muted hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                    aria-label="Zamknij słowniki">
                    <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                </button>
            </header>

            <div class="flex gap-1 border-b border-gray-200 px-4 pt-3" role="tablist" aria-label="Rodzaj słownika">
                <template x-for="tab in dict.tabs" :key="tab.key">
                    <button type="button" role="tab" :aria-selected="(dict.active === tab.key).toString()"
                        @click="dict.active = tab.key"
                        :class="dict.active === tab.key ? 'border-brand text-brand' : 'border-transparent text-muted hover:text-ink'"
                        class="border-b-2 px-3 pb-2 text-sm font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                        x-text="tab.label"></button>
                </template>
            </div>

            <div class="flex-1 overflow-y-auto p-5">
                {{-- Dodawanie pozycji --}}
                <form class="mb-4 flex flex-wrap items-end gap-2 rounded-lg bg-gray-50 p-3" @submit.prevent="dictAdd()">
                    <div x-show="dict.active === 'statut'" class="w-28">
                        <label for="d-code" class="mb-0.5 block text-xs font-semibold text-muted">Paragraf</label>
                        <input id="d-code" type="text" x-model="dict.newItem.code" placeholder="§8 pkt 6"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    </div>
                    <div class="min-w-[12rem] flex-1">
                        <label for="d-name" class="mb-0.5 block text-xs font-semibold text-muted" x-text="dict.active === 'statut' ? 'Treść punktu statutu' : 'Nazwa pozycji'"></label>
                        <input id="d-name" type="text" x-model="dict.newItem.name"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    </div>
                    <div x-show="dict.active === 'finansowanie'" class="w-40">
                        <label for="d-kind" class="mb-0.5 block text-xs font-semibold text-muted">Rodzaj</label>
                        <select id="d-kind" x-model="dict.newItem.kind" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            <template x-for="(label, key) in config.fundingKinds" :key="'fk' + key">
                                <option :value="key" x-text="label"></option>
                            </template>
                        </select>
                    </div>
                    <button type="submit" :disabled="dict.saving"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark disabled:opacity-60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj
                    </button>
                </form>

                {{-- Lista pozycji --}}
                <ul class="divide-y divide-gray-100">
                    <template x-for="item in dictItems()" :key="dict.active + item.id">
                        <li class="flex items-center gap-3 py-2.5">
                            <span class="min-w-0 flex-1 text-sm text-ink">
                                <strong x-show="item.code" x-text="item.code" class="mr-1"></strong>
                                <span x-text="item.name ?? item.title"></span>
                                <span x-show="item.is_active === false" class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-muted">nieaktywna</span>
                            </span>
                            <button type="button" @click="dictToggleActive(item)"
                                class="rounded-lg px-2 py-1.5 text-xs font-semibold text-muted hover:bg-gray-100 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                x-text="item.is_active === false ? 'Aktywuj' : 'Dezaktywuj'"></button>
                            <button type="button" @click="dictDelete(item)"
                                class="rounded-lg p-1.5 text-muted hover:bg-red-50 hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                                :aria-label="`Usuń pozycję: ${item.name ?? item.title}`">
                                <i class="fa-solid fa-trash-can text-sm" aria-hidden="true"></i>
                            </button>
                        </li>
                    </template>
                </ul>
                <p class="mt-3 text-xs text-muted">Pozycji użytych w planach nie można usunąć — można je dezaktywować, wtedy znikają z formularza, ale zostają w istniejących działaniach.</p>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    /**
     * Stan i logika modułu „Strategia organizacji" (Alpine.js).
     * Backend: JSON-owe endpointy StrategyPlanController / StrategyDictionaryController.
     */
    function strategyApp() {
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const HEADERS = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF };
        let resourceKey = 0;

        return {
            // ── Konfiguracja wstrzyknięta z serwera ────────────────────
            config: {
                targetGroups:   @js($targetGroups),
                actionTypes:    @js($actionTypes),
                statuteRefs:    @js($statuteRefs),
                fundingSources: @js($fundingSources),
                users:          @js($users),
                statuses:       @js($statuses),
                months:         @js($months),
                resourceTypes:  @js($resourceTypes),
                fundingKinds:   @js(\App\Models\StrategyFundingSource::KINDS),
            },
            urls: {
                plans: @js(route('admin.strategia.list')),
                dictionaries: @js(route('admin.strategia.index') . '/slowniki'),
            },

            // ── Stan listy ─────────────────────────────────────────────
            year: {{ $currentYear }},
            view: localStorage.getItem('strategy-view') || 'table',
            filters: { q: '', target_group_id: '', action_type_id: '', funding_source_id: '', status: '', month: '' },
            plans: [],
            summary: { total_count: 0, total_budget: 0, per_month: [], per_group: [] },
            loading: false,
            statusMsg: '',
            highlightMonth: null,

            // ── Stan drawera formularza ────────────────────────────────
            // (pełna struktura od razu, żeby bindingi działały przed init())
            drawer: {
                open: false, saving: false, errors: {},
                form: { id: null, title: '', description: '', year: {{ $currentYear }}, month: 1, status: 'planned', action_type_id: null, statute_ref_id: null, funding_source_id: null, budget_planned: 0, target_groups: [], resources: [] },
            },

            // ── Stan modala słowników ──────────────────────────────────
            dict: {
                open: false, saving: false, active: 'grupy',
                tabs: [
                    { key: 'grupy',        label: 'Grupy docelowe' },
                    { key: 'typy',         label: 'Typy działań' },
                    { key: 'statut',       label: 'Statut' },
                    { key: 'finansowanie', label: 'Finansowanie' },
                ],
                newItem: { name: '', code: '', kind: 'own' },
            },

            init() {
                this.drawer.form = this.emptyForm();
                this.load();
            },

            // ── Pobieranie listy z podsumowaniem ───────────────────────
            async load() {
                this.loading = true;
                const params = new URLSearchParams({ year: this.year });
                Object.entries(this.filters).forEach(([k, v]) => { if (v !== '' && v !== null) params.set(k, v); });
                try {
                    const res = await fetch(`${this.urls.plans}?${params}`, { headers: HEADERS });
                    if (!res.ok) throw new Error();
                    const data = await res.json();
                    this.plans = data.plans;
                    this.summary = data.summary;
                } catch {
                    this.flash('Nie udało się wczytać planu działań.');
                } finally {
                    this.loading = false;
                }
            },

            changeYear(delta) {
                this.year = Math.min(2100, Math.max(2020, this.year + delta));
                this.load();
            },

            setView(view) {
                this.view = view;
                localStorage.setItem('strategy-view', view);
            },

            plansInMonth(month) {
                return this.plans.filter(p => p.month === month);
            },

            // ── Formularz (drawer) ─────────────────────────────────────
            emptyForm(month = null) {
                return {
                    id: null,
                    title: '',
                    description: '',
                    year: this.year ?? {{ $currentYear }},
                    month: month ?? (new Date()).getMonth() + 1,
                    status: 'planned',
                    action_type_id: null,
                    statute_ref_id: null,
                    funding_source_id: null,
                    budget_planned: 0,
                    target_groups: [],
                    resources: [],
                };
            },

            openCreate(month = null) {
                this.drawer.errors = {};
                this.drawer.form = this.emptyForm(month);
                this.drawer.open = true;
                this.$nextTick(() => this.$refs.firstField?.focus());
            },

            openEdit(plan) {
                this.drawer.errors = {};
                // Głęboka kopia, żeby edycja nie modyfikowała listy przed zapisem.
                this.drawer.form = {
                    ...JSON.parse(JSON.stringify(plan)),
                    target_groups: plan.target_groups.map(g => g.id),
                    resources: plan.resources.map(r => ({ ...r, _key: ++resourceKey })),
                };
                this.drawer.open = true;
                this.$nextTick(() => this.$refs.firstField?.focus());
            },

            closeDrawer() {
                this.drawer.open = false;
            },

            toggleGroup(id) {
                const groups = this.drawer.form.target_groups;
                const pos = groups.indexOf(id);
                pos === -1 ? groups.push(id) : groups.splice(pos, 1);
            },

            addResource(type) {
                this.drawer.form.resources.push({
                    _key: ++resourceKey,
                    type,
                    name: type === 'financial' ? '' : '',
                    user_id: null,
                    quantity: 1,
                    unit: type === 'human' ? 'godz.' : (type === 'equipment' ? 'szt.' : null),
                    cost: null,
                    note: null,
                });
            },

            /** Po wybraniu osoby z panelu podpowiada jej nazwę w polu tekstowym. */
            onResourceUserChange(res) {
                if (res.user_id) {
                    const user = this.config.users.find(u => u.id === res.user_id);
                    if (user && !res.name) res.name = user.name;
                }
            },

            financialSum() {
                return this.drawer.form.resources
                    .filter(r => r.type === 'financial')
                    .reduce((sum, r) => sum + (Number(r.cost) || 0), 0);
            },

            async save() {
                this.drawer.saving = true;
                this.drawer.errors = {};
                const form = this.drawer.form;
                const url = form.id ? `${this.urls.plans}/${form.id}` : this.urls.plans;
                try {
                    const res = await fetch(url, {
                        method: form.id ? 'PUT' : 'POST',
                        headers: HEADERS,
                        body: JSON.stringify({
                            ...form,
                            resources: form.resources.map(({ _key, ...rest }) => rest),
                        }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (res.status === 422) {
                        // Walidacja Laravel → pierwszy komunikat per pole.
                        this.drawer.errors = Object.fromEntries(
                            Object.entries(data.errors ?? {}).map(([field, msgs]) => [field, msgs[0]])
                        );
                        return;
                    }
                    if (!res.ok) throw new Error();
                    this.drawer.open = false;
                    this.flash(data.message ?? 'Zapisano.');
                    await this.load();
                } catch {
                    this.drawer.errors._general = 'Zapis nie powiódł się — spróbuj ponownie.';
                } finally {
                    this.drawer.saving = false;
                }
            },

            async removePlan(plan) {
                // Globalny alertdialog panelu zamiast natywnego confirm().
                if (await Alpine.store('confirm').ask(`Usunąć działanie „${plan.title}" z planu? Tej operacji nie można cofnąć.`) !== 'ok') return;
                try {
                    const res = await fetch(`${this.urls.plans}/${plan.id}`, { method: 'DELETE', headers: HEADERS });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) throw new Error();
                    this.flash(data.message ?? 'Usunięto.');
                    await this.load();
                } catch {
                    this.flash('Nie udało się usunąć działania.');
                }
            },

            // ── Słowniki (modal admina) ────────────────────────────────
            dictItems() {
                return {
                    grupy: this.config.targetGroups,
                    typy: this.config.actionTypes,
                    statut: this.config.statuteRefs,
                    finansowanie: this.config.fundingSources,
                }[this.dict.active] ?? [];
            },

            dictPayload() {
                const item = this.dict.newItem;
                if (this.dict.active === 'statut') return { code: item.code, title: item.name };
                if (this.dict.active === 'finansowanie') return { name: item.name, kind: item.kind };
                return { name: item.name };
            },

            async dictAdd() {
                if (!this.dict.newItem.name.trim()) return;
                this.dict.saving = true;
                try {
                    const res = await fetch(`${this.urls.dictionaries}/${this.dict.active}`, {
                        method: 'POST', headers: HEADERS, body: JSON.stringify(this.dictPayload()),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) throw new Error(data.message);
                    this.dictItems().push(data.item);
                    this.dict.newItem = { name: '', code: '', kind: 'own' };
                    this.flash(data.message);
                } catch (e) {
                    this.flash(e.message || 'Nie udało się dodać pozycji.');
                } finally {
                    this.dict.saving = false;
                }
            },

            async dictToggleActive(item) {
                const payload = this.dict.active === 'statut'
                    ? { code: item.code, title: item.title, is_active: !(item.is_active ?? true) }
                    : { name: item.name, kind: item.kind, is_active: !(item.is_active ?? true) };
                try {
                    const res = await fetch(`${this.urls.dictionaries}/${this.dict.active}/${item.id}`, {
                        method: 'PUT', headers: HEADERS, body: JSON.stringify(payload),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) throw new Error(data.message);
                    Object.assign(item, data.item);
                    this.flash(data.message);
                } catch (e) {
                    this.flash(e.message || 'Nie udało się zapisać zmiany.');
                }
            },

            async dictDelete(item) {
                if (await Alpine.store('confirm').ask(`Usunąć pozycję „${item.name ?? item.title}" ze słownika?`) !== 'ok') return;
                try {
                    const res = await fetch(`${this.urls.dictionaries}/${this.dict.active}/${item.id}`, {
                        method: 'DELETE', headers: HEADERS,
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) { this.flash(data.message || 'Nie udało się usunąć pozycji.'); return; }
                    const list = this.dictItems();
                    list.splice(list.findIndex(i => i.id === item.id), 1);
                    this.flash(data.message);
                } catch {
                    this.flash('Nie udało się usunąć pozycji.');
                }
            },

            // ── Pomocnicze: formatowanie i klasy prezentacyjne ─────────
            money(value) {
                return new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN', maximumFractionDigits: 0 }).format(value || 0);
            },
            monthName(month) { return this.config.months[month] ?? month; },
            monthShort(month) { return (this.config.months[month] ?? '').slice(0, 3); },
            monthBarHeight(m) {
                const max = Math.max(...this.summary.per_month.map(x => x.budget), 1);
                return m.budget > 0 ? Math.max(6, Math.round((m.budget / max) * 56)) : 2;
            },
            resourceCount(plan, type) { return plan.resources.filter(r => r.type === type).length; },
            resourcesAria(plan) {
                return `Zasoby: ludzkie ${this.resourceCount(plan, 'human')}, sprzętowe ${this.resourceCount(plan, 'equipment')}, finansowe ${this.resourceCount(plan, 'financial')}`;
            },
            resourceIcon(type) {
                return { human: 'fa-user-group', equipment: 'fa-toolbox', financial: 'fa-coins' }[type] ?? 'fa-cube';
            },
            statusClass(status) {
                return {
                    planned:     'bg-sky-100 text-sky-800',
                    in_progress: 'bg-amber-100 text-amber-800',
                    done:        'bg-green-100 text-green-800',
                    cancelled:   'bg-gray-200 text-gray-600',
                }[status] ?? 'bg-gray-100 text-gray-600';
            },
            statusDot(status) {
                return {
                    planned:     'bg-sky-500',
                    in_progress: 'bg-amber-500',
                    done:        'bg-green-500',
                    cancelled:   'bg-gray-400',
                }[status] ?? 'bg-gray-300';
            },

            flash(message) {
                this.statusMsg = message;
                clearTimeout(this._flashTimer);
                this._flashTimer = setTimeout(() => this.statusMsg = '', 4000);
            },
        };
    }
</script>
@endpush
