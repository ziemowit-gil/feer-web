<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class News extends Model implements HasMedia
{
    use \App\Models\Concerns\Approvable;
    use \App\Models\Concerns\HasEtr;
    use \App\Models\Concerns\HasPreviewLink;
    use \App\Models\Concerns\HasRevisions;
    use \App\Models\Concerns\LogsActivity;
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use InteractsWithMedia;

    protected $table = 'news';

    public function revisionFields(): array
    {
        return ['title', 'slug', 'excerpt', 'content', 'meta_title', 'meta_description'];
    }

    protected function previewRouteName(): string
    {
        return 'news.show';
    }

    protected function previewRouteParam(): string
    {
        return 'news';
    }

    protected $fillable = [
        'news_category_id', 'project_id', 'title', 'slug', 'excerpt', 'audience', 'accent_color', 'image_alt', 'content', 'published_at', 'is_published', 'is_featured', 'is_archived', 'is_clone', 'cloned_from_id',
        'meta_title', 'meta_description', 'pending_approval', 'submitted_by_id',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_published' => 'boolean',
        'is_archived' => 'boolean',
        'is_featured' => 'boolean',
        'is_clone' => 'boolean',
        'pending_approval' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(NewsCategory::class, 'news_category_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->orderBy('order');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function clones(): HasMany
    {
        return $this->hasMany(self::class, 'cloned_from_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->where('published_at', '<=', now());
    }

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

    /**
     * The news image, or the site-wide default news image when this item has
     * no photo of its own.
     */
    public function imageUrlOrDefault(): ?string
    {
        return $this->image_url ?: SiteSetting::current()->newsDefaultImageUrl();
    }

    protected function imageWidth(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getFirstMedia('image')?->getCustomProperty('width'),
        );
    }

    protected function imageHeight(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getFirstMedia('image')?->getCustomProperty('height'),
        );
    }

    /**
     * Media library doesn't extract image dimensions on its own, so we read
     * them once after upload and cache them as custom properties on the media.
     */
    public function refreshImageDimensions(): void
    {
        $media = $this->getFirstMedia('image');

        if (! $media) {
            return;
        }

        $size = @getimagesize($media->getPath());

        if ($size) {
            $media->setCustomProperty('width', $size[0]);
            $media->setCustomProperty('height', $size[1]);
            $media->save();
        }
    }
}
