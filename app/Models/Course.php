<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Szkolenie lub cykl zajęć z oferty witryny (moduł „Szkolenia i zajęcia”). Konkretne terminy to {@see CourseEdition}.
 */
class Course extends Model
{
    use BelongsToSite;

    public const FORMATS = [
        'stationary' => 'Stacjonarnie',
        'online' => 'Online',
        'hybrid' => 'Hybrydowo',
    ];

    public const CATEGORIES = [
        'tyflo' => 'Tyfloinformatyka',
        'wcag' => 'Dostępność WCAG',
        'programming' => 'Programowanie',
        'm365' => 'Microsoft 365',
        'other' => 'Inne',
    ];

    public const STATUSES = [
        'draft' => 'Szkic',
        'active' => 'Aktywne',
        'finished' => 'Zakończone',
    ];

    protected $fillable = [
        'title', 'slug', 'lead', 'description', 'category', 'format', 'price', 'status', 'is_featured', 'order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_featured' => 'boolean',
    ];

    public function editions(): HasMany
    {
        return $this->hasMany(CourseEdition::class)->orderBy('starts_on');
    }

    /** Widoczne publicznie: aktywne szkolenia (szkice i zakończone są tylko w panelu). */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)->forCurrentSite()->first();
    }

    public function formatLabel(): string
    {
        return self::FORMATS[$this->format] ?? (string) $this->format;
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? (string) $this->category;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    /** Cena do wyświetlenia: „Bezpłatnie” przy braku ceny lub zerze. */
    public function priceLabel(): string
    {
        return (float) $this->price > 0
            ? number_format((float) $this->price, 2, ',', ' ').' zł'
            : 'Bezpłatnie';
    }

    /** Terminy, na które można jeszcze patrzeć na stronie: nie zakończone i z datą startu od dziś. */
    public function upcomingEditions()
    {
        return $this->editions->filter(fn (CourseEdition $e) => $e->isUpcoming());
    }

    /** Najbliższy termin z otwartą rekrutacją (do kafelka na liście). */
    public function nextEdition(): ?CourseEdition
    {
        return $this->upcomingEditions()->first();
    }
}
