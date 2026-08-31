<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ośrodek prowadzony przez KraFOS, prezentowany na mapie (moduł "Mapa pomocy",
 * szablon federation) — nie tylko w Krakowie, ale we wszystkich lokalizacjach,
 * w których federacja faktycznie działa.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class HelpPoint extends Model
{
    public const CATEGORIES = [
        'zywnosc' => 'Żywność',
        'schronienie' => 'Schronienie',
        'poradnictwo' => 'Poradnictwo',
        'zdrowie' => 'Zdrowie',
        'prawo' => 'Pomoc prawna',
        'inne' => 'Inne',
    ];

    public const CATEGORY_ICONS = [
        'zywnosc' => 'fa-utensils',
        'schronienie' => 'fa-house-chimney',
        'poradnictwo' => 'fa-comments',
        'zdrowie' => 'fa-heart-pulse',
        'prawo' => 'fa-scale-balanced',
        'inne' => 'fa-map-pin',
    ];

    protected $fillable = [
        'name', 'category', 'address', 'lat', 'lng', 'phone', 'url', 'description', 'is_published', 'order',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'is_published' => 'boolean',
    ];

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function categoryIcon(): string
    {
        return self::CATEGORY_ICONS[$this->category] ?? 'fa-map-pin';
    }
}
