<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class NavItem extends Model
{
    public const TYPES = [
        'link' => 'Zwykły link',
        'dropdown' => 'Rozwijane menu (własne podpozycje)',
        'projects' => 'Menu projektów (automatyczne z kategorii)',
        'pages' => 'Menu podstron (automatyczne z podstron)',
        'volunteering' => 'Ogłoszenia o wolontariacie',
        'events' => 'Szkolenia i wydarzenia',
        'faq' => 'FAQ (najczęstsze pytania)',
    ];

    /**
     * Where the item renders: the header's main menu, or the footer's link
     * list (which only ever renders items as plain links, regardless of type).
     */
    public const LOCATIONS = [
        'main' => 'Menu główne (nagłówek)',
        'footer' => 'Stopka',
    ];

    protected $fillable = [
        'parent_id', 'label', 'icon', 'url', 'type', 'module', 'location',
        'is_button', 'is_transparent_dropdown', 'is_active', 'order', 'button_color',
    ];

    protected $casts = [
        'is_button' => 'boolean',
        'is_transparent_dropdown' => 'boolean',
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
            ->with('publishedChildren')
            ->first();
    }

    /**
     * Whether this item represents the page currently being viewed.
     * Dropdown triggers are "current" when one of their children is.
     * Anchor links (#kontakt, #galeria) only exist on the homepage, so
     * "current" for them just means "on the homepage" — no scroll-spy.
     */
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
