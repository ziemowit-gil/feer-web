<?php

namespace App\Http\Controllers;

use App\Models\VolunteerAd;

/**
 * Publiczna lista aktywnych ogłoszeń wolontariackich i widok szczegółów ogłoszenia.
 *
 * Metody: index(), show().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class VolunteerController extends Controller
{
    /** Wyświetla listę aktywnych ogłoszeń wolontariackich. */
    public function index()
    {
        // Szablon FEER ma własny układ listy ogłoszeń.
        $view = \App\Models\SiteSetting::current()->site_template === 'feer' ? 'volunteer.index-feer' : 'volunteer.index';

        return view($view, [
            'ads' => VolunteerAd::active()->get(),
        ]);
    }

    /** Wyświetla stronę szczegółów opublikowanego i aktywnego (nie zakończonego) ogłoszenia wolontariackiego. */
    public function show(VolunteerAd $ad)
    {
        // Nieopublikowane/przeterminowane ogłoszenia nie są publicznie dostępne.
        abort_unless($ad->is_published && ! $ad->isClosed(), 404);

        $view = \App\Models\SiteSetting::current()->site_template === 'feer' ? 'volunteer.show-feer' : 'volunteer.show';

        return view($view, compact('ad'));
    }
}
