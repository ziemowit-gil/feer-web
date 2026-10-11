<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PageImage extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['page_id', 'alt', 'caption', 'title', 'text', 'button_label', 'button_url', 'order'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('webp')->format('webp')->quality(85)->nonQueued();
    }

    /** Czy slajd ma treść poza samym zdjęciem (tytuł, tekst lub przycisk). */
    public function hasSlideContent(): bool
    {
        return filled($this->title) || filled($this->text) || (filled($this->button_label) && filled($this->button_url));
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getFirstMedia('image')?->getAvailableUrl(['webp']) ?: null,
        );
    }
}
