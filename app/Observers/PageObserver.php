<?php

namespace App\Observers;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Czyści cache strony po zapisie/usunięciu. Klucze w Page::resolveRouteBinding()
 * i HomeController mają sufiks z ID witryny (page_item_{siteId}_{slug},
 * page_about_motto_{siteId}) — czyścimy je oraz starsze klucze bez sufiksu.
 */
class PageObserver
{
    public function saved(Page $page): void
    {
        $this->forget($page, $page->slug);

        if ($page->wasChanged('slug') && $page->getOriginal('slug')) {
            $this->forget($page, $page->getOriginal('slug'));
        }
    }

    public function deleted(Page $page): void
    {
        $this->forget($page, $page->slug);
    }

    public function restored(Page $page): void
    {
        $this->forget($page, $page->slug);
    }

    private function forget(Page $page, ?string $slug): void
    {
        $siteId = $page->site_id ?? SiteSetting::current()->id;

        foreach ([
            "page_item_{$siteId}_{$slug}", "page_item_{$slug}",
            "page_about_motto_{$siteId}", 'page_about_motto',
            "page_about_first_{$siteId}", 'page_about_first',
        ] as $key) {
            Cache::forget($key);
        }
    }
}
