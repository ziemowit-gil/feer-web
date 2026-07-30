<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\URL;

/**
 * Nadaje modelowi treści (Page/News/BlogArticle) możliwość wygenerowania
 * podpisanego linku do podglądu wersji roboczej — treść niepublikowaną można
 * obejrzeć na docelowej trasie publicznej bez wcześniejszej publikacji.
 *
 * Wymaga, aby model definiował trasę `show` z parametrem po slugu.
 */
trait HasPreviewLink
{
    /** Podpisany, wygasający link do podglądu tej treści przed publikacją. */
    public function previewUrl(int $days = 14): string
    {
        return URL::temporarySignedRoute(
            $this->previewRouteName(),
            now()->addDays($days),
            [$this->previewRouteParam() => $this->slug, 'preview' => 1],
        );
    }

    /** Nazwana trasa publicznego widoku treści (np. „news.show"). */
    abstract protected function previewRouteName(): string;

    /** Nazwa parametru trasy przenoszącego slug (np. „news"). */
    abstract protected function previewRouteParam(): string;
}
