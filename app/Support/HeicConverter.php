<?php

namespace App\Support;

/**
 * Konwersja zdjęć HEIC/HEIF (iPhone) do WebP przez Imagick: obrót według EXIF, usunięte metadane
 * (w tym położenie GPS), jakość 85. Bez Imagick z obsługą HEIC zwraca null — plik zostaje bez zmian
 * i odrzuci go walidacja.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class HeicConverter
{
    public const EXTENSIONS = ['heic', 'heif'];

    public const MIMES = ['image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence'];

    public function available(): bool
    {
        return class_exists(\Imagick::class) && \Imagick::queryFormats('HEIC') && \Imagick::queryFormats('WEBP');
    }

    /** Zwraca ścieżkę pliku WebP albo null, gdy konwersja się nie udała. */
    public function toWebp(string $path): ?string
    {
        if (! $this->available()) {
            return null;
        }
        try {
            $img = new \Imagick($path);
            $img->setIteratorIndex(0);
            if (method_exists($img, 'autoOrient')) {
                $img->autoOrient();
            }
            $img->stripImage();
            $img->setImageFormat('webp');
            $img->setImageCompressionQuality(85);
            $out = tempnam(sys_get_temp_dir(), 'heic_').'.webp';
            $img->writeImage($out);
            $img->clear();

            return $out;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
