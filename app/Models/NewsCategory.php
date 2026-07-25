<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsCategory extends Model
{
    protected $fillable = ['name', 'slug', 'order', 'color'];

    public function news(): HasMany
    {
        return $this->hasMany(News::class);
    }

    /**
     * Falls back to the site's brand color when the category has none of
     * its own set, so existing categories keep working without migration.
     */
    public function badgeColor(): string
    {
        return $this->color ?: SiteSetting::current()->brand_color;
    }
}
