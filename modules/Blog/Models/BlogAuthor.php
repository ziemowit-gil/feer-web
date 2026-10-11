<?php

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Blog\Support\BlogImages;

/**
 * Autor wpisów Wiem FEER. Strona autora: /wiem-feer/autor/{slug}.
 *
 * `user_id` wiąże profil z kontem CMS (bez klucza obcego — inna baza): taki
 * autor jest domyślnie wybrany w formularzu nowego wpisu. Nieaktywny autor
 * znika z list wyboru w panelu, ale jego wpisy i strona działają dalej.
 */
class BlogAuthor extends Model
{
    protected $connection = 'blog';

    protected $fillable = [
        'name', 'slug', 'role', 'bio', 'photo_path', 'photo_alt', 'email', 'show_email',
        'links', 'is_active', 'user_id',
    ];

    protected $casts = [
        'links'      => 'array',
        'is_active'  => 'boolean',
        'show_email' => 'boolean',
        'user_id'    => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Wpisy, w których jest autorem głównym. */
    public function mainArticles(): HasMany
    {
        return $this->hasMany(BlogArticle::class, 'author_id');
    }

    /** Wpisy, w których jest współautorem. */
    public function coAuthoredArticles(): BelongsToMany
    {
        return $this->belongsToMany(BlogArticle::class, 'blog_article_author', 'author_id', 'article_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function url(): string
    {
        return route('blog.author', $this);
    }

    public function photoUrl(): ?string
    {
        return BlogImages::url($this->photo_path);
    }

    /** Tekst alternatywny portretu — domyślnie imię i nazwisko. */
    public function photoAlt(): string
    {
        return trim((string) $this->photo_alt) ?: $this->name;
    }

    /**
     * Linki profilu jako lista [label, url] — tylko poprawne adresy http(s).
     *
     * @return array<int, array{label: string, url: string}>
     */
    public function linkList(): array
    {
        return collect($this->links ?? [])
            ->filter(fn ($l) => is_array($l) && preg_match('~^https?://~i', (string) ($l['url'] ?? '')))
            ->map(fn ($l) => ['label' => trim((string) ($l['label'] ?? '')) ?: (string) parse_url($l['url'], PHP_URL_HOST), 'url' => (string) $l['url']])
            ->values()
            ->all();
    }

    /**
     * Linki z pola tekstowego panelu: po jednym w wierszu, „Etykieta | https://…"
     * albo sam adres.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public static function parseLinks(?string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $text))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->map(function ($line) {
                [$label, $url] = str_contains($line, '|') ? array_map('trim', explode('|', $line, 2)) : ['', $line];

                return ['label' => $label, 'url' => $url];
            })
            ->values()
            ->all();
    }

    /** Odwrotność parseLinks() — do wypełnienia pola w formularzu. */
    public function linksText(): string
    {
        return collect($this->links ?? [])
            ->map(fn ($l) => trim(($l['label'] ?? '') !== '' ? $l['label'] . ' | ' . $l['url'] : (string) ($l['url'] ?? '')))
            ->implode("\n");
    }
}
