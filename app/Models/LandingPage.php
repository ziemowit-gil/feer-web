<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandingPage extends Model
{
    public const SECTIONS = ['speakers' => 'Prelegenci', 'benefits' => 'Korzyści', 'agenda' => 'Agenda'];

    protected $fillable = [
        'slug', 'title', 'is_published',
        'hero_eyebrow', 'hero_title', 'hero_lead', 'hero_cta_label', 'hero_image_url',
        'event_start', 'event_location',
        'speakers', 'benefits', 'agenda', 'section_order',
        'form_title', 'form_intro', 'form_success', 'form_consent_label',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'event_start' => 'datetime',
        'speakers' => 'array',
        'benefits' => 'array',
        'agenda' => 'array',
        'section_order' => 'array',
    ];

    public function registrations(): HasMany
    {
        return $this->hasMany(WebinarRegistration::class);
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    /** Środkowe sekcje w zapisanej kolejności; puste pomijane w widoku. */
    public function orderedSections(): array
    {
        $defined = array_keys(self::SECTIONS);
        $saved = array_values(array_intersect($this->section_order ?? [], $defined));

        return array_values(array_unique(array_merge($saved, $defined)));
    }

    /** Wpisy danej sekcji jako czysta lista tablic (odfiltrowane puste). */
    public function items(string $section): array
    {
        return collect($this->{$section} ?? [])
            ->filter(fn ($row) => collect($row)->filter()->isNotEmpty())
            ->values()
            ->all();
    }

    public function eventLabel(): ?string
    {
        return $this->event_start?->locale('pl')->isoFormat('D MMMM YYYY, HH:mm');
    }
}
