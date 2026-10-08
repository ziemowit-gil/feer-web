<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Project extends Model implements HasMedia
{
    use \App\Models\Concerns\Approvable;
    use \App\Models\Concerns\BelongsToSite;
    use \App\Models\Concerns\ScopedByEditor;
    use \App\Models\Concerns\HasRevisions;
    use \App\Models\Concerns\LogsActivity;
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use InteractsWithMedia;
    use Searchable;

    public function toSearchableArray(): array
    {
        return [
            'title'    => $this->title,
            'excerpt'  => $this->excerpt,
            'for_whom' => $this->for_whom,
            'content'  => strip_tags((string) $this->content),
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return (bool) $this->is_published && ! $this->trashed();
    }

    public function revisionFields(): array
    {
        return ['title', 'slug', 'excerpt', 'for_whom', 'content', 'why', 'outcomes', 'meta_title', 'meta_description'];
    }

    protected $fillable = [
        'site_id', 'created_by', 'category_id', 'title', 'slug', 'excerpt', 'for_whom', 'audience', 'accent_color', 'since', 'image_alt', 'content', 'why', 'outcomes', 'is_published', 'is_completed', 'completed_at', 'is_paid', 'pricing', 'order',
        'meta_title', 'meta_description', 'pending_approval', 'submitted_by_id',
        'coordinator_name', 'coordinator_email', 'coordinator_phone', 'is_featured_contact', 'show_coordinator',
        'custom_sections', 'sections_as_tabs', 'sections_nav', 'sidebar_buttons', 'sidebar_note', 'show_legacy_box', 'legacy_url',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'pending_approval' => 'boolean',
        'is_completed' => 'boolean',
        'completed_at' => 'date',
        'is_paid' => 'boolean',
        'pricing' => 'array',
        'is_featured_contact' => 'boolean',
        'show_coordinator' => 'boolean',
        'show_legacy_box' => 'boolean',
        'sections_as_tabs' => 'boolean',
        'custom_sections' => 'array',
        'sidebar_buttons' => 'array',
    ];

    /** Mikropis do list projektów: zajawka, a gdy jej brak — „dla kogo”, a potem początek treści. */
    public function teaser(int $limit = 140): ?string
    {
        $text = trim((string) ($this->excerpt ?: $this->for_whom));
        if ($text === '') {
            $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />', '</li>'], ' ', (string) $this->content)), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        }

        return $text === '' ? null : \Illuminate\Support\Str::limit(strip_tags($text), $limit);
    }

    /** Zakres edytora: projekty tylko z kategorii wskazanych w grupie użytkownika. */
    public function constrainForEditor($query, User $user): void
    {
        if (($ids = $user->allowedProjectCategoryIds()) !== null) {
            $query->whereIn($this->getTable().'.category_id', $ids);
        }
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return parent::resolveRouteBindingQuery($query, $value, $field)->forCurrentSite();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function attachments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->orderBy('order');
    }

    /**
     * Published subpages attached to this project, linked from the project page.
     */
    public function publishedPages(): HasMany
    {
        return $this->pages()->where('is_published', true)->orderBy('order')->orderBy('title');
    }

    /** Tryb nawigacji sekcji na stronie projektu: własny wybór projektu, inaczej ustawienie serwisu ('tabs' | 'sidebar'). */
    public function sectionsNavMode(): string
    {
        $mode = $this->sections_nav ?: SiteSetting::current()->project_sections_nav;

        return $mode === 'sidebar' ? 'sidebar' : 'tabs';
    }

    /**
     * Drzewo podstron projektu (dowolna głębokość): korzenie to strony powiązane z projektem,
     * które nie leżą pod inną stroną projektu; potomków dobieramy po parent_id.
     * Każdy węzeł dostaje relację `tree_children`. Dla widoku publicznego tylko opublikowane.
     */
    public function pageTree(bool $publishedOnly = false): \Illuminate\Support\Collection
    {
        $scope = fn ($q) => $publishedOnly ? $q->where('is_published', true) : $q;

        $all = $scope(Page::query())->where('project_id', $this->id)->get()->keyBy('id');
        $frontier = $all->keys()->all();
        for ($guard = 0; $frontier !== [] && $guard < 20; $guard++) {
            $found = $scope(Page::query())->whereIn('parent_id', $frontier)->whereNotIn('id', $all->keys())
                ->where('type', '!=', 'about_person')->get();
            $found->each(fn ($pg) => $all->put($pg->id, $pg));
            $frontier = $found->pluck('id')->all();
        }

        $byParent = $all->sortBy([['order', 'asc'], ['title', 'asc']])->groupBy(fn ($pg) => $all->has($pg->parent_id) ? $pg->parent_id : 0);
        $all->each(fn ($pg) => $pg->setRelation('tree_children', $byParent->get($pg->id, collect())->values()));

        return $byParent->get(0, collect())->values();
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
