<?php

namespace App\Http\Controllers\Bip;

use App\Http\Controllers\Controller;
use App\Models\BipDocument;
use App\Models\SiteSetting;

/**
 * Publiczne widoki BIP: lista dokumentów i szczegół dokumentu.
 *
 * Metody: index(), show().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class BipController extends Controller
{
    /**
     * Strona /bip: informacja o BIP oraz — gdy moduł włączony —
     * lista dokumentów publicznych pogrupowana według kategorii.
     */
    public function index()
    {
        $settings = SiteSetting::current();

        $documents = $settings->isModuleEnabled('bip')
            ? BipDocument::published()
                ->orderBy('category')
                ->orderBy('order')
                ->orderBy('title')
                ->with(['creator', 'updater', 'media'])
                ->get()
                ->groupBy('category')
            : collect();

        return view('bip', compact('documents'));
    }

    /** Wyświetla treść pojedynczego dokumentu BIP. */
    public function show(BipDocument $bipDocument)
    {
        abort_unless($bipDocument->is_published, 404);

        $bipDocument->load(['creator', 'updater', 'media']);

        return view('bip.show', compact('bipDocument'));
    }
}
