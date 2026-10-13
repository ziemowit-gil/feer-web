<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class News extends Model implements HasMedia
{
    use \App\Models\Concerns\Approvable;
    use \App\Models\Concerns\RedirectsOldSlug;
    use \App\Models\Concerns\BelongsToSite;
    use \App\Models\Concerns\ScopedByEditor;
    use \App\Models\Concerns\HasEtr;
    use \App\Models\Concerns\HasPreviewLink;
    use \App\Models\Concerns\HasRevisions;
    use \App\Models\Concerns\LogsActivity;
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use InteractsWithMedia;
    use Searchable;

    /**
     * Układy zdjęcia na stronie artykułu (pole `article_layout`): wartość =>
     * etykieta, opis i ikona do wyboru w formularzu. Źródło prawdy dla formularza,
     * walidacji i blokowania układów (Ustawienia → Moduły, grupa `news_layouts`).
     */
    public const ARTICLE_LAYOUTS = [
        'default' => ['label' => 'Banner', 'desc' => 'Zdjęcie na górze, pełna szerokość', 'icon' => 'fa-image'],
        'wide'    => ['label' => 'Banner duży', 'desc' => 'Jak banner, ale wyższy', 'icon' => 'fa-panorama'],
        'side'    => ['label' => 'Obok tekstu', 'desc' => 'Zdjęcie po lewej, treść po prawej', 'icon' => 'fa-table-columns'],
        'none'    => ['label' => 'Bez zdjęcia', 'desc' => 'Ukryj zdjęcie nagłówkowe', 'icon' => 'fa-eye-slash'],
        'portal'  => ['label' => 'Portal', 'desc' => 'Portal — treść i kolumna boczna działu', 'icon' => 'fa-newspaper'],
    ];

    /** Typy wpisu (pole `post_type`): własne pola w panelu i własny blok na stronie wpisu. */
    public const POST_TYPES = [
        'standard'     => ['label' => 'Zwykły wpis', 'desc' => 'Artykuł lub aktualność', 'icon' => 'fa-newspaper'],
        'announcement' => ['label' => 'Komunikat', 'desc' => 'Krótki, z datą ważności — potem sam trafia do archiwum', 'icon' => 'fa-bullhorn'],
        'review'       => ['label' => 'Test narzędzia', 'desc' => 'Ocena dostępności, plusy i minusy, wynik audytu', 'icon' => 'fa-clipboard-check'],
    ];

    /** Wynik audytu dostępności w teście narzędzia. */
    public const REVIEW_COMPLIANCE = [
        'full'    => 'Zgodne z WCAG 2.2 AA',
        'partial' => 'Częściowo zgodne z WCAG 2.2 AA',
        'none'    => 'Niezgodne z WCAG 2.2 AA',
    ];

    protected $table = 'news';

    public function toSearchableArray(): array
    {
        return [
            'title'   => $this->title,
            'excerpt' => $this->excerpt,
            'content' => strip_tags((string) $this->content),
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return (bool) $this->is_published && ! $this->is_archived && ! $this->trashed();
    }

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
        'site_id', 'created_by', 'news_category_id', 'project_id', 'title', 'slug', 'excerpt', 'audience', 'accent_color', 'image_alt', 'image_focal_x', 'image_focal_y', 'article_layout', 'post_type', 'type_data', 'expires_at', 'content', 'blocks', 'published_at', 'is_published', 'is_archived', 'is_legacy', 'is_clone', 'cloned_from_id',
        'meta_title', 'meta_description', 'pending_approval', 'submitted_by_id',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'type_data' => 'array',
        'is_published' => 'boolean',
        'is_archived' => 'boolean',
        'is_legacy' => 'boolean',
        'is_clone' => 'boolean',
        'pending_approval' => 'boolean',
        'blocks' => 'array',
    ];

    /**
     * Bloki edytora blokowego są aktualne tylko razem z `content`, który z nich
     * wyrenderowano — zmiana treści gdzie indziej (formularz, edycja na żywo,
     * przywrócenie wersji) unieważnia je, a edytor wczyta wtedy treść jako blok HTML.
     */
    protected static function booted(): void
    {
        static::saving(function (News $news) {
            if ($news->exists && $news->isDirty('content') && ! $news->isDirty('blocks') && $news->blocks !== null) {
                $news->blocks = null;
            }
        });
    }

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

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return parent::resolveRouteBindingQuery($query, $value, $field)->forCurrentSite();
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        $resolveField = $field ?? $this->getRouteKeyName();
        $settings = SiteSetting::current();

        if ($resolveField !== 'slug' || ! $settings->cacheEnabled('news')) {
            return parent::resolveRouteBinding($value, $field);
        }

        $ttl = $settings->cacheTtl('news_item', 3600);
        $cacheKey = "news_item_{$settings->id}_{$value}";

        $result = Cache::remember($cacheKey, $ttl, fn () => parent::resolveRouteBinding($value, $field));

        if (! $result instanceof self && $result !== null) {
            Cache::forget($cacheKey);
            return parent::resolveRouteBinding($value, $field);
        }

        return $result;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where('published_at', '<=', now())
            ->where('is_archived', false)
            // Komunikat po dacie ważności znika z list od razu (archiwizuje go news:archiwizuj-komunikaty).
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function isAnnouncement(): bool
    {
        return $this->post_type === 'announcement';
    }

    public function isReview(): bool
    {
        return $this->post_type === 'review';
    }

    /** Komunikat, którego data ważności minęła. */
    public function isExpired(): bool
    {
        return $this->isAnnouncement() && $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** Dane typu wpisu z oczyszczonymi listami (puste wiersze odrzucone). */
    public function typeData(): array
    {
        $d = (array) ($this->type_data ?? []);
        foreach (['pros', 'cons'] as $list) {
            $d[$list] = array_values(array_filter(array_map('trim', (array) ($d[$list] ?? [])), 'filled'));
        }

        return $d;
    }

    /** Nazwa typu wpisu do wyświetlenia (np. w meta nad tytułem). */
    public function postTypeLabel(): string
    {
        return self::POST_TYPES[$this->post_type ?? 'standard']['label'] ?? self::POST_TYPES['standard']['label'];
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

    /** Publiczny adres — cel przekierowania 301 ze starego adresu po zmianie sluga (RedirectsOldSlug). */
    public function redirectTargetUrl(): ?string
    {
        return route('news.show', $this);
    }
}
