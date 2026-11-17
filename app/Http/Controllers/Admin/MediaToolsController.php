<?php

namespace App\Http\Controllers\Admin;

use App\Models\Media;
use App\Models\MediaFolder;
use App\Models\MediaLibrary;
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
 * Metody: panel(), source(), editImage(), replace(), versionDownload(), versionRestore(), recheck(),
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
            'edit' => $media->archived_at === null ? $this->editorPayload($media) : null,
        ]);
    }

    /**
     * Oryginał obrazu dla edytora — z panelu, więc zawsze z tej samej domeny co edytor
     * (obraz z innej domeny „brudzi” canvas i przeglądarka nie pozwoliłaby go zapisać).
     */
    public function source(Media $media)
    {
        $this->authorizeMedia($media);
        abort_unless($media->isEditableImage() && is_file($media->getPath()), 404);

        return response()->file($media->getPath(), [
            'Content-Type' => $media->mime_type,
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Zapis z edytora obrazu (kadr, obrót, odbicie, zmiana rozmiaru — liczone w przeglądarce).
     * mode=version: nowa wersja pod tym samym adresem (poprzednia zostaje w historii wersji, można ją przywrócić);
     * mode=copy: nowy plik w tym samym folderze, z opisem alternatywnym, autorem i tagami oryginału.
     */
    public function editImage(Request $request, Media $media, MediaReplacer $replacer, MediaUsage $usage)
    {
        $this->authorizeMedia($media);

        // Panel nie renderuje błędów walidacji jako JSON (bootstrap/app.php: tylko api/* i mcp/*),
        // a edytor wysyła fetch — więc dla niego oddajemy 422 z komunikatem zamiast przekierowania.
        try {
            return $this->saveEditedImage($request, $media, $replacer, $usage);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if (! $request->expectsJson()) {
                throw $e;
            }

            return response()->json(['message' => collect($e->errors())->flatten()->first(), 'errors' => $e->errors()], 422);
        }
    }

    private function saveEditedImage(Request $request, Media $media, MediaReplacer $replacer, MediaUsage $usage)
    {
        if (! $media->isEditableImage() || $media->archived_at !== null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'file' => 'Ten plik nie jest edytowalnym obrazem (JPG, PNG lub WEBP poza archiwum).',
            ]);
        }

        $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimetypes:'.implode(',', Media::EDITABLE_IMAGES), 'dimensions:max_width=8000,max_height=8000'],
            'mode' => ['required', 'in:version,copy'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [], ['file' => 'obraz po edycji', 'mode' => 'sposób zapisu', 'note' => 'opis zmiany']);

        $file = $request->file('file');
        $incoming = $file->getMimeType();
        if ($incoming !== $media->mime_type) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'file' => 'Obraz po edycji ma inny format niż oryginał — zapisz go jako '.strtoupper(pathinfo($media->file_name, PATHINFO_EXTENSION)).' (ta przeglądarka mogła nie obsłużyć formatu).',
            ]);
        }

        $note = trim((string) $request->input('note')) ?: 'Edycja obrazu w bibliotece';

        if ($request->input('mode') === 'version') {
            $replacer->replace($media, $file, auth()->id(), $note);
            $targetId = $media->id;
            $message = 'Obraz zapisany jako nowa wersja — adres się nie zmienił, poprzednia wersja jest w historii.';
        } else {
            $base = pathinfo($media->file_name, PATHINFO_FILENAME);
            $ext = strtolower(pathinfo($media->file_name, PATHINFO_EXTENSION));

            /** @var Media $copy */
            $copy = MediaLibrary::instance()->addMedia($file)
                ->usingName($base.'-edycja')
                ->usingFileName(\Illuminate\Support\Str::limit($base, 180, '').'-edycja.'.$ext)
                ->toMediaCollection('files');

            foreach (['alt', 'author', 'tags'] as $key) {
                if (filled($media->getCustomProperty($key))) {
                    $copy->setCustomProperty($key, $media->getCustomProperty($key));
                }
            }
            if (filled($media->getCustomProperty('display_name'))) {
                $copy->setCustomProperty('display_name', $media->getCustomProperty('display_name').' (edycja)');
            }
            $copy->uploaded_by_user_id = auth()->id();
            $copy->media_folder_id = $media->media_folder_id;
            $copy->save();

            $targetId = $copy->id;
            $message = 'Zapisano kopię obrazu po edycji — oryginał został bez zmian.';
        }

        $usage->forget();
        $redirect = route('admin.multimedia.index', array_filter(['folder' => $media->media_folder_id]));

        if ($request->expectsJson()) {
            session()->flash('status', $message);
            session()->flash('media_panel', $targetId);

            return response()->json(['message' => $message, 'redirect' => $redirect, 'id' => $targetId]);
        }

        return redirect($redirect)->with('status', $message)->with('media_panel', $targetId);
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
