<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Banner extends Model
{
    protected $fillable = [
        'name', 'type', 'image_path', 'image_alt',
        'link_url', 'link_target', 'html_content',
        'is_active', 'starts_at', 'ends_at', 'conditions',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'starts_at'   => 'datetime',
        'ends_at'     => 'datetime',
        'conditions'  => 'array',
        'impressions' => 'integer',
        'clicks'      => 'integer',
    ];

    public function zones(): BelongsToMany
    {
        return $this->belongsToMany(BannerZone::class)
            ->withPivot('priority')
            ->withTimestamps();
    }

    /** Bannery aktywne w danej strefie i aktualnie w oknie czasowym. */
    public function scopeActiveForZone(Builder $query, string $zone): Builder
    {
        return $query
            ->whereHas('zones', fn (Builder $z) => $z->where('slug', $zone))
            ->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderByPivot('priority', 'desc');
    }

    /** Współczynnik kliknięć (CTR) w procentach. */
    public function ctr(): float
    {
        return $this->impressions > 0
            ? round($this->clicks / $this->impressions * 100, 2)
            : 0.0;
    }

    public function isScheduled(): bool
    {
        return $this->starts_at && $this->starts_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    /** Etykieta statusu do wyświetlenia w adminie. */
    public function statusLabel(): string
    {
        if (! $this->is_active) return 'wyłączony';
        if ($this->isExpired())  return 'wygasły';
        if ($this->isScheduled()) return 'zaplanowany';
        return 'aktywny';
    }

    public function statusColor(): string
    {
        return match ($this->statusLabel()) {
            'aktywny'     => 'green',
            'zaplanowany' => 'blue',
            'wygasły'     => 'gray',
            default       => 'red',
        };
    }
}
