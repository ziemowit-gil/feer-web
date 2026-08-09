<?php

namespace Modules\Blog\Models;

use App\Models\Concerns\HasPreviewLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected $fillable = [
        'title', 'slug', 'author_name', 'excerpt', 'body', 'is_published', 'published_at',
        'is_disabled', 'disabled_message', 'wip_mode', 'wip_message',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_disabled'  => 'boolean',
        'published_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class, 'article_id');
    }

    public function approvedComments(): HasMany
    {
        return $this->comments()->where('is_approved', true)->orderBy('created_at');
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
