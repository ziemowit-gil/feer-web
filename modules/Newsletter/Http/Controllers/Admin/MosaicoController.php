<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Newsletter\Services\MosaicoImageProcessor;

/**
 * Backend Mosaico: galeria/upload (protokół jQuery File Upload), przetwarzanie
 * obrazków (placeholder/resize/cover) i pobranie HTML.
 */
class MosaicoController extends Controller
{
    private const DIR = 'newsletter/images';

    public function gallery()
    {
        $files = collect(Storage::disk('public')->files(self::DIR))
            ->filter(fn ($f) => preg_match('/\.(jpe?g|png|gif|webp)$/i', $f))
            ->sortByDesc(fn ($f) => Storage::disk('public')->lastModified($f))
            ->map(fn ($f) => $this->fileJson($f))->values();

        return response()->json(['files' => $files]);
    }

    public function upload(Request $request)
    {
        $request->validate(['files' => ['required', 'array'], 'files.*' => ['image', 'mimes:jpeg,png,gif,webp', 'max:8192']]);
        $out = [];
        foreach ($request->file('files') as $file) {
            $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '-' . Str::lower(Str::random(6)) . '.' . strtolower($file->getClientOriginalExtension());
            $path = $file->storeAs(self::DIR, $name, 'public');
            $out[] = $this->fileJson($path);
        }

        return response()->json(['files' => $out]);
    }

    public function image(Request $request, MosaicoImageProcessor $processor)
    {
        $method = (string) $request->query('method', 'placeholder');
        [$w, $h] = array_pad(array_map(fn ($v) => $v === 'null' || $v === '' ? null : (int) $v, explode(',', (string) $request->query('params', '100,100'))), 2, null);

        if ($method === 'placeholder' || $method === 'placeholder2') {
            return response()->file($processor->placeholder((int) ($w ?: 100), (int) ($h ?: 100), (string) $request->query('text', '')), ['Cache-Control' => 'public, max-age=86400']);
        }

        $src = (string) $request->query('src', '');
        $file = $processor->transform($src, $method, $w, $h);
        if ($file === null) {
            // obrazek spoza naszego dysku — przekieruj do oryginału (Mosaico i tak wyświetli)
            return preg_match('#^https?://#', $src) ? redirect()->away($src) : abort(404);
        }

        return response()->file($file, ['Cache-Control' => 'public, max-age=86400']);
    }

    public function download(Request $request)
    {
        $html = (string) $request->input('html', '');
        $name = Str::slug((string) $request->input('filename', 'newsletter')) ?: 'newsletter';

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8', 'Content-Disposition' => "attachment; filename=\"{$name}.html\""]);
    }

    private function fileJson(string $path): array
    {
        $url = Storage::disk('public')->url($path);

        return [
            'name' => basename($path),
            'size' => Storage::disk('public')->size($path),
            'url'  => $url,
            'thumbnailUrl' => route('admin.newsletter.mosaico.image', ['src' => $url, 'method' => 'cover', 'params' => '90,90']),
            'deleteType' => 'DELETE',
        ];
    }
}
