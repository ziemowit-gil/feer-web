<?php

namespace App\Http\Controllers;

use App\Models\VolunteerAd;

class VolunteerController extends Controller
{
    public function index()
    {
        return view('volunteer.index', [
            'ads' => VolunteerAd::active()->get(),
        ]);
    }

    public function show(VolunteerAd $ad)
    {
        // Nieopublikowane/przeterminowane ogłoszenia nie są publicznie dostępne.
        abort_unless($ad->is_published && ! $ad->isClosed(), 404);

        return view('volunteer.show', compact('ad'));
    }
}
