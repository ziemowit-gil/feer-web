<?php

namespace App\Http\Controllers;

use App\Models\EducationalMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Publiczna lista opublikowanych materiałów edukacyjnych (posortowana wg kolejności i tytułu).
 *
 * W szablonie FEER lista ma filtry po stronie serwera (adres z parametrami da się udostępnić i działa bez JavaScriptu):
 * `q` — wyszukiwanie w tytule i opisie, `typ` — typ materiału, `dla` — grupa docelowa, `temat` — kategoria,
 * `sort` — kolejność (domyślna, `najnowsze`, `az`). Renderowane są wszystkie materiały, niepasujące dostają `hidden`,
 * żeby skrypt mógł filtrować na żywo bez przeładowania.
 *
 * Metody: index(), feerFilters(), matches().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class EducationalMaterialController extends Controller
{
    public const SORTS = [
        '' => 'Polecana kolejność',
        'najnowsze' => 'Najnowsze',
        'az' => 'Tytuł A–Z',
    ];

    /** Wyświetla listę opublikowanych materiałów edukacyjnych posortowaną wg kolejności i tytułu. */
    public function index(Request $request)
    {
        $materials = EducationalMaterial::where('is_published', true)
            ->orderBy('order')
            ->orderBy('title')
            ->get();

        $user = $request->user();
        $userCanAccessPremium = $user && $user->hasFeature('access-premium-materials');

        // Szablon FEER ma własny układ listy materiałów (filtry + karty w grupach).
        if (\App\Models\SiteSetting::current()->site_template === 'feer') {
            $filters = $this->feerFilters($request, $materials);

            if ($filters['sort'] === 'najnowsze') {
                $materials = $materials->sortByDesc(fn ($m) => [$m->created_at?->timestamp ?? 0, $m->id])->values();
            } elseif ($filters['sort'] === 'az') {
                $materials = $materials->sortBy(fn ($m) => Str::lower(Str::ascii($m->title)))->values();
            }

            $visibleIds = $materials->filter(fn ($m) => $this->matches($m, $filters))->pluck('id')->flip();

            return view('educational-materials.index-feer', compact('materials', 'userCanAccessPremium', 'filters', 'visibleIds'));
        }

        return view('educational-materials.index', compact('materials', 'userCanAccessPremium'));
    }

    /**
     * Odczytuje filtry z adresu; nieznane wartości są pomijane (nie psują strony ani nie ukrywają wszystkiego).
     *
     * @return array{q: string, typ: string, dla: string, temat: string, sort: string, active: bool}
     */
    private function feerFilters(Request $request, Collection $materials): array
    {
        $pick = function (string $key, Collection $allowed) use ($request): string {
            $value = (string) $request->query($key, '');

            return $allowed->contains($value) ? $value : '';
        };

        $filters = [
            'q' => Str::limit(trim((string) $request->query('q', '')), 100, ''),
            'typ' => $pick('typ', collect(array_keys(EducationalMaterial::TYPES))),
            'dla' => $pick('dla', $materials->pluck('target_group')->filter()->unique()),
            'temat' => $pick('temat', $materials->pluck('category')->filter()->unique()),
            'sort' => $pick('sort', collect(array_keys(self::SORTS))->filter()),
        ];
        $filters['active'] = $filters['q'] !== '' || $filters['typ'] !== '' || $filters['dla'] !== '' || $filters['temat'] !== '';

        return $filters;
    }

    /** Czy materiał pasuje do filtrów (wyszukiwanie bez wielkości liter i polskich znaków — tak samo jak w skrypcie). */
    private function matches(EducationalMaterial $material, array $filters): bool
    {
        if ($filters['typ'] !== '' && $material->type !== $filters['typ']) {
            return false;
        }
        if ($filters['dla'] !== '' && $material->target_group !== $filters['dla']) {
            return false;
        }
        if ($filters['temat'] !== '' && $material->category !== $filters['temat']) {
            return false;
        }
        if ($filters['q'] !== '') {
            $haystack = Str::lower(Str::ascii($material->title . ' ' . $material->description));

            return str_contains($haystack, Str::lower(Str::ascii($filters['q'])));
        }

        return true;
    }
}
