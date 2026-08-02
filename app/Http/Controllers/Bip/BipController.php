<?php

namespace App\Http\Controllers\Bip;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BipDocument;
use App\Models\SiteSetting;

/**
 * Publiczne widoki BIP: lista dokumentów, szczegół dokumentu i rejestr zmian.
 *
 * Metody: index(), show(), changeLog().
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

    /**
     * Publiczny rejestr zmian BIP (§ 14 rozporządzenia MSWiA).
     *
     * Wyświetla paginowaną historię wszystkich operacji (utworzenie, edycja,
     * usunięcie) wykonanych na dokumentach BIP, posortowaną od najnowszych.
     */
    public function changeLog()
    {
        $entries = ActivityLog::where('subject_type', 'BipDocument')
            ->latest()
            ->paginate(50);

        // Mapa id → slug opublikowanych dokumentów (do linków w tabeli).
        $documentMap = BipDocument::withTrashed()
            ->whereIn('id', $entries->pluck('subject_id')->unique())
            ->pluck('slug', 'id');

        return view('bip.changelog', compact('entries', 'documentMap'));
    }
}
