<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * „FEER Paski": pasek z tytułem, tekstem i do dwóch przycisków. Wstawiany na stronie głównej (szablon FEER) w wybranym
 * miejscu albo w treści dowolnej strony skrótem [pasek:ID].
 */
class FeerBand extends Model implements HasMedia
{
    use InteractsWithMedia;
    use \App\Models\Concerns\BelongsToSite;

    protected $fillable = [
        'site_id', 'title', 'text', 'button_label', 'button_url', 'button2_label', 'button2_url',
        'image_alt', 'style', 'placement', 'is_active', 'order',
    ];

    protected $casts = ['is_active' => 'boolean', 'order' => 'integer'];

    /** Style pasków (kontrast tekstu ≥ 4,5:1 w każdym: biały na #1456CC, biały na #1D1D1A, ink na #E8F0FF). */
    public const STYLES = [
        'brand' => 'Niebieski (kolor marki)',
        'dark'  => 'Ciemny (grafit)',
        'light' => 'Jasny (delikatny niebieski)',
    ];

    /** Miejsca: strona główna szablonu FEER, wszystkie podstrony (każdy szablon); „shortcode" = tylko jako [pasek:ID] w treści. */
    public const PLACEMENTS = [
        'after_hero'      => 'Strona główna — pod slajderem',
        'after_shortcuts' => 'Strona główna — pod „Na skróty"',
        'after_news'      => 'Strona główna — po aktualnościach',
        'after_trainings' => 'Strona główna — po szkoleniach',
        'after_projects'  => 'Strona główna — po działaniach',
        'end'             => 'Strona główna — na końcu (pod blokiem wsparcia)',
        'site_top'        => 'Wszystkie podstrony — nad treścią',
        'site_bottom'     => 'Wszystkie podstrony — pod treścią (nad stopką)',
        'shortcode'       => 'Tylko jako skrót [pasek:ID] w treści strony',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('webp')->format('webp')->quality(85)->width(900)->nonQueued();
    }

    /** Zdjęcie paska (wgrane, z biblioteki, z działania albo z Unsplash) — null, gdy brak. */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(get: fn () => $this->getFirstMedia('image')?->getAvailableUrl(['webp']) ?: null);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function hasButton(): bool
    {
        return filled($this->button_label) && filled($this->button_url);
    }

    public function hasButton2(): bool
    {
        return filled($this->button2_label) && filled($this->button2_url);
    }

    /** Skrót do wklejenia w treść strony. */
    public function shortcode(): string
    {
        return '[pasek:'.$this->id.']';
    }
}
