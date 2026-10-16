<?php

namespace App\Support;

use App\Models\News;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Support\Str;

/**
 * Unikalne adresy (slugi) stron i aktualności — wspólne dla formularzy i edytora blokowego.
 * Zajęte są też slugi w koszu (constraint UNIQUE w bazie), a dla stron dodatkowo
 * adresy zarezerwowane i slugi sub-witryn.
 */
class UniqueSlug
{
    public static function page(string $source, ?int $ignoreId = null): string
    {
        return static::make(Str::slug($source) ?: 'strona', fn (string $c) => in_array($c, Page::RESERVED_SLUGS, true)
            || Page::withTrashed()->where('slug', $c)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()
            || SiteSetting::whereNotNull('slug')->where('slug', $c)->exists());
    }

    public static function news(string $source, ?int $ignoreId = null): string
    {
        return static::make(Str::slug($source) ?: 'news', fn (string $c) => News::withTrashed()->where('slug', $c)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists());
    }

    private static function make(string $base, \Closure $isTaken): string
    {
        $slug = $base;
        for ($suffix = 2; $isTaken($slug); $suffix++) {
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }
}
