<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class NavItem extends Model
{
    use \App\Models\Concerns\BelongsToSite;
    use \App\Models\Concerns\LogsActivity;

    public const TYPES = [
        'link' => 'Zwykły link',
        'dropdown' => 'Rozwijane menu (własne podpozycje)',
        'projects' => 'Menu działań (automatyczne z kategorii)',
        'pages' => 'Menu podstron (automatyczne z podstron)',
        'volunteering' => 'Ogłoszenia o wolontariacie',
        'events' => 'Szkolenia i wydarzenia',
        'faq' => 'FAQ (najczęstsze pytania)',
    ];

    /**
     * Where the item renders: the header's main menu, or the footer's link
     * list (which only ever renders items as plain links, regardless of type).
     */
    /** Wielkość pozycji w panelu mega menu. */
    public const MEGA_SIZES = [
        'sm' => 'Kompaktowe (więcej kolumn, mniejszy tekst, bez opisów)',
        'md' => 'Standardowe (ikona, tytuł, opis)',
        'lg' => 'Duże (dwie kolumny, większe ikony i opisy)',
    ];

    /** Wygląd własnych linków pod/obok menu rozwijanego. */
    public const SIDE_LINK_STYLES = ['link', 'button', 'tile', 'tile_filled'];

    public const LOCATIONS = [
        'main' => 'Menu główne (nagłówek)',
        'footer' => 'Stopka',
        'bip' => 'Menu BIP (boczna nawigacja)',
    ];

    protected $fillable = [
        'site_id', 'parent_id', 'label', 'icon', 'description', 'mega_image', 'mega_image_alt', 'url', 'type', 'module', 'location',
        'is_button', 'is_column_heading', 'is_transparent_dropdown', 'is_mega', 'mega_size', 'mega_extra_title', 'mega_side_title', 'mega_side_links', 'hide_all_projects_btn', 'hide_archive_btn', 'is_active', 'order', 'button_color', 'accent_color',
    ];

    protected $casts = [
        'is_button' => 'boolean',
        'is_column_heading' => 'boolean',
        'is_transparent_dropdown' => 'boolean',
        'is_mega' => 'boolean',
        'mega_side_links' => 'array',
        'hide_all_projects_btn' => 'boolean',
        'hide_archive_btn' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(NavItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(NavItem::class, 'parent_id')->where('is_active', true)->orderBy('order');
    }

    /**
     * Unfiltered children (active and hidden), for the admin listing.
     */
    public function allChildren(): HasMany
    {
        return $this->hasMany(NavItem::class, 'parent_id')->orderBy('order');
    }

    public function isDropdown(): bool
    {
        return in_array($this->type, ['dropdown', 'projects', 'pages'], true);
    }

    /**
     * Czy pozycja otwiera mega menu (panel na całą szerokość paska): flaga
     * `is_mega` na rozwijanym menu z podpozycjami albo na linku do strony,
     * która ma opublikowane podstrony. Bez treści do pokazania wraca zwykłe
     * rozwijane menu / zwykły link.
     */
    /**
     * Własne linki i przyciski karty bocznej mega menu (bez wierszy pustych),
     * w postaci [label, url, style: button|link, new_tab].
     *
     * @return array<int, array{label: string, url: string, style: string, new_tab: bool}>
     */
    public function megaSideLinks(): array
    {
        return collect($this->mega_side_links ?? [])
            ->filter(fn ($l) => is_array($l) && filled($l['label'] ?? null) && filled($l['url'] ?? null))
            ->map(fn ($l) => [
                'label' => trim((string) $l['label']),
                'url' => trim((string) $l['url']),
                'style' => in_array($l['style'] ?? 'link', self::SIDE_LINK_STYLES, true) ? $l['style'] : 'link',
                'new_tab' => filter_var($l['new_tab'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ])
            ->values()
            ->all();
    }

    /** Bezpieczna wartość wielkości mega menu (domyślnie 'md'). */
    public function megaSize(): string
    {
        return array_key_exists((string) $this->mega_size, self::MEGA_SIZES) ? $this->mega_size : 'md';
    }

    public function isMega(): bool
    {
        if (! $this->is_mega || $this->is_button || $this->parent_id) {
            return false;
        }

        if ($this->type === 'dropdown') {
            return $this->children->isNotEmpty();
        }

        if ($this->type === 'link') {
            return $this->children->isNotEmpty()
                || (($page = $this->linkedPage()) && $page->publishedChildren->isNotEmpty());
        }

        // Menu działań: kolumny = kategorie z działaniami (partial sprawdza, czy są kategorie).
        return $this->type === 'projects';
    }

    /**
     * Strona, na którą wskazuje ten link (jeśli to wewnętrzny link do podstrony).
     * Dzięki temu zwykły link automatycznie dostaje submenu z opublikowanymi
     * podstronami. Zwraca null dla przycisków CTA, linków zewnętrznych, kotwic
     * oraz adresów, które nie odpowiadają istniejącej opublikowanej stronie.
     */
    public function linkedPage(): ?Page
    {
        if ($this->type !== 'link' || $this->is_button || blank($this->url)) {
            return null;
        }

        $url = $this->url;

        if (Str::startsWith($url, '#')) {
            return null;
        }

        // Linki zewnętrzne pomijamy; wewnętrzny bezwzględny sprowadzamy do ścieżki.
        if (Str::startsWith($url, ['http://', 'https://'])) {
            if (! Str::startsWith($url, url('/'))) {
                return null;
            }
            $url = Str::after($url, rtrim(url('/'), '/'));
        }

        $path = ltrim((string) parse_url($url, PHP_URL_PATH), '/');

        // Tylko adresy jednoczłonowe /{slug} (podstrony żyją na najwyższym poziomie).
        if ($path === '' || str_contains($path, '/')) {
            return null;
        }

        return Page::where('slug', $path)->where('is_published', true)
            ->with('publishedChildren.publishedChildren')
            ->first();
    }

    /**
     * Sekcje (kolumny) panelu mega menu: [ ['heading' => ?array, 'links' => array], … ].
     *
     * Źródła, w tej kolejności:
     *  1. ręczne podpozycje — pozycja z flagą „nagłówek kolumny” otwiera nową
     *     sekcję, kolejne podpozycje trafiają pod nią (przed pierwszym nagłówkiem
     *     powstaje sekcja bez nagłówka),
     *  2. podstrony powiązanej strony — podstrona mająca własne opublikowane
     *     podstrony staje się sekcją (nagłówek = ona sama, linki = jej podstrony),
     *     pozostałe trafiają do sekcji bez nagłówka.
     *
     * Wpis linku to [url, etykieta, opis, ikona, czy_bieżąca]; nagłówek to tablica
     * z kluczami url (null = sam tekst), label, description, icon, current.
     * Gdy żadna sekcja nie ma nagłówka, panel jest płaską listą jak dotąd.
     *
     * @return array<int, array{heading: ?array<string, mixed>, links: array<int, array<int, mixed>>}>
     */
    public function megaSections(?int $currentPageId = null, Page|null|false $linkedPage = false): array
    {
        $sections = [];
        $open = ['heading' => null, 'links' => []];

        foreach ($this->children as $child) {
            if ($child->is_column_heading) {
                if ($open['heading'] !== null || $open['links'] !== []) {
                    $sections[] = $open;
                }
                $open = ['heading' => [
                    'url' => filled($child->url) && $child->url !== '#' ? $child->url : null,
                    'label' => $child->label,
                    'description' => $child->description,
                    'icon' => $child->icon,
                    'current' => $child->isCurrent(),
                ], 'links' => []];

                continue;
            }

            $open['links'][] = [$child->url, $child->label, $child->description, $child->icon, $child->isCurrent()];
        }
        if ($open['heading'] !== null || $open['links'] !== []) {
            $sections[] = $open;
        }

        // Strona powiązana można przekazać z zewnątrz (partial już ją pobrał) — bez drugiego zapytania.
        $page = $linkedPage === false ? ($this->type === 'link' ? $this->linkedPage() : null) : $linkedPage;
        if ($page) {
            $loose = [];
            $groups = [];
            foreach ($page->publishedChildren as $child) {
                $grandchildren = $child->publishedChildren;
                if ($grandchildren->isNotEmpty()) {
                    $groups[] = [
                        'heading' => ['url' => $child->publicUrl(), 'label' => $child->title, 'description' => null, 'icon' => null, 'current' => $currentPageId === $child->id],
                        'links' => $grandchildren->map(fn (Page $g) => [$g->publicUrl(), $g->title, null, null, $currentPageId === $g->id])->all(),
                    ];
                } else {
                    $loose[] = [$child->publicUrl(), $child->title, null, null, $currentPageId === $child->id];
                }
            }

            if ($loose !== []) {
                $index = collect($sections)->search(fn ($s) => $s['heading'] === null);
                if ($index === false) {
                    array_unshift($sections, ['heading' => null, 'links' => $loose]);
                } else {
                    $sections[$index]['links'] = array_merge($sections[$index]['links'], $loose);
                }
            }
            array_push($sections, ...$groups);
        }

        return $sections;
    }

    /** Czy panel ma kolumny z nagłówkami (zamiast jednej płaskiej listy). */
    public static function sectionsAreGrouped(array $sections): bool
    {
        return collect($sections)->contains(fn ($s) => $s['heading'] !== null);
    }

    /**
     * Whether this item represents the page currently being viewed.
     * Dropdown triggers are "current" when one of their children is.
     * Anchor links (#kontakt, #galeria) only exist on the homepage, so
     * "current" for them just means "on the homepage" — no scroll-spy.
     */
    public function activityLabel(): string
    {
        return (string) ($this->label ?? ('#' . $this->getKey()));
    }

    public function isCurrent(): bool
    {
        if ($this->type === 'projects') {
            return request()->routeIs(['projects.index', 'projects.show', 'categories.show']);
        }

        if ($this->type === 'pages') {
            return request()->routeIs('page.show');
        }

        if ($this->type === 'dropdown') {
            return $this->children->contains(fn (NavItem $child) => $child->isCurrent());
        }

        if (str_starts_with($this->url, '#')) {
            return request()->routeIs('home');
        }

        if (Str::startsWith($this->url, ['http://', 'https://'])) {
            return false;
        }

        $path = ltrim(strtok($this->url, '#'), '/');

        if ($path === '') {
            return request()->is('/');
        }

        return request()->is($path) || request()->is($path.'/*');
    }
}
