<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'order', 'icon',
        'portal_slug', 'portal_title', 'portal_intro', 'portal_slider_id', 'portal_rss_url', 'portal_widgets'];

    protected $casts = [
        'portal_widgets' => 'array',
    ];

    /** Widżety portalu kategorii (klucz => etykieta w panelu), w domyślnej kolejności. */
    public const PORTAL_WIDGETS = [
        'projects' => 'Działania z kategorii (kafelki)',
        'news'     => 'Aktualności z działań kategorii',
        'events'   => 'Najbliższe szkolenia i wydarzenia',
        'rss'      => 'Zewnętrzny kanał RSS',
        'contact'  => 'Kontakt — koordynatorzy działań',
        'stats'    => 'Liczby (działania, aktualności, wydarzenia)',
    ];

    /**
     * Subdomeny, których nie wolno zająć portalem (inne serwisy i usługi na feer.org.pl).
     * Przekierowanie subdomeny działa i tak tylko dla adresów przypisanych do kategorii.
     */
    public const RESERVED_PORTAL_SLUGS = ['www', 'mail', 'poczta', 'ftp', 'cms', 'cms2', 'cmslicense', 'wiem', 'admin', 'api', 'bip', 'panel', 'webmail', 'smtp', 'imap', 'pop', 'autodiscover', 'portal'];

    /** Włączone widżety portalu w ustawionej kolejności (domyślnie wszystkie poza RSS, który wymaga adresu kanału). */
    public function portalWidgets(): array
    {
        $saved = is_array($this->portal_widgets ?? null) ? $this->portal_widgets : null;
        $keys = $saved ?? array_values(array_diff(array_keys(self::PORTAL_WIDGETS), ['rss']));

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
