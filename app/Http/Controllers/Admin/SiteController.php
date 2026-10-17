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
        // Witryny w koszu osobno (sekcja „Kosz” na liście) — w drzewie tylko aktywne.
        $sites = SiteSetting::notTrashed()->with(['subsites' => fn ($q) => $q->notTrashed()])->orderBy('id')->get();
        $trashed = SiteRemoval::trashed();

        return view('admin.sites.index', compact('mainSite', 'sites', 'trashed'));
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

    /**
     * Strona potwierdzenia: dla zwykłej witryny — przeniesienie do kosza (z „Wymuś usunięcie”, gdy ma treść
     * lub podwitryny), dla witryny w koszu — trwałe usunięcie.
     */
    public function confirmDestroy(SiteSetting $site)
    {
        abort_if($site->parent_site_id === null, 403, 'Nie można usunąć głównej witryny.');

        $descendants = array_values(array_filter(SiteRemoval::withDescendants($site), fn ($s) => $s->id !== $site->id));

        return view('admin.sites.destroy', [
            'site' => $site,
            'permanent' => $site->isTrashed(),
            'counts' => SiteRemoval::contentCountsDeep($site),
            'subsites' => collect($descendants),
        ]);
    }

    /** Przeniesienie do kosza na SiteSetting::TRASH_DAYS dni (treść zostaje, witryna znika publicznie). */
    public function destroy(Request $request, SiteSetting $site)
    {
        abort_if($site->parent_site_id === null, 403, 'Nie można usunąć głównej witryny.');
        if ($site->isTrashed()) {
            return redirect()->route('admin.witryny.confirm-destroy', $site);
        }

        $hasSubsites = $site->subsites()->exists();
        if (SiteRemoval::contentCountsDeep($site) !== [] || $hasSubsites) {
            $this->confirmed($request, $site, $hasSubsites
                ? 'Witryna ma podwitryny — zaznacz „Wymuś usunięcie”, aby przenieść ją do kosza razem z nimi.'
                : 'Witryna ma treść — zaznacz „Wymuś usunięcie”, aby przenieść ją do kosza razem z treścią.');
        }

        $count = SiteRemoval::trash($site);
        $this->log($request, 'Przeniesiono do kosza witrynę „'.$site->site_name.'”'.($count > 1 ? ' z podwitrynami ('.($count - 1).')' : ''), ['site' => $site->site_name, 'sites' => $count]);

        return redirect()->route('admin.witryny.index')->with('status',
            'Witryna „'.$site->site_name.'”'.($count > 1 ? ' i podwitryny ('.($count - 1).')' : '').' trafiła do kosza. Możesz ją przywrócić do '
            .now()->addDays(SiteSetting::TRASH_DAYS)->format('d.m.Y').' — potem zostanie usunięta trwale.');
    }

    public function restore(Request $request, SiteSetting $site)
    {
        abort_unless($site->isTrashed(), 404);

        $count = SiteRemoval::restore($site);
        $this->log($request, 'Przywrócono z kosza witrynę „'.$site->site_name.'”', ['site' => $site->site_name, 'sites' => $count]);

        return redirect()->route('admin.witryny.index')->with('status',
            'Przywrócono witrynę „'.$site->site_name.'”'.($count > 1 ? ' i podwitryny ('.($count - 1).')' : '').'.');
    }

    /** Trwałe usunięcie witryny z kosza (z podwitrynami i całą treścią) — przepisana nazwa, opcjonalnie kopia. */
    public function forceDestroy(Request $request, SiteSetting $site)
    {
        abort_if($site->parent_site_id === null, 403, 'Nie można usunąć głównej witryny.');
        abort_unless($site->isTrashed(), 404);

        $this->confirmed($request, $site, 'Zaznacz „Usuń trwale”, aby potwierdzić nieodwracalne usunięcie.');

        if ($request->boolean('backup')) {
            try {
                $backup = app(BackupManager::class)->create('pre-delete', false);
            } catch (\Throwable $e) {
                return back()->withInput()->with('error', 'Nie udało się zrobić kopii zapasowej, więc niczego nie usunięto: '.$e->getMessage());
            }
        }

        $name = $site->site_name;
        $removed = SiteRemoval::remove($site, true);
        $sitesRemoved = $removed['site_settings'] ?? 1;
        unset($removed['site_settings']);
        $total = array_sum($removed);
        $this->log($request, 'Trwale usunięto witrynę „'.$name.'”'.($sitesRemoved > 1 ? ' z podwitrynami ('.($sitesRemoved - 1).')' : '').($total ? ' razem z treścią' : ''),
            ['site' => $name, 'sites' => $sitesRemoved, 'removed' => $removed, 'backup' => isset($backup) ? basename($backup) : null]);

        $msg = 'Trwale usunięto witrynę „'.$name.'”';
        if ($sitesRemoved > 1) {
            $msg .= ', podwitryny: '.($sitesRemoved - 1);
        }
        $msg .= $total ? ' i '.$total.' powiązanych rekordów.' : '.';
        if (isset($backup)) {
            $msg .= ' Kopia zapasowa: '.basename($backup).'.';
        }

        return redirect()->route('admin.witryny.index')->with('status', $msg);
    }

    /** Zaznaczona opcja „force” i przepisana nazwa witryny. */
    private function confirmed(Request $request, SiteSetting $site, string $forceMessage): void
    {
        $request->validate([
            'force' => ['accepted'],
            'confirm_name' => ['required', 'string', function ($attribute, $value, $fail) use ($site) {
                if (trim((string) $value) !== trim((string) $site->site_name)) {
                    $fail('Przepisz dokładnie nazwę witryny: „'.$site->site_name.'”.');
                }
            }],
        ], ['force.accepted' => $forceMessage]);
    }

    private function log(Request $request, string $message, array $props): void
    {
        if (function_exists('activity')) {
            activity('system')->causedBy($request->user())->event('deleted')->withProperties($props)->log($message);
        }
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
