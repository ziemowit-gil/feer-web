<?php

namespace App\Http\Controllers;

use App\Models\HelpPoint;

/**
 * Publiczna mapa pomocy (moduł "help_map") — punkty wsparcia dla mieszkańców
 * na interaktywnej mapie (Leaflet).
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class HelpMapController extends Controller
{
    public function index()
    {
        $points = HelpPoint::where('is_published', true)->orderBy('order')->orderBy('name')->get();

        return view('templates.federation.help-map', compact('points'));
    }
}
