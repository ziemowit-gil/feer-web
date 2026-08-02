<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Bazowy kontroler aplikacji z pomocniczą metodą do weryfikacji podpisanego podglądu szkicu.
 *
 * Metody: isPreviewRequest().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
abstract class Controller
{
    /**
     * Czy żądanie jest prawidłowym podglądem wersji roboczej — tzn. ma
     * flagę `preview` oraz ważny podpis wygenerowany przez HasPreviewLink.
     */
    protected function isPreviewRequest(Request $request): bool
    {
        return $request->boolean('preview') && $request->hasValidSignature();
    }
}
