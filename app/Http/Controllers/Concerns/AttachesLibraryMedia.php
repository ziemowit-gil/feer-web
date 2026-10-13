<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Media;
use App\Models\MediaFolder;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\HasMedia;

/**
 * Obraz wybrany z biblioteki plików (pole „{pole}_library_id” z admin.partials.library-image-picker):
 * kopiujemy plik do kolekcji rekordu — oryginał zostaje w bibliotece i dalej można go używać gdzie indziej.
 */
trait AttachesLibraryMedia
{
    /** Reguła walidacji pola wyboru z biblioteki. */
    protected function libraryMediaRule(): array
    {
        return ['nullable', 'integer', 'exists:media,id'];
    }

    /** Dołącza obraz z biblioteki, jeśli wybrano; zwraca true, gdy coś dołączono. */
    protected function attachFromLibrary(Request $request, HasMedia $model, string $field, string $collection): bool
    {
        $id = $request->integer($field.'_library_id');
        if (! $id) {
            return false;
        }

        $media = Media::query()->whereKey($id)->where('mime_type', 'like', 'image/%')->first();
        if (! $media || ! MediaFolder::userCanAccess($request->user(), $media->media_folder_id)) {
            return false;
        }

        $copy = $media->copy($model, $collection);
        // Opis alternatywny z biblioteki przechodzi na kopię.
        if (filled($media->getCustomProperty('alt'))) {
            $copy->setCustomProperty('alt', $media->getCustomProperty('alt'))->save();
        }

        return true;
    }
}
