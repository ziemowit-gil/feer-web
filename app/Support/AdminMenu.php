<?php

declare(strict_types=1);

namespace App\Support;

use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\TrashController;
use App\Models\ContactMessage;
use App\Models\CooperationRequest;
use App\Models\FormSubmission;
use App\Models\SiteSetting;
use App\Models\User;
use App\Modules\ModuleManager;
use Closure;
use Illuminate\Support\Facades\Route;

/**
 * Definicja menu bocznego panelu administracyjnego.
 *
 * Układ jak w module menu TYPO3: najwyżej SZEŚĆ grup głównych (Pulpit, Strony, Treści,
 * Komunikacja, Użytkownicy, System), a w grupie bloki z podtytułami → pozycje → podpozycje.
 * Widok `admin.partials.sidebar` tylko to renderuje. Dzięki temu:
 *  - widoczność pozycji (moduły, role) jest rozstrzygana w jednym miejscu,
 *  - pozycje wskazujące na nieistniejącą trasę są pomijane zamiast wywalać
 *    cały panel fatalnym błędem,
 *  - stan „aktywna sekcja/pozycja" wynika z tras, nie z ręcznych list.
 *
 * Struktura zwracana przez {@see build()}:
 *
 *  group:   ['key' => string, 'label' => string, 'icon' => string, 'active' => bool, 'badge' => ?int,
 *            'blocks' => [['heading' => ?string, 'items' => item[]]]]
 *  item:    ['key' => string, 'label' => string, 'url' => string, 'icon' => string,
 *            'active' => bool, 'badge' => ?int, 'badge_tone' => 'brand'|'muted',
 *            'children' => child[]]
 *  child:   ['label' => string, 'url' => string, 'active' => bool, 'icon' => ?string]
 */
final class AdminMenu
{
    private User $user;

    private SiteSetting $site;

    private ModuleManager $modules;

    public static function for(User $user): array
    {
        return (new self($user))->build();
    }

    private function __construct(User $user)
    {
        $this->user    = $user;
        $this->site    = SiteSetting::current();
        $this->modules = app(ModuleManager::class);
    }

    /** @return array<int, array<string, mixed>> */
    public function build(): array
    {
        $isAdmin = $this->user->isAdmin();

        $sections = [
            $this->section('start', null, [
                $this->item('dashboard', 'Dashboard', 'admin.dashboard', 'fa-gauge'),
                $this->user->canApproveContent()
                    ? $this->item('approvals', 'Do zatwierdzenia', 'admin.zatwierdzanie.index', 'fa-clipboard-check',
                        active: 'admin.zatwierdzanie.*', badge: fn () => ApprovalController::pendingCount())
                    : null,
            ], defaultOpen: true),

            $this->can('pages') ? $this->section('pages', 'Strony', [
                $this->item('pages', 'Strony i menu', 'admin.podstrony.index', 'fa-file-lines',
                    active: ['admin.podstrony.*', 'admin.pozycje-menu.*', 'admin.osoby.*']),
                $this->can('timeline')
                    ? $this->item('timeline', 'Oś czasu (historia)', 'admin.os-czasu.edit', 'fa-timeline', active: 'admin.os-czasu.*')
                    : null,
                $this->can('cooperation')
                    ? $this->item('cooperation', 'Zgłoszenia współpracy', 'admin.wspolpraca-zgloszenia.index', 'fa-handshake',
                        active: 'admin.wspolpraca-zgloszenia.*',
                        badge: fn () => CooperationRequest::whereNull('read_at')->count())
                    : null,
            ], defaultOpen: true) : null,

            $this->section('homepage', 'Strona główna', [
                $this->can('hero')          ? $this->item('hero', 'Slajder (hero)', 'admin.hero.index', 'fa-images', active: 'admin.hero.*') : null,
                $this->can('gallery')       ? $this->item('gallery', 'Galeria', 'admin.galeria.index', 'fa-panorama', active: 'admin.galeria.*') : null,
                $this->can('quick_actions') ? $this->item('quick-actions', 'Szybkie akcje', 'admin.szybkie-akcje.index', 'fa-bolt', active: 'admin.szybkie-akcje.*') : null,
                $this->can('partners')      ? $this->item('partners', 'Partnerzy', 'admin.partnerzy.index', 'fa-handshake', active: 'admin.partnerzy.*') : null,
            ]),

            $this->section('content', 'Treści', [
                $this->can('news') ? $this->item('news', 'Aktualności', 'admin.newsy.index', 'fa-newspaper',
                    active: ['admin.newsy.*', 'admin.kategorie-newsow.*', 'admin.tagi.*'],
                    children: [
                        $this->child('Kategorie', 'admin.kategorie-newsow.index', 'admin.kategorie-newsow.*'),
                        $this->child('Tagi', 'admin.tagi.index', 'admin.tagi.*'),
                    ]) : null,
                $this->can('blog') ? $this->item('blog', 'Wiem FEER (blog)', 'admin.wiem-feer.index', 'fa-feather-pointed',
                    active: ['admin.wiem-feer.*', 'admin.komentarze-bloga.*'],
                    children: [
                        $this->child('Komentarze', 'admin.komentarze-bloga.index', 'admin.komentarze-bloga.*'),
                    ]) : null,
                $this->can('podcasts') ? $this->item('podcasts', 'Podcasty', 'admin.podcasty.index', 'fa-podcast', active: 'admin.podcasty.*') : null,
                $this->can('forms') ? $this->item('forms', 'Formularze', 'admin.formularze.index', 'fa-wpforms',
                    active: 'admin.formularze.*',
                    badge: fn () => FormSubmission::whereNull('read_at')->count()) : null,
                $this->can('materials') ? $this->item('materials', 'Materiały edukacyjne', 'admin.materialy-edukacyjne.index', 'fa-graduation-cap',
                    active: ['admin.materialy-edukacyjne.*', 'admin.zapisy-materialy.*'],
                    children: [
                        $this->child('Zapisy uczestników', 'admin.zapisy-materialy.index', 'admin.zapisy-materialy.*'),
                    ]) : null,
                $this->can('sklep') ? $this->item('shop', 'Sklep', 'admin.sklep.orders.index', 'fa-cart-shopping',
                    active: 'admin.sklep.*',
                    children: [
                        $this->child('Zamówienia', 'admin.sklep.orders.index', 'admin.sklep.orders.*'),
                        $this->child('Kody rabatowe', 'admin.sklep.kody-rabatowe.index', 'admin.sklep.kody-rabatowe.*'),
                    ]) : null,
                $this->can('events') ? $this->item('events', 'Szkolenia i wydarzenia', 'admin.wydarzenia.index', 'fa-calendar-days',
                    active: ['admin.wydarzenia.*', 'admin.prowadzacy.*'],
                    children: [
                        $this->child('Prowadzący', 'admin.prowadzacy.index', 'admin.prowadzacy.*'),
                    ]) : null,
                $this->can('volunteering') ? $this->item('volunteering', 'Wolontariat', 'admin.wolontariat.index', 'fa-hands-helping', active: 'admin.wolontariat.*') : null,
                $this->can('jobs')         ? $this->item('jobs', 'Ogłoszenia o pracę', 'admin.praca.index', 'fa-briefcase', active: 'admin.praca.*') : null,
                $this->can('projects') ? $this->item('projects', 'Projekty', 'admin.projekty.index', 'fa-diagram-project',
                    active: ['admin.projekty.*', 'admin.kategorie.*'],
                    children: [
                        $this->child('Kategorie projektów', 'admin.kategorie.index', 'admin.kategorie.*'),
                    ]) : null,
                $this->can('faq')      ? $this->item('faq', 'FAQ', 'admin.faq.index', 'fa-circle-question', active: 'admin.faq.*') : null,
                $this->can('reports')  ? $this->item('reports', 'Sprawozdania', 'admin.sprawozdania.index', 'fa-file-invoice', active: 'admin.sprawozdania.*') : null,
                $this->can('bip')      ? $this->item('bip', 'BIP — dokumenty', 'admin.bip-dokumenty.index', 'fa-landmark', active: 'admin.bip-dokumenty.*') : null,
                $this->can('landing')  ? $this->item('landing', 'Landing pages', 'admin.lp.index', 'fa-bullhorn', active: 'admin.lp.*') : null,
                $this->can('help_map') ? $this->item('help-map', 'Mapa pomocy', 'admin.mapa-pomocy.index', 'fa-map-location-dot', active: 'admin.mapa-pomocy.*') : null,
                $this->site->site_template === 'federation'
                    ? $this->item('organizations', 'Organizacje członkowskie', 'admin.organizacje.index', 'fa-people-roof', active: 'admin.organizacje.*')
                    : null,
                $this->can('polls') ? $this->item('polls', 'Ankiety', 'admin.ankiety.index', 'fa-square-poll-vertical', active: 'admin.ankiety.*') : null,
            ]),

            $this->section('library', 'Biblioteka', [
                $this->item('media', 'Multimedia', 'admin.multimedia.index', 'fa-photo-film', active: 'admin.multimedia.*'),
                $this->item('trash', 'Kosz', 'admin.kosz.index', 'fa-trash-can', active: 'admin.kosz.*',
                    badge: fn () => TrashController::count(), badgeTone: 'muted'),
            ], defaultOpen: true),

            $isAdmin ? $this->section('marketing', 'Marketing', [
                $this->item('banners', 'Bannery', 'admin.banery.index', 'fa-rectangle-ad', active: ['admin.banery.*', 'admin.strefy-bannerow.*'],
                    children: [
                        $this->child('Strefy bannerów', 'admin.strefy-bannerow.index', 'admin.strefy-bannerow.*'),
                    ]),
                $this->item('newsletter', 'Newsletter', 'admin.newsletter.edit', 'fa-envelope', active: 'admin.newsletter.*'),
                $this->item('subscribers', 'Subskrybenci', 'admin.subskrybenci.index', 'fa-bell', active: 'admin.subskrybenci.*'),
                $this->item('campaigns', 'Kampanie zbiórkowe', 'admin.kampanie.index', 'fa-hand-holding-heart', active: 'admin.kampanie.*'),
            ]) : null,

            $isAdmin ? $this->section('inbox', 'Skrzynka', [
                $this->item('contact', 'Wiadomości kontaktowe', 'admin.wiadomosci-kontaktowe.index', 'fa-envelope-open',
                    active: 'admin.wiadomosci-kontaktowe.*',
                    badge: function () {
                        try { return ContactMessage::unreadCount(); } catch (\Throwable) { return 0; }
                    }),
                $this->item('meetings', 'Zgłoszenia (spotkania)', 'admin.zgloszenia-spotkania.index', 'fa-handshake-angle', active: 'admin.zgloszenia-spotkania.*'),
                $this->item('barriers', 'Zgłoszenia barier', 'admin.zgloszenia-barier.index', 'fa-universal-access', active: 'admin.zgloszenia-barier.*'),
            ]) : null,

            $isAdmin ? $this->section('users', 'Użytkownicy', [
                $this->item('users', 'Użytkownicy', 'admin.uzytkownicy.index', 'fa-users', active: 'admin.uzytkownicy.*'),
                $this->item('groups', 'Grupy użytkowników', 'admin.grupy.index', 'fa-user-group', active: 'admin.grupy.*'),
                $this->item('invitations', 'Zaproszenia do strefy', 'admin.zaproszenia-strefy.index', 'fa-envelope-open-text', active: 'admin.zaproszenia-strefy.*'),
            ]) : null,

            $isAdmin ? $this->section('system', 'System', [
                $this->item('settings', 'Ustawienia strony', 'admin.ustawienia.edit', 'fa-sliders',
                    active: 'admin.ustawienia.*',
                    children: array_merge(
                        array_map(
                            fn (string $key, string $label) => $this->child(
                                $label,
                                'admin.ustawienia.edit',
                                fn () => request()->routeIs('admin.ustawienia.edit', 'admin.ustawienia.update') && request('tab', 'general') === $key,
                                params: ['tab' => $key],
                            ),
                            array_keys(SiteSetting::SETTINGS_TABS),
                            SiteSetting::SETTINGS_TABS,
                        ),
                        [$this->child('Plik .env', 'admin.ustawienia.env', 'admin.ustawienia.env*', icon: 'fa-file-code')],
                    )),
                $this->item('templates', 'Szablony', 'admin.szablony.manage', 'fa-clone',
                    active: ['admin.szablony.*', 'admin.mail-templates.*'],
                    children: [
                        $this->child('Szablony treści', 'admin.szablony.manage', 'admin.szablony.*'),
                        $this->child('Szablony maili', 'admin.mail-templates.index', 'admin.mail-templates.*'),
                    ]),
                $this->item('seo', 'Narzędzia SEO', 'admin.przekierowania.index', 'fa-signs-post',
                    active: ['admin.przekierowania.*', 'admin.martwe-linki.*', 'admin.tresc.*'],
                    children: [
                        $this->child('Przekierowania 301', 'admin.przekierowania.index', 'admin.przekierowania.*'),
                        $this->child('Martwe linki', 'admin.martwe-linki.index', 'admin.martwe-linki.*'),
                        $this->child('Przenoszenie treści', 'admin.tresc.index', 'admin.tresc.*'),
                    ]),
                $this->item('activity', 'Dziennik zdarzeń', 'admin.dziennik.index', 'fa-clock-rotate-left', active: 'admin.dziennik.*'),
                $this->item('wcag', 'Skaner WCAG', 'admin.wcag-scans.index', 'fa-universal-access', active: 'admin.wcag-scans.*'),
                $this->item('gdpr', 'Klauzule RODO', 'admin.klauzule-rodo.index', 'fa-shield-halved', active: 'admin.klauzule-rodo.*'),
                $this->item('modules', 'Moduły', 'admin.moduly.index', 'fa-puzzle-piece', active: 'admin.moduly.*'),
                $this->item('health', 'Health Check', 'admin.health.index', 'fa-heart-pulse', active: 'admin.health.*'),
                $this->item('cache', 'Cache', 'admin.cache.index', 'fa-bolt', active: 'admin.cache.*'),
                $this->item('sites', 'Witryny sieci', 'admin.witryny.index', 'fa-sitemap', active: 'admin.witryny.*'),
            ]) : null,
        ];

        // Sekcje robocze (poniżej) łączymy w sześć grup głównych w stylu TYPO3. Pozycje, do których
        // użytkownik nie ma dostępu, już wypadły — pusta grupa znika w całości.
        $byKey = collect(array_filter($sections))->keyBy('key');

        return array_values(array_filter([
            $this->group($byKey, 'start', 'Pulpit', 'fa-gauge', [[null, 'start']]),
            $this->group($byKey, 'pages', 'Strony', 'fa-file-lines', [[null, 'pages'], ['Strona główna', 'homepage']]),
            $this->group($byKey, 'content', 'Treści', 'fa-newspaper', [[null, 'content'], ['Biblioteka', 'library']]),
            $this->group($byKey, 'comms', 'Komunikacja', 'fa-comments', [['Marketing', 'marketing'], ['Skrzynka', 'inbox']]),
            $this->group($byKey, 'users', 'Użytkownicy', 'fa-users', [[null, 'users']]),
            $this->group($byKey, 'system', 'System', 'fa-gear', [
                ['Konfiguracja', 'system', ['settings', 'templates', 'seo']],
                ['Narzędzia', 'system', ['activity', 'wcag', 'gdpr', 'modules', 'health', 'cache', 'sites']],
            ]),
        ]));
    }

    /**
     * Grupa główna menu: scala bloki z sekcji roboczych. Blok = [podtytuł, klucz sekcji, ?klucze pozycji].
     *
     * @param  \Illuminate\Support\Collection<string, array<string, mixed>>  $sections
     * @param  array<int, array{0: ?string, 1: string, 2?: array<int, string>}>  $blockSpecs
     */
    private function group($sections, string $key, string $label, string $icon, array $blockSpecs): ?array
    {
        $blocks = [];
        foreach ($blockSpecs as $spec) {
            $items = $sections->get($spec[1])['items'] ?? [];
            if (isset($spec[2])) {
                $items = array_values(array_filter($items, fn (array $i) => in_array($i['key'], $spec[2], true)));
            }
            if ($items !== []) {
                $blocks[] = ['heading' => $spec[0], 'items' => $items];
            }
        }

        if ($blocks === []) {
            return null;
        }

        $all = collect($blocks)->flatMap(fn (array $b) => $b['items']);

        return [
            'key'    => $key,
            'label'  => $label,
            'icon'   => $icon,
            'active' => $all->contains(fn (array $i) => $i['active']),
            'badge'  => ($sum = (int) $all->sum(fn (array $i) => (int) ($i['badge'] ?? 0))) > 0 ? $sum : null,
            'blocks' => $blocks,
        ];
    }

    // ── Budowanie elementów ──────────────────────────────────────────

    /**
     * @param  array<int, ?array<string, mixed>>  $items
     */
    private function section(string $key, ?string $label, array $items, bool $defaultOpen = false): ?array
    {
        $items = array_values(array_filter($items));

        if ($items === []) {
            return null;
        }

        return [
            'key'          => $key,
            'label'        => $label,
            'items'        => $items,
            'active'       => (bool) array_filter($items, fn (array $item) => $item['active']),
            'default_open' => $defaultOpen,
        ];
    }

    /**
     * @param  string|array<int, string>|null  $active  wzorce tras (`routeIs`); domyślnie sama trasa
     * @param  array<int, ?array<string, mixed>>  $children
     */
    private function item(
        string $key,
        string $label,
        string $route,
        string $icon,
        string|array|null $active = null,
        ?Closure $badge = null,
        string $badgeTone = 'brand',
        array $children = [],
    ): ?array {
        if (! Route::has($route)) {
            return null;
        }

        $children = array_values(array_filter($children));
        $isActive = request()->routeIs($active ?? $route)
            || (bool) array_filter($children, fn (array $child) => $child['active']);

        $count = $badge ? (int) $badge() : null;

        return [
            'key'        => $key,
            'label'      => $label,
            'url'        => route($route),
            'icon'       => $icon,
            'active'     => $isActive,
            'badge'      => $count > 0 ? $count : null,
            'badge_tone' => $badgeTone,
            'children'   => $children,
        ];
    }

    /**
     * @param  string|array<int, string>|Closure  $active
     * @param  array<string, mixed>  $params
     */
    private function child(string $label, string $route, string|array|Closure $active, array $params = [], ?string $icon = null): ?array
    {
        if (! Route::has($route)) {
            return null;
        }

        return [
            'label'  => $label,
            'url'    => route($route, $params),
            'icon'   => $icon,
            'active' => $active instanceof Closure ? (bool) $active() : request()->routeIs($active),
        ];
    }

    /** Moduł włączony (menedżer modułów lub ustawienia witryny) i dostępny dla roli użytkownika. */
    private function can(string $module): bool
    {
        $enabled = $this->modules->get($module) !== null
            ? $this->modules->isActive($module)
            : $this->site->isModuleEnabled($module);

        return $enabled && $this->user->canAccessModule($module);
    }
}
