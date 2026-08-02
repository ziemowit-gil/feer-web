<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\IOFactory;

class DocxImportController extends Controller
{
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'docx' => ['required', 'file', 'max:10240',
                'mimes:docx,vnd.openxmlformats-officedocument.wordprocessingml.document,zip'],
        ]);

        try {
            $phpWord = IOFactory::load($request->file('docx')->getPathname(), 'Word2007');
            $writer  = IOFactory::createWriter($phpWord, 'HTML');
            $tmp = tempnam(sys_get_temp_dir(), 'feer_docx_');
            $writer->save($tmp);
            $raw = file_get_contents($tmp);
            unlink($tmp);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Nie udało się odczytać pliku. Upewnij się, że to plik .docx (Word 2007+).'], 422);
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
        $html = preg_replace('/\n{3,}/', "\n\n", trim($html));

        return response()->json(['html' => $html]);
    }
}
