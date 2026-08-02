<?php

namespace App\Http\Controllers;

use App\Models\Faq;

/**
 * Publiczna strona FAQ — pytania pogrupowane wg kategorii, posortowane wg kolejności.
 *
 * Metody: index().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class FaqController extends Controller
{
    /** Wyświetla stronę FAQ z pytaniami pogrupowanymi wg kategorii. */
    public function index()
    {
        // Grupujemy po kategorii (puste = „Pozostałe"/bez nagłówka), zachowując
        // kolejność zapisaną w panelu.
        $groups = Faq::published()->get()->groupBy(fn (Faq $faq) => $faq->category ?: '');

        return view('faq.index', compact('groups'));
    }
}
