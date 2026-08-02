<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejestruje statystyki banerów: wyświetlenia (próbkowanie 1:5) i kliknięcia.
 *
 * Metody: impression(), click().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class BannerTrackingController extends Controller
{
    /** Rejestruje wyświetlenie bannera (losowe próbkowanie 1:5, bez bazy przy każdym pageview). */
    public function impression(Banner $banner): Response
    {
        if (! $banner->is_active) {
            return response()->noContent();
        }

        if (random_int(1, 5) === 1) {
            Banner::where('id', $banner->id)->increment('impressions', 5);
        }

        return response()->noContent();
    }

    /** Rejestruje kliknięcie i przekierowuje na docelowy URL. */
    public function click(Banner $banner): RedirectResponse
    {
        if ($banner->link_url) {
            Banner::where('id', $banner->id)->increment('clicks');
        }

        return redirect()->away($banner->link_url ?? '/');
    }
}
