<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomepageLayoutController extends Controller
{
    /**
     * Zapisuje nową kolejność sekcji strony głównej.
     *
     * Oczekuje JSON: { "sections": ["hero", "news", "events", ...] }
     * Autoryzacja: middleware 'admin' w routes/web.php.
     */
    public function updateSectionOrder(Request $request): JsonResponse
    {
        $validKeys = implode(',', array_keys(SiteSetting::HOMEPAGE_SECTIONS));

        $request->validate([
            'sections'   => ['required', 'array', 'min:1'],
            'sections.*' => ['string', "in:{$validKeys}"],
        ]);

        $settings = SiteSetting::current();
        $settings->homepage_section_order = $request->input('sections');
        $settings->save();

        return response()->json(['ok' => true]);
    }
}
