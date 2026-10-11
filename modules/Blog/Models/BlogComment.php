<?php

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogComment extends Model
{
    protected $connection = 'blog';

    protected $fillable = [
        'article_id', 'author_name', 'email', 'body', 'is_approved',
        'parent_id', 'is_staff_reply', 'user_id',
    ];

    protected $casts = [
        'is_approved'    => 'boolean',
        'is_staff_reply' => 'boolean',
    ];

    /** Podpis odpowiedzi redakcji pokazywany przy komentarzu. */
    public const STAFF_LABEL = 'Odpowiedź redakcji';

    public function article(): BelongsTo
    {
        return $this->belongsTo(BlogArticle::class, 'article_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** Zatwierdzone odpowiedzi (redakcji) pod komentarzem. */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->where('is_approved', true)->orderBy('created_at');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_approved', false);
    }
}
