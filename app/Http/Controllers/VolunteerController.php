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
        return view('volunteer.index', [
            'ads' => VolunteerAd::active()->get(),
        ]);
    }

    /** Wyświetla stronę szczegółów opublikowanego i aktywnego (nie zakończonego) ogłoszenia wolontariackiego. */
    public function show(VolunteerAd $ad)
    {
        // Nieopublikowane/przeterminowane ogłoszenia nie są publicznie dostępne.
        abort_unless($ad->is_published && ! $ad->isClosed(), 404);

        return view('volunteer.show', compact('ad'));
    }
}
