<?php

namespace App\Http\Controllers\Admin;

use App\Models\Media;
use App\Models\MediaFolder;
use App\Models\MediaVersion;
use App\Models\UserGroup;
use App\Services\Media\DocumentA11yChecker;
use App\Services\Media\MediaCleanup;
use App\Services\Media\MediaReplacer;
use App\Services\Media\MediaUsage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Panel admin: narzędzia biblioteki plików — szczegóły pliku („gdzie użyto”, wersje),
 * podmiana z zachowaniem adresu, audyt dostępności dokumentów, porządki
 * (duplikaty, nieużywane, za duże obrazy, miejsce) i uprawnienia folderów.
 *
 * Metody: panel(), replace(), versionDownload(), versionRestore(), recheck(),
 *         documents(), cleanup(), folderPermissions().
 */
class MediaToolsController extends MediaLibraryController
{
    /** Panel szczegółów pliku (fragment HTML ładowany do szuflady na liście). */
    public function panel(Media $media, MediaUsage $usage, DocumentA11yChecker $checker)
    {
        $this->authorizeMedia($media);

        // Dokument jeszcze niesprawdzony albo zmieniony po sprawdzeniu — sprawdzamy od razu (to szybkie).
        if ($media->isCheckableDocument() && (! $media->a11y_checked_at || $media->updated_at?->gt($media->a11y_checked_at))) {
            $checker->checkAndStore($media);
        }

        $model = $media->model_type::find($media->model_id);
        $folderIds = MediaFolder::accessibleIdsFor(auth()->user());

        return view('admin.media.partials.panel', [
            'item' => $media->load(['versions.replacedBy:id,name', 'uploadedBy:id,name', 'folder']),
            'places' => $usage->forMedia($media),
            'owner' => $this->describeOwner($media, $model),
            'alt' => $this->describeAlt($media, $model),
            'allFolders' => MediaFolder::orderBy('name')->get()->when($folderIds !== null, fn ($c) => $c->whereIn('id', $folderIds))->values(),
        ]);
    }

    /** Podmienia plik na nową wersję pod tym samym adresem. */
    public function replace(Request $request, Media $media, MediaReplacer $replacer, MediaUsage $usage)
    {
        $this->authorizeMedia($media);

        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [], ['file' => 'nowa wersja pliku', 'note' => 'opis zmiany']);

        $replacer->replace($media, $request->file('file'), auth()->id(), $request->input('note'));
        $usage->forget();

        return redirect()->back()->with('status', 'Plik został podmieniony — adres się nie zmienił, poprzednia wersja jest w historii.')
            ->with('media_panel', $media->id);
    }

    /** Pobiera poprzednią wersję pliku (tylko z panelu — wersje nie są publiczne). */
    public function versionDownload(Media $media, MediaVersion $version): BinaryFileResponse
    {
        $this->authorizeMedia($media);
        abort_unless($version->media_id === $media->id && is_file($version->absolutePath()), 404);

        return response()->download($version->absolutePath(), $version->created_at->format('Y-m-d_H-i') . '_' . $version->file_name);
    }

    /** Przywraca poprzednią wersję (bieżąca trafia do historii). */
    public function versionRestore(Media $media, MediaVersion $version, MediaReplacer $replacer)
    {
        $this->authorizeMedia($media);
        abort_unless($version->media_id === $media->id, 404);

        $replacer->restore($version, auth()->id());

        return redirect()->back()->with('status', 'Przywrócono wybraną wersję pliku.')->with('media_panel', $media->id);
    }

    /** Ponowne sprawdzenie dostępności dokumentu. */
    public function recheck(Media $media, DocumentA11yChecker $checker)
    {
        $this->authorizeMedia($media);
        abort_unless($media->isCheckableDocument(), 404);

        $checker->checkAndStore($media);

        return redirect()->back()->with('status', 'Dokument został sprawdzony ponownie.');
    }

    /** Audyt dostępności dokumentów (PDF, DOCX) — jak audyt opisów alternatywnych. */
    public function documents(Request $request, DocumentA11yChecker $checker)
    {
        $docs = $this->visibleMedia()
            ->whereIn('mime_type', Media::CHECKABLE_DOCUMENTS)
            ->whereNull('archived_at')
            ->latest()
            ->get();

        // Niesprawdzone (albo zmienione) sprawdzamy przy wejściu — partiami, żeby strona nie wisiała.
        $pending = $docs->filter(fn (Media $m) => ! $m->a11y_checked_at || $m->updated_at?->gt($m->a11y_checked_at));
        $pending->take(40)->each(fn (Media $m) => $checker->checkAndStore($m));
        $remaining = max(0, $pending->count() - 40);

        $filter = in_array($request->input('status'), ['fail', 'warn', 'ok', 'error'], true) ? $request->input('status') : null;
        $order = ['fail' => 0, 'error' => 1, 'warn' => 2, 'ok' => 3];
        $rows = $docs->map(fn (Media $m) => ['media' => $m, 'report' => $m->a11y_report ?? ['status' => 'unchecked', 'checks' => []]])
            ->sortBy(fn ($r) => $order[$r['report']['status']] ?? 9)
            ->values();

        return view('admin.media.documents', [
            'rows' => $filter ? $rows->where('report.status', $filter)->values() : $rows,
            'summary' => $rows->countBy(fn ($r) => $r['report']['status']),
            'total' => $rows->count(),
            'filter' => $filter,
            'remaining' => $remaining,
        ]);
    }

    /** Porządki: duplikaty, nieużywane pliki, za duże obrazy i zajęte miejsce. */
    public function cleanup(MediaCleanup $cleanup)
    {
        $media = $this->visibleMedia()->with('folder')->get();
        $active = $media->whereNull('archived_at');

        return view('admin.media.cleanup', [
            'duplicates' => $cleanup->duplicates($active),
            'unused' => $cleanup->unused($active),
            'largeImages' => $cleanup->largeImages($active),
            'storage' => $cleanup->storage($media),
            'archivedCount' => $media->whereNotNull('archived_at')->count(),
            'archivedBytes' => (int) $media->whereNotNull('archived_at')->sum('size'),
        ]);
    }

    /** Uprawnienia folderu: które grupy użytkowników go widzą (tylko administrator). */
    public function folderPermissions(Request $request, MediaFolder $folder)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $data = $request->validate([
            'groups' => ['nullable', 'array'],
            'groups.*' => ['integer', 'exists:user_groups,id'],
        ]);

        $folder->userGroups()->sync($data['groups'] ?? []);

        $names = UserGroup::whereIn('id', $data['groups'] ?? [])->orderBy('name')->pluck('name');

        return redirect()->back()->with('status', $names->isEmpty()
            ? "Folder „{$folder->name}” jest teraz dostępny dla wszystkich z dostępem do biblioteki."
            : "Folder „{$folder->name}” widzą tylko administratorzy i grupy: {$names->implode(', ')}.");
    }
}
