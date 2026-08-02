<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;

/**
 * Przekierowania skrótowe na zewnętrzne profile organizacji (BIP, Instagram, Facebook).
 *
 * Metody: bip(), instagram(), facebook().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class ShortcutController extends Controller
{
    /**
     * Strona-pośrednik /bip: informacja, dlaczego BIP prowadzimy osobno, oraz
     * wyraźny odnośnik do właściwego Biuletynu. Bez automatycznego
     * przekierowania (WCAG 2.2.1) — użytkownik klika sam.
     */
    public function bip()
    {
        return view('bip');
    }

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
