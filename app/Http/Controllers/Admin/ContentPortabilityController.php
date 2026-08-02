<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ContentPortability;
use Illuminate\Http\Request;

/**
 * Panel admin: eksport całego serwisu do pliku ZIP i import z pliku ZIP (migracja treści).
 *
 * Metody: index(), export(), import().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class ContentPortabilityController extends Controller
{
    public function __construct(private ContentPortability $portability) {}

    /** Wyświetla ekran eksportu/importu treści z listą tabel. */
    public function index()
    {
        return view('admin.content-portability.index', [
            'tables' => $this->portability->contentTables(),
        ]);
    }

    /** Zbuduj i pobierz paczkę ZIP z treścią. */
    public function export()
    {
        $path = storage_path('app/content-export-'.date('Ymd-His').'.zip');
        $this->portability->export($path);

        return response()->download($path, basename($path))->deleteFileAfterSend();
    }

    /** Wgraj paczkę i zaimportuj treść (upsert po ID). */
    public function import(Request $request)
    {
        $request->validate([
            'package' => ['required', 'file', 'mimetypes:application/zip,application/octet-stream', 'max:262144'], // do 256 MB
        ]);

        try {
            $summary = $this->portability->import($request->file('package')->getRealPath());
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('admin.tresc.index')
                ->with('error', 'Import nie powiódł się: '.$e->getMessage());
        }

        $count = array_sum($summary);

        return redirect()->route('admin.tresc.index')
            ->with('status', "Zaimportowano treść ({$count} wierszy w ".count($summary).' tabelach). Istniejące dane nie zostały usunięte.');
    }
}
