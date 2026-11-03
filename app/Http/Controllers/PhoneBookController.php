<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PhoneBookEntry;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

/**
 * Publiczna strona modułu „Książka telefoniczna” (/ksiazka-telefoniczna). Gdy witryna ma własną, opublikowaną
 * podstronę o tym adresie (np. typ strony „Książka telefoniczna”), pokazujemy ją — strona ma pierwszeństwo.
 */
class PhoneBookController extends Controller
{
    public const SLUG = 'ksiazka-telefoniczna';

    public function show(Request $request)
    {
        $page = Page::forCurrentSite()->where('slug', self::SLUG)->where('is_published', true)->first();
        if ($page) {
            return app(PageController::class)->show($request, $page);
        }

        return view('phone-book.show', [
            'contacts' => PhoneBookEntry::listForCurrentSite(),
            'settings' => SiteSetting::current(),
        ]);
    }
}
