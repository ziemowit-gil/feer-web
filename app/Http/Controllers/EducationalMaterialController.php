<?php

namespace App\Http\Controllers;

use App\Models\EducationalMaterial;
use Illuminate\Http\Request;

/**
 * Publiczna lista opublikowanych materiałów edukacyjnych (posortowana wg kolejności i tytułu).
 *
 * Metody: index().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class EducationalMaterialController extends Controller
{
    /** Wyświetla listę opublikowanych materiałów edukacyjnych posortowaną wg kolejności i tytułu. */
    public function index(Request $request)
    {
        $materials = EducationalMaterial::where('is_published', true)
            ->orderBy('order')
            ->orderBy('title')
            ->get();

        $user = $request->user();
        $userCanAccessPremium = $user && $user->hasFeature('access-premium-materials');

        return view('educational-materials.index', compact('materials', 'userCanAccessPremium'));
    }
}
