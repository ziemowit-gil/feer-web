<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GdprClause;
use App\Services\GdprClauseImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Admin → Klauzule RODO: import z SZO, widoczność i kolejność na stronie.
 * Treści nie edytuje się tutaj — źródłem jest moduł „Klauzule RODO” w SZO.
 */
class GdprClauseController extends Controller
{
    public function index()
    {
        return view('admin.gdpr-clauses.index', [
            'clauses' => GdprClause::orderBy('lang')->orderBy('sort_order')->orderBy('title')->get(),
            'lastRun' => GdprClauseImporter::lastRun(),
            'szoUrl' => (string) config('szo.url'),
        ]);
    }

    public function import(GdprClauseImporter $importer)
    {
        try {
            $s = $importer->import();
        } catch (RuntimeException $e) {
            Log::warning('[SZO] Import klauzul RODO (panel) nieudany: ' . $e->getMessage());

            return back()->with('error', 'Import nieudany: ' . $e->getMessage());
        }

        return back()->with('status', "Zaimportowano klauzule z SZO: {$s['added']} nowych, {$s['updated']} zaktualizowanych, {$s['unchanged']} bez zmian, {$s['removed']} usuniętych.");
    }

    /** Zapis widoczności i kolejności dla całej listy naraz. */
    public function update(Request $request)
    {
        $data = $request->validate([
            'clauses' => ['array'],
            'clauses.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'clauses.*.is_visible' => ['nullable', 'boolean'],
        ]);

        foreach (GdprClause::all() as $clause) {
            $row = $data['clauses'][$clause->id] ?? null;
            if ($row === null) {
                continue;
            }
            $clause->fill([
                'sort_order' => (int) ($row['sort_order'] ?? $clause->sort_order),
                'is_visible' => ! empty($row['is_visible']),
            ]);
            if ($clause->isDirty()) {
                $clause->save();
            }
        }

        return back()->with('status', 'Zapisano widoczność i kolejność klauzul.');
    }
}
