<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Reads the latest posts from a Substack RSS feed. Results are cached so the
 * homepage never blocks on (or hammers) Substack, and any network/parse error
 * degrades gracefully to an empty list.
 */
class SubstackFeed
{
    public static function posts(string $baseUrl, int $limit = 4): array
    {
        $baseUrl = trim($baseUrl);

        if ($baseUrl === '') {
            return [];
        }

        $feedUrl = rtrim($baseUrl, '/').'/feed';

        return Cache::remember('substack_feed:'.md5($feedUrl).":$limit", now()->addHour(), function () use ($feedUrl, $limit) {
            try {
                $response = Http::timeout(8)->retry(1, 200)->get($feedUrl);

                if (! $response->successful()) {
                    return [];
                }

                $xml = @simplexml_load_string($response->body());

                if ($xml === false || ! isset($xml->channel->item)) {
                    return [];
                }

                $posts = [];

                foreach ($xml->channel->item as $item) {
                    $content = (string) $item->children('content', true)->encoded;
                    $description = (string) $item->description;

                    $posts[] = [
                        'title' => trim((string) $item->title),
                        'url' => trim((string) $item->link),
                        'date' => self::parseDate((string) $item->pubDate),
                        'image' => self::firstImage($content ?: $description),
                        'excerpt' => Str::limit(trim(strip_tags($description)), 140),
                    ];

                    if (count($posts) >= $limit) {
                        break;
                    }
                }

                return $posts;
            } catch (\Throwable $e) {
                return [];
            }
        });
    }

    private static function parseDate(string $value): ?Carbon
    {
        try {
            return $value !== '' ? Carbon::parse($value) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function firstImage(string $html): ?string
    {
        if (preg_match('/<img[^>]+src="([^"]+)"/i', $html, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
