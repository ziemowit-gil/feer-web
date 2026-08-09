<?php

namespace App\Observers;

use App\Models\NewsCategory;
use Illuminate\Support\Facades\Cache;

class NewsCategoryObserver
{
    public function saved(NewsCategory $category): void
    {
        Cache::forget('news_categories');
    }

    public function deleted(NewsCategory $category): void
    {
        Cache::forget('news_categories');
    }
}
