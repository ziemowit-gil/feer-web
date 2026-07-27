<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;

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
