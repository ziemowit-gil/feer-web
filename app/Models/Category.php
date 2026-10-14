<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'order', 'icon',
        'portal_slug', 'portal_title', 'portal_intro', 'portal_slider_id', 'portal_rss_url', 'portal_widgets', 'portal_options'];

    protected $casts = [
        'portal_widgets' => 'array',
        'portal_options' => 'array',
    ];

    /** Widżety portalu kategorii (klucz => etykieta w panelu), w domyślnej kolejności. */
    public const PORTAL_WIDGETS = [
        'projects' => 'Działania z kategorii (kafelki)',
        'news'     => 'Aktualności z działań kategorii',
        'contact'  => 'Kontakt — do obszaru i koordynatorzy działań',
        'events'   => 'Najbliższe szkolenia i wydarzenia',
        'rss'      => 'Zewnętrzny kanał RSS',
        'stats'    => 'Liczby (działania, aktualności, wydarzenia)',
    ];

    /** Domyślne nagłówki widżetów portalu (w panelu można je nadpisać, puste pole = domyślny). */
    public const PORTAL_WIDGET_TITLES = [
        'projects' => 'Działania',
        'news'     => 'Aktualności',
        'contact'  => 'Kontakt',
        'events'   => 'Najbliższe szkolenia i wydarzenia',
        'rss'      => 'Z innych serwisów',
        'stats'    => 'W liczbach',
    ];

    /**
     * Ustawienie portalu z kolumny portal_options: notice, notice_link_label, notice_link_url,
     * shortcuts (lista [label, url]), contact_text, contact_email, contact_phone (kontakt do obszaru),
     * stay_enabled, stay_title, stay_text, titles (klucz widżetu => nagłówek).
     */
    public function portalOption(string $key, mixed $default = null): mixed
    {
        $options = is_array($this->portal_options ?? null) ? $this->portal_options : [];
        $value = data_get($options, $key);

        return ($value === null || $value === '' || $value === []) ? $default : $value;
    }

    /**
     * „Na skróty” z panelu: wiersze „Etykieta | adres” zapisane jako lista [label, url].
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public function portalShortcuts(): array
    {
        return collect($this->portalOption('shortcuts', []))
            ->filter(fn ($s) => is_array($s) && filled($s['label'] ?? null) && filled($s['url'] ?? null))
            ->map(fn ($s) => [(string) $s['label'], (string) $s['url']])->values()->all();
    }

    /** Nagłówek widżetu portalu — własny z panelu albo domyślny. */
    public function portalWidgetTitle(string $widget): string
    {
        return (string) $this->portalOption("titles.$widget", self::PORTAL_WIDGET_TITLES[$widget] ?? '');
    }

    /**
     * Subdomeny, których nie wolno zająć portalem (inne serwisy i usługi na feer.org.pl).
     * Przekierowanie subdomeny działa i tak tylko dla adresów przypisanych do kategorii.
     */
    public const RESERVED_PORTAL_SLUGS = ['www', 'mail', 'poczta', 'ftp', 'cms', 'cms2', 'cmslicense', 'wiem', 'admin', 'api', 'bip', 'panel', 'webmail', 'smtp', 'imap', 'pop', 'autodiscover', 'portal'];

    /**
     * Widżety włączone, dopóki w panelu nie zapisano własnego wyboru: portal ma przede wszystkim informować
     * o obszarze działalności (wstęp, działania, kontakt), więc bez liczb i bez RSS, który wymaga adresu kanału.
     */
    public static function defaultPortalWidgets(): array
    {
        return array_values(array_diff(array_keys(self::PORTAL_WIDGETS), ['rss', 'stats']));
    }

    /** Włączone widżety portalu w ustawionej kolejności. */
    public function portalWidgets(): array
    {
        $saved = is_array($this->portal_widgets ?? null) ? $this->portal_widgets : null;
        $keys = $saved ?? self::defaultPortalWidgets();

        return array_values(array_filter($keys, fn ($k) => isset(self::PORTAL_WIDGETS[$k])
            && ($k !== 'rss' || filled($this->portal_rss_url ?? null))));
    }

    public function hasPortal(): bool
    {
        return filled($this->portal_slug ?? null);
    }

    /** Adres portalu na głównej domenie (/portal/{slug}). */
    public function portalUrl(): ?string
    {
        return $this->hasPortal() ? route('portal.show', $this->portal_slug) : null;
    }

    /** Gdzie prowadzą linki do kategorii (menu, okruszki, „← kategoria”): portal, jeśli kategoria go ma. */
    public function publicUrl(): string
    {
        return $this->portalUrl() ?? route('categories.show', $this);
    }

    /** Nazwa w linkach do kategorii — tytuł portalu albo nazwa kategorii. */
    public function publicLabel(): string
    {
        return ($this->hasPortal() ? $this->portal_title : null) ?: $this->name;
    }

    /**
     * Okruszki kategorii dla stron działań i ich podstron: z portalem — sam portal
     * (nawigacja względem portalu), bez portalu — „Działania › kategoria”.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public function breadcrumbTrail(): array
    {
        if ($this->hasPortal()) {
            return [['label' => $this->publicLabel(), 'url' => $this->portalUrl()]];
        }

        return [
            ['label' => 'Działania', 'url' => route('projects.index')],
            ['label' => $this->name, 'url' => route('categories.show', $this)],
        ];
    }

    /** Krótki adres w subdomenie ({slug}.feer.org.pl), wyliczany z APP_URL. */
    public function portalSubdomainUrl(): ?string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (! $this->hasPortal() || ! $host) {
            return null;
        }

        return (parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https').'://'.$this->portal_slug.'.'.preg_replace('/^www\./', '', $host);
    }

    /** Ikona dobierana po nazwie, gdy nie wybrano własnej (fragment nazwy => ikona Bootstrap Icons). */
    private const ICON_GUESS = [
        'wcag' => 'bi-universal-access', 'audyt' => 'bi-clipboard-check', 'platform' => 'bi-display', 'szkole' => 'bi-easel', 'warsztat' => 'bi-easel', 'kurs' => 'bi-mortarboard', 'eduk' => 'bi-mortarboard',
        'dostępn' => 'bi-universal-access', 'dostepn' => 'bi-universal-access', 'wspar' => 'bi-heart', 'pomoc' => 'bi-heart',
        'konsult' => 'bi-chat-dots', 'doradz' => 'bi-chat-dots', 'badan' => 'bi-graph-up', 'analiz' => 'bi-graph-up',
        'projekt' => 'bi-kanban', 'wydarz' => 'bi-calendar-event', 'konfer' => 'bi-mic', 'wolont' => 'bi-people',
        'publik' => 'bi-book', 'materia' => 'bi-book', 'kultur' => 'bi-palette', 'sport' => 'bi-trophy', 'dziec' => 'bi-balloon',
        'tłumacz' => 'bi-translate', 'audio' => 'bi-soundwave', 'film' => 'bi-camera-reels',
    ];

    /** Ikona do wyświetlenia: wybrana w panelu albo dobrana po nazwie. */
    public function iconValue(): string
    {
        if (filled($this->icon ?? null)) {
            return $this->icon;
        }
        $name = mb_strtolower((string) $this->name);
        foreach (self::ICON_GUESS as $fragment => $icon) {
            if (str_contains($name, $fragment)) {
                return $icon;
            }
        }

        return 'bi-grid';
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function publishedProjects(): HasMany
    {
        return $this->projects()->where('is_published', true)->whereNull('parent_id')->forCurrentSite()->orderBy('order');
    }
}
