<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class HeroSlide extends Model implements HasMedia
{
    use InteractsWithMedia;
    use \App\Models\Concerns\BelongsToSite;

    protected $fillable = ['site_id', 'slider_id', 'title', 'text', 'button_label', 'button_url', 'button2_label', 'button2_url', 'image_alt', 'order', 'duration', 'starts_at', 'ends_at'];

    protected $casts = ['duration' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];

    /**
     * Czy slajd jest teraz w terminie wyświetlania (puste daty = bez ograniczenia).
     * Sprawdzane na kolekcji, nie w zapytaniu — działa także, zanim na serwerze uruchomiono migrację z kolumnami dat.
     */
    public function isVisibleNow(): bool
    {
        $now = now();

        return ($this->starts_at === null || $this->starts_at->lte($now))
            && ($this->ends_at === null || $this->ends_at->gte($now));
    }

    /** Stan terminu do panelu: null (zawsze), 'scheduled' (jeszcze nie), 'active', 'expired'. */
    public function scheduleState(): ?string
    {
        if ($this->starts_at === null && $this->ends_at === null) {
            return null;
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'scheduled';
        }

        return $this->ends_at && $this->ends_at->isPast() ? 'expired' : 'active';
    }

    /** Czy jest już kolumna drugiego przycisku (migracja 2026_11_23_100000) — push wdraża kod przed migracją. */
    public static function supportsSecondButton(): bool
    {
        return once(fn () => \Illuminate\Support\Facades\Schema::hasColumn('hero_slides', 'button2_label'));
    }

    /** Czy jest już kolumna opisu zdjęcia (migracja 2026_11_23_130000). */
    public static function supportsImageAlt(): bool
    {
        return once(fn () => \Illuminate\Support\Facades\Schema::hasColumn('hero_slides', 'image_alt'));
    }

    public function slider(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Slider::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('webp')->format('webp')->quality(85)->nonQueued();
    }

    protected function imageUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn () => $this->getFirstMedia('image')?->getAvailableUrl(['webp']) ?: null,
        );
    }
}
