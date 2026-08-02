<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;

/**
 * Wyświetla publiczną stronę „Deklaracja dostępności" z formularzem zgłaszania barier.
 *
 * Metody: show().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class AccessibilityController extends Controller
{
    /** Strona „Deklaracja dostępności" wraz z formularzem zgłaszania barier. */
    public function show()
    {
        return view('accessibility.show', [
            'settings' => SiteSetting::current(),
        ]);
    }
}
