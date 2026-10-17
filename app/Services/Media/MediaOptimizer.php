<?php

namespace App\Services\Media;

use Illuminate\Support\Facades\Log;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;
use App\Models\Media;

/**
 * Zmniejsza za duże oryginały obrazów (MediaCleanup::LARGE_IMAGE_BYTES / LARGE_IMAGE_PX) do MAX_PX na dłuższym
 * boku, w tym samym formacie — adres pliku się nie zmienia (zamiana na WebP zmieniłaby rozszerzenie i zepsuła
 * odnośniki; WebP do wyświetlania serwis i tak robi w konwersjach). Podmiana przez MediaReplacer: poprzednia
 * treść zostaje jako wersja pliku, miniatury i WebP powstają od nowa.
 */
class MediaOptimizer
{
    public const MAX_PX = 2000;

    public const QUALITY = 82;

    /** Formaty, które zmniejszamy (GIF może być animowany, SVG jest wektorowy). */
    public const FORMATS = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(private MediaReplacer $replacer) {}

    public function needsShrinking(Media $media): bool
    {
        if (! in_array($media->mime_type, self::FORMATS, true) || ! is_file($media->getPath())) {
            return false;
        }
        $size = @getimagesize($media->getPath()) ?: [0, 0];

        return (int) $media->size > MediaCleanup::LARGE_IMAGE_BYTES || max((int) $size[0], (int) $size[1]) > MediaCleanup::LARGE_IMAGE_PX;
    }

    /**
     * @return array{before: int, after: int}|null null = nie zmniejszono (nie trzeba albo wynik nie jest lżejszy)
     */
    public function shrink(Media $media): ?array
    {
        if (! $this->needsShrinking($media)) {
            return null;
        }

        $ext = strtolower(pathinfo($media->file_name, PATHINFO_EXTENSION)) ?: 'jpg';
        $tmp = tempnam(sys_get_temp_dir(), 'media-shrink-').'.'.$ext;
        try {
            Image::load($media->getPath())
                ->fit(Fit::Max, self::MAX_PX, self::MAX_PX)
                ->quality(self::QUALITY)
                ->save($tmp);
            clearstatcache(true, $tmp);
            $before = (int) $media->size;
            $after = (int) (filesize($tmp) ?: 0);
            if ($after <= 0 || $after >= $before) {
                return null;
            }

            $this->replacer->replaceWithPath($media, $tmp, $media->mime_type, null,
                'Zmniejszono automatycznie do '.self::MAX_PX.' px ('.round($before / 1048576, 1).' MB → '.round($after / 1048576, 1).' MB)');

            return ['before' => $before, 'after' => $after];
        } catch (\Throwable $e) {
            Log::warning('[Multimedia] Nie udało się zmniejszyć obrazu #'.$media->id.': '.$e->getMessage());

            return null;
        } finally {
            @unlink($tmp);
        }
    }
}
