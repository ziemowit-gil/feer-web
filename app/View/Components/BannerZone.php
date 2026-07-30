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

        // Nie przypisujemy surowej wartości z cache do typowanej właściwości:
        // uszkodzony/nieaktualny wpis może zdeserializować się do __PHP_Incomplete_Class.
        $cached = Cache::get("banner_zone_{$name}");

        if (! $cached instanceof Collection) {
            $cached = Banner::activeForZone($name)->limit($maxConcurrent)->get();
            Cache::put("banner_zone_{$name}", $cached, now()->addMinutes(10));
        }

        $this->banners = $cached;
    }

    public function render(): View
    {
        return view('components.banner-zone');
    }
}
