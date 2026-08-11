<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class EducationalMaterial extends Model implements HasMedia
{
    use InteractsWithMedia;
    use \App\Models\Concerns\LogsActivity;

    public const TYPES = [
        'video' => 'Nagranie wideo',
        'pdf' => 'Plik PDF',
        'scenariusz' => 'Scenariusz',
    ];

    protected $fillable = [
        'title', 'description', 'target_group', 'type', 'video_url', 'order', 'is_published', 'is_archival', 'is_premium',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_archival' => 'boolean',
        'is_premium' => 'boolean',
    ];

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

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    /**
     * Whether this material is backed by an uploaded file (PDF or scenario)
     * rather than a video link.
     */
    public function hasFile(): bool
    {
        return in_array($this->type, ['pdf', 'scenariusz'], true);
    }

    /**
     * Font Awesome icon class representing this material's type.
     */
    public function typeIcon(): string
    {
        return match ($this->type) {
            'video' => 'fa-video',
            'scenariusz' => 'fa-chalkboard-user',
            default => 'fa-file-pdf',
        };
    }

    /**
     * The YouTube video id embedded in the video URL, if it is a YouTube link.
     */
    public function youtubeId(): ?string
    {
        if (! $this->video_url) {
            return null;
        }

        preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/|live/)|youtu\.be/)([\w-]{11})~', $this->video_url, $matches);

        return $matches[1] ?? null;
    }

    /**
     * A poster image for the video card — YouTube's thumbnail when available,
     * otherwise null so the card falls back to a play-button placeholder.
     */
    public function videoThumbnailUrl(): ?string
    {
        $id = $this->youtubeId();

        return $id ? "https://img.youtube.com/vi/{$id}/hqdefault.jpg" : null;
    }
}
