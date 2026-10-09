<?php

namespace App\Http\Controllers;

use App\Models\Page;

/**
 * Publiczna strona „Wyłączenia treści": lista stron, które są tymczasowo niedostępne (wyłączone w panelu),
 * z podaniem powodu/komunikatu i linkiem do strony. Wyłączenie działu obejmuje wszystkie jego podstrony.
 */
class ExclusionsController extends Controller
{
    public function index()
    {
        $pages = Page::forCurrentSite()
            ->where('is_disabled', true)
            ->orderBy('title')
            ->get();

        // Podstrony wyłączonego działu też są niedostępne — pokazujemy je jako „przez dział”.
        $inherited = Page::forCurrentSite()
            ->where('is_disabled', false)
            ->whereNotNull('parent_id')
            ->get()
            ->filter(fn (Page $p) => $p->isDisabledByAncestor())
            ->sortBy('title')
            ->values();

        return view('exclusions.index', compact('pages', 'inherited'));
    }
}
