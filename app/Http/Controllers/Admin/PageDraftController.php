<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageDraft;
use App\Models\User;
use App\Notifications\ContentSubmittedForApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

/**
 * Wersja robocza opublikowanej strony (Workspaces w TYPO3): publikacja zmian (osoby z prawem akceptacji),
 * zgłoszenie do akceptacji (pozostali — powiadomienie do akceptujących) i odrzucenie kopii.
 * Edycja kopii odbywa się w edytorze blokowym z ?robocza=1 (BlockEditorController).
 */
class PageDraftController extends Controller
{
    public function publish(Request $request, Page $page): RedirectResponse
    {
        $draft = $this->draftFor($request, $page);
        if (! $request->user()->canApproveContent()) {
            return $this->back($page)->with('error', 'Zmiany opublikuje osoba z prawem akceptacji — wyślij je do akceptacji.');
        }

        $page->updateFromBlockEditor([
            'title' => $draft->title,
            'meta_description' => $draft->meta_description,
            'blocks' => $draft->blocks ?? [],
            'content' => $draft->renderedContent(),
        ]);
        $draft->delete();

        return $this->back($page)->with('status', 'Opublikowano zmiany z wersji roboczej. Poprzednia treść jest w historii zmian.');
    }

    public function submit(Request $request, Page $page): RedirectResponse
    {
        $draft = $this->draftFor($request, $page);
        $draft->update(['submitted_at' => now(), 'submitted_by_id' => $request->user()->id]);

        $approvers = User::approvers()->whereKeyNot($request->user()->id)->whereNotNull('email')->get();
        if ($approvers->isNotEmpty()) {
            Notification::send($approvers, new ContentSubmittedForApproval($page, 'Strona (wersja robocza zmian)', $request->user()->name));
        }

        return $this->back($page)->with('status', 'Wysłano zmiany do akceptacji. Do czasu publikacji odwiedzający widzą poprzednią wersję.');
    }

    public function discard(Request $request, Page $page): RedirectResponse
    {
        $this->draftFor($request, $page)->delete();

        return $this->back($page)->with('status', 'Odrzucono wersję roboczą — strona zostaje bez zmian.');
    }

    private function draftFor(Request $request, Page $page): PageDraft
    {
        abort_unless($request->user()->canAccessModule('pages'), 403);
        abort_unless(Page::forCurrentSite()->whereKey($page->id)->exists(), 404);
        abort_if($page->is_locked && ! $request->user()->isAdmin(), 403, 'Strona została zablokowana do edycji przez administratora.');

        return $page->draft ?? abort(404, 'Ta strona nie ma wersji roboczej.');
    }

    private function back(Page $page): RedirectResponse
    {
        return redirect()->to(route('admin.podstrony.index', ['wybrana' => $page->id]).'#wersja-robocza');
    }
}
