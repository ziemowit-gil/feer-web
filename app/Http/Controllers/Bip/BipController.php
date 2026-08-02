<?php

namespace App\Http\Controllers\Bip;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
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
     * Strona /bip. Tryb wbudowany: lista dokumentów. Tryb zewnętrzny: intro + przycisk do zewnętrznego BIP.
     */
    public function index()
    {
        $settings = SiteSetting::current();
        $isExternal = ($settings->bip_mode ?? 'internal') === 'external';

        $documents = (! $isExternal && $settings->isModuleEnabled('bip'))
            ? BipDocument::published()
                ->orderBy('category')
                ->orderBy('order')
                ->orderBy('title')
                ->with(['creator', 'updater', 'media'])
                ->get()
                ->groupBy('category')
            : collect();

        return view('bip', compact('documents', 'isExternal'));
    }

    /** Wyświetla treść pojedynczego dokumentu BIP (tylko w trybie wbudowanym). */
    public function show(BipDocument $bipDocument)
    {
        $settings = SiteSetting::current();

        if (($settings->bip_mode ?? 'internal') === 'external') {
            return redirect()->route('bip');
        }

        abort_unless($bipDocument->is_published, 404);

        $bipDocument->load(['creator', 'updater', 'media']);

        return view('bip.show', compact('bipDocument'));
    }

    /** Publiczny rejestr zmian BIP (tylko w trybie wbudowanym). */
    public function changeLog()
    {
        $settings = SiteSetting::current();

        if (($settings->bip_mode ?? 'internal') === 'external') {
            return redirect()->route('bip');
        }

        $entries = ActivityLog::where('subject_type', 'BipDocument')
            ->latest()
            ->paginate(50);

        $documentMap = BipDocument::withTrashed()
            ->whereIn('id', $entries->pluck('subject_id')->unique())
            ->pluck('slug', 'id');

        return view('bip.changelog', compact('entries', 'documentMap'));
    }
}
