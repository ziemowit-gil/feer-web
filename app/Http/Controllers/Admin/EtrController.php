<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EtrContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Panel admin: zapis i usuwanie wersji ETR (Easy-To-Read) aktualności i podstron.
 *
 * Metody: update(), destroy().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class EtrController extends Controller
{
    private const ALLOWED_TYPES = [
        'news'     => \App\Models\News::class,
        'podstrona' => \App\Models\Page::class,
    ];

    /**
     * Zapisuje lub aktualizuje wersję ETR dla aktualności lub podstrony.
     *
     * @param  string  $type  Typ treści: news|podstrona
     * @param  int     $id    ID rekordu
     */
    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        abort_unless(array_key_exists($type, self::ALLOWED_TYPES), 404);

        $modelClass = self::ALLOWED_TYPES[$type];
        $model = $modelClass::findOrFail($id);

        $data = $request->validate([
            'is_enabled'  => ['boolean'],
            'etr_title'   => ['nullable', 'string', 'max:255'],
            'etr_summary' => ['nullable', 'string'],
            'etr_content' => ['nullable', 'string'],
        ]);

        $data['is_enabled'] = $request->boolean('is_enabled');

        $model->etr()->updateOrCreate(
            ['etrable_type' => $modelClass, 'etrable_id' => $id],
            $data,
        );

        return back()->with('status', 'Wersja ETR została zapisana.');
    }

    /**
     * Usuwa wersję ETR powiązaną z aktualności lub podstroną.
     *
     * @param  string  $type  Typ treści: news|podstrona
     * @param  int     $id    ID rekordu
     */
    public function destroy(string $type, int $id): RedirectResponse
    {
        abort_unless(array_key_exists($type, self::ALLOWED_TYPES), 404);

        $modelClass = self::ALLOWED_TYPES[$type];
        $model = $modelClass::findOrFail($id);
        $model->etr()?->delete();

        return back()->with('status', 'Wersja ETR została usunięta.');
    }
}
