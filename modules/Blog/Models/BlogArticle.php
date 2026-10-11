<?php

namespace Modules\Blog\Models;

use App\Models\Concerns\HasPreviewLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Blog\Support\BlogImages;

class BlogArticle extends Model
{
    use HasPreviewLink;

    protected $connection = 'blog';

    protected function previewRouteName(): string
    {
        return 'blog.show';
    }

    protected function previewRouteParam(): string
    {
        return 'article';
    }

    public const WIP_MODES = [
        'full'   => 'Pełnoekranowy komunikat (ukrywa treść)',
        'notice' => 'Pasek informacyjny (treść pozostaje widoczna)',
    ];

    public const DEFAULT_DISABLED_MESSAGE = 'Ten artykuł jest tymczasowo niedostępny. Zapraszamy wkrótce.';

    public const DEFAULT_WIP_FULL_MESSAGE = 'Ten artykuł jest w przygotowaniu. Pracujemy nad jego treścią — zapraszamy wkrótce.';

    public const DEFAULT_WIP_NOTICE_MESSAGE = 'Wprowadzamy zmiany w tym artykule — nie wszystkie elementy mogą jeszcze działać poprawnie.';

    /** Statusy na liście w panelu (zwykły tekst + filtr ?status=). */
    public const STATUSES = [
        'published' => 'Opublikowany',
        'scheduled' => 'Zaplanowany',
        'draft'     => 'Szkic',
        'disabled'  => 'Wyłączony',
    ];

    /** Średnie tempo czytania (słowa na minutę) do szacowania czasu lektury. */
    public const WORDS_PER_MINUTE = 200;

    protected $fillable = [
        'title', 'slug', 'author_name', 'author_bio', 'excerpt', 'body', 'is_published', 'published_at',
        'is_disabled', 'disabled_message', 'wip_mode', 'wip_message',
        'category_id', 'image_path', 'image_alt', 'image_decorative', 'is_featured',
        'meta_title', 'meta_description', 'author_id',
    ];

    protected $casts = [
        'is_published'     => 'boolean',
        'is_disabled'      => 'boolean',
        'is_featured'      => 'boolean',
        'image_decorative' => 'boolean',
        'published_at'     => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class, 'article_id');
    }

    /** Zatwierdzone komentarze najwyższego poziomu (odpowiedzi redakcji są w `replies`). */
    public function approvedComments(): HasMany
    {
        return $this->comments()->where('is_approved', true)->whereNull('parent_id')->orderBy('created_at');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    /** Autor główny (profil). Stare wpisy mogą mieć tylko tekstowe `author_name`. */
    public function author(): BelongsTo
    {
        return $this->belongsTo(BlogAuthor::class, 'author_id');
    }

    /** Współautorzy w ustalonej kolejności (bez autora głównego). */
    public function coAuthors(): BelongsToMany
    {
        return $this->belongsToMany(BlogAuthor::class, 'blog_article_author', 'article_id', 'author_id')
            ->withPivot('position')
            ->orderBy('blog_article_author.position');
    }

    /**
     * Wszyscy autorzy-profile: główny, potem współautorzy (bez powtórzeń).
     *
     * @return Collection<int, BlogAuthor>
     */
    public function authorProfiles(): Collection
    {
        return collect([$this->author])
            ->concat($this->coAuthors)
            ->filter()
            ->unique('id')
            ->values();
    }

    /** Podpis autorów do tekstu (meta, RSS): profile albo zapasowe `author_name`. */
    public function authorLine(): string
    {
        $profiles = $this->authorProfiles();

        return $profiles->isNotEmpty() ? $profiles->pluck('name')->join(', ', ' i ') : trim((string) $this->author_name);
    }

    /** Wpisy danego autora — jako głównego albo współautora. */
    public function scopeByAuthor(Builder $query, BlogAuthor $author): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('author_id', $author->id)
            ->orWhereHas('coAuthors', fn (Builder $a) => $a->whereKey($author->id)));
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_article_tag', 'article_id', 'tag_id')->orderBy('name');
    }

    /** Wpisy widoczne na listach: opublikowane, z datą nie w przyszłości i niewyłączone. */
    public function scopeListed(Builder $query): Builder
    {
        return $query->published()->where('is_disabled', false);
    }

    /** Kolejność „najnowsze najpierw". */
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('published_at')->orderByDesc('id');
    }

    /** Filtr statusu w panelu — klucze jak w STATUSES. */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return match ($status) {
            'disabled'  => $query->where('is_disabled', true),
            'draft'     => $query->where('is_disabled', false)->where('is_published', false),
            'scheduled' => $query->where('is_disabled', false)->where('is_published', true)->where('published_at', '>', now()),
            'published' => $query->where('is_disabled', false)->published(),
            default     => $query,
        };
    }

    /** Status do panelu: wyłączenie ma pierwszeństwo, potem szkic, zaplanowany, opublikowany. */
    public function status(): string
    {
        return match (true) {
            $this->isDisabled()      => 'disabled',
            ! $this->is_published    => 'draft',
            ! $this->isVisible()     => 'scheduled',
            default                  => 'published',
        };
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status()];
    }

    /** Data wpisu: publikacji, a gdy jej brak — utworzenia. */
    public function publishedDate(): ?Carbon
    {
        return $this->published_at ?? $this->created_at;
    }

    /** Czy pokazać „zaktualizowano" — zmiana co najmniej dzień po dacie wpisu. */
    public function wasUpdated(): bool
    {
        $date = $this->publishedDate();

        return $date && $this->updated_at && $this->updated_at->copy()->startOfDay()->gt($date->copy()->startOfDay());
    }

    /** Szacowany czas czytania w minutach (min. 1). */
    public function readingMinutes(): int
    {
        $text = trim(html_entity_decode(strip_tags((string) $this->body), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $words = $text === '' ? 0 : count(preg_split('/\s+/u', $text) ?: []);

        return max(1, (int) ceil($words / self::WORDS_PER_MINUTE));
    }

    public function hasImage(): bool
    {
        return filled($this->image_path);
    }

    /** Publiczny adres zdjęcia wyróżniającego (dysk `public`) albo null. */
    public function imageUrl(): ?string
    {
        return BlogImages::url($this->image_path);
    }

    /** Tekst alternatywny zdjęcia — pusty, gdy oznaczono je jako dekoracyjne. */
    public function imageAlt(): string
    {
        return $this->image_decorative ? '' : (string) $this->image_alt;
    }

    public function metaTitle(): string
    {
        return trim((string) $this->meta_title) ?: $this->title;
    }

    public function metaDescription(): string
    {
        $custom = trim((string) $this->meta_description);
        if ($custom !== '') {
            return $custom;
        }

        $excerpt = trim((string) $this->excerpt);

        return $excerpt !== ''
            ? $excerpt
            : Str::limit(trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $this->body), ENT_QUOTES | ENT_HTML5, 'UTF-8'))), 155);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(function (Builder $q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function isVisible(): bool
    {
        return $this->is_published && (is_null($this->published_at) || $this->published_at <= now());
    }

    public function isDisabled(): bool
    {
        return (bool) $this->is_disabled;
    }

    public function isWip(): bool
    {
        return array_key_exists((string) $this->wip_mode, self::WIP_MODES);
    }

    public function wipIsFull(): bool
    {
        return $this->wip_mode === 'full';
    }

    public function wipIsNotice(): bool
    {
        return $this->wip_mode === 'notice';
    }

    public function showsPlaceholder(): bool
    {
        return $this->isDisabled() || $this->wipIsFull();
    }

    public function disabledMessage(): string
    {
        return trim((string) $this->disabled_message) ?: self::DEFAULT_DISABLED_MESSAGE;
    }

    public function wipMessage(): string
    {
        $custom = trim((string) $this->wip_message);
        if ($custom !== '') {
            return $custom;
        }

        return $this->wipIsFull() ? self::DEFAULT_WIP_FULL_MESSAGE : self::DEFAULT_WIP_NOTICE_MESSAGE;
    }
}
