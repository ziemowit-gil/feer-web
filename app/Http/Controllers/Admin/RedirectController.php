<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Redirect as RedirectModel;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Panel admin: zarządzanie przekierowaniami HTTP (301/302) z eksportem i importem CSV.
 *
 * Metody: index(), store(), update(), destroy(), export(), import().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class RedirectController extends Controller
{
    /** Wyświetla listę wszystkich przekierowań HTTP. */
    public function index()
    {
        return view('admin.redirects.index', [
            'redirects' => RedirectModel::orderBy('from_path')->get(),
        ]);
    }

    /** Dodaje nowe przekierowanie HTTP z walidacją unikalności ścieżki. */
    public function store(Request $request)
    {
        $data = $this->validated($request);

        RedirectModel::create($data);

        return redirect()->route('admin.przekierowania.index')->with('status', 'Przekierowanie zostało dodane.');
    }

    /** Aktualizuje ścieżkę docelową lub status przekierowania. */
    public function update(Request $request, RedirectModel $przekierowanie)
    {
        $przekierowanie->update($this->validated($request, $przekierowanie->id));

        return redirect()->route('admin.przekierowania.index')->with('status', 'Przekierowanie zostało zapisane.');
    }

    /** Usuwa przekierowanie. */
    public function destroy(RedirectModel $przekierowanie)
    {
        $przekierowanie->delete();

        return redirect()->route('admin.przekierowania.index')->with('status', 'Przekierowanie zostało usunięte.');
    }

    /** Eksport wszystkich przekierowań do CSV. */
    public function export(): StreamedResponse
    {
        $filename = 'przekierowania-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['from_path', 'to_url', 'is_active']);
            RedirectModel::orderBy('from_path')->chunk(200, function ($chunk) use ($handle) {
                foreach ($chunk as $r) {
                    fputcsv($handle, [$r->from_path, $r->to_url, $r->is_active ? 1 : 0]);
                }
            });
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** Import CSV (kolumny: from_path, to_url, [is_active]); upsert po from_path. */
    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        $rows = array_map('str_getcsv', file($request->file('file')->getRealPath()));
        $header = array_map(fn ($h) => trim(strtolower((string) $h)), (array) array_shift($rows));
        $iFrom = array_search('from_path', $header, true);
        $iTo = array_search('to_url', $header, true);
        $iActive = array_search('is_active', $header, true);

        if ($iFrom === false || $iTo === false) {
            return back()->with('error', 'Plik CSV musi mieć kolumny „from_path" i „to_url".');
        }

        $count = 0;
        foreach ($rows as $row) {
            $from = RedirectModel::normalizePath((string) ($row[$iFrom] ?? ''));
            $to = trim((string) ($row[$iTo] ?? ''));
            if ($from === '/' || $to === '') {
                continue;
            }
            RedirectModel::updateOrCreate(
                ['from_path' => $from],
                ['to_url' => $to, 'is_active' => $iActive !== false ? (bool) ($row[$iActive] ?? true) : true],
            );
            $count++;
        }

        return redirect()->route('admin.przekierowania.index')->with('status', "Zaimportowano przekierowania: {$count}.");
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'from_path' => ['required', 'string', 'max:255'],
            'to_url' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['from_path'] = RedirectModel::normalizePath($data['from_path']);
        $data['is_active'] = $request->boolean('is_active');

        // Unikalność sprawdzamy na znormalizowanej ścieżce (nie na surowym polu).
        $exists = RedirectModel::where('from_path', $data['from_path'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'from_path' => 'Przekierowanie z tej ścieżki już istnieje.',
            ]);
        }

        return $data;
    }
}
