<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Media;
use App\Models\MediaFolder;
use Illuminate\Database\Eloquent\Builder;

/**
 * Dostęp do plików biblioteki: typ właściciela (moduły użytkownika) + uprawnienia folderów
 * (MediaFolder::accessibleIdsFor). Klasa używająca musi mieć accessibleModelTypes().
 */
trait AuthorizesMedia
{
    /** Zapytanie o pliki, które użytkownik może widzieć. */
    protected function visibleMedia(): Builder
    {
        $folderIds = MediaFolder::accessibleIdsFor(auth()->user());

        return Media::query()
            ->whereIn('model_type', $this->accessibleModelTypes())
            ->when($folderIds !== null, fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->whereNull('media_folder_id')
                ->orWhereIn('media_folder_id', $folderIds)));
    }

    protected function authorizeMedia(Media $media): void
    {
        abort_unless(in_array($media->model_type, $this->accessibleModelTypes(), true), 403);
        abort_unless(MediaFolder::userCanAccess(auth()->user(), $media->media_folder_id), 403, 'Nie masz dostępu do folderu z tym plikiem.');
    }

    protected function authorizeFolder(?int $folderId): void
    {
        abort_unless(MediaFolder::userCanAccess(auth()->user(), $folderId), 403, 'Nie masz dostępu do tego folderu.');
    }
}
