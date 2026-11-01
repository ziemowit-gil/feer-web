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
    /**
     * „Zarządzaj subwitrynami”: witryna główna u góry, a pod nią wszystkie subwitryny (także zagnieżdżone) jako płaska
     * lista z głębokością. Witryny w koszu są osobno (sekcja „Kosz”).
     */
    public function index()
    {
        $sites = SiteSetting::notTrashed()->orderBy('id')->get();
        $mains = $sites->whereNull('parent_site_id')->values();
        $children = $sites->whereNotNull('parent_site_id')->groupBy('parent_site_id');

        $rows = [];
        $walk = function (int $parentId, int $depth, ?SiteSetting $parent) use (&$walk, &$rows, $children): void {
            foreach ($children->get($parentId, collect()) as $site) {
                $rows[] = ['site' => $site, 'depth' => $depth, 'parent' => $parent];
                $walk($site->id, $depth + 1, $site);
            }
        };
        foreach ($mains as $main) {
            $walk($main->id, 0, $main);
        }

        return view('admin.sites.index', ['mains' => $mains, 'rows' => $rows, 'trashed' => SiteRemoval::trashed()]);
    }

    /** Zmień nazwę i adres: nazwa witryny oraz (dla subwitryn) adres skrócony i własna domena. */
    public function identity(SiteSetting $site)
    {
        abort_if($site->isTrashed(), 404);

        return view('admin.sites.identity', ['site' => $site, 'isMain' => $site->parent_site_id === null]);
    }

    public function updateIdentity(Request $request, SiteSetting $site)
    {
        abort_if($site->isTrashed(), 404);
        $isMain = $site->parent_site_id === null;

        $this->normalizeAddress($request);
        $rules = ['site_name' => ['required', 'string', 'max:255']];
        // Adres witryny głównej to adres serwera (APP_URL) — tu zmienia się tylko jej nazwa.
        if (! $isMain) {
            $rules['slug'] = $this->slugRules($site, $site->site_kind);
            $rules['domain'] = $this->domainRules($site);
        }
        $data = $request->validate($rules, $this->addressMessages());

        $before = $site->only(['site_name', 'slug', 'domain']);
        $site->update($data);
        $changed = array_keys(array_filter($data, fn ($value, $key) => ($before[$key] ?? null) !== $value, ARRAY_FILTER_USE_BOTH));

        if ($changed !== [] && function_exists('activity')) {
            activity('system')->causedBy($request->user())->event('updated')
                ->withProperties(['site' => $site->site_name, 'before' => $before, 'after' => $site->only(['site_name', 'slug', 'domain'])])
                ->log('Zmieniono nazwę lub adres witryny „'.$before['site_name'].'”');
        }

        $status = 'Zapisano nazwę i adres witryny „'.$site->site_name.'”.';
        if (! $isMain) {
            $status .= ' Publiczny adres: '.current_site_url($site).'.';
        }

        return redirect()->route('admin.witryny.index')->with('status', $status);
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

        // Nowa sub-witryna startuje ze stroną „dopiero tworzymy” (do wyłączenia w Ustawienia → Serwis).
        if (\Illuminate\Support\Facades\Schema::hasColumn('site_settings', 'under_construction')) {
            $data['under_construction'] = true;
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

    /**
     * Adres witryny wpisany po ludzku („https://Szkolenia.Przyklad.PL/”, „ Osrodek ”) zamieniamy na postać, którą rozpoznaje
     * ResolveCurrentSite: domena bez protokołu i ścieżki, małymi literami; slug małymi literami (middleware porównuje małe litery).
     */
    private function normalizeAddress(Request $request): void
    {
        if ($request->has('domain')) {
            $host = preg_replace('#^[a-z][a-z0-9+.\-]*://#i', '', trim((string) $request->input('domain')));
            $host = strtolower(trim(explode('/', (string) $host)[0], " ."));
            $request->merge(['domain' => $host === '' ? null : $host]);
        }
        if ($request->has('slug')) {
            $slug = strtolower(trim((string) $request->input('slug')));
            $request->merge(['slug' => $slug === '' ? null : $slug]);
        }
    }

    /** Komunikaty błędów adresu — po polsku i z podpowiedzią, co poprawić. */
    private function addressMessages(): array
    {
        return [
            'slug.alpha_dash' => 'Adres skrócony może zawierać tylko litery bez polskich znaków, cyfry, myślniki i podkreślenia.',
            'slug.not_in' => 'Ten adres jest zarezerwowany dla witryny głównej albo panelu — wybierz inny.',
            'slug.unique' => 'Ten adres jest już zajęty przez inną witrynę albo stronę.',
            'domain.regex' => 'Podaj samą nazwę domeny, np. przyklad.pl (bez http:// i bez ukośników).',
            'domain.unique' => 'Ta domena jest już przypisana do innej witryny.',
        ];
    }

    /** Slugi, które nie mogą być prefiksem witryny (zlałyby się z trasą albo panelem). */
    private function reservedSlugs(?string $kind): array
    {
        // Prefiks witryny to pierwszy segment adresu, więc nie może się zlać z żadną trasą ani panelem.
        $routeSegments = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes())
            ->map(fn ($route) => strtolower(explode('/', trim($route->uri(), '/'))[0] ?? ''))
            ->filter(fn ($segment) => $segment !== '' && ! str_contains($segment, '{'))
            ->unique()->values()->all();
        $reserved = array_values(array_unique(array_merge(Page::RESERVED_SLUGS, ['site', (string) config('app.admin_prefix', 'admin')], $routeSegments)));
        // Witryna BIP może mieć adres /bip — pod nim i tak jest pokazywany BIP witryny nadrzędnej.
        if ($kind === 'bip') {
            $reserved = array_values(array_diff($reserved, ['bip']));
        }

        return $reserved;
    }

    /** @return array<int, mixed> */
    private function slugRules(?SiteSetting $site, ?string $kind): array
    {
        return [
            'nullable', 'string', 'max:255', 'alpha_dash',
            Rule::notIn($this->reservedSlugs($kind)),
            Rule::unique('site_settings', 'slug')->ignore($site?->id),
            Rule::unique('pages', 'slug'),
        ];
    }

    /** @return array<int, mixed> */
    private function domainRules(?SiteSetting $site): array
    {
        return [
            'nullable', 'string', 'max:255',
            // Co najmniej dwie etykiety (przyklad.pl), litery bez polskich znaków, cyfry i myślniki (IDN w postaci xn--…).
            'regex:/^(?=.{1,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/',
            Rule::unique('site_settings', 'domain')->ignore($site?->id),
            // Domena witryny głównej (adres serwera) nie może należeć do subwitryny — przejęłaby całą witrynę główną.
            function ($attribute, $value, $fail) {
                $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
                if ($value !== null && $appHost !== '' && strtolower((string) $value) === $appHost) {
                    $fail('Ta domena to adres witryny głównej — wpisz inną dla subwitryny.');
                }
            },
        ];
    }

    private function validated(Request $request, ?SiteSetting $site = null): array
    {
        $this->normalizeAddress($request);
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
            'slug' => $this->slugRules($site, $request->input('site_kind', $site?->site_kind)),
            'domain' => $this->domainRules($site),
            'logo' => ['nullable', 'image', 'max:4096'],
        ], $this->addressMessages());
    }
}
