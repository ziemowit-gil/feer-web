<?php

namespace App\Services\Media;

use App\Models\Media;
use App\Models\MediaVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Throwable;

/**
 * Podmiana pliku w bibliotece z zachowaniem adresu.
 *
 * Nowa treść trafia pod tę samą ścieżkę i nazwę pliku, więc wszystkie odnośniki
 * w treściach (/storage/{id}/{nazwa}) dalej działają. Poprzednia wersja zostaje
 * na dysku „local” (poza katalogiem publicznym) i można ją pobrać lub przywrócić.
 * Rozszerzenie musi się zgadzać — zmiana np. z .docx na .pdf zmieniłaby adres.
 */
class MediaReplacer
{
    public function __construct(private FileManipulator $manipulator) {}

    public function replace(Media $media, UploadedFile $file, ?int $userId = null, ?string $note = null): MediaVersion
    {
        $current = strtolower(pathinfo($media->file_name, PATHINFO_EXTENSION));
        $incoming = strtolower($file->getClientOriginalExtension());
        $same = $current === $incoming || (in_array($current, ['jpg', 'jpeg'], true) && in_array($incoming, ['jpg', 'jpeg'], true));

        if (! $same) {
            throw ValidationException::withMessages([
                'file' => "Nowa wersja musi mieć to samo rozszerzenie (.{$current}) — inaczej zmieniłby się adres pliku i odnośniki przestałyby działać.",
            ]);
        }

        return $this->swap($media, $file->getRealPath(), $file->getMimeType() ?: $media->mime_type, $userId, $note);
    }

    /** Przywraca wskazaną wersję — bieżący plik sam staje się wersją, więc nic nie ginie. */
    public function restore(MediaVersion $version, ?int $userId = null): MediaVersion
    {
        $path = $version->absolutePath();
        abort_unless(is_file($path), 404, 'Plik tej wersji nie istnieje na dysku.');

        $tmp = tempnam(sys_get_temp_dir(), 'media-restore-');
        copy($path, $tmp);

        try {
            return $this->swap($version->media, $tmp, $version->mime_type ?: $version->media->mime_type, $userId,
                'Przywrócono wersję z ' . $version->created_at->format('d.m.Y H:i'));
        } finally {
            @unlink($tmp);
        }
    }

    private function swap(Media $media, string $newPath, string $mime, ?int $userId, ?string $note): MediaVersion
    {
        $target = $media->getPath();
        $dir = 'media-versions/' . $media->id;
        $stored = $dir . '/' . now()->format('Ymd-His') . '-' . uniqid() . '-' . $media->file_name;

        // 1. Kopia bieżącej treści jako wersja.
        Storage::disk('local')->makeDirectory($dir);
        if (is_file($target)) {
            copy($target, Storage::disk('local')->path($stored));
        }

        $version = MediaVersion::create([
            'media_id' => $media->id,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'size' => (int) $media->size,
            'path' => $stored,
            'replaced_by_user_id' => $userId,
            'note' => $note ? mb_substr($note, 0, 500) : null,
        ]);

        // 2. Nowa treść pod tym samym adresem.
        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0755, true);
        }
        copy($newPath, $target);
        clearstatcache(true, $target);

        $media->forceFill([
            'mime_type' => $mime,
            'size' => filesize($target) ?: 0,
            'sha1' => sha1_file($target) ?: null,
            'a11y_report' => null,
            'a11y_checked_at' => null,
        ])->save();

        // 3. Miniatury i WebP od nowa (te same nazwy plików konwersji — odnośniki do nich też działają).
        if ($media->isImage()) {
            try {
                $this->manipulator->createDerivedFiles($media);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $version;
    }
}
