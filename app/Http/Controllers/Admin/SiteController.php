<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\Backup\BackupManager;
use App\Support\BipSite;
use App\Support\SiteRemoval;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Zakładanie i podstawowa administracja sub-witrynami sieci (np. „Ośrodek"
 * prowadzony przez federację) — każda sub-witryna to kolejny wiersz
 * `SiteSetting` (patrz SiteSetting::current(), BelongsToSite). Pełna edycja
 * ustawień/treści danej witryny odbywa się przez istniejące ekrany admina po
 * przełączeniu się na nią (Admin\ActiveSiteController::switch) — ten
 * kontroler odpowiada tylko za pola założycielskie: nazwę, slug, domenę
 * i rodzica.
 */
class SiteController extends Controller
{
    public function index()
    {
        $mainSite = SiteSetting::whereNull('parent_site_id')->orderBy('id')->first();
        $sites = SiteSetting::with('subsites')->orderBy('id')->get();

        return view('admin.sites.index', compact('mainSite', 'sites'));
    }

    public function create()
    {
        return view('admin.sites.form', ['site' => new SiteSetting]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['parent_site_id'] = SiteSetting::current()->id;
        // Szablon wybrany w formularzu; bez wyboru — jak witryna nadrzędna.
        $data['site_template'] = $data['site_template'] ?? SiteSetting::current()->site_template;
        // Kolor marki jest opcjonalny w formularzu, ale kolumna jest NOT NULL: pusta wartość oznacza
        // „dziedzicz od witryny nadrzędnej” (a gdy i ona go nie ma — kolor domyślny), nie NULL.
        $data['brand_color'] = $data['brand_color'] ?? (SiteSetting::current()->brand_color ?: SiteSetting::DEFAULT_BRAND_COLOR);

        $parent = SiteSetting::current();

        // Witryna BIP startuje z ustawieniami BIP witryny nadrzędnej (wstęp, redakcja, logo BIP).
        if (($data['site_kind'] ?? 'standard') === 'bip') {
            $data += BipSite::inheritedAttributes($parent);
        }

        $site = SiteSetting::create($data);

        if ($request->hasFile('logo')) {
            $site->addMediaFromRequest('logo')->toMediaCollection('logo');
        }

        if ($site->isBipSite()) {
            BipSite::copyLogo($parent, $site);
            $this->moveBipContent($request, $site);
        }

        return redirect()->route('admin.witryny.index')->with('status', 'Witryna „'.$site->site_name.'" została założona.');
    }

    public function edit(SiteSetting $site)
    {
        return view('admin.sites.form', compact('site'));
    }

    public function update(Request $request, SiteSetting $site)
    {
        $data = $this->validated($request, $site);
        // Puste pole nie kasuje koloru (kolumna NOT NULL) — zostaje dotychczasowy.
        if (blank($data['brand_color'] ?? null)) {
            unset($data['brand_color']);
        }
        if (blank($data['site_template'] ?? null)) {
            unset($data['site_template']);
        }
        // Główna witryna zostaje zwykłą witryną — rodzaj „BIP" mają tylko sub-witryny.
        if ($site->parent_site_id === null) {
            unset($data['site_kind']);
        }
        $site->update($data);

        if ($request->hasFile('logo')) {
            $site->addMediaFromRequest('logo')->toMediaCollection('logo');
        }

        if ($site->isBipSite()) {
            $this->moveBipContent($request, $site);
        }

        return redirect()->route('admin.witryny.index')->with('status', 'Zapisano zmiany witryny „'.$site->site_name.'".');
    }

    /** Strona potwierdzenia usunięcia: ile treści ma witryna, podwitryny, opcja usunięcia razem z treścią. */
    public function confirmDestroy(SiteSetting $site)
    {
        abort_if($site->parent_site_id === null, 403, 'Nie można usunąć głównej witryny.');

        return view('admin.sites.destroy', [
            'site' => $site,
            'counts' => SiteRemoval::contentCounts($site),
            'subsites' => $site->subsites()->get(),
        ]);
    }

    public function destroy(Request $request, SiteSetting $site)
    {
        abort_if($site->parent_site_id === null, 403, 'Nie można usunąć głównej witryny.');

        if ($site->subsites()->exists()) {
            return redirect()->route('admin.witryny.confirm-destroy', $site)
                ->with('error', 'Witryna „'.$site->site_name.'” ma podwitryny — usuń je najpierw.');
        }

        $counts = SiteRemoval::contentCounts($site);
        if ($counts !== []) {
            // Wymuszone usunięcie z treścią: zgoda i przepisana nazwa witryny.
            $request->validate([
                'with_content' => ['accepted'],
                'confirm_name' => ['required', 'string', function ($attribute, $value, $fail) use ($site) {
                    if (trim((string) $value) !== trim((string) $site->site_name)) {
                        $fail('Przepisz dokładnie nazwę witryny: „'.$site->site_name.'”.');
                    }
                }],
            ], [
                'with_content.accepted' => 'Witryna ma treść — zaznacz, że chcesz usunąć ją razem z treścią.',
            ]);

            if ($request->boolean('backup')) {
                try {
                    $backup = app(BackupManager::class)->create('przed-usunieciem-witryny', false);
                } catch (\Throwable $e) {
                    return back()->withInput()->with('error', 'Nie udało się zrobić kopii zapasowej, więc niczego nie usunięto: '.$e->getMessage());
                }
            }
        }

        $name = $site->site_name;
        $removed = SiteRemoval::remove($site);

        if (function_exists('activity')) {
            activity('system')->causedBy($request->user())->event('deleted')
                ->withProperties(['site' => $name, 'removed' => $removed, 'backup' => $backup ?? null])
                ->log('Usunięto witrynę „'.$name.'”'.($removed ? ' razem z treścią' : ''));
        }

        $total = array_sum($removed);

        return redirect()->route('admin.witryny.index')->with('status', $removed
            ? 'Usunięto witrynę „'.$name.'” i '.$total.' powiązanych rekordów.'.(isset($backup) ? ' Kopia zapasowa: '.basename($backup).'.' : '')
            : 'Witryna „'.$name.'” została usunięta.');
    }

    /**
     * Na życzenie przenosi dokumenty BIP i menu boczne BIP z witryny nadrzędnej
     * do witryny BIP — dotychczasowy BIP głównej witryny staje się jej treścią.
     */
    private function moveBipContent(Request $request, SiteSetting $site): void
    {
        if ($request->boolean('move_bip_content')) {
            BipSite::moveContent($site);
        }
    }

    private function validated(Request $request, ?SiteSetting $site = null): array
    {
        $reserved = array_merge(Page::RESERVED_SLUGS, ['site']);
        // Witryna BIP może mieć adres /bip — pod nim i tak jest pokazywany BIP witryny nadrzędnej.
        if ($request->input('site_kind', $site?->site_kind) === 'bip') {
            $reserved = array_values(array_diff($reserved, ['bip']));
        }
        $parentId = $site?->parent_site_id ?? SiteSetting::current()->id;

        return $request->validate([
            'site_kind' => [
                'sometimes', Rule::in(array_keys(SiteSetting::SITE_KINDS)),
                // Jedna witryna BIP na witrynę nadrzędną — to ją wskazują linki „BIP" w nagłówkach.
                function ($attribute, $value, $fail) use ($site, $parentId) {
                    if ($value === 'bip' && SiteSetting::where('parent_site_id', $parentId)->where('site_kind', 'bip')
                        ->when($site, fn ($q) => $q->whereKeyNot($site->id))->exists()) {
                        $fail('Ta witryna ma już w sieci witrynę BIP.');
                    }
                },
            ],
            'site_name' => ['required', 'string', 'max:255'],
            'site_template' => [
                'nullable', Rule::in(array_keys(SiteSetting::SITE_TEMPLATES)),
                // Jak w Ustawieniach → Szablon: bez szablonów zablokowanych dla instalacji i płatnych w wersji darmowej
                // (obecny szablon edytowanej witryny pozostaje dozwolony).
                function ($attribute, $value, $fail) use ($site) {
                    if ($value === $site?->site_template) {
                        return;
                    }
                    if (in_array($value, SiteSetting::current()->blocked_options['site_templates'] ?? [], true)) {
                        $fail('Ten szablon jest wyłączony w tej instalacji.');
                    } elseif (! \App\Support\Edition::allowsTemplate((string) $value)) {
                        $fail('Ten szablon jest dostępny w wersji płatnej weCMS.');
                    }
                },
            ],
            'tagline' => ['nullable', 'string', 'max:255'],
            'brand_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'slug' => [
                'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::notIn($reserved),
                Rule::unique('site_settings', 'slug')->ignore($site?->id),
                Rule::unique('pages', 'slug'),
            ],
            'domain' => [
                'nullable', 'string', 'max:255',
                Rule::unique('site_settings', 'domain')->ignore($site?->id),
            ],
            'logo' => ['nullable', 'image', 'max:4096'],
        ]);
    }
}
