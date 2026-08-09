<?php

namespace App\Observers;

use App\Models\News;
use Illuminate\Support\Facades\Cache;

class NewsObserver
{
    public function saved(News $news): void
    {
        Cache::forget("news_item_{$news->slug}");
        Cache::forget('news_categories');
        Cache::forget('news_latest3');

        if ($news->wasChanged('slug') && $news->getOriginal('slug')) {
            Cache::forget('news_item_' . $news->getOriginal('slug'));
        }
    }

    public function deleted(News $news): void
    {
        Cache::forget("news_item_{$news->slug}");
        Cache::forget('news_latest3');
    }

    public function restored(News $news): void
    {
        Cache::forget("news_item_{$news->slug}");
        Cache::forget('news_latest3');
    }
}
