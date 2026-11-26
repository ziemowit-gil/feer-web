@extends('layouts.site')

@section('title', 'Materiały edukacyjne — ' . $siteSettings->site_name)
@section('meta_description', 'Materiały edukacyjne ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Materiały edukacyjne', 'url' => null],
    ]])
@endsection

{{--
    Materiały edukacyjne w układzie FEER. Nagłówek na brand-light: H1, wstęp, komunikat z panelu i skróty do typów
    (liczba materiałów każdego typu, link z filtrem). Pod nim po lewej formularz filtrów (GET: q, typ, dla, temat, sort —
    adres da się udostępnić, działa bez JavaScriptu), po prawej liczba wyników (role="status") i karty w grupach docelowych.
    Wszystkie karty są w HTML-u, niepasujące mają `hidden`, więc skrypt filtruje na żywo i aktualizuje adres
    (history.replaceState); zmiana kolejności wysyła formularz. Style .mat-* w educational-materials/partials/styles.
    Kontrast: ink/muted na bieli, gray-50 i brand-light, linki brand-dark — ≥ 4,5:1 we wszystkich stanach.
--}}
@section('content')
    @php
        $controller = \App\Http\Controllers\EducationalMaterialController::class;
        $total = $materials->count();
        $visibleCount = $visibleIds->count();
        $groups = $materials->groupBy(fn ($m) => $m->target_group ?: '');
        $hasNamedGroups = $groups->keys()->filter(fn ($k) => $k !== '')->isNotEmpty();
        $groupLabel = fn ($key) => $key === ''
            ? ($hasNamedGroups ? 'Pozostałe materiały' : 'Wszystkie materiały')
            : (\App\Models\EducationalMaterial::TARGET_GROUPS[$key] ?? $key);
        $plural = fn (int $n) => $n === 1 ? 'materiał'
            : (in_array($n % 10, [2, 3, 4], true) && ! in_array($n % 100, [12, 13, 14], true) ? 'materiały' : 'materiałów');
        $typeNames = [
            'video' => ['Nagrania', 'nagranie', 'nagrania', 'nagrań'],
            'pdf' => ['PDF', 'plik PDF', 'pliki PDF', 'plików PDF'],
            'scenariusz' => ['Scenariusze', 'scenariusz', 'scenariusze', 'scenariuszy'],
        ];
        $typeCount = fn (string $type, int $n) => $n === 1 ? $typeNames[$type][1]
            : (in_array($n % 10, [2, 3, 4], true) && ! in_array($n % 100, [12, 13, 14], true) ? $typeNames[$type][2] : $typeNames[$type][3]);
        $typeCounts = collect($typeNames)->map(fn ($_, $t) => $materials->where('type', $t)->count())->filter();
        $groupCounts = $materials->filter(fn ($m) => $m->target_group)->countBy('target_group');
        $categoryCounts = $materials->filter(fn ($m) => $m->category)->countBy('category');
        $countText = $filters['active']
            ? 'Pasujące: ' . $visibleCount . ' ' . $plural($visibleCount) . ' z ' . $total . '.'
            : $total . ' ' . $plural($total) . '.';
        $newsletterCta = $siteSettings->isModuleEnabled('newsletter') && \Illuminate\Support\Facades\Route::has('newsletter.show');
        $actionPrimary = 'inline-flex min-h-11 items-center gap-2 rounded-md bg-brand px-4 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2';
    @endphp

    <section class="mat-hero" aria-labelledby="mat-h1">
        <div class="mx-auto max-w-6xl px-4">
            <p class="mat-hero-eyebrow">Baza wiedzy</p>
            <h1 id="mat-h1" class="mat-hero-title">Materiały edukacyjne</h1>
            @if ($siteSettings->materials_intro)
                <div class="mat-hero-intro">{!! $siteSettings->materials_intro !!}</div>
            @endif
            @if ($siteSettings->materials_notice)
                {{-- Komunikat z panelu jako jedna spokojna linia, bez szarego bloku. --}}
                <div class="mat-notice">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    <div>{!! $siteSettings->materials_notice !!}</div>
                </div>
            @endif
            @if ($total > 0)
                {{-- Skróty: liczba materiałów każdego typu jako link z filtrem (działa też bez JavaScriptu). --}}
                <ul class="mat-stats" role="list" aria-label="Rodzaje materiałów">
                    <li><a href="{{ route('materials.index') }}" class="mat-stat" data-material-shortcut=""><span class="mat-stat-n">{{ $total }}</span> {{ $plural($total) }} w bazie</a></li>
                    @foreach ($typeCounts as $tKey => $n)
                        <li><a href="{{ route('materials.index', ['typ' => $tKey]) }}#lista" class="mat-stat mat-stat--{{ $tKey }}" data-material-shortcut="{{ $tKey }}"><span class="mat-type-icon"><i class="fa-solid {{ ['video' => 'fa-video', 'pdf' => 'fa-file-pdf', 'scenariusz' => 'fa-chalkboard-user'][$tKey] }}" aria-hidden="true"></i></span><span class="mat-stat-n">{{ $n }}</span> {{ $typeCount($tKey, $n) }}</a></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    <div id="lista" class="mx-auto max-w-6xl scroll-mt-24 px-4 py-10 md:py-12">
        @if ($materials->isEmpty())
            <p class="text-muted">Brak materiałów do wyświetlenia.</p>
        @else
            <div class="mat-layout">
                {{-- Filtry — zwykły formularz GET; ze skryptem wyniki zmieniają się od razu, bez przycisku. --}}
                <aside class="mat-aside" aria-labelledby="mat-filters-h">
                    <h2 id="mat-filters-h" class="mat-aside-h">Znajdź materiał</h2>
                    <form method="GET" action="{{ route('materials.index') }}#lista" role="search" aria-label="Filtry materiałów" class="mat-filters" data-material-form>
                        <div class="mat-field">
                            <label for="material-search" class="mat-label">Szukaj w tytułach i opisach</label>
                            <input type="search" id="material-search" name="q" value="{{ $filters['q'] }}" data-material-search autocomplete="off" maxlength="100" class="mat-input">
                        </div>

                        @if ($typeCounts->count() > 1)
                            <fieldset class="mat-field mat-fieldset">
                                <legend class="mat-label">Typ materiału</legend>
                                <div class="mat-options">
                                    <label class="mat-option"><input type="radio" name="typ" value="" @checked($filters['typ'] === '') data-material-filter="typ"> <span>Wszystkie</span> <span class="mat-option-n">{{ $total }}</span></label>
                                    @foreach ($typeCounts as $tKey => $n)
                                        <label class="mat-option"><input type="radio" name="typ" value="{{ $tKey }}" @checked($filters['typ'] === $tKey) data-material-filter="typ"> <span>{{ $typeNames[$tKey][0] }}</span> <span class="mat-option-n">{{ $n }}</span></label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endif

                        @if ($groupCounts->count() > 1)
                            <div class="mat-field">
                                <label for="material-group" class="mat-label">Dla kogo</label>
                                <select id="material-group" name="dla" class="mat-input mat-select" data-material-filter="dla">
                                    <option value="">Wszystkie grupy</option>
                                    @foreach (\App\Models\EducationalMaterial::TARGET_GROUPS as $gKey => $gLabel)
                                        @if ($groupCounts->has($gKey))
                                            <option value="{{ $gKey }}" @selected($filters['dla'] === $gKey)>{{ $gLabel }} ({{ $groupCounts[$gKey] }})</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if ($categoryCounts->isNotEmpty())
                            <div class="mat-field">
                                <label for="material-topic" class="mat-label">Temat</label>
                                <select id="material-topic" name="temat" class="mat-input mat-select" data-material-filter="temat">
                                    <option value="">Wszystkie tematy</option>
                                    @foreach (\App\Models\EducationalMaterial::CATEGORIES as $cKey => $cLabel)
                                        @if ($categoryCounts->has($cKey))
                                            <option value="{{ $cKey }}" @selected($filters['temat'] === $cKey)>{{ $cLabel }} ({{ $categoryCounts[$cKey] }})</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if ($total > 1)
                            <div class="mat-field">
                                <label for="material-sort" class="mat-label">Kolejność</label>
                                <select id="material-sort" name="sort" class="mat-input mat-select" data-material-sort>
                                    @foreach ($controller::SORTS as $sKey => $sLabel)
                                        <option value="{{ $sKey }}" @selected($filters['sort'] === $sKey)>{{ $sLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="mat-actions">
                            <button type="submit" class="mat-btn" data-material-submit>Pokaż materiały</button>
                            <a href="{{ route('materials.index') }}#lista" class="mat-reset" data-material-reset @if (! $filters['active']) hidden @endif>Wyczyść filtry</a>
                        </div>
                    </form>

                    <p class="mat-aside-more">
                        <a href="#zapis">{{ $newsletterCta ? 'Zapisz się na newsletter' : 'Powiadomienia o nowych materiałach' }} <span aria-hidden="true">→</span></a>
                    </p>
                </aside>

                <div class="min-w-0">
                    <div class="mat-results-bar">
                        @if ($groups->count() < 2)
                            <h2 class="sr-only">Lista materiałów</h2>
                        @endif
                        <p class="mat-count" role="status" data-material-count>{{ $countText }}</p>
                    </div>

                    <div class="mat-groups">
                    @foreach ($groups as $gKey => $items)
                        @php $groupVisible = $items->filter(fn ($m) => $visibleIds->has($m->id))->count(); @endphp
                        <section id="grupa-{{ $loop->index }}" class="scroll-mt-24" @if ($groups->count() > 1) aria-labelledby="grupa-h-{{ $loop->index }}" @endif data-material-group @if ($groupVisible === 0) hidden @endif>
                            @if ($groups->count() > 1)
                                <h2 id="grupa-h-{{ $loop->index }}" class="mat-group-h">
                                    {{ $groupLabel($gKey) }}
                                    <span class="mat-group-count" data-material-group-count>{{ $groupVisible }} {{ $plural($groupVisible) }}</span>
                                </h2>
                            @endif

                            <ul class="mat-grid" role="list">
                                @foreach ($items as $material)
                                    <li @if (! $visibleIds->has($material->id)) hidden @endif>@include('partials.material-card-feer', ['material' => $material])</li>
                                @endforeach
                            </ul>
                        </section>
                    @endforeach
                    </div>
                    <div class="mat-empty" data-material-empty @if ($visibleCount > 0) hidden @endif>
                        <p>Nie znaleźliśmy materiałów pasujących do filtrów. Zmień słowa wyszukiwania albo wybierz inny typ.</p>
                        <a href="{{ route('materials.index') }}#lista" class="mat-reset">Pokaż wszystkie materiały</a>
                    </div>
                </div>
            </div>
        @endif

        @if ($newsletterCta)
            {{-- Zapis przez FEERletter — ten sam blok co pod „Naszymi działaniami” (brand-light, koperta w tle, przycisk do strony zapisu).
                 Kontrast: ink na brand-light, biały na brand-dark, po najechaniu tło ink (≥ 4,5:1). --}}
            <section id="zapis" class="relative isolate mt-16 flex scroll-mt-24 flex-col gap-6 overflow-hidden rounded-md bg-brand-light p-6 text-ink sm:flex-row sm:items-center sm:justify-between md:p-8" aria-labelledby="zapis-h">
                <span class="pointer-events-none absolute -bottom-8 right-56 -z-10 hidden select-none text-[9rem] leading-none text-white md:block" aria-hidden="true"><i class="fa-regular fa-envelope"></i></span>
                <div class="max-w-2xl">
                    <span class="text-xs font-bold uppercase tracking-widest text-brand-dark">Newsletter</span>
                    <h2 id="zapis-h" class="mt-1 text-xl font-bold leading-snug">Nowe materiały prosto do Twojej skrzynki</h2>
                    <p class="mt-3 text-sm leading-relaxed">Zapisz się na FEERletter, jako pierwsza osoba dowiesz się o nowych bezpłatnych materiałach, terminach szkoleń i webinarów online. FEERletter wychodzi raz na dwa tygodnie.</p>
                </div>
                <a href="{{ route('newsletter.show') }}"
                   class="inline-flex min-h-12 shrink-0 items-center justify-center gap-2 rounded-md bg-brand-dark px-6 text-sm font-bold text-white transition-colors hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2 motion-reduce:transition-none">
                    Zapisuję się na newsletter <span aria-hidden="true">→</span>
                </a>
            </section>
        @else
            {{-- Zapis na powiadomienia o nowych materiałach --}}
            <section id="zapis" class="mt-16 scroll-mt-24 rounded-md bg-gray-50 p-6 sm:p-8" aria-labelledby="zapis-h">
                <div class="max-w-xl">
                    <h2 id="zapis-h" class="text-2xl font-bold text-ink">Powiadomienia o nowych materiałach</h2>
                    <p class="mt-1 text-sm text-ink">Zostaw swój adres e-mail, a damy Ci znać, gdy pojawią się nowe materiały.</p>

                    @if (session('materials_subscribed'))
                        <p class="mt-4 text-sm font-bold text-green-800" role="status">Dziękujemy! Adres został zapisany.</p>
                    @else
                        <form method="POST" action="{{ route('materials.subscribe') }}" class="mt-4">
                            @csrf
                            @include('partials.spam-protection', ['spamScope' => 'materialy', 'flat' => true])
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <label for="subscribe-email" class="sr-only">Adres e-mail</label>
                                <input type="email" id="subscribe-email" name="email" value="{{ old('email') }}" required placeholder="twoj@email.pl" autocomplete="email"
                                       class="min-h-11 w-full rounded-md border-gray-500 focus:border-brand focus:ring-brand">
                                <button type="submit" class="{{ $actionPrimary }} justify-center">Zapisz się</button>
                            </div>
                            @error('email') <p class="mt-2 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
                            <p class="mt-2 text-xs text-muted">Wykorzystamy Twój adres wyłącznie do informowania o nowych materiałach.</p>
                        </form>
                    @endif
                </div>
            </section>
        @endif
    </div>

    @include('educational-materials.partials.styles')

    <script>
        (function () {
            const form = document.querySelector('[data-material-form]');
            if (!form) return;

            const input = form.querySelector('[data-material-search]');
            const filters = form.querySelectorAll('[data-material-filter]');
            const sort = form.querySelector('[data-material-sort]');
            const submit = form.querySelector('[data-material-submit]');
            const reset = form.querySelector('[data-material-reset]');
            const count = document.querySelector('[data-material-count]');
            const empty = document.querySelector('[data-material-empty]');
            const cards = Array.from(document.querySelectorAll('.mat-groups [data-material-card]'));
            const groups = document.querySelectorAll('[data-material-group]');
            const norm = (t) => (t || '').toLowerCase().replace(/ł/g, 'l').normalize('NFD').replace(/[̀-ͯ]/g, '');
            const plural = (n) => n === 1 ? 'materiał' : ([2, 3, 4].includes(n % 10) && ![12, 13, 14].includes(n % 100) ? 'materiały' : 'materiałów');
            cards.forEach((c) => { c.dataset.searchNorm = norm(c.dataset.search); });
            if (submit) submit.hidden = true;
            let timer = null;

            function state() {
                const data = new FormData(form);
                return { q: (data.get('q') || '').trim(), typ: data.get('typ') || '', dla: data.get('dla') || '', temat: data.get('temat') || '', sort: data.get('sort') || '' };
            }

            function apply(announce) {
                const s = state();
                const q = norm(s.q);
                let visible = 0;
                cards.forEach((c) => {
                    const show = (!s.typ || c.dataset.type === s.typ)
                        && (!s.dla || c.dataset.group === s.dla)
                        && (!s.temat || c.dataset.category === s.temat)
                        && (!q || c.dataset.searchNorm.includes(q));
                    c.closest('li').hidden = !show;
                    if (show) visible++;
                });
                groups.forEach((g) => {
                    const n = g.querySelectorAll('li:not([hidden])').length;
                    g.hidden = n === 0;
                    const label = g.querySelector('[data-material-group-count]');
                    if (label) label.textContent = n + ' ' + plural(n);
                });
                const active = !!(s.q || s.typ || s.dla || s.temat);
                empty.hidden = visible !== 0;
                if (reset) reset.hidden = !active;
                if (announce) {
                    count.textContent = active
                        ? 'Pasujące: ' + visible + ' ' + plural(visible) + ' z ' + cards.length + '.'
                        : cards.length + ' ' + plural(cards.length) + '.';
                }
                // Adres odzwierciedla filtry — można go skopiować i wysłać.
                const params = new URLSearchParams();
                Object.entries(s).forEach(([k, v]) => { if (v) params.set(k, v); });
                const qs = params.toString();
                history.replaceState(null, '', location.pathname + (qs ? '?' + qs : '') + location.hash);
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                apply(true);
            });
            input.addEventListener('input', function () {
                clearTimeout(timer);
                apply(false);
                timer = setTimeout(() => apply(true), 400);
            });
            filters.forEach((f) => f.addEventListener('change', () => apply(true)));
            // Kolejność liczy serwer — zmiana przeładowuje listę z bieżącymi filtrami.
            if (sort) sort.addEventListener('change', function () {
                const s = state();
                const params = new URLSearchParams();
                Object.entries(s).forEach(([k, v]) => { if (v) params.set(k, v); });
                location.assign(form.getAttribute('action').split('#')[0] + (params.toString() ? '?' + params : '') + '#lista');
            });
            // Skróty typów w nagłówku i „Wyczyść filtry” działają bez przeładowania.
            document.querySelectorAll('[data-material-shortcut]').forEach((a) => a.addEventListener('click', function (e) {
                const radio = form.querySelector('input[name="typ"][value="' + a.dataset.materialShortcut + '"]');
                if (!radio) return;
                e.preventDefault();
                input.value = '';
                form.querySelectorAll('select[data-material-filter]').forEach((sel) => { sel.value = ''; });
                radio.checked = true;
                apply(true);
                document.getElementById('lista').scrollIntoView();
                radio.focus();
            }));
            document.querySelectorAll('.mat-reset').forEach((a) => a.addEventListener('click', function (e) {
                e.preventDefault();
                input.value = '';
                form.querySelectorAll('select[data-material-filter]').forEach((sel) => { sel.value = ''; });
                const all = form.querySelector('input[name="typ"][value=""]');
                if (all) all.checked = true;
                apply(true);
                input.focus();
            }));
        })();
    </script>
    @include('partials.material-preview-dialog')
@endsection
