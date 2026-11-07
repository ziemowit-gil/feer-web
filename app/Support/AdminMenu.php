<?php

declare(strict_types=1);

namespace App\Support;

use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\TrashController;
use App\Models\ContactMessage;
use App\Models\CooperationRequest;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\SiteSetting;
use App\Models\User;
use App\Modules\ModuleManager;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Definicja menu bocznego panelu administracyjnego.
 *
 * Układ jak w module menu TYPO3, ale grupy to obszary pracy (Start, Treści, Działalność, Komunikacja,
 * Dokumenty i BIP, a na końcu administracja: Ustawienia i System), a w grupie bloki z podtytułami → pozycje →
 * podpozycje. Widok `admin.partials.sidebar` tylko to renderuje. Dzięki temu:
 *  - widoczność pozycji (moduły, role) jest rozstrzygana w jednym miejscu; rola widzi tylko swoje obszary,
 *    obszar roboczy roli jest zaraz po „Starcie” ({@see ROLE_FOCUS}), a grupa z samymi plikami nazywa się „Pliki”,
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
 *  child:   ['label' => string, 'url' => string, 'active' => bool, 'icon' => ?string, 'group' => ?string]
 */
final class AdminMenu
{
    /** Moduły premium oznaczane w menu niebieską obwódką: grupy i pozycje. */
    public const PREMIUM_GROUPS = ['activity', 'docs'];

    public const PREMIUM_ITEMS = ['reports'];

    /**
     * Obszar roboczy roli zaraz po grupie „Start” (reszta grup w kolejności domyślnej): koordynator zaczyna od
     * Działalności, redaktor PR od Treści i Komunikacji, role BIP od BIP, role newslettera od Komunikacji.
     * Administrator, edytor treści i edytor z grupy mają kolejność domyślną.
     */
    public const ROLE_FOCUS = [
        User::ROLE_COORDINATOR          => ['activity'],
        User::ROLE_PR_EDITOR            => ['content', 'comms'],
        User::ROLE_BIP_EDITOR_PLUS      => ['docs'],
        User::ROLE_BIP_EDITOR           => ['docs'],
        User::ROLE_NEWSLETTER_EDITOR    => ['comms'],
        User::ROLE_NEWSLETTER_PUBLISHER => ['comms'],
    ];

    /**
     * Pozycje zdefiniowane w sekcjach, których główny układ celowo nie umieszcza: „Ustawienia strony” (zastąpione
     * zakładkami w grupie Ustawienia, używane tylko w menu witryny bloga) i sekcja `blog` (menu witryny „Wiem FEER”
     * oraz pozycja „Wiem FEER (blog)” w Treściach). Każda inna pozycja musi mieć miejsce w {@see layout()}.
     */
    private const OUTSIDE_MAIN_LAYOUT = ['settings'];

    private const BLOG_ONLY_SECTION = 'blog';

    /**
     * Menu skupione na blogu, gdy w panelu aktywna jest witryna „Wiem FEER” (BlogAdmin::isBlogSiteActive()):
     * lista dozwolonych sekcji roboczych → klucze pozycji (null = wszystkie pozycje sekcji).
     * Sekcje spoza listy (aktualności, działania, BIP, sklep, strona główna, marketing…) są ukryte.
     */
    public const BLOG_SITE_SECTIONS = [
        'blog'       => null,
        'start'      => ['approvals'],
        'pages'      => ['pages', 'nav'],
        'library'    => null,
        'newsletter' => null,
        'users'      => null,
        'system'     => null,
    ];

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
        $byKey = $this->sectionsByKey();

        if ($byKey->has('blog') && BlogAdmin::isBlogSiteActive()) {
            return $this->blogSiteGroups($byKey);
        }

        return $this->adaptToRole($this->layout($byKey));
    }

    /**
     * Klucze pozycji dostępnych dla użytkownika, których główny układ nigdzie nie umieszcza — takie pozycje nie
     * pokazałyby się w menu wcale (tak zniknęły „Portale”, „Jakość tekstów” i „Serwer MCP”). Pilnuje tego test.
     *
     * @return array<int, string>
     */
    public static function unplacedItemKeys(User $user): array
    {
        $menu = new self($user);
        $byKey = $menu->sectionsByKey();
        $placed = collect($menu->layout($byKey))->flatMap(fn (array $g) => $g['blocks'])->flatMap(fn (array $b) => $b['items'])->pluck('key');
        $defined = $byKey->except(self::BLOG_ONLY_SECTION)->flatMap(fn (array $s) => $s['items'])->pluck('key');

        return $defined->diff($placed)->diff(self::OUTSIDE_MAIN_LAYOUT)->unique()->values()->all();
    }

    /**
     * Czy moduł jest włączony i dostępny dla użytkownika — ta sama reguła co widoczność pozycji menu
     * (menedżer modułów albo ustawienia witryny, plus uprawnienie roli). Pulpit używa jej do skrótów „Utwórz”.
     */
    public static function moduleAllowed(User $user, string $module): bool
    {
        return (new self($user))->can($module);
    }

    /** @return Collection<string, array<string, mixed>> */
    private function sectionsByKey(): Collection
    {
        return collect(array_filter($this->sections()))->keyBy('key');
    }

    /**
     * Sekcje robocze: pozycje z uprawnieniami i licznikami. Do głównych grup łączy je {@see layout()}.
     *
     * @return array<int, ?array<string, mixed>>
     */
    private function sections(): array
    {
        $isAdmin = $this->user->isAdmin();

        return [
            $this->section('start', null, [
                $this->item('dashboard', 'Pulpit', 'admin.dashboard', 'fa-gauge'),
                $this->user->canApproveContent()
                    ? $this->item('approvals', 'Do zatwierdzenia', 'admin.zatwierdzanie.index', 'fa-clipboard-check',
                        active: 'admin.zatwierdzanie.*', badge: fn () => ApprovalController::pendingCount())
                    : null,
            ], defaultOpen: true),

            // Witryna = struktura serwisu: drzewo stron, menu nawigacji i osoby (strony typu „osoba”).
            $this->can('pages') ? $this->section('pages', 'Strony', [
                $this->item('pages', 'Strony', 'admin.podstrony.index', 'fa-file-lines',
                    active: fn () => request()->routeIs('admin.podstrony.*') && ! request()->routeIs('admin.podstrony.mapa')),
                $isAdmin ? $this->item('nav', 'Menu nawigacji', 'admin.pozycje-menu.index', 'fa-bars',
                    active: fn () => request()->routeIs('admin.pozycje-menu.*') && request('location') !== 'bip') : null,
                $this->item('persons', 'Osoby', 'admin.osoby.index', 'fa-address-card', active: 'admin.osoby.*'),
                $this->item('page-graph', 'Mapa zależności', 'admin.podstrony.mapa', 'fa-diagram-project', active: 'admin.podstrony.mapa'),
            ], defaultOpen: true) : null,

            // Pozycje bloga jako osobna sekcja — używana w menu witryny „Wiem FEER”.
            $this->can('blog') ? $this->section('blog', 'Wiem FEER', [
                $this->item('blog-dashboard', 'Pulpit Wiem FEER', 'admin.wiem.dashboard', 'fa-gauge'),
                $this->item('blog-articles', 'Wpisy', 'admin.wiem-feer.index', 'fa-feather-pointed', active: 'admin.wiem-feer.*'),
                $this->item('blog-categories', 'Kategorie', 'admin.kategorie-bloga.index', 'fa-tags', active: 'admin.kategorie-bloga.*'),
                $this->item('blog-authors', 'Autorzy', 'admin.autorzy-bloga.index', 'fa-user-pen', active: 'admin.autorzy-bloga.*'),
                $this->item('blog-comments', 'Komentarze', 'admin.komentarze-bloga.index', 'fa-comments',
                    active: 'admin.komentarze-bloga.*', badge: fn () => BlogAdmin::pendingCommentsCount()),
            ], defaultOpen: true) : null,

            // BIP jako osobna „witryna”: boczne menu BIP i rejestr dokumentów.
            $this->section('bipsite', 'BIP', [
                // Menu BIP tylko przy włączonym module BIP (wyłączony BIP chowa całą grupę „Dokumenty i BIP”).
                $isAdmin && $this->bipEnabled() ? $this->item('bip-menu', 'Menu BIP', 'admin.pozycje-menu.index', 'fa-bars',
                    active: fn () => request()->routeIs('admin.pozycje-menu.*') && request('location') === 'bip', params: ['location' => 'bip']) : null,
                $this->can('bip') ? $this->item('bip', 'Dokumenty BIP', 'admin.bip-dokumenty.index', 'fa-landmark', active: 'admin.bip-dokumenty.*') : null,
            ]),

            $this->section('homepage', 'Strona główna', [
                $this->can('hero')          ? $this->item('hero', 'Slidery', 'admin.hero.index', 'fa-images', active: ['admin.hero.*', 'admin.slidery.*']) : null,
                $this->can('gallery')       ? $this->item('gallery', 'Galeria', 'admin.galeria.index', 'fa-panorama', active: 'admin.galeria.*') : null,
                $this->can('feer_bands')    ? $this->item('feer-bands', 'FEER Paski', 'admin.feer-paski.index', 'fa-grip-lines', active: 'admin.feer-paski.*') : null,
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
                    active: BlogAdmin::ROUTES,
                    children: [
                        $this->child('Pulpit Wiem FEER', 'admin.wiem.dashboard', 'admin.wiem.dashboard'),
                        $this->child('Kategorie', 'admin.kategorie-bloga.index', 'admin.kategorie-bloga.*'),
                        $this->child('Autorzy', 'admin.autorzy-bloga.index', 'admin.autorzy-bloga.*'),
                        $this->child('Komentarze', 'admin.komentarze-bloga.index', 'admin.komentarze-bloga.*'),
                    ]) : null,
                $this->can('podcasts') ? $this->item('podcasts', 'Podcasty', 'admin.podcasty.index', 'fa-podcast', active: 'admin.podcasty.*') : null,
                $this->can('forms') ? $this->item('forms', 'Formularze', 'admin.formularze.index', 'fa-wpforms',
                    active: 'admin.formularze.*',
                    badge: fn () => FormSubmission::forCurrentSite()->whereNull('read_at')->count()) : null,
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
                $this->can('courses') ? $this->item('courses', 'Oferta szkoleń i zajęć', 'admin.kursy.index', 'fa-chalkboard-user', active: 'admin.kursy.*') : null,
                $this->can('volunteering') ? $this->item('volunteering', 'Wolontariat', 'admin.wolontariat.index', 'fa-hands-helping', active: 'admin.wolontariat.*') : null,
                $this->can('jobs')         ? $this->item('jobs', 'Ogłoszenia o pracę', 'admin.praca.index', 'fa-briefcase', active: 'admin.praca.*') : null,
                $this->can('projects') ? $this->item('projects', 'Działania', 'admin.projekty.index', 'fa-diagram-project',
                    active: ['admin.projekty.*', 'admin.kategorie.*'],
                    children: [
                        $this->child('Drzewo działań', 'admin.projekty.index',
                            fn () => request()->routeIs('admin.projekty.index') && request()->query('widok') !== 'lista' && ! request()->hasAny(['status', 'category', 'sort']),
                            icon: 'fa-sitemap', group: 'Widoki'),
                        $this->child('Lista i filtry', 'admin.projekty.index',
                            fn () => request()->routeIs('admin.projekty.index') && (request()->query('widok') === 'lista' || request()->hasAny(['status', 'category', 'sort'])),
                            params: ['widok' => 'lista'], icon: 'fa-list', group: 'Widoki'),
                        $this->child('Nowe działanie', 'admin.projekty.create', 'admin.projekty.create', icon: 'fa-plus', group: 'Zarządzanie'),
                        $this->child('Kategorie działań', 'admin.kategorie.index', 'admin.kategorie.*', icon: 'fa-tags', group: 'Zarządzanie'),
                    ]) : null,
                $this->can('projects') && \Illuminate\Support\Facades\Schema::hasColumn('categories', 'portal_slug')
                    ? $this->item('portals', 'Portale', 'admin.portale.index', 'fa-table-columns', active: 'admin.portale.*')
                    : null,
                $this->can('projects') && \Illuminate\Support\Facades\Schema::hasTable('coordinators')
                    ? $this->item('coordinators', 'Koordynatorzy', 'admin.koordynatorzy.index', 'fa-user-tie', active: 'admin.koordynatorzy.*')
                    : null,
                // Moduł Kontakt: strona kontaktu, formularz, podstrony, „Gdzie działamy” i dodatki (modules/Contact).
                $this->can('contact') && \Illuminate\Support\Facades\Route::has('admin.kontakt.index')
                    ? $this->item('contact-hub', 'Kontakt', 'admin.kontakt.index', 'fa-address-card', active: 'admin.kontakt.*') : null,
                $this->can('phone_book') ? $this->item('phone-book', 'Książka telefoniczna', 'admin.ksiazka-telefoniczna.index', 'fa-address-book', active: 'admin.ksiazka-telefoniczna.*') : null,
                $this->can('faq')      ? $this->item('faq', 'FAQ', 'admin.faq.index', 'fa-circle-question', active: 'admin.faq.*') : null,
                $this->can('reports')  ? $this->item('reports', 'Sprawozdania', 'admin.sprawozdania.index', 'fa-file-invoice', active: 'admin.sprawozdania.*') : null,
                $this->can('landing')  ? $this->item('landing', 'Landing pages', 'admin.lp.index', 'fa-bullhorn', active: 'admin.lp.*') : null,
                $this->can('help_map') ? $this->item('help-map', 'Mapa pomocy', 'admin.mapa-pomocy.index', 'fa-map-location-dot', active: 'admin.mapa-pomocy.*') : null,
                $this->site->site_template === 'federation'
                    ? $this->item('organizations', 'Organizacje członkowskie', 'admin.organizacje.index', 'fa-people-roof', active: 'admin.organizacje.*')
                    : null,
                ($this->can('pages') || $this->can('news') || $this->can('projects'))
                    ? $this->item('content-review', 'Przegląd treści', 'admin.przeglad-tresci.index', 'fa-calendar-check', active: 'admin.przeglad-tresci.*',
                        badge: fn () => ContentReview::available() ? ContentReview::staleCount(fn ($m) => $this->user->canAccessModule($m)) : 0, badgeTone: 'muted')
                    : null,
                ($this->can('pages') || $this->can('news') || $this->can('projects'))
                    ? $this->item('content-a11y', 'Dostępność treści', 'admin.content-a11y.index', 'fa-universal-access', active: 'admin.content-a11y.*')
                    : null,
                $this->can('polls') ? $this->item('polls', 'Ankiety', 'admin.ankiety.index', 'fa-square-poll-vertical', active: 'admin.ankiety.*') : null,
                $this->can('timeline')
                    ? $this->item('timeline', 'Oś czasu (historia)', 'admin.os-czasu.edit', 'fa-timeline', active: 'admin.os-czasu.*')
                    : null,
                $this->can('cooperation')
                    ? $this->item('cooperation', 'Zgłoszenia współpracy', 'admin.wspolpraca-zgloszenia.index', 'fa-handshake',
                        active: 'admin.wspolpraca-zgloszenia.*',
                        badge: fn () => CooperationRequest::whereNull('read_at')->count())
                    : null,
            ]),

            $this->section('library', 'Biblioteka', [
                $this->item('media', 'Multimedia', 'admin.multimedia.index', 'fa-photo-film', active: 'admin.multimedia.*'),
                // Kosz zbiera strony, aktualności, działania i dokumenty BIP — rola bez tych modułów miałaby go zawsze pustego.
                ($this->can('pages') || $this->can('news') || $this->can('projects') || $this->can('bip'))
                    ? $this->item('trash', 'Kosz', 'admin.kosz.index', 'fa-trash-can', active: 'admin.kosz.*',
                        badge: fn () => TrashController::count(), badgeTone: 'muted')
                    : null,
            ], defaultOpen: true),

            $isAdmin ? $this->section('marketing', 'Marketing', [
                $this->item('banners', 'Bannery', 'admin.banery.index', 'fa-rectangle-ad', active: ['admin.banery.*', 'admin.strefy-bannerow.*'],
                    children: [
                        $this->child('Strefy bannerów', 'admin.strefy-bannerow.index', 'admin.strefy-bannerow.*'),
                    ]),
                $this->item('campaigns', 'Kampanie zbiórkowe', 'admin.kampanie.index', 'fa-hand-holding-heart', active: 'admin.kampanie.*'),
            ]) : null,

            // Newsletter to osobny moduł: jedna pozycja, a jego sekcje są kafelkami na pulpicie.
            $this->can('newsletter') ? $this->section('newsletter', 'Newsletter', [
                $this->item('newsletter', 'Newsletter', 'admin.newsletter.dashboard', 'fa-envelope', active: 'admin.newsletter.*',
                    children: $this->newsletterChildren()),
            ], defaultOpen: true) : null,

            $isAdmin ? $this->section('inbox', 'Skrzynka', [
                $this->contactInboxItem(),
                $this->item('meetings', 'Zgłoszenia (spotkania)', 'admin.zgloszenia-spotkania.index', 'fa-handshake-angle', active: 'admin.zgloszenia-spotkania.*'),
                $this->item('barriers', 'Zgłoszenia barier', 'admin.zgloszenia-barier.index', 'fa-universal-access', active: 'admin.zgloszenia-barier.*'),
            ]) : null,

            $isAdmin ? $this->section('users', 'Użytkownicy', [
                $this->item('users', 'Użytkownicy', 'admin.uzytkownicy.index', 'fa-users', active: 'admin.uzytkownicy.*'),
                $this->item('groups', 'Grupy użytkowników', 'admin.grupy.index', 'fa-user-group', active: 'admin.grupy.*'),
                $this->item('invitations', 'Zaproszenia do strefy', 'admin.zaproszenia-strefy.index', 'fa-envelope-open-text', active: 'admin.zaproszenia-strefy.*'),
            ]) : null,

            // Zakładki ustawień jako zwykłe pozycje — w grupie „Ustawienia” podzielone na bloki (SETTINGS_TAB_GROUPS).
            $isAdmin ? $this->section('settings', 'Ustawienia', $this->settingsItems()) : null,

            $isAdmin ? $this->section('system', 'System', [
                $this->item('settings', 'Ustawienia strony', 'admin.ustawienia.edit', 'fa-sliders',
                    active: 'admin.ustawienia.*',
                    children: $this->settingsChildren()),
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
                $this->item('text_quality', 'Jakość tekstów', 'admin.jakosc-tekstow.index', 'fa-spell-check', active: 'admin.jakosc-tekstow.*'),
                $this->item('gdpr', 'Klauzule RODO', 'admin.klauzule-rodo.index', 'fa-shield-halved', active: 'admin.klauzule-rodo.*'),
                $this->item('modules', 'Instalacja modułów', 'admin.moduly.index', 'fa-puzzle-piece', active: 'admin.moduly.*'),
                $this->item('health', 'Kondycja serwisu', 'admin.health.index', 'fa-heart-pulse', active: 'admin.health.*'),
                $this->item('mcp', 'Serwer MCP', 'admin.mcp.index', 'fa-plug', active: 'admin.mcp.*'),
                $this->item('cache', 'Cache', 'admin.cache.index', 'fa-bolt', active: 'admin.cache.*'),
                $this->item('backups', 'Kopie zapasowe', 'admin.kopie.index', 'fa-box-archive', active: 'admin.kopie.*'),
                $this->item('database', 'Baza MySQL', 'admin.baza-mysql.index', 'fa-database', active: 'admin.baza-mysql.*'),
                $this->item('cron', 'Harmonogram (cron)', 'admin.cron.index', 'fa-clock', active: 'admin.cron.*'),
                $this->item('sites', 'Zarządzaj subwitrynami', 'admin.witryny.index', 'fa-sitemap', active: 'admin.witryny.*'),
            ]) : null,
        ];
    }

    /**
     * Główne grupy menu: grupa → bloki → pozycje. Każda pozycja z sekcji ma tu dokładnie jedno miejsce
     * ({@see unplacedItemKeys()} musi być puste). Grupy to obszary pracy, a nie moduły:
     *  - Treści — wszystko, co widać na stronie: struktura i nawigacja, publikacje, strona główna, pliki, kontrola jakości,
     *  - Działalność — to, co organizacja robi i oferuje: działania, szkolenia i oferta, zaangażowanie,
     *  - Komunikacja — kontakt z odbiorcami: skrzynka, formularze i ankiety, newsletter, promocja i kampanie,
     *  - Dokumenty i BIP — osobny obszar urzędowy (tylko przy włączonym module),
     *  - na końcu administracja: Ustawienia (konfiguracja serwisu) i System (użytkownicy, dane, diagnostyka).
     * Pozycje, do których użytkownik nie ma dostępu, już wypadły z sekcji — pusta grupa znika w całości.
     *
     * @param  Collection<string, array<string, mixed>>  $byKey
     * @return array<int, array<string, mixed>>
     */
    private function layout(Collection $byKey): array
    {
        // Bloki „Ustawień” = kategorie zakładek; do „SEO i dostępności” dochodzą narzędzia SEO i skaner WCAG,
        // do „Funkcji i integracji” szablony treści i maili.
        $settingsBlocks = collect(SiteSetting::SETTINGS_TAB_GROUPS)
            ->map(fn ($tabs, $heading) => [$heading, ['settings', 'system'], array_merge(
                array_map(fn ($t) => 'settings-'.$t, $tabs),
                match ($heading) {
                    'SEO i dostępność' => ['seo', 'wcag'],
                    'Funkcje i integracje' => ['templates'],
                    default => [],
                },
            )])
            ->values()->all();
        $settingsBlocks[] = ['Zaawansowane', ['settings', 'system'], ['settings-other', 'settings-env']];

        return array_values(array_filter([
            $this->group($byKey, 'start', 'Start', 'fa-gauge', [[null, 'start']]),
            // „Treści” — to, co widać na stronie, i narzędzia do pracy z nim.
            $this->group($byKey, 'content', 'Treści', 'fa-newspaper', [
                ['Strony i nawigacja', 'pages'],
                ['Publikacje', 'content', ['news', 'blog', 'podcasts', 'faq', 'timeline']],
                ['Strona główna', 'homepage'],
                ['Pliki', 'library'],
                ['Kontrola jakości', ['content', 'system'], ['content-review', 'content-a11y', 'text_quality']],
            ]),
            // „Działalność” — to, co organizacja robi i oferuje.
            $this->group($byKey, 'activity', 'Działalność', 'fa-diagram-project', [
                ['Działania', 'content', ['projects', 'portals', 'coordinators', 'help-map', 'organizations']],
                ['Szkolenia i oferta', 'content', ['events', 'courses', 'materials', 'shop']],
                ['Zaangażowanie', 'content', ['volunteering', 'jobs']],
                // Bez modułu BIP sprawozdania są tutaj — grupy „Dokumenty i BIP” wtedy nie ma.
                ...($this->bipEnabled() ? [] : [['Sprawozdawczość', 'content', ['reports']]]),
            ]),
            // „Komunikacja” — kontakt z odbiorcami: przychodzące, formularze, wysyłki i promocja.
            $this->group($byKey, 'comms', 'Komunikacja', 'fa-comments', [
                ['Skrzynka', ['inbox', 'content'], ['contact', 'meetings', 'barriers', 'cooperation']],
                ['Kontakt', 'content', ['contact-hub', 'phone-book']],
                ['Formularze i ankiety', 'content', ['forms', 'polls']],
                ['Newsletter', 'newsletter'],
                ['Promocja i kampanie', ['content', 'marketing'], ['landing', 'campaigns', 'banners']],
            ]),
            $this->bipEnabled() ? $this->group($byKey, 'docs', 'Dokumenty i BIP', 'fa-landmark', [
                ['Biuletyn (BIP)', 'bipsite'],
                ['Sprawozdawczość', 'content', ['reports']],
            ]) : null,
            $this->group($byKey, 'settings', 'Ustawienia', 'fa-sliders', $settingsBlocks),
            $this->group($byKey, 'system', 'System', 'fa-gear', [
                ['Użytkownicy i dostęp', 'users'],
                ['Dane i bezpieczeństwo', 'system', ['activity', 'gdpr', 'backups', 'database']],
                ['Diagnostyka', 'system', ['health', 'cache', 'cron']],
                ['Moduły i integracje', 'system', ['modules', 'mcp']],
                ['Sieć witryn', 'system', ['sites']],
            ]),
        ]));
    }

    /**
     * Dopasowanie do roli: obszar roboczy roli zaraz po „Starcie” ({@see ROLE_FOCUS}), a grupa złożona z samych
     * plików (rola bez modułów treści, np. BIP albo newsletter) nazywa się po prostu „Pliki”.
     *
     * @param  array<int, array<string, mixed>>  $groups
     * @return array<int, array<string, mixed>>
     */
    private function adaptToRole(array $groups): array
    {
        foreach ($groups as &$group) {
            if ($group['key'] === 'content' && count($group['blocks']) === 1 && $group['blocks'][0]['heading'] === 'Pliki') {
                $group['key'] = 'library';
                $group['label'] = 'Pliki';
                $group['icon'] = 'fa-photo-film';
                $group['blocks'][0]['heading'] = null;
            }
        }
        unset($group);

        $focus = self::ROLE_FOCUS[(string) $this->user->role] ?? [];
        $rank = function (array $group) use ($focus): int {
            if ($group['key'] === 'start') {
                return -1;
            }
            $position = array_search($group['key'], $focus, true);

            return $position === false ? count($focus) : $position;
        };
        // usort jest stabilny (PHP ≥ 8): grupy o tej samej randze zostają w kolejności domyślnej.
        usort($groups, fn (array $a, array $b) => $rank($a) <=> $rank($b));

        return $groups;
    }

    /**
     * Menu witryny „Wiem FEER”: najpierw blog, potem wspólne niezbędne pozycje — według BLOG_SITE_SECTIONS.
     *
     * @param  \Illuminate\Support\Collection<string, array<string, mixed>>  $sections
     * @return array<int, array<string, mixed>>
     */
    private function blogSiteGroups($sections): array
    {
        $sections = $sections->only(array_keys(self::BLOG_SITE_SECTIONS));
        $spec = fn (?string $heading, string $key, ?array $keys = null) => array_filter(
            [$heading, $key, $keys ?? self::BLOG_SITE_SECTIONS[$key]],
            fn ($v, $i) => $i < 2 || $v !== null,
            ARRAY_FILTER_USE_BOTH,
        );

        return array_values(array_filter([
            $this->group($sections, 'start', 'Wiem FEER', 'fa-feather-pointed', [$spec(null, 'blog'), $spec(null, 'start')]),
            $this->group($sections, 'pages', 'Witryna', 'fa-sitemap', [$spec('Strony i nawigacja', 'pages')]),
            $this->group($sections, 'content', 'Biblioteka', 'fa-photo-film', [$spec(null, 'library'), $spec('Newsletter', 'newsletter')]),
            $this->group($sections, 'users', 'Użytkownicy', 'fa-users', [$spec(null, 'users')]),
            $this->group($sections, 'system', 'System', 'fa-gear', [
                $spec('Konfiguracja', 'system', ['settings', 'templates', 'seo']),
                $spec('Narzędzia', 'system', ['activity', 'wcag', 'gdpr', 'modules', 'health', 'cache', 'cron', 'backups', 'database', 'sites']),
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
            $items = collect((array) $spec[1])->flatMap(fn (string $k) => $sections->get($k)['items'] ?? [])->all();
            if (isset($spec[2])) {
                // Kolejność pozycji jak w specyfikacji bloku.
                $byItemKey = collect($items)->keyBy('key');
                $items = collect($spec[2])->map(fn ($k) => $byItemKey->get($k))->filter()->values()->all();
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
            // Licznik grupy to sprawy „do obsłużenia”; informacyjne liczniki (Kosz, Przegląd treści) go nie podbijają.
            'badge'  => ($sum = (int) $all->sum(fn (array $i) => ($i['badge_tone'] ?? 'brand') === 'muted' ? 0 : (int) ($i['badge'] ?? 0))) > 0 ? $sum : null,
            'premium' => in_array($key, self::PREMIUM_GROUPS, true),
            'blocks' => $blocks,
        ];
    }

    /**
     * Podpozycje „Ustawień strony” pogrupowane w kategorie (SiteSetting::SETTINGS_TAB_GROUPS), z nagłówkami
     * grup w kluczu `group`; zakładki spoza podziału trafiają do grupy „Inne”, a plik .env do „Zaawansowane”.
     *
     * @return array<int, ?array<string, mixed>>
     */
    private function settingsChildren(): array
    {
        $tabs = SiteSetting::SETTINGS_TABS;
        $children = [];
        $placed = [];

        foreach (SiteSetting::SETTINGS_TAB_GROUPS as $group => $keys) {
            foreach ($keys as $key) {
                if (isset($tabs[$key])) {
                    $children[] = $this->settingsTab($key, $tabs[$key], $group);
                    $placed[] = $key;
                }
            }
        }
        foreach (array_diff(array_keys($tabs), $placed) as $key) {
            $children[] = $this->settingsTab($key, $tabs[$key], 'Inne');
        }

        $children[] = $this->child('Plik .env', 'admin.ustawienia.env', 'admin.ustawienia.env*', icon: 'fa-file-code', group: 'Zaawansowane');

        return $children;
    }

    /** Ikony zakładek ustawień w grupie „Ustawienia”. */
    private const SETTINGS_ICONS = [
        'general' => 'fa-circle-info', 'header' => 'fa-window-maximize', 'colors' => 'fa-palette', 'template' => 'fa-swatchbook',
        'homepage' => 'fa-house', 'content' => 'fa-align-left', 'contact' => 'fa-address-book', 'social' => 'fa-share-nodes',
        'support' => 'fa-hand-holding-heart', 'join' => 'fa-user-plus', 'registry' => 'fa-building-columns', 'modules' => 'fa-puzzle-piece',
        'login' => 'fa-right-to-bracket', 'mail' => 'fa-envelope', 'szo' => 'fa-plug', 'maintenance' => 'fa-screwdriver-wrench',
        'seo' => 'fa-magnifying-glass-chart', 'accessibility' => 'fa-universal-access',
    ];

    /**
     * Zakładki „Ustawień strony” jako pozycje menu (klucz `settings-<zakładka>`), plus zakładki spoza
     * podziału (`settings-other` — pierwsza z nich) i plik .env (`settings-env`).
     *
     * @return array<int, ?array<string, mixed>>
     */
    private function settingsItems(): array
    {
        $items = [];
        $placed = collect(SiteSetting::SETTINGS_TAB_GROUPS)->flatten()->all();
        foreach (SiteSetting::SETTINGS_TABS as $key => $label) {
            $items[] = $this->item(
                in_array($key, $placed, true) ? 'settings-'.$key : 'settings-other',
                $label,
                'admin.ustawienia.edit',
                self::SETTINGS_ICONS[$key] ?? 'fa-sliders',
                active: fn () => request()->routeIs('admin.ustawienia.edit', 'admin.ustawienia.update') && request('tab', 'general') === $key,
                params: ['tab' => $key],
            );
        }
        $items[] = $this->item('settings-env', 'Plik .env', 'admin.ustawienia.env', 'fa-file-code', active: 'admin.ustawienia.env*');

        return $items;
    }

    private function settingsTab(string $key, string $label, string $group): ?array
    {
        return $this->child(
            $label,
            'admin.ustawienia.edit',
            fn () => request()->routeIs('admin.ustawienia.edit', 'admin.ustawienia.update') && request('tab', 'general') === $key,
            params: ['tab' => $key],
            group: $group,
        );
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
     * @param  string|array<int, string>|Closure|null  $active  wzorce tras (`routeIs`) albo warunek; domyślnie sama trasa
     * @param  array<int, ?array<string, mixed>>  $children
     * @param  array<string, mixed>  $params  parametry adresu (np. ['location' => 'bip'])
     */
    private function item(
        string $key,
        string $label,
        string $route,
        string $icon,
        string|array|Closure|null $active = null,
        ?Closure $badge = null,
        string $badgeTone = 'brand',
        array $children = [],
        array $params = [],
    ): ?array {
        if (! Route::has($route)) {
            return null;
        }

        $children = array_values(array_filter($children));
        $isActive = ($active instanceof Closure ? (bool) $active() : request()->routeIs($active ?? $route))
            || (bool) array_filter($children, fn (array $child) => $child['active']);

        $count = $badge ? (int) $badge() : null;

        return [
            'key'        => $key,
            'label'      => $label,
            'url'        => route($route, $params),
            'icon'       => $icon,
            'active'     => $isActive,
            'badge'      => $count > 0 ? $count : null,
            'badge_tone' => $badgeTone,
            'premium'    => in_array($key, self::PREMIUM_ITEMS, true),
            'children'   => $children,
        ];
    }

    /**
     * @param  string|array<int, string>|Closure  $active
     * @param  array<string, mixed>  $params
     */
    private function child(string $label, string $route, string|array|Closure $active, array $params = [], ?string $icon = null, ?string $group = null): ?array
    {
        if (! Route::has($route)) {
            return null;
        }

        return [
            'label'  => $label,
            'url'    => route($route, $params),
            'icon'   => $icon,
            'group'  => $group,
            'active' => $active instanceof Closure ? (bool) $active() : request()->routeIs($active),
        ];
    }

    /**
     * Rozwijane podpozycje newslettera — tylko te, do których użytkownik ma uprawnienie (App\Support\NewsletterPermissions).
     *
     * @return array<int, ?array<string, mixed>>
     */
    private function newsletterChildren(): array
    {
        $can = fn (string $ability): bool => $this->user->can($ability);

        return [
            $this->child('Pulpit', 'admin.newsletter.dashboard', 'admin.newsletter.dashboard'),
            $can('newsletter.view') ? $this->child('Kampanie', 'admin.newsletter.kampanie.index',
                fn () => request()->routeIs('admin.newsletter.kampanie.*') && ! request()->routeIs('admin.newsletter.kampanie.create'), group: 'Wysyłki') : null,
            $can('newsletter.campaigns') ? $this->child('Nowa kampania', 'admin.newsletter.kampanie.create', 'admin.newsletter.kampanie.create', group: 'Wysyłki') : null,
            $can('newsletter.reports') ? $this->child('Raporty', 'admin.newsletter.raporty.index', 'admin.newsletter.raporty.*', group: 'Wysyłki') : null,
            $can('newsletter.subscribers') ? $this->child('Subskrybenci', 'admin.newsletter.subskrybenci.index', 'admin.newsletter.subskrybenci.*', group: 'Odbiorcy') : null,
            $can('newsletter.subscribers') ? $this->child('Listy', 'admin.newsletter.listy.index', 'admin.newsletter.listy.*', group: 'Odbiorcy') : null,
            $can('newsletter.subscribers') ? $this->child('Segmenty', 'admin.newsletter.segmenty.index', 'admin.newsletter.segmenty.*', group: 'Odbiorcy') : null,
            $can('newsletter.subscribers') ? $this->child('Tematy', 'admin.newsletter.tematy.index', 'admin.newsletter.tematy.*', group: 'Odbiorcy') : null,
            $can('newsletter.subscribers') ? $this->child('Formularze zapisu', 'admin.newsletter.formularze.index', 'admin.newsletter.formularze.*', group: 'Odbiorcy') : null,
            $can('newsletter.templates') ? $this->child('Szablony i maile systemowe', 'admin.newsletter.szablony.index', ['admin.newsletter.szablony.*', 'admin.newsletter.mosaico'], group: 'Wygląd') : null,
            $can('newsletter.settings') ? $this->child('Ustawienia', 'admin.newsletter.ustawienia.edit', 'admin.newsletter.ustawienia.*', group: 'Wygląd') : null,
        ];
    }

    /** Moduł włączony (menedżer modułów lub ustawienia witryny) i dostępny dla roli użytkownika. */
    /**
     * „Wiadomości z formularza”: formularz kontaktowy jest w kreatorze (Formularze → Kontakt), więc pozycja
     * prowadzi do jego zgłoszeń; wiadomości z dawnego formularza zostają jako archiwum (podpozycja), o ile są.
     */
    private function contactInboxItem(): ?array
    {
        try {
            $form = FormDefinition::where('slug', ContactForm::SLUG)->first(['id']);
            $hasArchive = ContactMessage::forCurrentSite()->exists();
        } catch (\Throwable) {
            $form = null;
            $hasArchive = true;
        }

        $legacyUnread = function () {
            try { return ContactMessage::unreadCount(); } catch (\Throwable) { return 0; }
        };

        if (! $form) {
            return $this->item('contact', 'Wiadomości z formularza', 'admin.wiadomosci-kontaktowe.index', 'fa-envelope-open',
                active: 'admin.wiadomosci-kontaktowe.*', badge: $legacyUnread);
        }

        return $this->item('contact', 'Wiadomości z formularza', 'admin.formularze.zgloszenia', 'fa-envelope-open',
            active: fn () => request()->routeIs('admin.wiadomosci-kontaktowe.*')
                || (request()->routeIs('admin.formularze.zgloszenia*') && (int) (request()->route('formularz')?->id ?? request()->route('formularz')) === $form->id),
            badge: fn () => $form->unreadSubmissions()->count() + $legacyUnread(),
            children: $hasArchive ? [
                $this->child('Nowe wiadomości', 'admin.formularze.zgloszenia', fn () => request()->routeIs('admin.formularze.zgloszenia*'), params: ['formularz' => $form->id]),
                $this->child('Archiwum (dawny formularz)', 'admin.wiadomosci-kontaktowe.index', 'admin.wiadomosci-kontaktowe.*'),
            ] : [],
            params: ['formularz' => $form->id],
        );
    }

    private function bipEnabled(): bool
    {
        return $this->modules->get('bip') !== null ? $this->modules->isActive('bip') : $this->site->isModuleEnabled('bip');
    }

    private function can(string $module): bool
    {
        $enabled = $this->modules->get($module) !== null
            ? $this->modules->isActive($module)
            : $this->site->isModuleEnabled($module);

        return $enabled && $this->user->canAccessModule($module);
    }
}
