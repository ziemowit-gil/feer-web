<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Podgląd menu w panelu (Menu nawigacji → „Podgląd menu na stronie”): sam nagłówek serwisu
 * albo sama stopka, w prawdziwym wyglądzie szablonu — bez treści strony głównej.
 */
class NavItemPreviewController extends Controller
{
    public function __invoke(Request $request)
    {
        $location = $request->query('location') === 'footer' ? 'footer' : 'main';

        return response()->view('admin.nav-items.preview', ['menuPreviewLocation' => $location])
            ->header('X-Robots-Tag', 'noindex');
    }
}
