<?php

namespace App\Observers;

use App\Models\Banner;
use Illuminate\Support\Facades\Cache;

class BannerObserver
{
    public function saved(Banner $banner): void
    {
        $this->clearZoneCaches($banner);
    }

    public function deleted(Banner $banner): void
    {
        $this->clearZoneCaches($banner);
    }

    private function clearZoneCaches(Banner $banner): void
    {
        $banner->loadMissing('zones');
        foreach ($banner->zones as $zone) {
            Cache::forget("banner_zone_{$zone->slug}");
            Cache::forget("banner_zone_max_{$zone->slug}");
        }
    }
}
