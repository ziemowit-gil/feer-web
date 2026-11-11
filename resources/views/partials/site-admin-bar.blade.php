{{--
    Pasek panelu na stronie publicznej: zalogowana redakcja (każda rola panelu — administrator, edytorzy, koordynator…) widzi
    na samej górze ten sam ciemny pasek co w panelu w stylu Metro (admin/partials/topnav-metro): logo → pulpit, edytowana
    witryna, grupy menu z kaflami, „Edytuj”, Skróty i konto. Zastępuje dawny pływający przycisk z kluczem (partials/admin-bar)
    i rozsiane po stronie skróty do panelu („Zarządzaj aktualnościami”, „Edytuj stopkę”, „Edytuj” na stronie głównej…) —
    zebrane są w okienku „Edytuj”, zależnie od tego, co jest na ekranie.
    Pasek nie jest przyklejony (strony mają własne przyklejone nagłówki); na wąskim ekranie bez grup menu (wystarczą Skróty
    z wyszukiwaniem w menu i logo prowadzące do pulpitu), napisy przy ikonach zostają dla czytnika.
    Bez paska: goście, podgląd menu w panelu (ramka), widok osadzony (?osadzony=1).
--}}
@php
    $sabUser = auth('web')->user();
    $sabShow = $sabUser
        && array_key_exists((string) $sabUser->role, \App\Models\User::ROLES)
        && trim($__env->yieldContent('menu_preview')) === ''
        && ! request()->boolean('osadzony');
@endphp
@if ($sabShow)
    @php
        $sabAdmin = $sabUser->isAdmin();
        $sabBlocks = $siteSettings->isModuleEnabled('block_editor');
        $sabEdit = [];

        // Bieżąca treść: aktualność, strona albo działanie (ta sama kolejność co dawniej w pływającym panelu).
        $sabCurrent = null;
        if (isset($news) && $news instanceof \App\Models\News && $news->exists && $sabUser->canAccessModule('news')) {
            $sabCurrent = ['title' => 'Ta aktualność', 'blocks' => route('admin.edytor-blokowy.edit', ['news', $news->id]), 'edit' => route('admin.newsy.edit', $news)];
        } elseif (isset($page) && $page instanceof \App\Models\Page && $page->exists && $sabUser->canAccessModule('pages')) {
            $sabCurrent = ['title' => 'Ta strona', 'blocks' => route('admin.podstrony.bloki.edit', $page), 'edit' => route('admin.podstrony.edit', $page)];
        } elseif (isset($project) && $project instanceof \App\Models\Project && $project->exists && $sabUser->canAccessModule('projects')) {
            $sabCurrent = ['title' => 'To działanie', 'blocks' => route('admin.edytor-blokowy.edit', ['project', $project->id]), 'edit' => route('admin.projekty.edit', $project)];
        }
        if ($sabCurrent) {
            $sabEdit[] = ['title' => $sabCurrent['title'], 'items' => array_values(array_filter([
                ['label' => 'Edytuj na stronie', 'event' => 'site-admin-edit', 'icon' => 'fa-pen'],
                $sabBlocks ? ['label' => 'Edytor blokowy', 'url' => $sabCurrent['blocks'], 'icon' => 'fa-cubes'] : null,
                ['label' => 'Edytuj w panelu', 'url' => $sabCurrent['edit'], 'icon' => 'fa-pen-to-square'],
            ]))];
        }

        // Strona główna i listy: skróty, które wcześniej były przyciskami w treści strony.
        $sabHome = request()->routeIs('home');
        $sabHere = [];
        if ($sabHome) {
            $sabHere[] = ['label' => 'Zmień kolejność sekcji', 'event' => 'site-admin-layout', 'icon' => 'fa-arrows-up-down'];
            if ($sabAdmin) {
                $sabHere[] = ['label' => 'Edytuj stronę główną', 'url' => route('admin.ustawienia.edit', ['tab' => 'homepage']), 'icon' => 'fa-house'];
                if (($siteSettings->site_template ?? 'default') === 'feer' && \Illuminate\Support\Facades\Route::has('admin.szybkie-akcje.index')) {
                    $sabHere[] = ['label' => 'Zarządzaj skrótami', 'url' => route('admin.szybkie-akcje.index'), 'icon' => 'fa-bolt'];
                }
            }
        }
        if (($sabHome || request()->routeIs('news.index')) && $siteSettings->isModuleEnabled('news') && $sabUser->canAccessModule('news')) {
            $sabHere[] = ['label' => 'Zarządzaj aktualnościami', 'url' => route('admin.newsy.index'), 'icon' => 'fa-newspaper'];
        }
        if (($sabHome || request()->routeIs('projects.index')) && $sabUser->canAccessModule('projects')) {
            $sabHere[] = ['label' => 'Zarządzaj działaniami', 'url' => route('admin.projekty.index'), 'icon' => 'fa-diagram-project'];
        }
        // Route::has — po pushu serwer może mieć stary cache tras (do czasu ./deploy.sh).
        if (request()->routeIs('projects.index') && $sabAdmin && $sabBlocks && \Illuminate\Support\Facades\Route::has('admin.edytor-blokowy.listing')) {
            $sabSidebar = $sidebarBlocks ?? \App\Models\ListingSection::html('projects_sidebar');
            $sabHere[] = ['label' => $sabSidebar ? 'Edytuj bloki pod menu kategorii' : 'Dodaj bloki pod menu kategorii', 'url' => route('admin.edytor-blokowy.listing', 'projects_sidebar'), 'icon' => 'fa-cubes'];
        }
        if (request()->routeIs('support.show') && $sabAdmin) {
            $sabHere[] = ['label' => 'Ustawienia strony „Wesprzyj nas”', 'url' => route('admin.ustawienia.edit', ['tab' => 'support']), 'icon' => 'fa-hand-holding-heart'];
        }
        if ($sabHere !== []) {
            $sabEdit[] = ['title' => $sabHome ? 'Strona główna' : 'Na tej stronie', 'items' => $sabHere];
        }

        // Cała witryna: stopka jest na każdej stronie (dawny skrót „Edytuj stopkę” w stopce).
        if ($sabAdmin) {
            $sabEdit[] = ['title' => 'Cała witryna', 'items' => [
                ['label' => 'Edytuj stopkę', 'url' => route('admin.ustawienia.edit', ['tab' => 'general']).'#gen-footer-h', 'icon' => 'fa-table-columns', 'attrs' => ['data-footer-edit']],
            ]];
        }
    @endphp
    <style>
        @include('admin.partials.metro-bar-css')
        /* Na stronie publicznej: pasek widoczny na każdej szerokości, nieprzyklejony, nad nagłówkami witryny; własna typografia
           (nie dziedziczy rozmiaru i kroju z szablonu). */
        .site-admin-bar .mt-nav { position: relative; z-index: 60; display: block; background: var(--m-ink); color: #fff; font-size: 1rem; line-height: 1.5; text-align: left; }
        .site-admin-bar .mt-nav :is(ul, p) { margin: 0; }
        /* Kreska pod paskiem: oddziela go od ciemnych pasków szablonu (np. pasek górny FEER), żeby nie zlewały się w jeden. */
        .site-admin-bar .mt-nav { box-shadow: inset 0 -1px 0 rgba(255, 255, 255, .28); }
        .site-admin-bar .mt-nav a { text-decoration: none; }
        @media (min-width: 1024px) { .site-admin-bar .mt-nav { position: relative; z-index: 60; } }
        @media (max-width: 1023.98px) {
            .site-admin-bar .mt-groups { display: none; }
            .site-admin-bar .mt-tools { margin-left: auto; }
            .site-admin-bar .mt-tool-text { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
            /* Okienka (Edytuj, Skróty, witryna, konto) na całą szerokość pod paskiem, żeby nie wychodziły poza ekran. */
            .site-admin-bar .mt-rel { position: static; }
            .site-admin-bar :is(.mt-drop, .mt-tip) { left: .5rem; right: .5rem; width: auto; max-width: none; }
        }
        @media (max-width: 26rem) {
            .site-admin-bar .mt-brand-name { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
        }
        @media print { .site-admin-bar { display: none !important; } }
    </style>
    <div class="admin-metro site-admin-bar" data-site-admin-bar>
        @include('admin.partials.topnav-metro', ['tnPublic' => true, 'tnEdit' => $sabEdit])
    </div>
    @include('admin.partials.nav-quick-script')
@endif
