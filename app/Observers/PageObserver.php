<?php

namespace App\Observers;

use App\Models\Page;
use Illuminate\Support\Facades\Cache;

class PageObserver
{
    public function saved(Page $page): void
    {
        Cache::forget("page_item_{$page->slug}");
        Cache::forget('page_about_motto');
        Cache::forget('page_about_first');

        if ($page->wasChanged('slug') && $page->getOriginal('slug')) {
            Cache::forget('page_item_' . $page->getOriginal('slug'));
        }
    }

    public function deleted(Page $page): void
    {
        Cache::forget("page_item_{$page->slug}");
        Cache::forget('page_about_motto');
        Cache::forget('page_about_first');
    }

    public function restored(Page $page): void
    {
        Cache::forget("page_item_{$page->slug}");
        Cache::forget('page_about_motto');
        Cache::forget('page_about_first');
    }
}
