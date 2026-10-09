<?php

declare(strict_types=1);

namespace Modules\Newsletter\Services;

use Illuminate\Support\Facades\Storage;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

/**
 * Backend obrazków dla Mosaico: placeholder / resize / cover z cache na dysku.
 * Obsługuje tylko pliki z własnego dysku `public` (brak proxy do cudzych adresów).
 */
class MosaicoImageProcessor
{
    private const CACHE_DIR = 'newsletter/cache';

    public function placeholder(int $w, int $h, string $text = ''): string
    {
        $w = max(1, min(2000, $w));
        $h = max(1, min(2000, $h));
        $path = self::CACHE_DIR . "/ph-{$w}x{$h}.png";

        if (! Storage::disk('public')->exists($path)) {
            $im = imagecreatetruecolor($w, $h);
            $bg = imagecolorallocate($im, 0xEA, 0xF1, 0xFF);
            $fg = imagecolorallocate($im, 0x17, 0x52, 0xBF);
            imagefilledrectangle($im, 0, 0, $w, $h, $bg);
            for ($x = -$h; $x < $w; $x += 24) {
                imageline($im, $x, $h, $x + $h, 0, imagecolorallocatealpha($im, 0x1E, 0x6D, 0xFF, 110));
            }
            $label = $text !== '' ? $text : "{$w} × {$h}";
            $fw = imagefontwidth(5) * strlen($label);
            imagestring($im, 5, max(2, (int) (($w - $fw) / 2)), max(2, (int) ($h / 2) - 8), $label, $fg);
            ob_start();
            imagepng($im);
            Storage::disk('public')->put($path, (string) ob_get_clean());
            imagedestroy($im);
        }

        return Storage::disk('public')->path($path);
    }

    /** @return string|null ścieżka pliku wynikowego */
    public function transform(string $src, string $method, ?int $w, ?int $h): ?string
    {
        $local = $this->localPath($src);
        if ($local === null) {
            return null;
        }

        $key = self::CACHE_DIR . '/' . substr(sha1($local . filemtime($local) . $method . $w . 'x' . $h), 0, 32) . '.' . strtolower(pathinfo($local, PATHINFO_EXTENSION) ?: 'jpg');
        $out = Storage::disk('public')->path($key);

        if (! file_exists($out)) {
            @mkdir(dirname($out), 0775, true);
            $img = Image::load($local);
            if ($method === 'cover' && $w && $h) {
                $img->fit(Fit::Crop, $w, $h);
            } elseif ($w && $h) {
                $img->fit(Fit::Contain, $w, $h);
            } elseif ($w) {
                $img->width($w);
            } elseif ($h) {
                $img->height($h);
            }
            $img->save($out);
        }

        return $out;
    }

    /** Dopuszcza tylko obrazki z dysku public (URL /storage/… lub względna ścieżka). */
    private function localPath(string $src): ?string
    {
        $path = parse_url($src, PHP_URL_PATH) ?: $src;
        $prefix = rtrim(parse_url(Storage::disk('public')->url(''), PHP_URL_PATH) ?: '/storage', '/');
        if (str_starts_with($path, $prefix . '/')) {
            $relative = substr($path, strlen($prefix) + 1);
        } elseif (str_starts_with($path, '/vendor/mosaico/')) {
            $full = public_path(ltrim($path, '/'));

            return is_file($full) ? $full : null;
        } else {
            return null;
        }
        $relative = str_replace(['..', "\0"], '', urldecode($relative));
        $full = Storage::disk('public')->path($relative);

        return is_file($full) ? $full : null;
    }
}
