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
            // Pola-tablice (repeatery): edycja pojedynczego podpola jednego elementu.
            // "accessor" to metoda modelu zwracająca aktualną tablicę razem z domyślnymi
            // wartościami (ta sama, której używa widok) — dzięki temu pierwsza edycja
            // "na żywo" domyślnych kafelków poprawnie zapisuje je do bazy w całości.
            'array_fields' => [
                'federation_hero_tiles' => [
                    'accessor' => 'federationHeroTiles',
                    'subfields' => [
                        'title' => ['required', 'string', 'max:100'],
                        'value' => ['nullable', 'string', 'max:20'],
                    ],
                ],
            ],
        ],
    ];

    public function update(Request $request)
    {
        $request->validate([
            'model' => ['required', 'string', Rule::in(array_keys(self::TARGETS))],
            'id' => ['required', 'integer'],
            'field' => ['required', 'string'],
            'subfield' => ['nullable', 'string'],
            'index' => ['required_with:subfield', 'integer', 'min:0'],
        ]);

        $target = self::TARGETS[$request->input('model')];
        $field = $request->input('field');
        $isArrayItem = $request->filled('subfield');

        if ($isArrayItem) {
            abort_unless(array_key_exists($field, $target['array_fields'] ?? []), 422, 'Nieobsługiwane pole.');
            $subfield = $request->input('subfield');
            $subfieldRules = $target['array_fields'][$field]['subfields'][$subfield] ?? null;
            abort_unless($subfieldRules !== null, 422, 'Nieobsługiwane podpole.');
        } else {
            abort_unless(array_key_exists($field, $target['fields'] ?? []), 422, 'Nieobsługiwane pole.');
        }

        if ($target['admin_only'] ?? false) {
            abort_unless(auth()->user()->isAdmin(), 403);
        } else {
            abort_unless(auth()->user()->canAccessModule($target['module']), 403);
        }

        $record = ($target['singleton'] ?? false)
            ? $target['model']::current()
            : $target['model']::findOrFail($request->input('id'));

        if ($isArrayItem) {
            $accessor = $target['array_fields'][$field]['accessor'];
            $items = $record->{$accessor}();
            $index = (int) $request->input('index');
            abort_unless(array_key_exists($index, $items), 404, 'Nie znaleziono elementu.');

            $validated = validator(['value' => $request->input('value')], ['value' => $subfieldRules])->validate();
            $items[$index][$subfield] = $validated['value'];

            $record->update([$field => array_values($items)]);
        } else {
            $validated = validator(
                ['value' => $request->input('value')],
                ['value' => $target['fields'][$field]]
            )->validate();

            $record->update([$field => $validated['value']]);
        }

        return response()->json(['ok' => true]);
    }
}
