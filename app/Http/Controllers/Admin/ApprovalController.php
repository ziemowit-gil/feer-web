<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use App\Notifications\ContentApproved;
use App\Notifications\ContentRejected;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    /** typ => [klasa modelu, trasa edycji, etykieta]. */
    private const TYPES = [
        'news' => [News::class, 'admin.newsy.edit', 'Aktualność'],
        'page' => [Page::class, 'admin.podstrony.edit', 'Strona'],
        'project' => [Project::class, 'admin.projekty.edit', 'Projekt'],
    ];

    public function index()
    {
        abort_unless(auth()->user()->canApproveContent(), 403);

        $items = collect();
        foreach (self::TYPES as $type => [$class, $editRoute, $label]) {
            foreach ($class::pendingApproval()->with('submittedBy')->get() as $model) {
                $items->push([
                    'type' => $type,
                    'label' => $label,
                    'title' => $model->title,
                    'submitted_by' => $model->submittedBy?->name ?? '—',
                    'updated_at' => $model->updated_at,
                    'edit_url' => route($editRoute, $model),
                    'id' => $model->id,
                ]);
            }
        }

        return view('admin.approvals.index', ['items' => $items->sortByDesc('updated_at')->values()]);
    }

    public function approve(string $type, int $id)
    {
        $model = $this->resolve($type, $id);
        $model->update(['is_published' => true, 'pending_approval' => false]);

        $this->notifyAuthor($model, new ContentApproved($model, $model->approvalLabel()));

        return back()->with('status', 'Treść została zatwierdzona i opublikowana.');
    }

    public function reject(Request $request, string $type, int $id)
    {
        $reason = $request->validate(['reason' => 'nullable|string|max:1000'])['reason'] ?? null;

        $model = $this->resolve($type, $id);
        $model->update(['pending_approval' => false]);

        $this->notifyAuthor($model, new ContentRejected($model, $model->approvalLabel(), $reason));

        return back()->with('status', 'Treść odrzucona — wróciła do wersji roboczej (szkicu).');
    }

    /** Powiadom autora zgłoszenia, jeśli jest znany i ma adres e-mail. */
    private function notifyAuthor($model, $notification): void
    {
        $author = $model->submittedBy;

        if ($author && filled($author->email)) {
            $author->notify($notification);
        }
    }

    private function resolve(string $type, int $id)
    {
        abort_unless(auth()->user()->canApproveContent(), 403);
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type][0]::findOrFail($id);
    }

    /** Liczba treści oczekujących na zatwierdzenie (do plakietki w menu). */
    public static function pendingCount(): int
    {
        return News::pendingApproval()->count()
            + Page::pendingApproval()->count()
            + Project::pendingApproval()->count();
    }
}
