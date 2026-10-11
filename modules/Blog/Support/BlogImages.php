<?php

namespace Modules\Blog\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Zdjęcia bloga (wyróżniające wpisów, portrety autorów) na dysku `public`.
 *
 * Blog ma osobną bazę, więc nie używa spatie/medialibrary bezpośrednio
 * (relacja `media` szukałaby tabeli w bazie bloga) — trzymamy ścieżkę pliku.
 * Zdjęcie z biblioteki multimediów jest kopiowane, żeby nie zniknęło razem
 * z oryginałem.
 */
final class BlogImages
{
    public const DISK = 'public';

    public static function storeUpload(UploadedFile $file, string $dir): string
    {
        return $file->store($dir, self::DISK);
    }

    /** Kopiuje obraz z biblioteki multimediów; null, gdy nie istnieje albo kopia się nie powiodła. */
    public static function copyFromLibrary(int $mediaId, string $dir): ?string
    {
        $media = Media::query()->where('mime_type', 'like', 'image/%')->find($mediaId);
        if (! $media) {
            return null;
        }

        try {
            $contents = Storage::disk($media->disk)->get($media->getPathRelativeToRoot());
        } catch (\Throwable) {
            return null;
        }

        if ($contents === null) {
            return null;
        }

        $extension = strtolower(pathinfo($media->file_name, PATHINFO_EXTENSION)) ?: 'jpg';
        $path = trim($dir, '/') . '/' . Str::random(40) . '.' . $extension;

        return Storage::disk(self::DISK)->put($path, $contents) ? $path : null;
    }

    public static function url(?string $path): ?string
    {
        return filled($path) ? Storage::disk(self::DISK)->url($path) : null;
    }

    public static function delete(?string $path): void
    {
        if (filled($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
