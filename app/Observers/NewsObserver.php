<?php

namespace App\Observers;

use App\Models\News;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Czyści cache aktualności po zapisie/usunięciu. Klucze w News::resolveRouteBinding()
 * i HomeController mają sufiks z ID witryny (news_item_{siteId}_{slug},
 * news_latest3_{siteId}) — czyścimy je oraz starsze klucze bez sufiksu.
 */
class NewsObserver
{
    public function saved(News $news): void
    {
        $this->forget($news, $news->slug);

        if ($news->wasChanged('slug') && $news->getOriginal('slug')) {
            $this->forget($news, $news->getOriginal('slug'));
        }
    }

    public function deleted(News $news): void
    {
        $this->forget($news, $news->slug);
    }

    public function restored(News $news): void
    {
        $this->forget($news, $news->slug);
    }

    private function forget(News $news, ?string $slug): void
    {
        $siteId = $news->site_id ?? SiteSetting::current()->id;

        foreach ([
            "news_item_{$siteId}_{$slug}", "news_item_{$slug}",
            "news_latest3_{$siteId}", 'news_latest3',
            "news_categories_{$siteId}", 'news_categories',
        ] as $key) {
            Cache::forget($key);
        }
    }
}
