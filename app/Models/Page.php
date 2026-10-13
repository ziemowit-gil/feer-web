<?php

namespace App\Models;

use App\Models\Concerns\Approvable;
use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\LogsActivity;
use App\Models\SiteSetting;
use App\PageTypes\PageTypeRegistry;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Page extends Model
{
    use Approvable;
    use \App\Models\Concerns\RedirectsOldSlug;
    use BelongsToSite;
    use \App\Models\Concerns\ScopedByEditor;
    use \App\Models\Concerns\HasEtr;
    use \App\Models\Concerns\HasPreviewLink;
    use \App\Models\Concerns\HasRevisions;
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use LogsActivity;
    use Searchable;

    public function toSearchableArray(): array
    {
        return [
            'title'   => $this->title,
            'content' => strip_tags((string) $this->content),
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return (bool) $this->is_published && ! $this->trashed();
    }

    public function revisionFields(): array
    {
        return ['title', 'slug', 'content', 'meta_title', 'meta_description'];
    }

    protected function previewRouteName(): string
    {
        return 'page.show';
    }

    protected function previewRouteParam(): string
    {
        return 'page';
    }

    /**
     * Top-level URL segments already used by other routes. Pages render at
     * the site root (e.g. /fundacja), so a page can't take one of these
     * slugs without shadowing — or being shadowed by — the real route.
     */
    public const RESERVED_SLUGS = [
        'strona', 'dzialania', 'projekty', 'aktualnosci', 'newsletter', 'wsparcie', 'materialy', 'kontakt',
        'bip', 'instagram', 'fb', 'facebook', 'li', 'linkedin',
        // Blog Wiem FEER (witryna sieci) i jego stare adresy przekierowujące.
        'wiem', 'blog', 'wiem-feer',
        'dashboard', 'profile', 'admin', 'login', 'logout', 'ankieta', 'strefa',
        'forgot-password', 'reset-password', 'verify-email', 'confirm-password',
    ];

    /** Visual frontend templates selectable per page (apply to standard/content pages). */
    public const TEMPLATES = [
        'default' => 'Domyślny (tytuł + treść + boczna nawigacja)',
        'wide'    => 'Szeroki (pełna szerokość, bez bocznej nawigacji)',
        'hero'    => 'Z hero (kolorowy baner z tytułem nad treścią)',
        'landing' => 'Landing page (duży hero, bez okruszków nawigacyjnych)',
        'portal'  => 'Portal (hero ze zdjęciem, treść + sidebar, galeria, kontakt)',
        'section' => 'Strona działu (slider ze zdjęć strony, aktualności, nawigacja po podstronach w prawej kolumnie)',
        'minimal' => 'Minimalna (bez nagłówka i stopki serwisu)',
    ];

    /**
     * Sposób prezentacji podstron działu (pole `side_nav_style`, ustawiane na
     * stronie nadrzędnej działu): boczna lista rodzeństwa, poziome zakładki
     * nad treścią albo pełne, wielopoziomowe drzewo działu w lewej kolumnie
     * (układ znany z serwisów TYPO3: ścieżka strony + rozwijane gałęzie).
     */
    public const SIDE_NAV_STYLES = [
        'sidebar' => 'Boczne drzewo',
        'accordion' => 'Podstrony jako akordeon (rozwijane sekcje)',
        'panel'   => 'Panel boczny (styl działań)',
        'tabs'    => 'Zakładki nad treścią',
        'tree'    => 'Drzewo działu (styl TYPO3)',
        'tiles'   => 'Nawigacja kafelkowa (podstrony jako kafelki)',
    ];

    /** Maksymalna głębokość drzewa działu renderowanego w nawigacji „tree". */
    public const TREE_NAV_MAX_DEPTH = 5;

    /**
     * Typy stron — każdy typ to klasa w app/PageTypes/Types (patrz PageType:
     * „Jak dodać nowy typ strony"). Poniższe metody zastępują dawne stałe
     * TYPES / TYPE_GROUPS / TYPE_ICONS / TYPE_DATA_TYPES i czytają z rejestru.
     *
     * @return array<string, string> klucz => etykieta
     */
    public static function types(): array
    {
        return PageTypeRegistry::labels();
    }

    /** Grupy typów stron — wspólne dla wyboru typu w edycji i filtra na liście. */
    public static function typeGroups(): array
    {
        return PageTypeRegistry::groups();
    }

    /** Ikony Font Awesome typów stron (karty podstron, kafelki działu, drzewo stron). */
    public static function typeIcons(): array
    {
        return PageTypeRegistry::icons();
    }

    /** Typy, których dane trzymamy we wspólnej kolumnie JSON `type_data`. */
    public static function typeDataTypes(): array
    {
        return PageTypeRegistry::typeDataTypes();
    }

    /**
     * Statusy zapytania ofertowego. „auto": do terminu składania ofert „Przyjmowanie
     * ofert", po terminie „Ocena ofert", po wpisaniu wyniku „Rozstrzygnięte".
     */
    public const TENDER_STATUSES = [
        'auto'       => 'Automatycznie z terminu i wyniku',
        'open'       => 'Przyjmowanie ofert',
        'evaluation' => 'Ocena ofert',
        'awarded'    => 'Rozstrzygnięte',
        'cancelled'  => 'Unieważnione',
    ];

    /**
     * Statusy naboru (typ „Rekrutacja do projektu"). „auto" wylicza status z dat
     * naboru; pozostałe to ręczne ustawienie (np. wstrzymanie albo lista rezerwowa).
     */
    public const RECRUITMENT_STATUSES = [
        'auto'       => 'Automatycznie z dat naboru',
        'planned'    => 'Nabór wkrótce',
        'open'       => 'Nabór trwa',
        'continuous' => 'Nabór ciągły',
        'reserve'    => 'Lista rezerwowa',
        'suspended'  => 'Nabór wstrzymany',
        'closed'     => 'Nabór zakończony',
    ];

    /** Poziomy trudności poradnika. */
    public const GUIDE_LEVELS = [
        'basic'    => 'Podstawowy',
        'medium'   => 'Średni',
        'advanced' => 'Zaawansowany',
    ];

    /** Tryby dostępu do strony wewnętrznej. */
    public const ACCESS_MODES = [
        'password' => 'Hasło',
        'microsoft' => 'Zalogowanie do strefy wewnętrznej (Microsoft 365)',
    ];

    /** Poziomy dostępu strony publicznej (poza typami wewnętrznymi, które mają własny tryb). */
    public const ACCESS_LEVELS = [
        'inherit'  => 'Jak strona nadrzędna (domyślnie)',
        'public'   => 'Publiczna — widoczna dla każdego',
        'password' => 'Chroniona hasłem',
        'member'   => 'Zalogowani — strefa współpracownika (Microsoft 365)',
        'panel'    => 'Zalogowani użytkownicy panelu (dowolna rola)',
        'groups'   => 'Wybrane grupy użytkowników panelu',
    ];

    /** Slug automatycznie zakładanej strony „Strefa współpracownika" (/strefa-wspolpracownika-feer). */
    public const STREFA_SLUG = 'strefa-wspolpracownika-feer';

    /** Domyślna treść strefy, gdy nadpisywana strona nie ma własnej treści. */
    public const STREFA_DEFAULT_CONTENT = '<p>Witamy w strefie współpracownika. Poniżej znajdziesz wewnętrzne '
        .'komunikaty i materiały dostępne tylko dla zalogowanych osób.</p>';

    /**
     * Kanoniczne atrybuty strony „Strefa współpracownika": wewnętrzny panel
     * (internal_hub — hero, odnośniki, komunikaty SZO) z logowaniem MS365,
     * systemowa (chroniona przed usunięciem), poza menu. Wspólne dla
     * automatycznego zakładania i nadpisywania przez administratora.
     *
     * @return array<string, mixed>
     */
    public static function strefaAttributes(): array
    {
        return [
            'title' => 'Strefa współpracownika',
            'type' => 'internal_hub',
            'access_mode' => 'microsoft',
            'is_published' => true,
            'is_system' => true,
            'show_in_menu' => false,
            'meta_title' => 'Strefa współpracownika',
        ];
    }

    /** Czy ta strona jest prawidłową strefą współpracownika (panel wewnętrzny + MS365). */
    public function isStrefaZone(): bool
    {
        return $this->slug === self::STREFA_SLUG
            && $this->type === 'internal_hub'
            && $this->access_mode === 'microsoft';
    }

    /**
     * Strona zajmująca adres /strefa w sposób kolidujący ze strefą współpracownika
     * (istnieje, ale nie jest stroną wewnętrzną z logowaniem MS365), albo null gdy
     * konfliktu nie ma. Podstawa komunikatu „potwierdź i nadpisz" w panelu.
     */
    public static function strefaSlugConflict(): ?self
    {
        $page = static::query()->where('slug', self::STREFA_SLUG)->first();

        return ($page && ! $page->isStrefaZone()) ? $page : null;
    }

    /** How an event is held. */
    public const EVENT_MODES = [
        'onsite' => 'Stacjonarne',
        'online' => 'Webinar',
    ];

    /** Reorderable sections of an "about the organisation" page (the hero always comes first). */
    /** Nagłówki i napisy strony „O organizacji" (klucz => [etykieta w panelu, wartość domyślna]) — każdy do zmiany w edycji strony. */
    public const ABOUT_LABELS = [
        'nav_intro' => ['Menu sekcji: wstęp', 'O nas'],
        'nav_founder' => ['Menu sekcji: słowo od Fundatora', 'Od Fundatora'],
        'nav_stats' => ['Menu sekcji: statystyki', 'W liczbach'],
        'nav_values' => ['Menu sekcji: wartości', 'Wartości'],
        'nav_timeline' => ['Menu sekcji: historia', 'Historia'],
        'nav_team' => ['Menu sekcji: zespół', 'Zespół'],
        'nav_gallery' => ['Menu sekcji: galeria', 'Galeria'],
        'nav_partners' => ['Menu sekcji: partnerzy', 'Partnerzy'],
        'nav_press' => ['Menu sekcji: media', 'Media'],
        'nav_documents' => ['Menu sekcji: dokumenty', 'Dokumenty'],
        'nav_faq' => ['Menu sekcji: FAQ', 'FAQ'],
        'nav_help' => ['Menu sekcji: jak pomóc', 'Jak pomóc'],
        'mission_support' => ['Misja — przycisk wsparcia', 'Wesprzyj nas'],
        'mission_contact' => ['Misja — przycisk kontaktu', 'Kontakt'],
        'stats_asof' => ['Statystyki — napis przed datą', 'Stan na'],
        'timeline_all' => ['Historia — przycisk rozwinięcia', 'Cała historia'],
        'timeline_less' => ['Historia — przycisk zwinięcia', 'Pokaż mniej'],
        'team_join' => ['Zespół — kafel „Dołącz”', 'Dołącz do zespołu'],
        'team_join_text' => ['Zespół — opis kafla „Dołącz”', 'Szukamy osób, które chcą zmieniać świat razem z nami.'],
        'registry_title' => ['Dokumenty — nagłówek danych rejestrowych', 'Dane organizacji'],
        'help_title' => ['Jak możesz pomóc — nagłówek', 'Jak możesz pomóc'],
        'help_text' => ['Jak możesz pomóc — zdanie pod nagłówkiem', 'Każda forma wsparcia ma znaczenie. Wybierz tę, która pasuje do Ciebie.'],
        'founder_kicker' => ['Słowo od Fundatora — napis nad imieniem', 'Słowo od Fundatora'],
        'stats_title' => ['Statystyki — nagłówek', 'W liczbach'],
        'gallery_title' => ['Galeria — nagłówek', 'Galeria'],
        'values_title' => ['Wartości — nagłówek', 'Nasze wartości'],
        'timeline_title' => ['Historia — nagłówek', 'Nasza historia'],
        'timeline_more' => ['Historia — napis linku bez etykiety', 'Zobacz więcej'],
        'team_title' => ['Zespół — nagłówek', 'Nasz zespół'],
        'partners_title' => ['Partnerzy — nagłówek', 'Nasi partnerzy — wspierają nas'],
        'partners_text' => ['Partnerzy — zdanie pod nagłówkiem', 'Dziękujemy organizacjom i instytucjom, które nas wspierają.'],
        'documents_title' => ['Dokumenty — nagłówek', 'Dokumenty i sprawozdania'],
        'documents_bip' => ['Dokumenty — zdanie przed linkiem do BIP', 'Pozostałe dokumenty publikujemy w'],
        'documents_download' => ['Dokumenty — napis przycisku pobierania', 'Pobierz'],
        'documents_more' => ['Dokumenty — napis „Zobacz wszystkie”', 'Zobacz wszystkie'],
        'press_title' => ['Media — nagłówek', 'My w mediach'],
        'press_read' => ['Media — napis „Czytaj”', 'Czytaj'],
        'faq_title' => ['FAQ — nagłówek', 'Masz pytania?'],
        'faq_text' => ['FAQ — zdanie pod nagłówkiem', 'Odpowiedzi na najczęstsze pytania zebraliśmy w jednym miejscu.'],
        'faq_button' => ['FAQ — napis przycisku', 'Najczęściej zadawane pytania'],
    ];

    /** Nagłówek lub napis sekcji „O organizacji": własny (jeśli wpisany) albo domyślny. */
    public function aboutLabel(string $key): string
    {
        $own = is_array($this->about_labels) ? ($this->about_labels[$key] ?? null) : null;

        return is_string($own) && trim($own) !== '' ? $own : (self::ABOUT_LABELS[$key][1] ?? '');
    }

    public const ABOUT_SECTIONS = [
        'intro'     => 'Wstęp i zdjęcia',
        'founder'   => 'Słowo od Fundatora',
        'stats'     => 'Statystyki',
        'values'    => 'Wartości',
        'timeline'  => 'Oś czasu',
        'team'      => 'Zespół',
        'gallery'   => 'Galeria',
        'partners'  => 'Nasi partnerzy',
        'press'     => 'My w mediach',
        'documents' => 'Dokumenty i sprawozdania',
        'faq'       => 'Odnośnik do FAQ',
        'help'      => 'Jak możesz pomóc',
    ];

    /** How a page attached to a project is surfaced on that project's page. */
    public const PROJECT_DISPLAYS = [
        'link' => 'Tylko odnośnik (na liście stron działania)',
        'tab' => 'Zakładka na stronie działania',
        'inline' => 'Sekcja w treści strony działania',
        'accordion' => 'Rozwijana sekcja (akordeon) w treści działania',
    ];

    /** "Under construction" modes — full-screen notice vs. an info banner over the content. */
    public const WIP_MODES = [
        'full' => 'Pełnoekranowy komunikat (ukrywa treść)',
        'notice' => 'Pasek informacyjny (treść pozostaje widoczna)',
    ];

    /** Fallback messages used when the admin leaves the custom message empty. */
    public const DEFAULT_DISABLED_MESSAGE = 'Ta strona jest tymczasowo niedostępna. Zapraszamy wkrótce.';

    public const DEFAULT_WIP_FULL_MESSAGE = 'Ta strona jest w przygotowaniu. Pracujemy nad jej zawartością — zapraszamy wkrótce.';

    public const DEFAULT_WIP_NOTICE_MESSAGE = 'Wprowadzamy zmiany na tej stronie — nie wszystkie elementy mogą jeszcze działać poprawnie.';

    protected $fillable = [
        'unpublish_at', 'reviewed_at',
        'metka_author', 'published_by_id',
        'site_id', 'created_by', 'parent_id', 'project_id', 'project_display', 'title', 'slug', 'content', 'is_published', 'publish_at', 'is_featured', 'is_archived', 'show_in_menu', 'hide_in_sitemap', 'show_side_nav', 'side_nav_style', 'is_system', 'is_locked', 'order',
        'meta_title', 'meta_description', 'pending_approval', 'submitted_by_id',
        'is_disabled', 'disabled_message', 'wip_mode', 'wip_message',
        'type', 'event_mode', 'event_when', 'event_location', 'event_how_to_join', 'event_registration_url',
        'schedule_items', 'schedule_change_notice', 'schedule_pending',
        'about_motto', 'about_motto_author', 'about_intro', 'about_stats', 'about_timeline', 'about_values', 'about_team', 'about_section_order', 'about_sections_hidden', 'about_partner_ids', 'about_documents_intro', 'about_documents_bip_url', 'about_press_intro', 'about_press', 'about_faq_visible', 'about_labels',
        'faq_intro', 'faq_items', 'bip_move_url', 'bip_move_note', 'show_gallery',
        'training_manager_name', 'training_manager_title', 'training_ris_number', 'training_bur_number', 'training_extra_info', 'training_bur_note',
        'content_image', 'content_image_alt', 'content_image_width',
        'founder_image', 'founder_image_alt', 'founder_quote',
        'access_mode', 'access_password', 'access_level', 'access_group_ids', 'hub_hero', 'hub_intro', 'hub_links', 'hub_tiles_enabled', 'tiles', 'tiles_content_position', 'tiles_enabled',
        'legacy_name', 'legacy_intro',
        'brand_brandbook_url', 'brand_sections',
        'person_phone', 'person_role', 'person_bio', 'person_email', 'person_social', 'person_member_label', 'person_name_genitive', 'person_department',
        'page_template', 'layout_data',
        'cooperation_data', 'type_data', 'blocks',
    ];

    protected $casts = [
        'unpublish_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'is_published' => 'boolean',
        'publish_at' => 'datetime',
        'first_published_at' => 'datetime',
        'is_featured' => 'boolean',
        'is_archived' => 'boolean',
        'pending_approval' => 'boolean',
        'is_disabled' => 'boolean',
        'show_in_menu' => 'boolean',
        'hide_in_sitemap' => 'boolean',
        'show_side_nav' => 'boolean',
        'is_system' => 'boolean',
        'is_locked' => 'boolean',
        'show_gallery' => 'boolean',
        'schedule_items' => 'array',
        'schedule_pending' => 'boolean',
        'about_stats' => 'array',
        'about_timeline' => 'array',
        'about_values' => 'array',
        'about_team' => 'array',
        'about_faq_visible' => 'boolean',
        'hub_links' => 'array',
        'hub_tiles_enabled' => 'boolean',
        'tiles'     => 'array',
        'tiles_enabled' => 'boolean',
        'access_group_ids' => 'array',
        'about_section_order' => 'array',
        'about_sections_hidden' => 'array',
        'about_partner_ids' => 'array',
        'about_press' => 'array',
        'about_labels' => 'array',
        'faq_items' => 'array',
        'brand_sections' => 'array',
        'person_social'      => 'array',
        'person_department'  => 'array',
        'cooperation_data'   => 'array',
        'type_data'          => 'array',
        'blocks'             => 'array',
        'layout_data'        => 'array',
    ];

    /**
     * Treść zmieniona poza edytorem blokowym (formularz panelu, edycja na żywo,
     * przywrócenie wersji) unieważnia zapisane bloki — inaczej edytor blokowy
     * pokazałby starą wersję. Edytor wczyta wtedy aktualny HTML jako blok HTML.
     */
    protected static function booted(): void
    {
        static::saving(function (Page $page) {
            if ($page->exists && $page->isDirty('content') && ! $page->isDirty('blocks') && $page->blocks !== null) {
                $page->blocks = null;
            }

            // Metryczka: kto i kiedy pierwszy raz opublikował stronę — zapisujemy sami, bez edycji w formularzu.
            if ($page->is_published && (! $page->exists || $page->isDirty('is_published') || ! $page->published_by_id)
                && once(fn () => \Illuminate\Support\Facades\Schema::hasColumn('pages', 'published_by_id'))) {
                $page->first_published_at ??= now();
                $page->published_by_id ??= auth()->id() ?? $page->created_by;
            }
        });
    }

    public function publishedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_id');
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Czy pokazać metryczkę: ustawienie strony (layout_data.metka: show/hide) ma pierwszeństwo,
     * potem typy stron i cały serwis (Ustawienia → Treści → Metryczka).
     */
    public function showsMetka(): bool
    {
        $own = ($this->layout_data ?? [])['metka'] ?? 'inherit';
        if (in_array($own, ['show', 'hide'], true)) {
            return $own === 'show';
        }
        $settings = SiteSetting::current();

        return match ($settings->metka_mode ?? 'off') {
            'all' => true,
            'types' => in_array($this->type ?? 'standard', (array) ($settings->metka_types ?? []), true),
            default => false,
        };
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return parent::resolveRouteBindingQuery($query, $value, $field)->forCurrentSite();
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        $resolveField = $field ?? $this->getRouteKeyName();
        $settings = SiteSetting::current();

        if ($resolveField !== 'slug' || ! $settings->cacheEnabled('pages')) {
            return parent::resolveRouteBinding($value, $field);
        }

        $ttl = $settings->cacheTtl('page_item', 3600);
        $cacheKey = "page_item_{$settings->id}_{$value}";

        try {
            $cached = Cache::get($cacheKey);

            if ($cached !== null) {
                if ($cached instanceof self) {
                    return $cached;
                }
                // Stale/corrupted serialized class — drop and re-fetch from DB.
                Cache::forget($cacheKey);
            }
        } catch (\Throwable) {
            Cache::forget($cacheKey);
        }

        $page = parent::resolveRouteBinding($value, $field);

        if ($page !== null) {
            Cache::put($cacheKey, $page, $ttl);
        }

        return $page;
    }

    public function isEvent(): bool
    {
        return $this->type === 'event';
    }

    public function isSchedule(): bool
    {
        return $this->type === 'schedule';
    }

    public function isAbout(): bool
    {
        return $this->type === 'about';
    }

    public function isFaq(): bool
    {
        return $this->type === 'faq';
    }

    public function isBipMove(): bool
    {
        return $this->type === 'bip_move';
    }

    public function isTrainingInstitution(): bool
    {
        return $this->type === 'training_institution';
    }

    public function isInternal(): bool
    {
        return $this->type === 'internal';
    }

    public function isInternalHub(): bool
    {
        return $this->type === 'internal_hub';
    }

    public function isLinksHub(): bool
    {
        return $this->type === 'links_hub';
    }

    /** Czy strona pokazuje kafelki: „Siatka kafelków" zawsze, pozostałe typy tylko po włączeniu „Kafelki na stronie". */
    public function showsTiles(): bool
    {
        return $this->isTilesGrid() || (bool) $this->tiles_enabled;
    }

    public function isTilesGrid(): bool
    {
        return $this->type === 'tiles_grid';
    }

    public function isCooperation(): bool
    {
        return $this->type === 'wspolpraca';
    }

    public function isLegacy(): bool
    {
        return $this->type === 'legacy';
    }

    public function isBrandAssets(): bool
    {
        return $this->type === 'brand_assets';
    }

    public function isAboutPerson(): bool
    {
        return $this->type === 'about_person';
    }

    public function isService(): bool
    {
        return $this->type === 'service';
    }

    public function isGuide(): bool
    {
        return $this->type === 'guide';
    }

    public function isGlossary(): bool
    {
        return $this->type === 'glossary';
    }

    public function isPricing(): bool
    {
        return $this->type === 'pricing';
    }

    public function isTeam(): bool
    {
        return $this->type === 'team';
    }

    public function isDocuments(): bool
    {
        return $this->type === 'documents';
    }

    /**
     * Ścieżka okruszków z menu: gdy strona nie ma stron nadrzędnych w drzewie, ale w menu głównym jest podpięta pod
     * grupę (np. „Fundacja" → „O nas"), zwraca nazwy grup nadrzędnych (od najwyższej), żeby okruszki odzwierciedlały strukturę menu.
     *
     * @return array<int, array{label: string, url: ?string}>
     */
    public function menuTrail(): array
    {
        $path = fn (?string $u) => trim((string) parse_url((string) $u, PHP_URL_PATH), '/');
        $mine = $path($this->publicUrl());

        $item = NavItem::where('location', 'main')->where('is_active', true)->whereNotNull('parent_id')->get()
            ->first(fn ($n) => $path($n->url) === $mine && $mine !== '');
        if (! $item) {
            return [];
        }

        $trail = [];
        $guard = 0;
        for ($parent = $item->parent; $parent && $guard < 5; $parent = $parent->parent, $guard++) {
            $url = trim((string) $parent->url);
            array_unshift($trail, ['label' => $parent->label, 'url' => ($url === '' || $url === '#') ? null : $url]);
        }

        return $trail;
    }

    public function isPartners(): bool
    {
        return $this->type === 'partners';
    }

    public function isReports(): bool
    {
        return $this->type === 'reports';
    }

    public function isRedirect(): bool
    {
        return $this->type === 'redirect';
    }

    /** Adres docelowy strony typu „Przekierowanie" (ścieżka lub http/https) albo null, gdy niepoprawny. */
    public function redirectTarget(): ?string
    {
        $url = trim((string) ($this->typeData()['target_url'] ?? ''));

        return preg_match('~^(https?://|/(?!/))~i', $url) ? $url : null;
    }

    public function isMenuLink(): bool
    {
        return $this->type === 'menu_link';
    }

    public function isMenuHeading(): bool
    {
        return $this->type === 'menu_heading';
    }

    /**
     * Adres pozycji menu: „Własny link w menu” i „Przekierowanie” prowadzą od razu pod swój
     * adres docelowy (w menu bocznym, drzewie działu, menu rozwijanym i zakładkach działania),
     * reszta — na stronę.
     */
    public function navUrl(): string
    {
        if (($this->isMenuLink() || $this->isRedirect()) && ($target = $this->redirectTarget())) {
            return $target;
        }

        return $this->publicUrl();
    }

    /** Atrybuty linku w menu (nowa karta dla linku/przekierowania z zaznaczoną opcją). */
    public function navLinkAttributes(): string
    {
        return ($this->isMenuLink() || $this->isRedirect()) && ! empty($this->typeData()['new_tab']) && $this->redirectTarget()
            ? 'target="_blank" rel="noopener"'
            : '';
    }

    /** Tekst dla czytników przy linku otwieranym w nowej karcie (pusty, gdy nie dotyczy). */
    public function navNewTabNotice(): string
    {
        return $this->navLinkAttributes() !== '' ? '<span class="sr-only"> (otwiera się w nowej karcie)</span>' : '';
    }

    /**
     * Etykieta sposobu pokazania podstrony na stronie działania (panel): przekierowanie,
     * własny link i nagłówek wyróżnione osobno — to nie zakładki, tylko linki w menu.
     *
     * @return array{label: string, link: bool, target: ?string}
     */
    public function projectDisplayInfo(): array
    {
        if ($this->isMenuHeading()) {
            return ['label' => 'Nagłówek w menu', 'link' => true, 'target' => null];
        }

        if ($this->navUrl() !== $this->publicUrl()) {
            return ['label' => $this->isRedirect() ? 'Przekierowanie' : 'Link', 'link' => true, 'target' => $this->redirectTarget()];
        }

        $labels = ['tab' => 'Zakładka', 'inline' => 'Sekcja w treści', 'accordion' => 'Akordeon', 'link' => 'Odnośnik'];

        return ['label' => $labels[$this->project_display ?? 'link'] ?? 'Odnośnik', 'link' => false, 'target' => null];
    }

    public function isRegulation(): bool
    {
        return $this->type === 'regulation';
    }

    public function isCaseStudy(): bool
    {
        return $this->type === 'case_study';
    }

    public function isRecruitment(): bool
    {
        return $this->type === 'recruitment';
    }

    public function isTender(): bool
    {
        return $this->type === 'tender';
    }

    /** Termin składania ofert (type_data.tender_deadline); sama data obowiązuje do końca dnia. */
    public function tenderDeadline(): ?\Illuminate\Support\Carbon
    {
        $raw = trim((string) ($this->typeData()['tender_deadline'] ?? ''));
        if ($raw === '') {
            return null;
        }
        try {
            $date = \Illuminate\Support\Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }

        return str_contains($raw, 'T') || str_contains($raw, ':') ? $date : $date->endOfDay();
    }

    /** Bieżący status zapytania: ręczny z panelu albo wyliczony z terminu i wyniku. */
    public function tenderStatus(): string
    {
        $td = $this->typeData();
        $manual = $td['tender_status'] ?? 'auto';
        if ($manual !== 'auto' && array_key_exists($manual, self::TENDER_STATUSES)) {
            return $manual;
        }
        if (filled($td['tender_result'] ?? null)) {
            return 'awarded';
        }
        $deadline = $this->tenderDeadline();

        return $deadline && $deadline->isPast() ? 'evaluation' : 'open';
    }

    /** Początek / koniec naboru (pola rec_starts_at / rec_ends_at w type_data) albo null. */
    public function recruitmentDate(string $which): ?\Illuminate\Support\Carbon
    {
        $raw = trim((string) ($this->typeData()[$which === 'start' ? 'rec_starts_at' : 'rec_ends_at'] ?? ''));
        if ($raw === '') {
            return null;
        }
        try {
            $date = \Illuminate\Support\Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
        // Sama data końca (bez godziny) obowiązuje do końca dnia.
        return $which === 'end' && ! str_contains($raw, 'T') && ! str_contains($raw, ':') ? $date->endOfDay() : $date;
    }

    /**
     * Bieżący status naboru: ręczny z panelu albo — w trybie „auto" — wyliczony
     * z dat: przed startem „wkrótce", w terminie „trwa", po końcu „zakończony",
     * bez dat „ciągły".
     */
    public function recruitmentStatus(): string
    {
        $manual = $this->typeData()['rec_status'] ?? 'auto';
        if ($manual !== 'auto' && array_key_exists($manual, self::RECRUITMENT_STATUSES)) {
            return $manual;
        }

        $start = $this->recruitmentDate('start');
        $end = $this->recruitmentDate('end');
        if ($start && $start->isFuture()) {
            return 'planned';
        }
        if ($end && $end->isPast()) {
            return 'closed';
        }

        return $start || $end ? 'open' : 'continuous';
    }

    /** Czy przyjmujemy zgłoszenia (przycisk/formularz zgłoszenia widoczny). */
    public function recruitmentAcceptsApplications(): bool
    {
        return in_array($this->recruitmentStatus(), ['open', 'continuous', 'reserve'], true);
    }

    /** Statusy „aktualne" na listach [nabory] / [zapytania-ofertowe] i stronie głównej. */
    public const LISTING_ACTIVE_STATUSES = [
        'recruitment' => ['planned', 'open', 'continuous', 'reserve'],
        'tender'      => ['open', 'evaluation'],
    ];

    /** Statusy „zakończone" (archiwum) na listach naborów i zapytań ofertowych. */
    public const LISTING_ARCHIVE_STATUSES = [
        'recruitment' => ['closed'],
        'tender'      => ['awarded', 'cancelled'],
    ];

    /** Strony opublikowane i już widoczne (bez zaplanowanej publikacji w przyszłości). */
    public function scopeLive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where($this->getTable().'.is_published', true)
            ->where(fn ($q) => $q->whereNull($this->getTable().'.publish_at')->orWhere($this->getTable().'.publish_at', '<=', now()));
    }

    /** Strony danego typu (np. 'recruitment', 'tender'). */
    public function scopeOfType(\Illuminate\Database\Eloquent\Builder $query, string $type): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where($this->getTable().'.type', $type);
    }

    /** Status naboru albo zapytania ofertowego (null dla innych typów). */
    public function listingStatus(): ?string
    {
        return match ($this->type) {
            'recruitment' => $this->recruitmentStatus(),
            'tender' => $this->tenderStatus(),
            default => null,
        };
    }

    /** Termin, wg którego sortujemy listy: koniec naboru albo termin składania ofert. */
    public function listingDeadline(): ?\Illuminate\Support\Carbon
    {
        return match ($this->type) {
            'recruitment' => $this->recruitmentDate('end'),
            'tender' => $this->tenderDeadline(),
            default => null,
        };
    }

    /**
     * Dane typu (kolumna `type_data`) z domyślnymi pustymi listami, żeby widoki
     * i formularz nie musiały sprawdzać istnienia każdego klucza.
     */
    public function typeData(): array
    {
        $data = is_array($this->type_data) ? $this->type_data : [];

        foreach (PageTypeRegistry::listKeys() as $list) {
            $data[$list] = array_values(array_filter(
                is_array($data[$list] ?? null) ? $data[$list] : [],
                fn ($row) => is_array($row) && array_filter($row, fn ($v) => is_string($v) && trim($v) !== ''),
            ));
        }

        return $data;
    }

    /** Czy strona używa standardowej sekcji treści (a nie własnego układu typowego). */
    public function usesStandardLayout(): bool
    {
        // Typy spoza rejestru (np. stara wartość w bazie) — jak strona standardowa.
        return PageTypeRegistry::get($this->type)?->standardLayout() ?? true;
    }

    /** Kanoniczny publiczny URL strony (uwzględnia wielosegmentowy slug dla stron osoby). */
    public function publicUrl(): string
    {
        return $this->isAboutPerson()
            ? url('/' . $this->slug)
            : route('page.show', $this);
    }

    /**
     * Strona, od której pochodzi poziom dostępu: ta lub najbliższy przodek z poziomem innym niż „jak nadrzędna".
     * Zwraca null, gdy cały łańcuch jest publiczny.
     */
    public function accessSource(): ?Page
    {
        $node = $this;
        for ($i = 0; $node && $i < 20; $i++, $node = $node->parent) {
            $level = $node->access_level;
            if ($level && $level !== 'inherit') {
                return $level === 'public' ? null : $node;
            }
        }

        return null;
    }

    /** Efektywny poziom dostępu: public | password | member | panel | groups. */
    public function effectiveAccessLevel(): string
    {
        return $this->accessSource()?->access_level ?? 'public';
    }

    /** Czy odwiedzający musi przejść kontrolę dostępu (typ wewnętrzny albo poziom inny niż publiczny). */
    public function requiresAccess(): bool
    {
        return $this->isAccessRestricted() || $this->effectiveAccessLevel() !== 'public';
    }

    /** Poziom dostępu oparty na ustawieniu „Dostęp do strony" (nie dotyczy typów wewnętrznych). */
    private function levelAccessGranted(): bool
    {
        $source = $this->accessSource();
        if (! $source) {
            return true;
        }

        $user = auth('web')->user();
        if ($user?->isAdmin()) {
            return true;
        }

        return match ($source->access_level) {
            'password' => blank($source->access_password) || in_array($source->id, session('unlocked_pages', []), true),
            'member' => auth('member')->check() || (bool) $user,
            'panel' => (bool) $user,
            'groups' => $user && $user->user_group_id && in_array((int) $user->user_group_id, array_map('intval', (array) $source->access_group_ids), true),
            default => true,
        };
    }

    /** Czy strona jest chroniona dostępem (zwykła wewnętrzna, panel współpracownika lub marka). */
    public function isAccessRestricted(): bool
    {
        return in_array($this->type, ['internal', 'internal_hub', 'brand_assets'], true);
    }

    /**
     * Czy dostęp do tej strony wewnętrznej jest już przyznany bieżącemu
     * odwiedzającemu.
     */
    public function accessGranted(): bool
    {
        if (! $this->isAccessRestricted()) {
            return $this->levelAccessGranted();
        }

        // Indywidualny login+hasło dla strony z zasobami marki.
        if ($this->type === 'brand_assets') {
            return filled(session("brand_access_{$this->id}"));
        }

        if ($this->access_mode === 'microsoft') {
            return auth('member')->check();
        }

        // Tryb hasła: brak ustawionego hasła = brak blokady (nie zamykamy przez pomyłkę).
        if (blank($this->access_password)) {
            return true;
        }

        return in_array($this->id, session('unlocked_pages', []), true);
    }

    /**
     * The "about" section keys in the order they should render, falling back to
     * the default order and silently dropping any saved key that no longer
     * exists (so a code change can never break the page).
     */
    public function orderedAboutSections(bool $onlyEnabled = false): array
    {
        $defined = array_keys(self::ABOUT_SECTIONS);
        $saved = array_values(array_intersect($this->about_section_order ?? [], $defined));
        $ordered = array_values(array_unique(array_merge($saved, $defined)));

        return $onlyEnabled ? array_values(array_diff($ordered, $this->about_sections_hidden ?? [])) : $ordered;
    }

    /** Czy sekcja strony „O organizacji" jest włączona (nie została wyłączona w panelu). */
    public function isAboutSectionEnabled(string $key): bool
    {
        return ! in_array($key, $this->about_sections_hidden ?? [], true);
    }

    /** Selected partners for the "about" page's "Nasi partnerzy" section, kept in the chosen order. */
    public function aboutPartners()
    {
        $ids = array_values(array_filter((array) ($this->about_partner_ids ?? [])));

        if (empty($ids)) {
            return collect();
        }

        return Partner::whereIn('id', $ids)->get()
            ->sortBy(fn ($partner) => array_search($partner->id, $ids))
            ->values();
    }

    public function eventModeLabel(): ?string
    {
        return self::EVENT_MODES[$this->event_mode] ?? null;
    }

    /** The page is turned off and should show the "unavailable" message. */
    /**
     * Strona jest niedostępna, gdy wyłączono ją samą albo którąkolwiek stronę
     * nadrzędną — wyłączenie działu wyłącza wszystkie jego podstrony.
     */
    public function isDisabled(): bool
    {
        return (bool) $this->is_disabled || $this->disabledAncestor() !== null;
    }

    /** Najbliższa wyłączona strona nadrzędna (null, gdy żadna przodek nie jest wyłączony). */
    public function disabledAncestor(): ?self
    {
        return $this->ancestors()->reverse()->first(fn (Page $a) => (bool) $a->is_disabled);
    }

    /** Wyłączona wyłącznie dlatego, że wyłączono stronę nadrzędną (sama nie ma flagi). */
    public function isDisabledByAncestor(): bool
    {
        return ! $this->is_disabled && $this->disabledAncestor() !== null;
    }

    /**
     * Mapa stron niedostępnych przez wyłączonego przodka: [id strony => id najbliższego
     * wyłączonego przodka]. Jedno zapytanie na całe drzewo — dla widoków zbiorczych
     * (drzewo w panelu, wyszukiwarka), gdzie isDisabled() na każdej stronie byłoby kosztowne.
     *
     * @param  \Illuminate\Support\Collection<int, Page>|null  $pages  gotowa kolekcja stron (id, parent_id, is_disabled)
     * @return array<int, int>
     */
    public static function inheritedDisabledMap(?Collection $pages = null): array
    {
        $pages ??= static::query()->get(['id', 'parent_id', 'is_disabled']);
        $byId = $pages->keyBy('id');
        $map = [];

        foreach ($byId as $id => $page) {
            $seen = [$id];
            for ($node = $page; $node->parent_id && ($parent = $byId->get($node->parent_id)) && ! in_array($parent->id, $seen, true); $node = $parent) {
                $seen[] = $parent->id;
                if ($parent->is_disabled) {
                    $map[$id] = $parent->id;
                    break;
                }
            }
        }

        return $map;
    }

    /** The page is in any "under construction" mode. */
    public function isWip(): bool
    {
        return array_key_exists((string) $this->wip_mode, self::WIP_MODES);
    }

    /** WIP as a full-screen notice that hides the content. */
    public function wipIsFull(): bool
    {
        return $this->wip_mode === 'full';
    }

    /** WIP as an info banner shown above the (still visible) content. */
    public function wipIsNotice(): bool
    {
        return $this->wip_mode === 'notice';
    }

    /**
     * True when nothing but a stand-in message should be shown instead of the
     * page content — i.e. the page is disabled or in full-screen WIP mode.
     */
    public function showsPlaceholder(): bool
    {
        return $this->isDisabled() || $this->wipIsFull();
    }

    public function disabledMessage(): string
    {
        // Strona wyłączona przez przodka pokazuje komunikat tej strony nadrzędnej.
        $source = $this->is_disabled ? $this : ($this->disabledAncestor() ?? $this);

        return trim((string) $source->disabled_message) ?: self::DEFAULT_DISABLED_MESSAGE;
    }

    public function wipMessage(): string
    {
        $custom = trim((string) $this->wip_message);
        if ($custom !== '') {
            return $custom;
        }

        return $this->wipIsFull() ? self::DEFAULT_WIP_FULL_MESSAGE : self::DEFAULT_WIP_NOTICE_MESSAGE;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->orderBy('order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(PageImage::class)->orderBy('order');
    }

    public function brandAccessUsers(): HasMany
    {
        return $this->hasMany(BrandAccessUser::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Page::class, 'parent_id')->orderBy('order')->orderBy('title');
    }

    /**
     * Kafelki strony typu „kafelki — metro”: ręcznie dodane odnośniki (hub_links)
     * oraz — automatycznie — kafle opublikowanych podstron, których jeszcze
     * nie ma na liście ręcznej (porównanie po ścieżce adresu). Nowa podstrona
     * działu pojawia się więc sama, bez edycji kafelków.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function hubTiles(): Collection
    {
        // Opcja „Kafelki na stronie — włącz" (kolumna dochodzi z migracją; brak kolumny = włączone).
        if (array_key_exists('hub_tiles_enabled', $this->attributes) && ! $this->hub_tiles_enabled) {
            return collect();
        }

        $manual = collect($this->hub_links ?? [])
            ->filter(fn ($l) => is_array($l) && filled($l['label'] ?? null) && filled($l['url'] ?? null))
            ->values();

        $path = fn (string $url) => '/'.trim(strtolower((string) parse_url(trim($url), PHP_URL_PATH)), '/');
        $linked = $manual->map(fn ($l) => $path($l['url']))->all();

        $palette = ['blue', 'green', 'purple', 'orange', 'red', 'dark'];
        $offset = $manual->count();

        $auto = $this->publishedChildren->reject(fn (Page $c) => in_array($path($c->publicUrl()), $linked, true))
            ->values()
            ->map(function (Page $c, int $i) use ($palette, $offset) {
                $text = trim((string) $c->meta_description);
                if ($text === '') {
                    $text = Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags(str_replace('<', ' <', (string) $c->content)))), 120);
                }

                return [
                    'label' => $c->title,
                    'url' => $c->publicUrl(),
                    'description' => $text,
                    'icon' => 'fa-solid '.(self::typeIcons()[$c->type] ?? 'fa-file-lines'),
                    'color' => $palette[($offset + $i) % count($palette)],
                    'cta_label' => null,
                    'auto' => true,
                ];
            });

        return $manual->concat($auto)->values();
    }

    public function publishedChildren(): HasMany
    {
        return $this->children()->where('is_published', true)->where('type', '!=', 'about_person');
    }

    /**
     * Pages that belong together in the same local sub-menu: siblings under
     * the same parent, the published pages of the project it is attached to,
     * or (for a top-level page) its own published children.
     */
    /**
     * Sposób prezentacji podstron działu ('sidebar' | 'tabs'). O stylu decyduje
     * strona nadrzędna działu, żeby wszystkie podstrony wyglądały tak samo;
     * strona bez rodzica używa własnego ustawienia.
     */
    public function sideNavStyle(): string
    {
        $style = $this->parent_id ? $this->sectionRoot()->side_nav_style : $this->side_nav_style;

        // Strona „Dostępność" (dział podstron o dostępności) zawsze pokazuje podstrony jako kafelki.
        $root = $this->parent_id ? $this->sectionRoot() : $this;
        if ($root->slug === 'dostepnosc' && ! $root->parent_id) {
            return 'tiles';
        }

        return array_key_exists((string) $style, self::SIDE_NAV_STYLES) ? $style : 'sidebar';
    }

    /**
     * Strony nadrzędne od korzenia działu do bezpośredniego rodzica (rootline
     * w nomenklaturze TYPO3). Pusta kolekcja dla strony najwyższego poziomu.
     * Zabezpieczenie przed pętlą w `parent_id`: maksymalnie 10 poziomów.
     */
    /** Zakres redaktora: tylko strony (i podstrony) wskazane w jego grupie. */
    public function constrainForEditor($query, User $user): void
    {
        if (($ids = $user->allowedPageIds()) !== null) {
            $query->whereIn($this->getTable().'.id', $ids);
        }
    }

    public function ancestors(): \Illuminate\Support\Collection
    {
        $chain = collect();
        $node = $this;
        $seen = [$this->id];

        while ($node->parent_id && ($node = $node->parent) && ! in_array($node->id, $seen, true) && $chain->count() < 10) {
            $chain->prepend($node);
            $seen[] = $node->id;
        }

        return $chain;
    }

    /** Strona najwyższego poziomu w dziale, do którego należy ta strona (lub ona sama). */
    public function sectionRoot(): self
    {
        return $this->ancestors()->first() ?? $this;
    }

    public function menuSiblings()
    {
        if ($this->parent_id) {
            return $this->parent->children()
                ->where('is_published', true)
                ->where('type', '!=', 'about_person')
                ->get();
        }

        if ($this->project_id) {
            return $this->project->publishedPages()->get();
        }

        return $this->publishedChildren()->get();
    }

    /** Publiczny adres — cel przekierowania 301 ze starego adresu po zmianie sluga (RedirectsOldSlug). */
    public function redirectTargetUrl(): ?string
    {
        return $this->publicUrl();
    }
}
