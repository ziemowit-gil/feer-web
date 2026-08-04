<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Attachment extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['label', 'group', 'order'];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile();
    }

    protected function fileUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getFirstMediaUrl('file') ?: null,
        );
    }

    protected function fileExtension(): Attribute
    {
        return Attribute::make(
            get: fn () => strtoupper(pathinfo($this->getFirstMedia('file')?->file_name ?? '', PATHINFO_EXTENSION)),
        );
    }

    protected function fileSize(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getFirstMedia('file')?->human_readable_size,
        );
    }
}
