<?php

namespace App\Http\Controllers\Bip;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\AnnualReport;
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

        if ($redirect = $this->redirectToBipSite($settings, '', $request->getQueryString())) {
            return $redirect;
        }

        // Nagłówek i menu boczne BIP zaznaczają „Stronę główną BIP" — także wtedy,
        // gdy to strona główna witryny BIP (HomeController), a nie trasa /bip.
        $request->attributes->set('bip_home', true);
        $isExternal = ($settings->bip_mode ?? 'internal') === 'external';

        $documents = collect();
        $recentChanges = collect();
        $reports = collect();
        $q = trim((string) $request->query('q', ''));
        $lastUpdate = null;

        if (! $isExternal) {
            $lastUpdate = BipDocument::published()->forCurrentSite()->max('updated_at');

            // Opcja: sprawozdania roczne z modułu „Sprawozdania" w BIP (wyszukiwane po roku i słowach „sprawozdanie", „finansowe", „merytoryczne").
            if (($settings->bip_show_reports ?? true) && $settings->isModuleEnabled('reports')) {
                $reports = AnnualReport::published()->with('media')->get();
                if ($q !== '') {
                    $needle = mb_strtolower($q);
                    $reports = $reports->filter(fn ($r) => str_contains((string) $r->year, $needle)
                        || str_contains('sprawozdanie roczne merytoryczne finansowe', $needle)
                        || str_contains($needle, 'sprawozd'))->values();
                }
            }

            // Wyszukiwarka BIP (wymóg rozporządzenia): tytuł, streszczenie i treść dokumentów.
            $documents = BipDocument::published()
                ->forCurrentSite()
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
                ->whereIn('subject_id', $this->siteDocumentIds())
                ->where('log_name', 'cms')
                ->with('causer')
                ->latest()
                ->limit(8)
                ->get();
        }

        $lastUpdate = $lastUpdate ? \Illuminate\Support\Carbon::parse($lastUpdate) : null;

        return view('bip', compact('documents', 'isExternal', 'recentChanges', 'q', 'lastUpdate', 'reports'));
    }

    /** Instrukcja korzystania z BIP (wymóg rozporządzenia w sprawie BIP). */
    public function instructions()
    {
        if ($redirect = $this->redirectToBipSite(SiteSetting::current(), 'bip/instrukcja')) {
            return $redirect;
        }

        return view('bip.instructions', ['isExternal' => (SiteSetting::current()->bip_mode ?? 'internal') === 'external']);
    }

    /** Wyświetla treść pojedynczego dokumentu BIP z historią edycji. */
    public function show(string $bipSlug)
    {
        $settings = SiteSetting::current();

        if ($redirect = $this->redirectToBipSite($settings, 'bip/'.$bipSlug)) {
            return $redirect;
        }

        if (($settings->bip_mode ?? 'internal') === 'external') {
            return redirect()->to(bip_route('bip'));
        }

        $bipDocument = BipDocument::forCurrentSite()->where('slug', $bipSlug)->firstOrFail();

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
    public function changeLog(Request $request)
    {
        $settings = SiteSetting::current();

        if ($redirect = $this->redirectToBipSite($settings, 'bip/rejestr-zmian', $request->getQueryString())) {
            return $redirect;
        }

        if (($settings->bip_mode ?? 'internal') === 'external') {
            return redirect()->to(bip_route('bip'));
        }

        $entries = Activity::where('subject_type', BipDocument::class)
            ->whereIn('subject_id', $this->siteDocumentIds())
            ->where('log_name', 'cms')
            ->with('causer')
            ->latest()
            ->paginate(50);

        $documentMap = BipDocument::withTrashed()
            ->whereIn('id', $entries->pluck('subject_id')->unique())
            ->pluck('slug', 'id');

        return view('bip.changelog', compact('entries', 'documentMap'));
    }

    /**
     * Gdy witryna ma w sieci osobną witrynę BIP (np. bip.feer.org.pl), jej
     * dotychczasowe adresy /bip… przekierowują tam na stałe.
     */
    private function redirectToBipSite(SiteSetting $settings, string $path, ?string $query = null)
    {
        $bipSite = $settings->bipSite();

        if (! $bipSite || $bipSite->is($settings)) {
            return null;
        }

        $url = rtrim(current_site_url($bipSite), '/').($path !== '' ? '/'.$path : '');

        return redirect()->away($url.($query ? '?'.$query : ''), 301);
    }

    /** Identyfikatory dokumentów BIP bieżącej witryny (także usuniętych) — do rejestru zmian. */
    private function siteDocumentIds()
    {
        return BipDocument::withTrashed()->forCurrentSite()->select('id');
    }
}
