<?php

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Temat subskrypcji (edytowalny w panelu). `key` trafia do `subscribers.topics`,
 * formularzy, segmentów i kampanii — dlatego po utworzeniu nie zmienia się.
 */
class NewsletterTopic extends Model
{
    protected $table = 'newsletter_topics';

    protected $fillable = ['site_id', 'key', 'label', 'description', 'icon', 'color', 'news_category_slugs', 'feed_sources', 'is_active', 'is_default', 'order'];

    protected $casts = ['news_category_slugs' => 'array', 'feed_sources' => 'array', 'is_active' => 'boolean', 'is_default' => 'boolean'];

    public const CACHE_KEY = 'newsletter.topics.v1';

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget(self::CACHE_KEY);
        static::saved($flush);
        static::deleted($flush);
    }

    /** @return array<string, string> key => label (aktywne, w kolejności) */
    public static function options(bool $onlyActive = true): array
    {
        $all = Cache::remember(self::CACHE_KEY, 300, fn () => static::query()->orderBy('order')->orderBy('id')->get()->toArray());
        $out = [];
        foreach ($all as $t) {
            if ($onlyActive && ! $t['is_active']) {
                continue;
            }
            $out[$t['key']] = $t['label'];
        }

        return $out;
    }

    public function subscribersCount(): int
    {
        return Subscriber::whereJsonContains('topics', $this->key)->count();
    }

    public function activeSubscribersCount(): int
    {
        return Subscriber::confirmed()->whereJsonContains('topics', $this->key)->count();
    }
}
