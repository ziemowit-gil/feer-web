<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Support\CookieInventory;

/** Strona /informacja-o-cookies: jakie pliki cookies i dane w przeglądarce zapisuje strona, z przyciskiem zmiany decyzji. */
class CookieInfoController extends Controller
{
    public function show()
    {
        $site = SiteSetting::current();

        return view('cookies.show', [
            'groups' => CookieInventory::for($site),
            'intro' => $site->cookieInfoText(),
            'privacyUrl' => $site->privacyPolicyUrl(),
        ]);
    }
}
