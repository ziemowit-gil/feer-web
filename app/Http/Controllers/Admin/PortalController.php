<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ValidatesPortalSettings;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Panel admin: Portale — lista portali kategorii (i kategorii bez portalu) oraz edycja ustawień portalu.
 * Portal to kategoria działań z adresem portal_slug; publiczna strona: App\Http\Controllers\PortalController.
 *
 * Metody: index(), edit(), update().
 */
class PortalController extends Controller
{
    use ValidatesPortalSettings;

    /** Portale (kategorie z adresem portalu) i kategorie, z których można utworzyć portal. */
    public function index()
    {
        abort_unless(Schema::hasColumn('categories', 'portal_slug'), 404);

        $categories = Category::withCount('projects')->orderBy('order')->orderBy('name')->get();

        return view('admin.portals.index', [
            'portals' => $categories->filter(fn (Category $c) => filled($c->portal_slug))->values(),
            'withoutPortal' => $categories->filter(fn (Category $c) => blank($c->portal_slug))->values(),
            'enabled' => Category::portalsEnabled(),
        ]);
    }

    /** Ustawienia portalu kategorii (dla kategorii bez portalu — utworzenie portalu). */
    public function edit(Category $category)
    {
        abort_unless(Schema::hasColumn('categories', 'portal_slug'), 404);

        return view('admin.portals.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        abort_unless(Schema::hasColumn('categories', 'portal_slug'), 404);

        $category->update($this->portalData($request, $category));

        return redirect()->route('admin.portale.edit', $category)->with('status', $category->portal_slug ? 'Portal został zapisany.' : 'Zapisano — kategoria nie ma adresu portalu, więc portal nie jest widoczny.');
    }
}
