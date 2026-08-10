<?php

namespace App\Models;

use App\Models\Concerns\Approvable;
use App\Models\Concerns\LogsActivity;
use App\Models\SiteSetting;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class Page extends Model
{
    use Approvable;
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
        'bip', 'instagram', 'fb', 'facebook',
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
        'links_hub' => 'Strona z kafelkami (publiczne linki do działów)',
        'wspolpraca' => 'Współpraca z FEER (partnerstwo, sektory, formy, CTA)',
        'legacy' => 'Prezentacja tego, co było',
        'brand_assets'  => 'Marka — identyfikacja wizualna (pliki do pobrania)',
        'about_person'  => 'O organizacji — osoba',
    ];

    /** Tryby dostępu do strony wewnętrznej. */
    public const ACCESS_MODES = [
        'password' => 'Hasło',
        'microsoft' => 'Zalogowanie do strefy wewnętrznej (Microsoft 365)',
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
        'parent_id', 'project_id', 'project_display', 'title', 'slug', 'content', 'is_published', 'publish_at', 'is_featured', 'is_archived', 'show_in_menu', 'show_side_nav', 'is_system', 'is_locked', 'order',
        'meta_title', 'meta_description', 'pending_approval', 'submitted_by_id',
        'is_disabled', 'disabled_message', 'wip_mode', 'wip_message',
        'type', 'event_mode', 'event_when', 'event_location', 'event_how_to_join', 'event_registration_url',
        'schedule_items', 'schedule_change_notice', 'schedule_pending',
        'about_motto', 'about_motto_author', 'about_intro', 'about_stats', 'about_timeline', 'about_values', 'about_team', 'about_section_order', 'about_partner_ids', 'about_documents_intro', 'about_documents_bip_url', 'about_press_intro', 'about_press', 'about_faq_visible',
        'faq_intro', 'faq_items', 'bip_move_url', 'bip_move_note', 'show_gallery',
        'training_manager_name', 'training_manager_title', 'training_ris_number', 'training_bur_number', 'training_extra_info', 'training_bur_note',
        'content_image', 'content_image_alt', 'content_image_width',
        'access_mode', 'access_password', 'hub_hero', 'hub_intro', 'hub_links',
        'legacy_name', 'legacy_intro',
        'brand_brandbook_url', 'brand_sections',
        'person_phone', 'person_role', 'person_bio', 'person_email', 'person_social', 'person_member_label', 'person_name_genitive', 'person_department',
        'page_template',
        'cooperation_data',
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
        'about_section_order' => 'array',
        'about_partner_ids' => 'array',
        'about_press' => 'array',
        'faq_items' => 'array',
        'brand_sections' => 'array',
        'person_social'      => 'array',
        'person_department'  => 'array',
        'cooperation_data'   => 'array',
    ];

    public function resolveRouteBinding($value, $field = null): ?self
    {
        $resolveField = $field ?? $this->getRouteKeyName();
        $settings = SiteSetting::current();

        if ($resolveField !== 'slug' || ! $settings->cacheEnabled('pages')) {
            return parent::resolveRouteBinding($value, $field);
        }

        $ttl = $settings->cacheTtl('page_item', 3600);
        $cacheKey = "page_item_{$value}";

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

    /** Czy strona używa standardowej sekcji treści (a nie własnego układu typowego). */
    public function usesStandardLayout(): bool
    {
        return ! in_array($this->type, [
            'event', 'schedule', 'about', 'faq', 'bip_move',
            'internal_hub', 'links_hub', 'wspolpraca', 'training_institution', 'brand_assets',
            'legacy', 'about_person',
        ], true);
    }

    /** Kanoniczny publiczny URL strony (uwzględnia wielosegmentowy slug dla stron osoby). */
    public function publicUrl(): string
    {
        return $this->isAboutPerson()
            ? url('/' . $this->slug)
            : route('page.show', $this);
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
            return true;
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
    public function orderedAboutSections(): array
    {
        $defined = array_keys(self::ABOUT_SECTIONS);
        $saved = array_values(array_intersect($this->about_section_order ?? [], $defined));

        return array_values(array_unique(array_merge($saved, $defined)));
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
    public function isDisabled(): bool
    {
        return (bool) $this->is_disabled;
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
        return trim((string) $this->disabled_message) ?: self::DEFAULT_DISABLED_MESSAGE;
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

    public function publishedChildren(): HasMany
    {
        return $this->children()->where('is_published', true)->where('type', '!=', 'about_person');
    }

    /**
     * Pages that belong together in the same local sub-menu: siblings under
     * the same parent, the published pages of the project it is attached to,
     * or (for a top-level page) its own published children.
     */
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
