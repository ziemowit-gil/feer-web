<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaLibrary;
use App\Support\DocxToHtml;
use App\Support\HeadingStructure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;

/**
 * Panel admin: konwersja pliku .docx do oczyszczonego HTML gotowego do wklejenia w edytor.
 * Najpierw parser stylów DocxToHtml (nagłówki, listy, cytaty, tabele, ramki, obrazy do biblioteki mediów —
 * edytor blokowy rozbija wynik na bloki); gdy zawiedzie, dawna konwersja przez PhpWord.
 * Domyślnie poprawia też strukturę nagłówków (HeadingStructure: od H2, bez przeskoków, bez pustych);
 * `fix_headings=0` zostawia poziomy z Worda.
 *
 * Metody: import().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class DocxImportController extends Controller
{
    /** Konwertuje wgrany plik .docx do oczyszczonego HTML i zwraca go jako JSON. */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'docx' => ['required', 'file', 'max:10240',
                'mimes:docx,vnd.openxmlformats-officedocument.wordprocessingml.document,zip'],
        ]);

        $stats = null;
        $html = null;
        if (! $request->boolean('phpword')) {
            try {
                $parser = new DocxToHtml($request->boolean('images', true) ? $this->imageStore() : null);
                $html = $parser->convert($request->file('docx')->getPathname());
                $stats = $parser->stats;
            } catch (\Throwable $e) {
                report($e);
                $html = null;
            }
        }
        if ($html === null) {
            $html = $this->viaPhpWord($request->file('docx')->getPathname());
            if ($html === null) {
                return response()->json(['error' => 'Nie udało się odczytać pliku. Upewnij się, że to plik .docx (Word 2007+).'], 422);
            }
        }

        $headings = ['changed' => 0, 'removed' => 0];
        if ($request->boolean('fix_headings', true)) {
            $headings = HeadingStructure::fix($html);
            $html = $headings['html'];
        }

        return response()->json([
            'html'     => $html,
            'headings' => ['changed' => $headings['changed'], 'removed' => $headings['removed']],
            'stats'    => $stats,
        ]);
    }

    /** Obrazy z dokumentu → biblioteka mediów (kolekcja files); zwraca adres pliku. */
    private function imageStore(): \Closure
    {
        return function (string $bytes, string $name): ?string {
            try {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION)) ?: 'png';
                $media = MediaLibrary::instance()
                    ->addMediaFromString($bytes)
                    ->usingName('Word: '.pathinfo($name, PATHINFO_FILENAME))
                    ->usingFileName(Str::random(20).'.'.$ext)
                    ->withCustomProperties(['docx_import' => true])
                    ->toMediaCollection('files');
                $media->update(['uploaded_by_user_id' => auth()->id()]);

                return $media->getUrl();
            } catch (\Throwable $e) {
                report($e);

                return null;
            }
        };
    }

    /** Dawna konwersja: PhpWord → HTML bez stylów (zapas, gdy parser stylów zawiedzie). */
    private function viaPhpWord(string $path): ?string
    {
        try {
            $phpWord = IOFactory::load($path, 'Word2007');
            $writer  = IOFactory::createWriter($phpWord, 'HTML');
            $tmp = tempnam(sys_get_temp_dir(), 'feer_docx_');
            $writer->save($tmp);
            $raw = file_get_contents($tmp);
            unlink($tmp);
        } catch (\Throwable $e) {
            return null;
        }

        // Extract <body> content only
        preg_match('/<body[^>]*>(.*?)<\/body>/si', $raw, $m);
        $html = $m[1] ?? $raw;

        // Strip inline styles, Word classes, empty spans, o: and w: tags
        $html = preg_replace('/\s+style="[^"]*"/i', '', $html);
        $html = preg_replace('/\s+class="[^"]*"/i', '', $html);
        $html = preg_replace('/<\/?o:[a-z][^>]*>/i', '', $html);
        $html = preg_replace('/<\/?w:[a-z][^>]*>/i', '', $html);
        $html = preg_replace('/<span>\s*<\/span>/i', '', $html);
        $html = preg_replace('/(<p[^>]*>)\s*(<\/p>)/i', '', $html); // empty paragraphs from Word
        return preg_replace('/\n{3,}/', "\n\n", trim($html));
    }
}
