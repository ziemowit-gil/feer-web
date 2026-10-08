<?php

namespace App\Models;

use App\Models\Concerns\Approvable;
use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\LogsActivity;
use App\Models\SiteSetting;
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
        'strona', 'projekty', 'aktualnosci', 'newsletter', 'wsparcie', 'materialy', 'kontakt',
        'bip', 'instagram', 'fb', 'facebook', 'li', 'linkedin',
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
        'panel'   => 'Panel boczny (styl projektów)',
        'tabs'    => 'Zakładki nad treścią',
        'tree'    => 'Drzewo działu (styl TYPO3)',
        'tiles'   => 'Nawigacja kafelkowa (podstrony jako kafelki)',
    ];

    /** Maksymalna głębokość drzewa działu renderowanego w nawigacji „tree". */
    public const TREE_NAV_MAX_DEPTH = 5;

    /** Page types: a plain content page, an event (webinar / on-site), a schedule listing, or an "about the organisation" page — each with its own layout. */
    public const TYPES = [
        'standard' => 'Standardowa',
        'event' => 'Wydarzenie',
        'schedule' => 'Harmonogram zajęć / spotkań',
        'about' => 'O organizacji',
        'faq' => 'FAQ (pytania i odpowiedzi)',
        'training_institution' => 'Instytucja szkoleniowa',
        'bip_move' => 'Przeniesiono do BIP',
        'internal' => 'Wewnętrzna (dostęp ograniczony)',
        'internal_hub' => 'Strefa współpracownika (wewnętrzny panel: komunikaty i odnośniki)',
        'links_hub' => 'Strona z kafelkami — metro (publiczne linki do działów)',
        'tiles_grid' => 'Siatka kafelków + treść (kafelki z ikonami, treść nad lub pod nimi, boczne menu)',
        'wspolpraca' => 'Współpraca z FEER (partnerstwo, sektory, formy, CTA)',
        'legacy' => 'Prezentacja tego, co było',
        'brand_assets'  => 'Marka — identyfikacja wizualna (pliki do pobrania)',
        'about_person'  => 'O organizacji — osoba',
        'contact'       => 'Kontakt (formularz, dane teleadresowe, spotkania)',
        'service'       => 'Oferta / usługa (korzyści, dla kogo, jak działamy, CTA)',
        'guide'         => 'Poradnik krok po kroku (numerowane kroki, wymagania, podsumowanie)',
        'glossary'      => 'Słownik pojęć (hasła z definicjami i indeksem liter)',
        'case_study'    => 'Studium przypadku (wyzwanie, rozwiązanie, efekty, cytat)',
        'pricing'       => 'Cennik i opłaty (karty cen, zasady płatności)',
        'team'          => 'Zespół (osoby z rolą i krótkim opisem)',
        'documents'     => 'Dokumenty (grupy odnośników do plików i stron)',
        'regulation'    => 'Regulamin lub dokument (data obowiązywania, spis treści)',
    ];

    /** Ikony Font Awesome typów stron (karty podstron, kafelki działu). */
    /** Grupy typów stron — wspólne dla wyboru typu w edycji i filtra na liście. */
    public const TYPE_GROUPS = [
        'Treść i informacja' => ['standard', 'faq', 'glossary', 'guide', 'documents', 'regulation'],
        'Oferta i edukacja' => ['service', 'pricing', 'case_study', 'training_institution'],
        'Organizacja' => ['about', 'about_person', 'team', 'wspolpraca', 'legacy', 'brand_assets'],
        'Kafelki i nawigacja' => ['links_hub', 'tiles_grid'],
        'Kontakt i wydarzenia' => ['contact', 'event', 'schedule'],
        'Wewnętrzne i przekierowania' => ['internal', 'internal_hub', 'bip_move'],
    ];

    public const TYPE_ICONS = [
        'pricing' => 'fa-coins', 'team' => 'fa-people-group', 'documents' => 'fa-folder-open', 'regulation' => 'fa-scale-balanced',
        'service' => 'fa-briefcase', 'guide' => 'fa-list-ol', 'glossary' => 'fa-book', 'case_study' => 'fa-chart-line',
        'faq' => 'fa-circle-question', 'event' => 'fa-calendar', 'schedule' => 'fa-calendar-days', 'links_hub' => 'fa-table-cells-large',
        'tiles_grid' => 'fa-table-cells', 'internal' => 'fa-lock', 'internal_hub' => 'fa-user-lock', 'bip_move' => 'fa-landmark',
        'about' => 'fa-building', 'contact' => 'fa-envelope', 'wspolpraca' => 'fa-handshake', 'brand_assets' => 'fa-palette', 'legacy' => 'fa-clock-rotate-left',
    ];

    /** Typy, których dane trzymamy we wspólnej kolumnie JSON `type_data`. */
    public const TYPE_DATA_TYPES = ['service', 'guide', 'glossary', 'case_study', 'pricing', 'team', 'documents', 'regulation'];

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
    ];

    /** How a page attached to a project is surfaced on that project's page. */
    public const PROJECT_DISPLAYS = [
        'link' => 'Tylko odnośnik (na liście stron projektu)',
        'tab' => 'Zakładka na stronie projektu',
        'inline' => 'Sekcja w treści strony projektu',
        'accordion' => 'Rozwijana sekcja (akordeon) w treści projektu',
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
        'site_id', 'created_by', 'parent_id', 'project_id', 'project_display', 'title', 'slug', 'content', 'is_published', 'publish_at', 'is_featured', 'is_archived', 'show_in_menu', 'show_side_nav', 'side_nav_style', 'is_system', 'is_locked', 'order',
        'meta_title', 'meta_description', 'pending_approval', 'submitted_by_id',
        'is_disabled', 'disabled_message', 'wip_mode', 'wip_message',
        'type', 'event_mode', 'event_when', 'event_location', 'event_how_to_join', 'event_registration_url',
        'schedule_items', 'schedule_change_notice', 'schedule_pending',
        'about_motto', 'about_motto_author', 'about_intro', 'about_stats', 'about_timeline', 'about_values', 'about_team', 'about_section_order', 'about_sections_hidden', 'about_partner_ids', 'about_documents_intro', 'about_documents_bip_url', 'about_press_intro', 'about_press', 'about_faq_visible',
        'faq_intro', 'faq_items', 'bip_move_url', 'bip_move_note', 'show_gallery',
        'training_manager_name', 'training_manager_title', 'training_ris_number', 'training_bur_number', 'training_extra_info', 'training_bur_note',
        'content_image', 'content_image_alt', 'content_image_width',
        'founder_image', 'founder_image_alt', 'founder_quote',
        'access_mode', 'access_password', 'access_level', 'access_group_ids', 'hub_hero', 'hub_intro', 'hub_links', 'hub_tiles_enabled', 'tiles', 'tiles_content_position', 'tiles_enabled',
        'legacy_name', 'legacy_intro',
        'brand_brandbook_url', 'brand_sections',
        'person_phone', 'person_role', 'person_bio', 'person_email', 'person_social', 'person_member_label', 'person_name_genitive', 'person_department',
        'page_template',
        'cooperation_data', 'type_data',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'publish_at' => 'datetime',
        'is_featured' => 'boolean',
        'is_archived' => 'boolean',
        'pending_approval' => 'boolean',
        'is_disabled' => 'boolean',
        'show_in_menu' => 'boolean',
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
        'faq_items' => 'array',
        'brand_sections' => 'array',
        'person_social'      => 'array',
        'person_department'  => 'array',
        'cooperation_data'   => 'array',
        'type_data'          => 'array',
    ];

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

    public function isRegulation(): bool
    {
        return $this->type === 'regulation';
    }

    public function isCaseStudy(): bool
    {
        return $this->type === 'case_study';
    }

    /**
     * Dane typu (kolumna `type_data`) z domyślnymi pustymi listami, żeby widoki
     * i formularz nie musiały sprawdzać istnienia każdego klucza.
     */
    public function typeData(): array
    {
        $data = is_array($this->type_data) ? $this->type_data : [];

        foreach (['benefits', 'audience', 'steps', 'requirements', 'terms', 'results', 'price_rows', 'members', 'docs', 'versions'] as $list) {
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
        return ! in_array($this->type, [
            'event', 'schedule', 'about', 'faq', 'bip_move',
            'internal_hub', 'links_hub', 'wspolpraca', 'training_institution', 'brand_assets',
            'legacy', 'about_person', 'contact', 'service', 'guide', 'glossary', 'case_study', 'tiles_grid', 'pricing', 'team', 'documents', 'regulation',
        ], true);
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
                    'icon' => 'fa-solid '.(self::TYPE_ICONS[$c->type] ?? 'fa-file-lines'),
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
}
