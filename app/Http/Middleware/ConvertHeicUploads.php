<?php

namespace App\Http\Middleware;

use App\Support\HeicConverter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wgrane zdjęcia HEIC/HEIF (iPhone) zamienia na WebP, zanim zobaczy je walidacja i kontroler —
 * działa dla wszystkich formularzy (grupa web, rejestracja: ImageValidationServiceProvider).
 * Nazwa pliku zostaje, zmienia się rozszerzenie (IMG_0001.heic → IMG_0001.webp).
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class ConvertHeicUploads
{
    public function __construct(private HeicConverter $converter) {}

    public function handle(Request $request, Closure $next): Response
    {
        $files = $request->files->all();
        if ($files) {
            $changed = false;
            $mapped = $this->map($files, $changed);
            if ($changed) {
                $request->files->replace($mapped);
                // Request::allFiles() trzyma kopię — wymuś ponowne odczytanie.
                \Closure::bind(function () { $this->convertedFiles = null; }, $request, Request::class)();
            }
        }

        return $next($request);
    }

    private function map(array $files, bool &$changed): array
    {
        foreach ($files as $key => $file) {
            if (is_array($file)) {
                $files[$key] = $this->map($file, $changed);
            } elseif ($file instanceof SymfonyUploadedFile && $this->isHeic($file)) {
                $webp = $this->converter->toWebp($file->getPathname());
                if ($webp !== null) {
                    $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'zdjecie';
                    $files[$key] = new UploadedFile($webp, $name.'.webp', 'image/webp', null, true);
                    $changed = true;
                }
            }
        }

        return $files;
    }

    private function isHeic(SymfonyUploadedFile $file): bool
    {
        if (! $file->isValid()) {
            return false;
        }
        $ext = strtolower($file->getClientOriginalExtension());

        return in_array($ext, HeicConverter::EXTENSIONS, true)
            || in_array(strtolower((string) $file->getClientMimeType()), HeicConverter::MIMES, true);
    }
}
