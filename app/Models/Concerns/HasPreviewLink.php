<?php

namespace App\Models\Concerns;

/**
 * Nadaje modelowi treści (Page/News/BlogArticle) możliwość wygenerowania
 * podpisanego linku do podglądu wersji roboczej — treść niepublikowaną można
 * obejrzeć na docelowej trasie publicznej bez wcześniejszej publikacji.
 *
 * Wymaga, aby model definiował trasę `show` z parametrem po slugu.
 */
trait HasPreviewLink
{
    use BuildsSitePublicUrls;

    /** Podpisany, wygasający link do podglądu tej treści przed publikacją. */
    public function previewUrl(int $days = 14): string
    {
        return $this->siteAwareRoute(
            $this->previewRouteName(),
            [$this->previewRouteParam() => $this->slug, 'preview' => 1],
            now()->addDays($days),
        );
    }

    /** Nazwana trasa publicznego widoku treści (np. „news.show"). */
    abstract protected function previewRouteName(): string;

    /** Nazwa parametru trasy przenoszącego slug (np. „news"). */
    abstract protected function previewRouteParam(): string;
}
