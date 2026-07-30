<?php

namespace App\View\Components;

use App\Models\Banner;
use App\Models\BannerZone as BannerZoneModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;
use Illuminate\View\View;

class BannerZone extends Component
{
    public Collection $banners;

    public function __construct(public string $name)
    {
        $maxConcurrent = Cache::remember(
            "banner_zone_max_{$name}",
            now()->addHour(),
            fn () => BannerZoneModel::where('slug', $name)->value('max_concurrent') ?? 1
        );

        $this->banners = Cache::remember(
            "banner_zone_{$name}",
            now()->addMinutes(10),
            fn () => Banner::activeForZone($name)->limit($maxConcurrent)->get()
        );
    }

    public function render(): View
    {
        return view('components.banner-zone');
    }
}
