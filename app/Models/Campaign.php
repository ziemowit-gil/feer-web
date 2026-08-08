<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Campaign extends Model implements HasMedia
{
    use \App\Models\Concerns\LogsActivity;
    use SoftDeletes;
    use InteractsWithMedia;

    protected $fillable = [
        'title', 'slug', 'excerpt', 'content',
        'goal_amount', 'collected_amount', 'donation_url',
        'starts_at', 'ends_at', 'is_published', 'order',
        'meta_title', 'meta_description',
    ];

    protected $casts = [
        'is_published'      => 'boolean',
        'starts_at'         => 'date',
        'ends_at'           => 'date',
        'goal_amount'       => 'integer',
        'collected_amount'  => 'integer',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('webp')->format('webp')->quality(85)->nonQueued();
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getFirstMedia('image')?->getAvailableUrl(['webp']) ?: null,
        );
    }

    /** Procent realizacji celu (0–100). */
    public function progressPercent(): int
    {
        if ($this->goal_amount <= 0) {
            return 0;
        }

        return min(100, (int) round($this->collected_amount / $this->goal_amount * 100));
    }

    /** Czy kampania jest aktywna (opublikowana i w zakresie dat). */
    public function isActive(): bool
    {
        if (! $this->is_published) {
            return false;
        }

        $today = now()->toDateString();

        if ($this->starts_at && $this->starts_at->toDateString() > $today) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->toDateString() < $today) {
            return false;
        }

        return true;
    }

    /** Czy cel został osiągnięty. */
    public function isGoalReached(): bool
    {
        return $this->goal_amount > 0 && $this->collected_amount >= $this->goal_amount;
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
