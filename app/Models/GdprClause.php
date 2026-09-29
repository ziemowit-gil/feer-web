<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Klauzula informacyjna RODO zaimportowana z SZO (App\Services\GdprClauseImporter).
 * Treść, tytuł i adresy nadpisuje każdy import; `is_visible` i `sort_order`
 * ustawia się w panelu i import ich nie rusza.
 */
class GdprClause extends Model
{
    use \App\Models\Concerns\LogsActivity;

    protected $fillable = [
        'slug', 'lang', 'title', 'html', 'version', 'remote_updated_at',
        'url', 'pdf_url', 'is_visible', 'sort_order', 'imported_at',
    ];

    protected $casts = [
        'version' => 'integer',
        'remote_updated_at' => 'datetime',
        'imported_at' => 'datetime',
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function activityLabel(): string
    {
        return (string) ($this->title ?: $this->slug);
    }

    /** Klauzule do pokazania na stronie w danym języku, w ustalonej kolejności. */
    public function scopeListed(Builder $query, string $lang = 'pl'): Builder
    {
        return $query->where('lang', $lang)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('title');
    }
}
