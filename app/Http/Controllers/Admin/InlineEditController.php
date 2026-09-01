<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Generyczny zapis pojedynczego pola z wizualnej edycji "na żywo" (alternatywa
 * dla formularzy admina — kliknij i edytuj treść bezpośrednio na stronie).
 *
 * Każdy obsługiwany model ma jawną listę edytowalnych pól z regułami walidacji
 * identycznymi jak w jego zwykłym formularzu — to nie jest osobna, luźniejsza
 * ścieżka zapisu, tylko inny interfejs do tych samych, zaufanych pól.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class InlineEditController extends Controller
{
    private const TARGETS = [
        'page' => [
            'model' => Page::class,
            'module' => 'pages',
            'fields' => [
                'title' => ['required', 'string', 'max:255'],
                'content' => ['nullable', 'string'],
            ],
        ],
        // Singleton (SiteSetting::current()) — id przesyłany z frontendu jest ignorowany.
        'site_setting' => [
            'model' => SiteSetting::class,
            'singleton' => true,
            'admin_only' => true,
            'fields' => [
                'federation_hero_heading' => ['required', 'string', 'max:255'],
                'federation_hero_intro' => ['nullable', 'string'],
            ],
        ],
    ];

    public function update(Request $request)
    {
        $request->validate([
            'model' => ['required', 'string', Rule::in(array_keys(self::TARGETS))],
            'id' => ['required', 'integer'],
            'field' => ['required', 'string'],
        ]);

        $target = self::TARGETS[$request->input('model')];

        abort_unless($request->has('field') && array_key_exists($request->input('field'), $target['fields']), 422, 'Nieobsługiwane pole.');

        if ($target['admin_only'] ?? false) {
            abort_unless(auth()->user()->isAdmin(), 403);
        } else {
            abort_unless(auth()->user()->canAccessModule($target['module']), 403);
        }

        $field = $request->input('field');
        $record = ($target['singleton'] ?? false)
            ? $target['model']::current()
            : $target['model']::findOrFail($request->input('id'));

        $validated = validator(
            ['value' => $request->input('value')],
            ['value' => $target['fields'][$field]]
        )->validate();

        $record->update([$field => $validated['value']]);

        return response()->json(['ok' => true]);
    }
}
