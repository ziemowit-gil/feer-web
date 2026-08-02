<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Wyświetla publiczną stronę informacyjną o standardzie ETR (Easy-To-Read).
 *
 * Metody: about().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class EtrController extends Controller
{
    /** Wyświetla stronę informacyjną o standardzie ETR (Easy-To-Read). */
    public function about(): View
    {
        return view('etr.about');
    }
}
