<?php

namespace App\Models;

use App\Support\Color;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SiteSetting extends Model implements HasMedia
{
    use InteractsWithMedia;

    /**
     * Toggleable content modules, keyed by the identifier used in
     * `disabled_modules` and the `module:` route middleware.
     */
    public const MODULES = [
        'pages' => 'Podstrony',
        'news' => 'Aktualności',
        'polls' => 'Ankiety',
        'hero' => 'Slajder (hero)',
        'gallery' => 'Galeria',
        'projects' => 'Projekty',
        'quick_actions' => 'Szybkie akcje',
        'partners' => 'Partnerzy',
        'materials' => 'Materiały edukacyjne',
        'volunteering' => 'Wolontariat',
        'events' => 'Szkolenia i wydarzenia',
        'faq' => 'FAQ (najczęstsze pytania)',
        'support' => 'Wesprzyj nas',
        'reports' => 'Sprawozdania roczne',
        'blog' => 'Wiem FEER (blog)',
        'landing' => 'Landing page (webinary)',
    ];

    /**
     * Tabs of the site-settings admin screen, keyed by the value used in the
     * `?tab=` query param and the in-page Alpine state. Single source of truth
     * shared by the settings form and the sidebar sub-menu, so the two never
     * drift apart.
     */
    public const SETTINGS_TABS = [
        'general' => 'Ogólne',
        'colors' => 'Kolory',
        'maintenance' => 'Serwis',
        'seo' => 'SEO',
        'contact' => 'Kontakt',
        'social' => 'Media i BIP',
        'registry' => 'Dane rejestrowe',
        'accessibility' => 'Dostępność',
        'support' => 'Wesprzyj nas',
        'content' => 'Projekty',
        'modules' => 'Moduły',
        'homepage' => 'Strona główna',
        'login' => 'Logowanie',
        'mail' => 'Poczta',
    ];

    /**
     * Reorderable sections of the homepage. "ankieta" bundles the poll and
     * quick-actions blocks, which always render side by side as one section.
     */
    public const HOMEPAGE_SECTIONS = [
        'hero' => 'Slajder (hero)',
        'news' => 'Aktualności',
        'events' => 'Szkolenia i wydarzenia',
        'ankieta' => 'Ankieta i szybkie akcje',
        'gallery' => 'Galeria',
        'substack' => 'O tym piszemy (Substack)',
    ];

    /**
     * Layouts for the main navigation bar, keyed by the value stored in
     * `header_layout`.
     */
    public const HEADER_LAYOUTS = [
        'classic' => 'Klasyczny (menu na białym tle, obok logo)',
        'brand_bar' => 'Pasek w kolorze marki (menu na osobnym pasku pod logo)',
        'brand_bar_inline' => 'Pasek w kolorze marki (menu w jednym rzędzie z logo)',
    ];

    /**
     * Rich text editor used for page/news/project content fields.
     */
    public const EDITORS = [
        'tinymce' => 'TinyMCE',
        'ckeditor' => 'CKEditor 5',
    ];

    /**
     * Target audience of a project/news, driving which colour palette its page
     * uses: the default brand colour or the dedicated NGO colour.
     */
    public const AUDIENCES = [
        'brand' => 'Kolor marki (domyślny)',
        'ngo' => 'NGO (dedykowany kolor)',
    ];

    /**
     * Mail gateway modes selectable in the admin panel. "default" keeps the
     * .env configuration; "smtp" uses the panel-provided SMTP settings.
     */
    public const MAIL_TRANSPORTS = [
        'default' => 'Dziedzicz z serwera (.env)',
        'smtp' => 'Własny serwer SMTP',
        'sendmail' => 'Wbudowana poczta PHP (sendmail)',
    ];

    /**
     * SMTP encryption options; empty value means no encryption.
     */
    public const MAIL_ENCRYPTIONS = [
        '' => 'Brak',
        'tls' => 'TLS (STARTTLS)',
        'ssl' => 'SSL',
    ];

    protected $fillable = [
        'site_name', 'tagline', 'brand_color', 'brand_color_2', 'brand_color_3', 'brand_color_4', 'meta_description', 'allow_indexing', 'disabled_modules', 'homepage_section_order', 'events_home_color',
        'bip_url', 'bip_intro', 'facebook_url', 'twitter_url', 'instagram_url', 'linkedin_url', 'youtube_url', 'substack_url',
        'contact_address', 'contact_city', 'contact_email', 'contact_phone', 'contact_intro', 'contact_bank_accounts',
        'contact_meeting_title', 'contact_online_meeting_url', 'contact_online_meeting_label', 'contact_online_meeting_text',
        'contact_schedule_title', 'contact_schedule', 'contact_schedule_enabled', 'contact_no_schedule_note', 'contact_remote_note', 'contact_meeting_notify_email',
        'contact_edelivery_address',
        'contact_shipping_note', 'contact_paczkomat_code', 'contact_paczkomat_address', 'contact_paczkomat_location', 'contact_shipping_phone', 'contact_shipping_visible',
        'contact_box_text', 'contact_box_link_label', 'contact_box_link_url', 'contact_box_visible_from', 'contact_box_visible_until',
        'homepage_banner_text', 'homepage_banner_link_label', 'homepage_banner_link_url', 'homepage_banner_visible_from', 'homepage_banner_visible_until',
        'newsletter_code', 'header_layout', 'show_topbar_bip', 'show_topbar_social', 'content_editor',
        'site_url', 'maintenance_mode', 'maintenance_message',
        'microsoft_login_enabled', 'microsoft_client_id', 'microsoft_client_secret', 'microsoft_tenant_id',
        'member_login_enabled', 'member_allowed_domains', 'szo_api_url', 'yubico_client_id', 'yubico_secret_key', 'two_factor_required_admins',
        'unsplash_access_key', 'cookie_banner_enabled', 'cookie_banner_text', 'show_cms_credit',
        'mail_transport', 'mail_from_address', 'mail_from_name', 'mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption',
        'show_coordinators', 'ngo_color', 'sub_brands',
        'logo_alt', 'logo_only',
        'news_layout', 'volunteer_layout',
        'krs_number', 'nip_number', 'regon_number', 'projects_intro', 'materials_intro', 'materials_notice',
        'accessibility_entity_name', 'accessibility_status', 'accessibility_status_note',
        'accessibility_page_published_at', 'accessibility_page_updated_at', 'accessibility_declaration_date',
        'accessibility_review_method', 'accessibility_contact_name', 'accessibility_contact_email',
        'accessibility_contact_phone', 'accessibility_architectural',
        'bank_account_number', 'bank_account_tax_number',
        'support_intro', 'support_quick_transfer_url', 'support_buycoffee_url',
        'support_wplacam_url', 'support_method4_title', 'support_method4_text', 'support_method4_cta_label',
        'support_show_partners', 'support_testimonial_quote', 'support_testimonial_author', 'support_testimonial_role',
        'support_fundraiser_title', 'support_fundraiser_text', 'support_fundraiser_goal', 'support_fundraiser_raised',
        'support_fundraiser_url', 'support_fundraiser_cta_label',
        'support_hero_badge', 'support_hero_title', 'support_hero_subtitle', 'support_hero_cta_label',
        'support_benefits_title', 'support_benefits_subtitle',
        'support_benefit1_icon', 'support_benefit1_title', 'support_benefit1_text',
        'support_benefit2_icon', 'support_benefit2_title', 'support_benefit2_text',
        'support_benefit3_icon', 'support_benefit3_title', 'support_benefit3_text',
        'support_methods_title',
        'support_method1_title', 'support_method1_account_label', 'support_method1_tax_label',
        'support_transfer_title', 'support_method1_transfer_label',
        'support_method2_title', 'support_method2_text', 'support_method2_cta_label',
        'support_method3_title', 'support_method3_text', 'support_method3_cta_label',
        'support_outro_title', 'support_outro_subtitle',
    ];

    /**
     * Built-in copy for the /wsparcie page. Each of these fields is editable in
     * the admin settings; when its column is empty the value here is shown, so
     * the page renders identically before an admin ever touches it.
     */
    public const SUPPORT_DEFAULTS = [
        'support_hero_badge' => 'Wesprzyj nas',
        'support_hero_title' => 'Twoje wsparcie tworzy świat bez barier cyfrowych',
        'support_hero_subtitle' => 'Dzięki Tobie więcej osób zyska dostęp do wiedzy i niezależności. Każda forma wsparcia realnie napędza nasze działania.',
        'support_hero_cta_label' => 'Wpłać teraz',

        'support_benefits_title' => 'Dlaczego warto nas wspierać',
        'support_benefits_subtitle' => 'Działamy na rzecz dostępności cyfrowej i edukacji. Oto, co umożliwia Twoje wsparcie.',
        'support_benefit1_icon' => 'fa-solid fa-universal-access',
        'support_benefit1_title' => 'Dostępność dla każdego',
        'support_benefit1_text' => 'Usuwamy bariery cyfrowe, aby z internetu mogły swobodnie korzystać osoby z niepełnosprawnościami.',
        'support_benefit2_icon' => 'fa-solid fa-graduation-cap',
        'support_benefit2_title' => 'Edukacja i narzędzia',
        'support_benefit2_text' => 'Finansujemy szkolenia, audyty WCAG oraz otwarte narzędzia dostępne bezpłatnie dla wszystkich.',
        'support_benefit3_icon' => 'fa-solid fa-hand-holding-heart',
        'support_benefit3_title' => 'Niezależność działań',
        'support_benefit3_text' => 'Darowizny pozwalają nam działać niezależnie i reagować tam, gdzie wsparcie jest najbardziej potrzebne.',

        'support_methods_title' => 'Jak możesz pomóc',
        'support_method1_title' => 'Darowizna na cele statutowe',
        'support_method1_account_label' => 'Numer konta',
        'support_method1_tax_label' => 'Konto na 1,5% podatku',
        'support_method1_transfer_label' => 'Tytuł przelewu',
        'support_transfer_title' => 'Darowizna na cele statutowe',
        'support_method2_title' => 'Szybki przelew',
        'support_method2_text' => 'Wpłać darowiznę od razu, bez przepisywania numeru konta.',
        'support_method2_cta_label' => 'Przejdź do szybkiego przelewu',
        'support_method3_title' => 'BuyCoffee',
        'support_method3_text' => 'Postaw nam kawę i wesprzyj naszą pracę drobną kwotą.',
        'support_method3_cta_label' => 'Postaw kawę',
        'support_method4_title' => 'wpłacam.ngo.pl',
        'support_method4_text' => 'Bezpieczna wpłata online przez zaufany portal dla organizacji pozarządowych.',
        'support_method4_cta_label' => 'Wpłać przez wpłacam.ngo.pl',

        'support_outro_title' => 'Każda złotówka przybliża nas do świata bez barier.',
        'support_outro_subtitle' => 'Dziękujemy, że jesteś częścią tej zmiany.',
    ];

    /**
     * The editable value of a /wsparcie text field, falling back to the built-in
     * default when the admin has left it blank.
     */
    public function supportText(string $key): string
    {
        $value = $this->{$key} ?? null;

        return trim((string) $value) !== '' ? $value : (self::SUPPORT_DEFAULTS[$key] ?? '');
    }

    /**
     * Whether any registry number is set, so the footer widget can hide
     * itself cleanly instead of rendering an empty box.
     */
    public function hasRegistryData(): bool
    {
        return (bool) ($this->krs_number || $this->nip_number || $this->regon_number);
    }

    protected $casts = [
        'allow_indexing' => 'boolean',
        'disabled_modules' => 'array',
        'homepage_section_order' => 'array',
        'contact_bank_accounts' => 'array',
        'contact_schedule' => 'array',
        'contact_schedule_enabled' => 'boolean',
        'show_topbar_bip' => 'boolean',
        'show_topbar_social' => 'boolean',
        'contact_shipping_visible' => 'boolean',
        'cookie_banner_enabled' => 'boolean',
        'show_cms_credit' => 'boolean',
        'support_show_partners' => 'boolean',
        'support_fundraiser_goal' => 'integer',
        'support_fundraiser_raised' => 'integer',
        'logo_only' => 'boolean',
        'maintenance_mode' => 'boolean',
        'microsoft_login_enabled' => 'boolean',
        'microsoft_client_secret' => 'encrypted',
        'member_login_enabled' => 'boolean',
        'yubico_secret_key' => 'encrypted',
        'two_factor_required_admins' => 'boolean',
        'unsplash_access_key' => 'encrypted',
        'mail_password' => 'encrypted',
        'mail_port' => 'integer',
        'show_coordinators' => 'boolean',
        'sub_brands' => 'array',
        'contact_box_visible_from' => 'datetime',
        'contact_box_visible_until' => 'datetime',
        'homepage_banner_visible_from' => 'datetime',
        'homepage_banner_visible_until' => 'datetime',
        'accessibility_page_published_at' => 'date',
        'accessibility_page_updated_at' => 'date',
        'accessibility_declaration_date' => 'date',
    ];

    /** Status zgodności z ustawą o dostępności cyfrowej (deklaracja dostępności). */
    public const ACCESSIBILITY_STATUSES = [
        'compliant' => 'Zgodna',
        'partially' => 'Częściowo zgodna',
        'none' => 'Niezgodna',
    ];

    /** Sposób sporządzenia deklaracji: samoocena podmiotu lub audyt zewnętrzny. */
    public const ACCESSIBILITY_REVIEW_METHODS = [
        'self' => 'samooceny podmiotu publicznego',
        'external' => 'oceny podmiotu zewnętrznego',
    ];

    /** Nazwa podmiotu w deklaracji dostępności — własna lub nazwa strony. */
    public function accessibilityEntityName(): string
    {
        return trim((string) $this->accessibility_entity_name) ?: $this->site_name;
    }

    /** Adres e-mail do zgłaszania barier — dedykowany lub ogólny kontaktowy. */
    public function accessibilityContactEmail(): ?string
    {
        return filled($this->accessibility_contact_email)
            ? $this->accessibility_contact_email
            : ($this->contact_email ?: null);
    }

    /** Telefon do zgłaszania barier — dedykowany lub ogólny kontaktowy. */
    public function accessibilityContactPhone(): ?string
    {
        return filled($this->accessibility_contact_phone)
            ? $this->accessibility_contact_phone
            : ($this->contact_phone ?: null);
    }

    /** Czytelna etykieta statusu zgodności. */
    public function accessibilityStatusLabel(): string
    {
        return self::ACCESSIBILITY_STATUSES[$this->accessibility_status] ?? self::ACCESSIBILITY_STATUSES['partially'];
    }

    /**
     * Polish plural weekday names for recurring schedule entries, keyed by the
     * ISO weekday number (1 = Monday … 7 = Sunday) stored in the schedule.
     */
    public const WEEKDAYS = [
        1 => 'poniedziałki',
        2 => 'wtorki',
        3 => 'środy',
        4 => 'czwartki',
        5 => 'piątki',
        6 => 'soboty',
        7 => 'niedziele',
    ];

    /**
     * The stationary schedule, normalised for display: each entry gets its next
     * occurrence (a Carbon date), a human "when" label and a place/note. One-off
     * dates already in the past are dropped, the list is sorted by soonest, and
     * the first (nearest) entry is flagged `is_next` so the view can highlight it.
     *
     * Entry shape in `contact_schedule`:
     *   ['type' => 'date',   'date' => 'Y-m-d', 'time' => '10:00–14:00', 'where' => '…', 'note' => '…']
     *   ['type' => 'weekly', 'weekday' => 1..7,  'time' => '…',          'where' => '…', 'note' => '…']
     *
     * @return array<int, array<string, mixed>>
     */
    public function contactScheduleUpcoming(): array
    {
        $today = now()->startOfDay();
        $result = [];

        foreach ($this->contact_schedule ?? [] as $entry) {
            $type = $entry['type'] ?? 'date';
            $time = trim((string) ($entry['time'] ?? ''));
            $where = trim((string) ($entry['where'] ?? ''));
            $note = trim((string) ($entry['note'] ?? ''));
            $timeSuffix = $time !== '' ? ', '.$time : '';

            if ($type === 'weekly') {
                $weekday = (int) ($entry['weekday'] ?? 0);
                if (! isset(self::WEEKDAYS[$weekday])) {
                    continue;
                }
                // Soonest upcoming occurrence of this weekday, today included.
                $next = $today->copy()->addDays((($weekday - $today->isoWeekday()) + 7) % 7);
                $label = 'W '.self::WEEKDAYS[$weekday].$timeSuffix;
                $recurring = true;
            } else {
                $date = trim((string) ($entry['date'] ?? ''));
                if ($date === '') {
                    continue;
                }
                try {
                    $next = Carbon::parse($date)->startOfDay();
                } catch (\Throwable) {
                    continue;
                }
                if ($next->lt($today)) {
                    continue; // przeszły jednorazowy termin — nie pokazujemy
                }
                $label = $next->locale('pl')->isoFormat('D MMMM YYYY').$timeSuffix;
                $recurring = false;
            }

            $result[] = [
                'next' => $next,
                'when_label' => $label,
                'where' => $where,
                'note' => $note,
                'recurring' => $recurring,
                'is_next' => false,
            ];
        }

        usort($result, fn ($a, $b) => $a['next'] <=> $b['next']);

        if ($result !== []) {
            $result[0]['is_next'] = true;
        }

        return $result;
    }

    /**
     * Recipient for "Daj znać, że przyjdziesz" notices and the CC copy of
     * schedule-change notifications: the dedicated address if set, else the
     * general contact inbox.
     */
    public function meetingNotifyEmail(): ?string
    {
        return filled($this->contact_meeting_notify_email)
            ? $this->contact_meeting_notify_email
            : ($this->contact_email ?: null);
    }

    /**
     * Whether the optional contact notice box should currently be shown: it
     * needs some content, and — if a schedule is set — "now" must fall within
     * the visible-from / visible-until window (either bound may be open).
     */
    public function contactBoxIsVisible(): bool
    {
        $hasContent = filled($this->contact_box_text)
            || (filled($this->contact_box_link_label) && filled($this->contact_box_link_url));

        if (! $hasContent) {
            return false;
        }

        $now = now();

        if ($this->contact_box_visible_from && $now->lt($this->contact_box_visible_from)) {
            return false;
        }

        if ($this->contact_box_visible_until && $now->gt($this->contact_box_visible_until)) {
            return false;
        }

        return true;
    }

    /**
     * Whether the optional homepage information bar should currently be shown:
     * it needs some text, and — if a schedule is set — "now" must fall within
     * the visible-from / visible-until window (either bound may be open).
     */
    public function homepageBannerIsVisible(): bool
    {
        if (blank($this->homepage_banner_text)) {
            return false;
        }

        $now = now();

        if ($this->homepage_banner_visible_from && $now->lt($this->homepage_banner_visible_from)) {
            return false;
        }

        if ($this->homepage_banner_visible_until && $now->gt($this->homepage_banner_visible_until)) {
            return false;
        }

        return true;
    }

    /**
     * Konfiguracja logowania Microsoft 365 dla Laravel Socialite. Wartości
     * z panelu mają pierwszeństwo, a puste pola dziedziczą z config/services
     * (czyli z .env), więc obie metody konfiguracji mogą współistnieć.
     */
    public function microsoftConfig(): array
    {
        return [
            'client_id' => $this->microsoft_client_id ?: config('services.microsoft.client_id'),
            'client_secret' => $this->microsoft_client_secret ?: config('services.microsoft.client_secret'),
            'tenant' => $this->microsoft_tenant_id ?: config('services.microsoft.tenant', 'common'),
            'redirect' => config('services.microsoft.redirect') ?: url('/auth/microsoft/callback'),
        ];
    }

    /**
     * Czy logowanie MS365 jest aktywne: włączone przełącznikiem i faktycznie
     * skonfigurowane (są Client ID oraz Client Secret — z panelu lub z .env).
     */
    public function microsoftLoginEnabled(): bool
    {
        // Podczas przerwy technicznej logowanie SSO jest całkowicie zablokowane,
        // aby w tym czasie nikt niepowołany nie dostał się do panelu.
        if ($this->maintenance_mode) {
            return false;
        }

        if (! $this->microsoft_login_enabled) {
            return false;
        }

        $config = $this->microsoftConfig();

        return filled($config['client_id']) && filled($config['client_secret']);
    }

    /**
     * Konfiguracja MS365 dla logowania współpracowników do stron wewnętrznych.
     * Reużywa tej samej aplikacji Azure co panel, ale z osobnym adresem powrotu
     * (redirect URI), aby callback trafił do guardu „member".
     */
    public function memberMicrosoftConfig(): array
    {
        return array_merge($this->microsoftConfig(), [
            'redirect' => url('/strefa/microsoft/callback'),
        ]);
    }

    /** Czy osobne logowanie do stron wewnętrznych (MS365) jest aktywne. */
    public function memberLoginEnabled(): bool
    {
        if ($this->maintenance_mode || ! $this->member_login_enabled) {
            return false;
        }

        $config = $this->memberMicrosoftConfig();

        return filled($config['client_id']) && filled($config['client_secret']);
    }

    /**
     * Dozwolone domeny e-mail dla logowania współpracowników (małe litery).
     * Pusta lista = dowolne konto z tenanta skonfigurowanego w Azure.
     *
     * @return array<int, string>
     */
    public function memberAllowedDomains(): array
    {
        return collect(explode(',', (string) $this->member_allowed_domains))
            ->map(fn ($domain) => strtolower(trim(ltrim($domain, '@'))))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** Czy podany e-mail należy do dozwolonej domeny (lub gdy brak ograniczeń). */
    public function memberEmailAllowed(?string $email): bool
    {
        $domains = $this->memberAllowedDomains();

        if (empty($domains)) {
            return true;
        }

        $emailDomain = strtolower(trim((string) strrchr((string) $email, '@')));
        $emailDomain = ltrim($emailDomain, '@');

        return in_array($emailDomain, $domains, true);
    }

    /** Czy skonfigurowano adres systemu SZO (integracja komunikatów strefy). */
    public function szoConfigured(): bool
    {
        return filled($this->szo_api_url);
    }

    /**
     * Pełny adres endpointu listy komunikatów SZO
     * ({adres bazowy}/api/komunikaty/list.php) albo null, gdy brak konfiguracji.
     */
    public function szoKomunikatyUrl(): ?string
    {
        if (! $this->szoConfigured()) {
            return null;
        }

        return rtrim(trim($this->szo_api_url), '/').'/api/komunikaty/list.php';
    }

    /**
     * Adres Panelu Współpracownika w Systemie Zarządzania Organizacją (adres
     * bazowy SZO) albo null, gdy brak konfiguracji — pełny dostęp jest tam,
     * a strefa na stronie pokazuje tylko komunikaty i odnośniki.
     */
    public function szoPanelUrl(): ?string
    {
        return $this->szoConfigured() ? rtrim(trim($this->szo_api_url), '/') : null;
    }

    /**
     * Adres zarządzania tożsamością współpracownika w SZO
     * ({adres bazowy SZO}/tozsamosc) albo null, gdy brak konfiguracji SZO.
     */
    public function szoTozsamoscUrl(): ?string
    {
        $base = $this->szoPanelUrl();

        return $base ? $base.'/tozsamosc' : null;
    }

    /** Czy uwierzytelnianie kluczem YubiKey jest skonfigurowane (Yubico API). */
    public function yubicoConfigured(): bool
    {
        return filled($this->yubico_client_id) && filled($this->yubico_secret_key);
    }

    /** Domyślny komunikat trybu konserwacji, gdy admin nie podał własnego. */
    public const DEFAULT_MAINTENANCE_MESSAGE = 'Trwa przerwa techniczna. Wprowadzamy zmiany na stronie — zapraszamy wkrótce.';

    /** Treść komunikatu przerwy technicznej (własna lub domyślna). */
    public function maintenanceMessage(): string
    {
        return trim((string) $this->maintenance_message) ?: self::DEFAULT_MAINTENANCE_MESSAGE;
    }

    /**
     * Alt text for the logo image: the admin-provided value, falling back to
     * the site name so the logo is never unlabelled (WCAG 1.1.1).
     */
    public function logoAltText(): string
    {
        return trim((string) $this->logo_alt) !== '' ? $this->logo_alt : $this->site_name;
    }

    /**
     * Whether the header should show the logo on its own, hiding the site name
     * and tagline. Only takes effect when a logo is actually uploaded.
     */
    public function showLogoOnly(): bool
    {
        return $this->logo_only && $this->logoUrl() !== null;
    }

    /** Domyślny tekst banera cookies, gdy admin nie podał własnego. */
    public const DEFAULT_COOKIE_BANNER_TEXT = 'Ta strona używa plików cookies, aby zapewnić najlepsze działanie serwisu. Korzystając z witryny, zgadzasz się na ich użycie.';

    /** Treść banera cookies (własna lub domyślna). */
    public function cookieBannerText(): string
    {
        return trim((string) $this->cookie_banner_text) ?: self::DEFAULT_COOKIE_BANNER_TEXT;
    }

    /** Klucz Unsplash: z panelu, a gdy pusty — z konfiguracji (.env). */
    public function unsplashAccessKey(): ?string
    {
        return filled($this->unsplash_access_key)
            ? $this->unsplash_access_key
            : (config('services.unsplash.access_key') ?: null);
    }

    private static ?self $cached = null;

    public static function current(): self
    {
        return static::$cached ??= static::query()->firstOrCreate(['id' => 1]);
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::$cached = null);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
        $this->addMediaCollection('og_image')->singleFile();
        $this->addMediaCollection('support_image')->singleFile();
        $this->addMediaCollection('news_default_image')->singleFile();
        $this->addMediaCollection('bip_logo')->singleFile();
        // Osobna galeria dla strony „Wesprzyj nas" (wiele zdjęć, niezależna od
        // głównej galerii strony) — kolaż „działamy" jako dowód aktywności.
        $this->addMediaCollection('support_gallery');
    }

    /** Zdjęcia dedykowanej galerii strony „Wesprzyj nas" (posortowane). */
    public function supportGalleryImages(): Collection
    {
        return $this->getMedia('support_gallery');
    }

    /** Czy pokazać blok zbiórki na /wsparcie (potrzebny tytuł i cel > 0). */
    public function hasFundraiser(): bool
    {
        return filled($this->support_fundraiser_title) && (int) $this->support_fundraiser_goal > 0;
    }

    /** Postęp zbiórki w procentach (0–100), przycięty do celu. */
    public function fundraiserProgress(): int
    {
        $goal = (int) $this->support_fundraiser_goal;

        if ($goal <= 0) {
            return 0;
        }

        return (int) min(100, round(((int) $this->support_fundraiser_raised / $goal) * 100));
    }

    public function bipLogoUrl(): ?string
    {
        return $this->getFirstMediaUrl('bip_logo') ?: null;
    }

    /**
     * Fallback image shown for news without their own photo.
     */
    public function newsDefaultImageUrl(): ?string
    {
        return $this->getFirstMediaUrl('news_default_image') ?: null;
    }

    public function isModuleEnabled(string $module): bool
    {
        return ! in_array($module, $this->disabled_modules ?? [], true);
    }

    /**
     * Efektywny n-ty kolor marki (2–4): własny (jeśli ustawiony i poprawny) albo
     * fallback do koloru głównego — dzięki temu zmienne CSS zawsze mają wartość.
     */
    public function brandColorN(int $n): string
    {
        $hex = $this->{'brand_color_'.$n} ?? null;

        return Color::isValid($hex) ? $hex : $this->brandPalette()['color'];
    }

    /** Wszystkie ustawione kolory identyfikacji (główny + 2–4), do paska/akcentów. */
    public function brandPaletteColors(): array
    {
        return collect([$this->brand_color, $this->brand_color_2, $this->brand_color_3, $this->brand_color_4])
            ->filter(fn ($c) => Color::isValid($c))
            ->values()
            ->all();
    }

    /** Czy zdefiniowano dodatkowe kolory marki (poza głównym). */
    public function hasExtraBrandColors(): bool
    {
        return collect([$this->brand_color_2, $this->brand_color_3, $this->brand_color_4])
            ->contains(fn ($c) => Color::isValid($c));
    }

    /**
     * Kolor akcentu sekcji „Szkolenia i wydarzenia" na stronie głównej: własny
     * (z kontrolą kontrastu WCAG) albo — gdy nie ustawiono — kolor marki.
     */
    public function eventsHomeAccent(): string
    {
        return Color::isValid($this->events_home_color)
            ? $this->contrastSafeColor($this->events_home_color)
            : $this->brandPalette()['color'];
    }

    /**
     * The homepage section keys in the order they should render. Falls back
     * to the default declaration order, and silently drops/ignores any saved
     * keys that no longer exist so a future code change can't break the page.
     */
    public function orderedHomepageSections(): array
    {
        $defined = array_keys(self::HOMEPAGE_SECTIONS);
        $saved = array_values(array_intersect($this->homepage_section_order ?? [], $defined));

        return array_values(array_unique(array_merge($saved, $defined)));
    }

    public function logoUrl(): ?string
    {
        return $this->getFirstMediaUrl('logo') ?: null;
    }

    public function ogImageUrl(): ?string
    {
        return $this->getFirstMediaUrl('og_image') ?: null;
    }

    public function supportImageUrl(): ?string
    {
        return $this->getFirstMediaUrl('support_image') ?: null;
    }

    public function brandColorDark(): string
    {
        return $this->shade($this->brand_color, -0.25);
    }

    public function brandColorLight(): string
    {
        return $this->shade($this->brand_color, 0.92);
    }

    /**
     * Whether the panel provides its own mail gateway (i.e. we should override
     * the .env mail configuration at runtime).
     */
    public function mailConfigured(): bool
    {
        return $this->mail_transport === 'smtp';
    }

    /**
     * The brand palette ([color, dark, light]) for the given hex, falling back
     * to the site brand colour. Used to recolour a page for a target audience.
     */
    public function brandPalette(?string $hex = null): array
    {
        $base = ($hex && Color::isValid($hex)) ? $hex : $this->brand_color;

        // Fresh SiteSetting rows created via firstOrCreate() can return a null
        // brand_color on the in-memory model even though the column has a DB
        // default — fall back so colour maths never receives null.
        if (! Color::isValid($base)) {
            $base = '#c31432';
        }

        return [
            'color' => $base,
            'dark' => $this->shade($base, -0.25),
            'light' => $this->shade($base, 0.92),
        ];
    }

    /**
     * Named sub-brand colours (managed in Ustawienia → Kolory), cleaned to the
     * valid entries only: [{name, color}].
     */
    public function subBrands(): array
    {
        return collect($this->sub_brands ?? [])
            ->filter(fn ($s) => filled($s['name'] ?? null) && Color::isValid($s['color'] ?? ''))
            ->map(fn ($s) => ['name' => $s['name'], 'color' => $s['color']])
            ->values()
            ->all();
    }

    /**
     * Options for the "colour scheme" (audience) picker on projects/news: the
     * brand colour, the built-in NGO sub-brand, and every named sub-brand.
     * Sub-brands are keyed "sub:<name>" so audienceColor() can resolve them.
     */
    public function audienceOptions(): array
    {
        $options = ['brand' => 'Kolor marki (domyślny)'];

        // Dodatkowe kolory identyfikacji (2–4) — dostępne, gdy zdefiniowane.
        foreach ([2, 3, 4] as $n) {
            if (Color::isValid($this->{'brand_color_'.$n})) {
                $options['brand'.$n] = 'Kolor marki '.$n;
            }
        }

        $options['ngo'] = 'NGO';

        foreach ($this->subBrands() as $subBrand) {
            $options['sub:'.$subBrand['name']] = $subBrand['name'];
        }

        return $options;
    }

    /**
     * The effective brand colour for a target audience: the NGO colour for
     * "ngo", a named sub-brand for "sub:<name>", otherwise the brand colour.
     */
    public function audienceColor(?string $audience): string
    {
        if ($audience === 'ngo' && Color::isValid($this->ngo_color)) {
            return $this->ngo_color;
        }

        // Dodatkowe kolory marki: „brand2"/„brand3"/„brand4".
        if (in_array($audience, ['brand2', 'brand3', 'brand4'], true)) {
            $hex = $this->{'brand_color_'.substr($audience, 5)} ?? null;
            if (Color::isValid($hex)) {
                return $hex;
            }
        }

        if ($audience && str_starts_with($audience, 'sub:')) {
            $name = substr($audience, 4);
            foreach ($this->subBrands() as $subBrand) {
                if ($subBrand['name'] === $name) {
                    return $subBrand['color'];
                }
            }
        }

        // Świeże wiersze (firstOrCreate) mogą mieć null w brand_color mimo
        // domyślnej wartości kolumny — zwróć bezpieczny fallback, aby typ zwrotu
        // (string) był zawsze dotrzymany (por. brandPalette()).
        return Color::isValid($this->brand_color) ? $this->brand_color : '#c31432';
    }

    /**
     * WCAG 2.2 contrast ratio (1–21) of the brand color against white, used
     * wherever it appears as button/link text on a white background.
     */
    public function brandContrastWithWhite(): float
    {
        return $this->contrastRatio($this->brand_color, '#ffffff');
    }

    public function brandMeetsMinimumContrast(): bool
    {
        return $this->brandContrastWithWhite() >= 4.5;
    }

    /**
     * Darken the given color, step by step, until it reaches the WCAG AA
     * minimum contrast (4.5:1) against white. Returns it unchanged if it
     * already passes, so a compliant color is never altered.
     */
    public function contrastSafeColor(string $hex): string
    {
        for ($step = 0; $step <= 20; $step++) {
            $candidate = $step === 0 ? $hex : $this->shade($hex, -0.05 * $step);

            if ($this->contrastRatio($candidate, '#ffffff') >= 4.5) {
                return $candidate;
            }
        }

        return '#000000';
    }

    private function contrastRatio(string $hexA, string $hexB): float
    {
        $luminanceA = $this->relativeLuminance($hexA);
        $luminanceB = $this->relativeLuminance($hexB);

        $lighter = max($luminanceA, $luminanceB);
        $darker = min($luminanceA, $luminanceB);

        return round(($lighter + 0.05) / ($darker + 0.05), 2);
    }

    private function relativeLuminance(string $hex): float
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6) {
            return 0;
        }

        [$r, $g, $b] = array_map(function ($part) {
            $channel = hexdec($part) / 255;

            return $channel <= 0.03928 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
        }, str_split($hex, 2));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Mix a hex color towards black (negative $amount) or white (positive $amount).
     */
    private function shade(string $hex, float $amount): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6) {
            return '#'.$hex;
        }

        [$r, $g, $b] = array_map(fn ($part) => hexdec($part), str_split($hex, 2));
        $target = $amount < 0 ? 0 : 255;
        $ratio = abs($amount);

        $mix = fn ($channel) => (int) round($channel + ($target - $channel) * $ratio);

        return sprintf('#%02x%02x%02x', $mix($r), $mix($g), $mix($b));
    }
}
