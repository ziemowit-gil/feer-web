<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;

/**
 * Przekierowania skrótowe na zewnętrzne profile organizacji (Instagram, Facebook).
 *
 * Metody: instagram(), facebook().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class ShortcutController extends Controller
{

    /** Przekierowuje na profil organizacji w serwisie Instagram. */
    public function instagram()
    {
        return $this->away(SiteSetting::current()->instagram_url);
    }

    /** Przekierowuje na profil organizacji w serwisie Facebook. */
    public function facebook()
    {
        return $this->away(SiteSetting::current()->facebook_url);
    }

    /**
     * Przekieruj na zewnętrzny adres, a gdy go nie ustawiono — na stronę główną.
     */
    private function away(?string $url)
    {
        return filled($url) ? redirect()->away($url) : redirect()->route('home');
    }
}
