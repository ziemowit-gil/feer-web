<?php

namespace App\Http\Controllers\Bip;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\BipDocument;
use App\Models\SiteSetting;

/**
 * Publiczne widoki BIP: lista dokumentów, szczegół dokumentu i rejestr zmian.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class BipController extends Controller
{
    /**
     * Strona /bip. Tryb wbudowany: lista dokumentów + ostatnie zmiany.
     * Tryb zewnętrzny: intro + przycisk do zewnętrznego BIP.
     */
    public function index()
    {
        $settings = SiteSetting::current();
        $isExternal = ($settings->bip_mode ?? 'internal') === 'external';

        $documents = collect();
        $recentChanges = collect();

        if (! $isExternal && $settings->isModuleEnabled('bip')) {
            $documents = BipDocument::published()
                ->orderBy('category')
                ->orderBy('order')
                ->orderBy('title')
                ->with(['creator', 'updater', 'media'])
                ->get()
                ->groupBy('category');

            $recentChangeIds = $documents->flatten()->pluck('id');

            $recentChanges = Activity::where('subject_type', BipDocument::class)
                ->where('log_name', 'cms')
                ->with('causer')
                ->latest()
                ->limit(8)
                ->get();
        }

        return view('bip', compact('documents', 'isExternal', 'recentChanges'));
    }

    /** Wyświetla treść pojedynczego dokumentu BIP z historią edycji. */
    public function show(BipDocument $bipDocument)
    {
        $settings = SiteSetting::current();

        if (($settings->bip_mode ?? 'internal') === 'external') {
            return redirect()->route('bip');
        }

        abort_unless($bipDocument->is_published, 404);

        $bipDocument->load(['creator', 'updater', 'media']);

        $history = Activity::where('subject_type', BipDocument::class)
            ->where('log_name', 'cms')
            ->where('subject_id', $bipDocument->id)
            ->with('causer')
            ->latest()
            ->get();

        return view('bip.show', compact('bipDocument', 'history'));
    }

    /** Publiczny rejestr zmian BIP (tylko w trybie wbudowanym). */
    public function changeLog()
    {
        $settings = SiteSetting::current();

        if (($settings->bip_mode ?? 'internal') === 'external') {
            return redirect()->route('bip');
        }

        $entries = Activity::where('subject_type', BipDocument::class)
            ->where('log_name', 'cms')
            ->with('causer')
            ->latest()
            ->paginate(50);

        $documentMap = BipDocument::withTrashed()
            ->whereIn('id', $entries->pluck('subject_id')->unique())
            ->pluck('slug', 'id');

        return view('bip.changelog', compact('entries', 'documentMap'));
    }
}
