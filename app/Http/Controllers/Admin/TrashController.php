<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BipDocument;
use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;

/**
 * Panel admin: kosz usuniętych treści — przywracanie soft-deleted rekordów
 * i trwałe kasowanie wraz z plikami mediów.
 *
 * Metody: index(), restore(), forceDelete(), count().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class TrashController extends Controller
{
    /** typ => [klasa modelu, moduł, etykieta]. */
    private const TYPES = [
        'page'         => [Page::class, 'pages', 'Strona'],
        'news'         => [News::class, 'news', 'Aktualność'],
        'project'      => [Project::class, 'projects', 'Projekt'],
        'bip_document' => [BipDocument::class, 'bip', 'Dokument BIP'],
    ];

    /** Kosz — usunięte treści z możliwością przywrócenia lub trwałego skasowania. */
    public function index()
    {
        $user = auth()->user();
        $items = collect();

        foreach (self::TYPES as $type => [$class, $module, $label]) {
            if (! $user->canAccessModule($module)) {
                continue;
            }

            foreach ($class::onlyTrashed()->latest('deleted_at')->get() as $model) {
                $items->push([
                    'type' => $type,
                    'label' => $label,
                    'title' => $model->title,
                    'deleted_at' => $model->deleted_at,
                    'id' => $model->id,
                ]);
            }
        }

        return view('admin.trash.index', ['items' => $items->sortByDesc('deleted_at')->values()]);
    }

    /** Przywraca soft-deleted rekord z kosza. */
    public function restore(string $type, int $id)
    {
        $this->resolve($type, $id)->restore();

        return back()->with('status', 'Treść została przywrócona.');
    }

    /** Trwale usuwa rekord z kosza wraz z plikami mediów. */
    public function forceDelete(string $type, int $id)
    {
        $this->resolve($type, $id)->forceDelete();

        return back()->with('status', 'Treść została trwale usunięta.');
    }

    private function resolve(string $type, int $id): Model
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        [$class, $module] = self::TYPES[$type];
        abort_unless(auth()->user()->canAccessModule($module), 403);

        return $class::onlyTrashed()->findOrFail($id);
    }

    /** Liczba elementów w koszu (do plakietki w menu). */
    public static function count(): int
    {
        return Page::onlyTrashed()->count()
            + News::onlyTrashed()->count()
            + Project::onlyTrashed()->count()
            + BipDocument::onlyTrashed()->count();
    }
}
