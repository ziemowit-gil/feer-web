<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BannerZone extends Model
{
    protected $fillable = ['slug', 'label', 'description', 'max_concurrent'];

    protected $casts = [
        'max_concurrent' => 'integer',
    ];

    public function banners(): BelongsToMany
    {
        return $this->belongsToMany(Banner::class, 'banner_zone_banner')
            ->withPivot('priority')
            ->withTimestamps();
    }
}
