<?php

namespace App\Models\Concerns;

use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Scopes a content model (news, pages, gallery images, events, partners,
 * quick actions, polls, hero slides) to the site it belongs to — the main
 * federation site or one of its sub-sites (see SiteSetting::current()).
 *
 * New records are stamped with the currently active site automatically;
 * public/admin queries opt in with `forCurrentSite()` to only see the
 * content that belongs to whichever site is active for the request.
 */
trait BelongsToSite
{
    public static function bootBelongsToSite(): void
    {
        static::creating(function ($model) {
            if (! $model->site_id) {
                $model->site_id = SiteSetting::current()->id;
            }
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(SiteSetting::class, 'site_id');
    }

    public function scopeForCurrentSite(Builder $query): Builder
    {
        return $query->where($this->getTable().'.site_id', SiteSetting::current()->id);
    }
}
