<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\Attachment;

class JobOffer extends Model
{
    use \App\Models\Concerns\LogsActivity;

    public const TYPES = [
        'pelny_etat'  => 'Pełny etat',
        'pol_etatu'   => 'Pół etatu',
        'b2b'         => 'B2B',
        'uod'         => 'Umowa o dzieło / zlecenie',
        'praktyka'    => 'Praktyka / staż',
    ];

    public const MODES = [
        'stacjonarnie' => 'Stacjonarnie',
        'zdalnie'      => 'Zdalnie',
        'hybrydowo'    => 'Hybrydowo',
    ];

    /** Typy umów z polami specyficznymi dla UOP/zlecenia. */
    public const UOP_TYPES = ['pelny_etat', 'pol_etatu'];
    public const CONTRACT_DURATION_TYPES = [
        'nieokreslony' => 'Czas nieokreślony',
        'okreslony'    => 'Czas określony',
    ];

    protected $fillable = [
        'title', 'slug', 'lead',
        'job_type', 'mode', 'location', 'salary_range', 'hourly_rate',
        'contract_duration_type', 'contract_duration', 'start_date',
        'duties', 'requirements', 'nice_to_have', 'benefits',
        'contact_name', 'contact_email',
        'application_url', 'application_cta_label', 'apply_note', 'grant_condition',
        'offer_deadline', 'task_period',
        'audience', 'is_published', 'closes_at', 'order', 'archived_at',
    ];

    protected $casts = [
        'duties'        => 'array',
        'requirements'  => 'array',
        'nice_to_have'  => 'array',
        'benefits'      => 'array',
        'is_published' => 'boolean',
        'grant_condition' => 'boolean',
        'offer_deadline' => 'date',
        'closes_at'    => 'date',
        'start_date'   => 'date',
        'archived_at'  => 'datetime',
    ];

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->orderBy('order');
    }

    public function isUop(): bool
    {
        return in_array($this->job_type, self::UOP_TYPES, true);
    }

    public function isZlecenie(): bool
    {
        return $this->job_type === 'uod';
    }

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

    public function jobTypeLabel(): string
    {
        return self::TYPES[$this->job_type] ?? $this->job_type;
    }

    public function modeLabel(): string
    {
        return self::MODES[$this->mode] ?? $this->mode;
    }

    public function applyHref(): ?string
    {
        if (filled($this->application_url)) {
            return $this->application_url;
        }

        return filled($this->contact_email) ? 'mailto:'.$this->contact_email : null;
    }

    public function isClosed(): bool
    {
        return $this->closes_at !== null && $this->closes_at->isPast();
    }
}
