<?php

declare(strict_types=1);

namespace Modules\Newsletter\Services;

use Carbon\CarbonInterface;

/** Jednolity element treści dynamicznej (aktualność, wydarzenie, artykuł, materiał). */
final class FeedItem
{
    public function __construct(
        public readonly string $source,
        public readonly int $id,
        public readonly string $title,
        public readonly string $excerpt,
        public readonly string $url,
        public readonly ?string $imageUrl,
        public readonly string $imageAlt,
        public readonly ?CarbonInterface $publishedAt,
        public readonly ?string $category,
        public readonly ?string $categorySlug = null,
        public readonly ?string $categoryColor = null,
        public readonly ?string $topic = null,
    ) {}
}
