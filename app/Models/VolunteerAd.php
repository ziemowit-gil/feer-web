<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class VolunteerAd extends Model
{
    /** Tryb działań wolontariatu (pytanie 3: gdzie/jak). */
    public const MODES = [
        'stacjonarnie' => 'Stacjonarnie',
        'zdalnie' => 'Zdalnie',
        'hybrydowo' => 'Hybrydowo',
    ];

    protected $fillable = [
        'title', 'slug', 'lead',
        'q_beneficiaries', 'q_tasks', 'q_mode', 'q_location', 'q_schedule',
        'q_time_commitment', 'q_benefits', 'q_how_to_apply',
        'application_url', 'application_cta_label', 'contact_name', 'contact_email',
        'audience', 'is_published', 'closes_at', 'order', 'archived_at',
    ];

    protected $casts = [
        'q_tasks' => 'array',
        'q_benefits' => 'array',
        'is_published' => 'boolean',
        'closes_at' => 'date',
        'archived_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Opublikowane i nieprzeterminowane — widoczne dla odwiedzających. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(function (Builder $q) {
                $q->whereNull('closes_at')->orWhereDate('closes_at', '>=', now());
            })
            ->orderBy('order')
            ->latest('id');
    }

    /** Czytelna etykieta trybu. */
    public function modeLabel(): string
    {
        return self::MODES[$this->q_mode] ?? $this->q_mode;
    }

    /**
     * Dokąd prowadzi przycisk „Zgłoś się": zewnętrzny formularz, a gdy go nie
     * podano — e-mail kontaktowy ogłoszenia.
     */
    public function applyHref(): ?string
    {
        if (filled($this->application_url)) {
            return $this->application_url;
        }

        return filled($this->contact_email) ? 'mailto:'.$this->contact_email : null;
    }

    /** Czy termin zgłoszeń już minął (do oznaczenia w panelu). */
    public function isClosed(): bool
    {
        return $this->closes_at !== null && $this->closes_at->isPast();
    }
}
