@extends('admin.layout')

@section('title', $group->exists ? 'Edytuj grupę' : 'Nowa grupa')

@section('content')
    @php
        $allModules = \App\Models\SiteSetting::MODULES;
        // Moduły pogrupowane tematycznie; wszystko, czego tu brakuje, trafia do „Inne".
        $moduleSections = [
            'Treści i strony' => ['pages', 'news', 'faq', 'timeline', 'landing', 'forms', 'polls'],
            'Wygląd strony głównej' => ['hero', 'gallery', 'quick_actions', 'feer_bands', 'partners'],
            'Szkolenia i ludzie' => ['events', 'materials', 'volunteering', 'jobs', 'cooperation', 'help_map'],
            'Organizacja i dokumenty' => ['strategy', 'authorizations', 'bip', 'reports', 'support'],
        ];
        $placed = collect($moduleSections)->flatten()->all();
        $rest = array_values(array_diff(array_keys($allModules), $placed));
        if ($rest) { $moduleSections['Inne'] = $rest; }
        $sections = collect($moduleSections)->map(fn ($keys) => collect($keys)->filter(fn ($k) => isset($allModules[$k]))->mapWithKeys(fn ($k) => [$k => $allModules[$k]])->all())->filter()->all();

        $selectedModules = array_values(old('modules', $group->modules ?? []));
        $selCats = array_map('intval', (array) old('project_category_ids', $group->project_category_ids ?? []));
        // Gotowe zestawy — przyspieszają konfigurację typowych ról.
        $presets = [
            'Redaktor treści' => ['pages', 'news', 'faq', 'gallery', 'polls'],
            'Koordynator szkoleń' => ['events', 'materials', 'volunteering', 'jobs'],
            'Wszystkie' => array_keys($allModules),
            'Żadne' => [],
        ];
    @endphp

    <form method="POST" action="{{ $group->exists ? route('admin.grupy.update', $group) : route('admin.grupy.store') }}"
        x-data="{
            name: @js(old('name', $group->name)),
            modules: @js($selectedModules),
            own: @js((bool) old('own_content_only', $group->own_content_only ?? false)),
            cats: @js($selCats),
            approve: @js((bool) old('can_approve', $group->can_approve ?? false)),
            labels: @js($allModules),
            total: {{ count($allModules) }},
            sections: @js(collect($sections)->map(fn ($m) => array_keys($m))->all()),
            catNames: @js($categories->pluck('name', 'id')->all()),
            has(k) { return this.modules.includes(k); },
            toggleSection(keys, on) { this.modules = on ? [...new Set([...this.modules, ...keys])] : this.modules.filter(k => ! keys.includes(k)); },
            sectionState(keys) { const n = keys.filter(k => this.has(k)).length; return n === 0 ? 'none' : (n === keys.length ? 'all' : 'some'); },
            preset(keys) { this.modules = [...keys]; },
        }"
        class="grid items-start gap-6 grp-cols">
        <style>@media (min-width: 1024px) { .grp-cols { grid-template-columns: minmax(0, 1fr) 20rem; } }</style>
        @csrf
        @if ($group->exists) @method('PUT') @endif

        <div class="space-y-6">
            {{-- ── Podstawy ── --}}
            <section class="rounded-lg border border-gray-200 bg-white p-6" aria-labelledby="g-basic">
                <h2 id="g-basic" class="mb-4 text-base font-bold text-ink">Grupa</h2>
                <label for="name" class="mb-1 block text-sm font-bold">Nazwa grupy</label>
                <input type="text" id="name" name="name" x-model="name" required placeholder="np. Redaktorzy bloga"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @error('name') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </section>

            {{-- ── Moduły ── --}}
            <section class="rounded-lg border border-gray-200 bg-white p-6" aria-labelledby="g-modules">
                <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 id="g-modules" class="text-base font-bold text-ink">Dostępne moduły</h2>
                        <p class="text-sm text-muted">Użytkownicy tej grupy (rola: edytor) widzą w panelu tylko zaznaczone moduły.</p>
                    </div>
                    <div class="flex flex-wrap gap-1.5" role="group" aria-label="Gotowe zestawy modułów">
                        @foreach ($presets as $label => $keys)
                            <button type="button" @click="preset(@js($keys))"
                                class="rounded border border-gray-300 bg-white px-3 py-1.5 text-xs font-bold text-ink hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-4">
                    @foreach ($sections as $title => $items)
                        @php $keys = array_keys($items); @endphp
                        <fieldset class="rounded-lg border border-gray-200">
                            <legend class="sr-only">{{ $title }}</legend>
                            <div class="flex items-center justify-between gap-3 rounded-t-lg bg-gray-50 px-4 py-2.5">
                                <label class="flex items-center gap-2 text-sm font-bold text-ink">
                                    <input type="checkbox" class="rounded border-gray-300 text-brand focus:ring-brand"
                                        :checked="sectionState(@js($keys)) === 'all'"
                                        x-effect="$el.indeterminate = sectionState(@js($keys)) === 'some'"
                                        @change="toggleSection(@js($keys), $event.target.checked)"
                                        aria-label="Zaznacz wszystkie moduły: {{ $title }}">
                                    {{ $title }}
                                </label>
                                <span class="text-xs text-muted" x-text="@js($keys).filter(k => has(k)).length + ' / {{ count($keys) }}'"></span>
                            </div>
                            <div class="grid gap-x-6 gap-y-2.5 p-4 sm:grid-cols-2">
                                @foreach ($items as $key => $label)
                                    <label class="flex items-start gap-2 text-sm">
                                        <input type="checkbox" name="modules[]" value="{{ $key }}" x-model="modules"
                                            class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                </div>
                @error('modules') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
            </section>

            {{-- ── Zakres treści ── --}}
            <section class="rounded-lg border border-gray-200 bg-white p-6" aria-labelledby="g-scope">
                <h2 id="g-scope" class="text-base font-bold text-ink">Zakres treści</h2>
                <p class="mb-4 text-sm text-muted">Dodatkowo zawęża moduły zaznaczone wyżej.</p>

                <label class="flex items-start gap-2">
                    <input type="checkbox" name="own_content_only" value="1" x-model="own" class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                    <span class="text-sm font-bold">Tylko własne wpisy
                        <span class="block font-normal text-muted">Edytor widzi i edytuje tylko aktualności, strony, wydarzenia i projekty, które sam utworzył. Treści dodane wcześniej (bez autora) są dla niego niewidoczne.</span>
                    </span>
                </label>

                <div class="mt-5">
                    <p class="mb-1 text-sm font-bold">Projekty tylko z kategorii</p>
                    <p class="mb-2 text-xs text-muted">Bez zaznaczenia — wszystkie kategorie.</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($categories as $cat)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="project_category_ids[]" value="{{ $cat->id }}" x-model.number="cats" class="peer sr-only">
                                <span class="inline-flex min-h-9 items-center rounded-full border border-gray-300 bg-white px-3 text-sm font-medium text-ink transition peer-checked:border-brand peer-checked:bg-brand peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand peer-focus-visible:ring-offset-2">{{ $cat->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('project_category_ids.*') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
            </section>

            {{-- ── Zatwierdzanie ── --}}
            <section class="rounded-lg border border-gray-200 bg-white p-6" aria-labelledby="g-approve">
                <h2 id="g-approve" class="mb-3 text-base font-bold text-ink">Publikowanie</h2>
                <label class="flex items-start gap-2">
                    <input type="checkbox" name="can_approve" value="1" x-model="approve" class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                    <span class="text-sm font-bold">Moderator / akceptant treści
                        <span class="block font-normal text-muted">Może zatwierdzać i publikować treści. Bez tego uprawnienia treści zgłoszone przez tę grupę trafiają do kolejki „Do zatwierdzenia” i czekają na publikację.</span>
                    </span>
                </label>
            </section>
        </div>

        {{-- ── Podsumowanie (przyklejone) ── --}}
        <aside class="space-y-4 lg:sticky lg:top-6" aria-label="Podsumowanie grupy">
            <div class="rounded-lg border border-gray-200 bg-white p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-muted">Podsumowanie</p>
                <p class="mt-1 truncate text-lg font-bold text-ink" x-text="name || 'Nowa grupa'"></p>

                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="font-bold text-ink">Moduły</dt>
                        <dd class="text-muted"><span x-text="modules.length"></span> z <span x-text="total"></span></dd>
                        <dd class="mt-2 flex flex-wrap gap-1" x-show="modules.length" x-cloak>
                            <template x-for="k in modules" :key="k">
                                <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-ink" x-text="(labels[k] || k).split(' (')[0]"></span>
                            </template>
                        </dd>
                        <dd class="text-xs text-amber-800" x-show="! modules.length" x-cloak>Brak modułów — użytkownicy tej grupy nie zobaczą nic w panelu.</dd>
                    </div>
                    <div>
                        <dt class="font-bold text-ink">Zakres</dt>
                        <dd class="text-muted" x-text="own ? 'Tylko własne wpisy' : 'Wszystkie wpisy w modułach'"></dd>
                        <dd class="text-muted" x-text="cats.length ? 'Projekty z kategorii: ' + cats.map(i => catNames[i] || '?').join(', ') : 'Projekty ze wszystkich kategorii'"></dd>
                    </div>
                    <div>
                        <dt class="font-bold text-ink">Publikowanie</dt>
                        <dd class="text-muted" x-text="approve ? 'Może zatwierdzać i publikować' : 'Treści idą do zatwierdzenia'"></dd>
                    </div>
                </dl>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="rounded bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz grupę</button>
                <a href="{{ route('admin.grupy.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
            </div>
        </aside>
    </form>
@endsection
