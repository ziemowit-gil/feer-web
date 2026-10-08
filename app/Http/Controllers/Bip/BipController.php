<?php

namespace App\Http\Controllers\Bip;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\BipDocument;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

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
    public function index(Request $request)
    {
        $settings = SiteSetting::current();
        $isExternal = ($settings->bip_mode ?? 'internal') === 'external';

        $documents = collect();
        $recentChanges = collect();
        $q = trim((string) $request->query('q', ''));
        $lastUpdate = null;

        if (! $isExternal) {
            $lastUpdate = BipDocument::published()->max('updated_at');

            // Wyszukiwarka BIP (wymóg rozporządzenia): tytuł, streszczenie i treść dokumentów.
            $documents = BipDocument::published()
                ->when($q !== '', function ($query) use ($q) {
                    $like = '%'.addcslashes($q, '%_\\').'%';
                    $query->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('summary', 'like', $like)->orWhere('content', 'like', $like));
                })
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

        $lastUpdate = $lastUpdate ? \Illuminate\Support\Carbon::parse($lastUpdate) : null;

        return view('bip', compact('documents', 'isExternal', 'recentChanges', 'q', 'lastUpdate'));
    }

    /** Instrukcja korzystania z BIP (wymóg rozporządzenia w sprawie BIP). */
    public function instructions()
    {
        return view('bip.instructions', ['isExternal' => (SiteSetting::current()->bip_mode ?? 'internal') === 'external']);
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
