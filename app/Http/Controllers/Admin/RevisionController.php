<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class RevisionController extends Controller
{
    /** typ => [klasa modelu, moduł, trasa edycji, etykieta, nazwa pola tytułu]. */
    private const TYPES = [
        'page' => [Page::class, 'pages', 'admin.podstrony.edit', 'Strona'],
        'news' => [News::class, 'news', 'admin.newsy.edit', 'Aktualność'],
        'project' => [Project::class, 'projects', 'admin.projekty.edit', 'Projekt'],
    ];

    /** Historia zmian danego rekordu — lista wersji z różnicami względem bieżącej. */
    public function index(string $type, int $id)
    {
        $model = $this->resolve($type, $id);
        [, , $editRoute, $label] = self::TYPES[$type];

        $revisions = $model->revisions()->with('user')->get();

        return view('admin.revisions.index', [
            'model' => $model,
            'type' => $type,
            'label' => $label,
            'editUrl' => route($editRoute, $model),
            'revisions' => $revisions,
            'fields' => $model->revisionFields(),
        ]);
    }

    /** Lista wersji jako JSON — dla modala historii w edytorze treści. */
    public function json(string $type, int $id): JsonResponse
    {
        $model = $this->resolve($type, $id);

        $revisions = $model->revisions()->with('user')->get()
            ->filter(fn ($r) => isset($r->data['content']))
            ->map(fn ($r) => [
                'id'         => $r->id,
                'label'      => $r->created_at->format('d.m.Y H:i'),
                'ago'        => $r->created_at->diffForHumans(),
                'user'       => $r->user?->name ?? '—',
                'content'    => $r->data['content'],
                'word_count' => str_word_count(strip_tags($r->data['content'] ?? '')),
            ])
            ->values();

        return response()->json($revisions);
    }

    /** Przywróć wskazaną wersję (tworzy nową bieżącą wersję z jej treścią). */
    public function restore(string $type, int $id, int $revision)
    {
        $model = $this->resolve($type, $id);

        $rev = $model->revisions()->findOrFail($revision);
        $model->restoreRevision($rev);

        [, , $editRoute] = self::TYPES[$type];

        return redirect()->route($editRoute, $model)
            ->with('status', 'Przywrócono wcześniejszą wersję treści.');
    }

    private function resolve(string $type, int $id): Model
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        [$class, $module] = self::TYPES[$type];
        abort_unless(auth()->user()->canAccessModule($module), 403);

        return $class::findOrFail($id);
    }
}
