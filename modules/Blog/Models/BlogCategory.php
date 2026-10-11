<?php

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kategoria wpisów Wiem FEER (jedna na wpis). Filtr na liście: /wiem-feer?kategoria=slug.
 */
class BlogCategory extends Model
{
    protected $connection = 'blog';

    protected $fillable = ['name', 'slug', 'description', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(BlogArticle::class, 'category_id');
    }

    public function url(): string
    {
        return route('blog.index', ['kategoria' => $this->slug]);
    }
}
