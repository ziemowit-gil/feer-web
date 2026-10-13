<?php

namespace App\Models\Concerns;

use App\Support\SlugRedirects;

/**
 * Po zmianie sluga opublikowanej treści tworzy przekierowanie 301 ze starego adresu (App\Support\SlugRedirects).
 * Model podaje swój publiczny adres w redirectTargetUrl().
 */
trait RedirectsOldSlug
{
    public static function bootRedirectsOldSlug(): void
    {
        static::updated(fn ($model) => SlugRedirects::afterUpdate($model));
    }

    abstract public function redirectTargetUrl(): ?string;
}
