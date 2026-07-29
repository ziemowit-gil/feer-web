<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Project extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'category_id', 'title', 'slug', 'excerpt', 'for_whom', 'audience', 'accent_color', 'since', 'image_alt', 'content', 'why', 'outcomes', 'is_published', 'is_completed', 'is_paid', 'pricing', 'order',
        'meta_title', 'meta_description',
        'coordinator_name', 'coordinator_email', 'coordinator_phone', 'is_featured_contact', 'show_coordinator',
        'custom_sections', 'sections_as_tabs', 'show_legacy_box', 'legacy_url',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_completed' => 'boolean',
        'is_paid' => 'boolean',
        'pricing' => 'array',
        'is_featured_contact' => 'boolean',
        'show_coordinator' => 'boolean',
        'show_legacy_box' => 'boolean',
        'sections_as_tabs' => 'boolean',
        'custom_sections' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    /**
     * Published subpages attached to this project, linked from the project page.
     */
    public function publishedPages(): HasMany
    {
        return $this->pages()->where('is_published', true)->orderBy('order')->orderBy('title');
    }

    /**
     * Published news pinned to this project, newest first — shown as the
     * "Aktualności" section on the project page.
     */
    public function publishedNews(): HasMany
    {
        return $this->hasMany(News::class)
            ->where('is_published', true)
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getFirstMediaUrl('image') ?: null,
        );
    }

    /**
     * The e-mail to show for this project: its coordinator's if one is set,
     * otherwise the site's general contact address.
     */
    public function contactEmail(): string
    {
        return $this->coordinator_email ?: SiteSetting::current()->contact_email;
    }

    /**
     * Whether the coordinator block should be shown for this project: it needs
     * the per-project toggle on AND the site-wide master switch on.
     */
    public function showsCoordinator(): bool
    {
        return $this->show_coordinator && SiteSetting::current()->show_coordinators;
    }
}
