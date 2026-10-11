<?php

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Tag wpisu Wiem FEER. Tworzony „w locie" z pola „Tagi" (lista po przecinku)
 * w formularzu artykułu. Filtr na liście: /wiem-feer?tag=slug.
 */
class BlogTag extends Model
{
    protected $connection = 'blog';

    protected $fillable = ['name', 'slug'];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(BlogArticle::class, 'blog_article_tag', 'tag_id', 'article_id');
    }

    public function url(): string
    {
        return route('blog.index', ['tag' => $this->slug]);
    }

    /**
     * Zamienia listę nazw po przecinku na identyfikatory tagów (tworząc brakujące).
     * Duplikaty (także różniące się wielkością liter) są pomijane.
     *
     * @return array<int, int>
     */
    public static function idsFromList(?string $list): array
    {
        return Collection::make(explode(',', (string) $list))
            ->map(fn ($name) => Str::limit(trim(preg_replace('/\s+/u', ' ', $name)), 60, ''))
            ->filter(fn ($name) => $name !== '' && Str::slug($name) !== '')
            ->unique(fn ($name) => Str::slug($name))
            ->map(fn ($name) => static::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])->id)
            ->values()
            ->all();
    }
}
