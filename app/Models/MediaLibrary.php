<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Singleton polymorphic owner for files uploaded directly to the media
 * library/file browser, rather than attached to a specific content record
 * (gallery image, hero slide, project, ...).
 */
class MediaLibrary extends Model implements HasMedia
{
    use InteractsWithMedia;

    public static function instance(): self
    {
        return static::query()->first() ?? static::create();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('files')->withResponsiveImages();
    }

    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('webp')->format('webp')->quality(85)->nonQueued();
        $this->addMediaConversion('thumb')->width(400)->format('webp')->quality(80)->nonQueued();
    }
}
