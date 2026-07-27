<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Page extends Model
{
    /**
     * Top-level URL segments already used by other routes. Pages render at
     * the site root (e.g. /fundacja), so a page can't take one of these
     * slugs without shadowing — or being shadowed by — the real route.
     */
    public const RESERVED_SLUGS = [
        'strona', 'projekty', 'aktualnosci', 'newsletter', 'wsparcie', 'materialy', 'kontakt',
        'bip', 'instagram', 'fb', 'facebook',
        'dashboard', 'profile', 'admin', 'login', 'logout', 'ankieta',
        'forgot-password', 'reset-password', 'verify-email', 'confirm-password',
    ];

    /** Page types: a plain content page, an event (webinar / on-site), a schedule listing, or an "about the organisation" page — each with its own layout. */
    public const TYPES = [
        'standard' => 'Standardowa',
        'event' => 'Wydarzenie',
        'schedule' => 'Harmonogram zajęć / spotkań',
        'about' => 'O organizacji',
        'faq' => 'FAQ (pytania i odpowiedzi)',
        'bip_move' => 'Przeniesiono do BIP',
        'internal' => 'Wewnętrzna (dostęp ograniczony)',
    ];

    /** Tryby dostępu do strony wewnętrznej. */
    public const ACCESS_MODES = [
        'password' => 'Hasło',
        'microsoft' => 'Zalogowanie (Microsoft 365 / konto panelu)',
    ];

    /** How an event is held. */
    public const EVENT_MODES = [
        'onsite' => 'Stacjonarne',
        'online' => 'Webinar',
    ];

    /** Reorderable sections of an "about the organisation" page (the hero always comes first). */
    public const ABOUT_SECTIONS = [
        'intro' => 'Wstęp i zdjęcia',
        'stats' => 'Statystyki',
        'values' => 'Wartości',
        'timeline' => 'Oś czasu',
        'team' => 'Zespół',
        'gallery' => 'Galeria',
        'partners' => 'Nasi partnerzy',
        'press' => 'My w mediach',
        'documents' => 'Dokumenty i sprawozdania',
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
        'parent_id', 'project_id', 'project_display', 'title', 'slug', 'content', 'is_published', 'is_archived', 'show_in_menu', 'is_system', 'is_locked', 'order',
        'is_disabled', 'disabled_message', 'wip_mode', 'wip_message',
        'type', 'event_mode', 'event_when', 'event_location', 'event_how_to_join', 'event_registration_url',
        'schedule_items', 'schedule_change_notice', 'schedule_pending',
        'about_motto', 'about_motto_author', 'about_intro', 'about_stats', 'about_timeline', 'about_values', 'about_team', 'about_section_order', 'about_partner_ids', 'about_documents_intro', 'about_documents_bip_url', 'about_press_intro', 'about_press',
        'faq_intro', 'faq_items', 'bip_move_url', 'bip_move_note', 'show_gallery',
        'access_mode', 'access_password',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_archived' => 'boolean',
        'is_disabled' => 'boolean',
        'show_in_menu' => 'boolean',
        'is_system' => 'boolean',
        'is_locked' => 'boolean',
        'show_gallery' => 'boolean',
        'schedule_items' => 'array',
        'schedule_pending' => 'boolean',
        'about_stats' => 'array',
        'about_timeline' => 'array',
        'about_values' => 'array',
        'about_team' => 'array',
        'about_section_order' => 'array',
        'about_partner_ids' => 'array',
        'about_press' => 'array',
        'faq_items' => 'array',
    ];

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

    public function isInternal(): bool
    {
        return $this->type === 'internal';
    }

    /**
     * Czy dostęp do tej strony wewnętrznej jest już przyznany bieżącemu
     * odwiedzającemu: dla trybu „microsoft" — zalogowanie; dla trybu „hasło" —
     * wcześniejsze odblokowanie zapisane w sesji.
     */
    public function accessGranted(): bool
    {
        if (! $this->isInternal()) {
            return true;
        }

        if ($this->access_mode === 'microsoft') {
            return auth()->check();
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

    public function children(): HasMany
    {
        return $this->hasMany(Page::class, 'parent_id')->orderBy('order')->orderBy('title');
    }

    public function publishedChildren(): HasMany
    {
        return $this->children()->where('is_published', true);
    }

    /**
     * Pages that belong together in the same local sub-menu: siblings under
     * the same parent, the published pages of the project it is attached to,
     * or (for a top-level page) its own published children.
     */
    public function menuSiblings()
    {
        if ($this->parent_id) {
            return $this->parent->children()->where('is_published', true)->get();
        }

        if ($this->project_id) {
            return $this->project->publishedPages()->get();
        }

        return $this->publishedChildren()->get();
    }
}
