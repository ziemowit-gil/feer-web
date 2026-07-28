<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnnualReportRequest;
use App\Models\AnnualReport;

class AnnualReportController extends Controller
{
    public function index()
    {
        $reports = AnnualReport::orderByDesc('year')->with('media')->get();

        return view('admin.reports.index', compact('reports'));
    }

    public function create()
    {
        return view('admin.reports.form', [
            'report' => new AnnualReport(['year' => now()->year - 1]),
        ]);
    }

    public function store(AnnualReportRequest $request)
    {
        $report = AnnualReport::create($this->prepared($request));
        $this->syncFiles($report, $request);

        return redirect()->route('admin.sprawozdania.index')->with('status', "Sprawozdania za {$report->year} rok zostały dodane.");
    }

    public function edit(AnnualReport $annualReport)
    {
        return view('admin.reports.form', ['report' => $annualReport]);
    }

    public function update(AnnualReportRequest $request, AnnualReport $annualReport)
    {
        $annualReport->update($this->prepared($request));
        $this->syncFiles($annualReport, $request);

        return redirect()->route('admin.sprawozdania.index')->with('status', "Sprawozdania za {$annualReport->year} rok zostały zaktualizowane.");
    }

    public function destroy(AnnualReport $annualReport)
    {
        $year = $annualReport->year;
        $annualReport->delete();

        return redirect()->route('admin.sprawozdania.index')->with('status', "Sprawozdania za {$year} rok zostały usunięte.");
    }

    /**
     * Dane do zapisania (bez pól plikowych — te obsługuje syncFiles()).
     * Powód czyścimy, gdy status nie jest „własny", by nie zostawiać sierot.
     */
    private function prepared(AnnualReportRequest $request): array
    {
        $data = $request->safe()->only([
            'year', 'substantive_status', 'substantive_reason',
            'financial_status', 'financial_reason',
        ]);

        foreach (['substantive', 'financial'] as $type) {
            if (($data[$type.'_status'] ?? null) !== 'custom') {
                $data[$type.'_reason'] = null;
            }
        }

        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }

    /**
     * Wgrywa/usuwa pliki PDF sprawozdań oraz pliki dodatkowe.
     */
    private function syncFiles(AnnualReport $report, AnnualReportRequest $request): void
    {
        foreach (['substantive', 'financial'] as $type) {
            if ($request->boolean("remove_{$type}")) {
                $report->clearMediaCollection($type);
            }
            if ($request->hasFile("{$type}_file")) {
                $report->addMediaFromRequest("{$type}_file")->toMediaCollection($type);
            }
        }

        // Usuwanie wybranych plików dodatkowych (po id mediów tego rekordu).
        $removeIds = array_map('intval', (array) $request->input('remove_files', []));
        if ($removeIds !== []) {
            $report->getMedia('additional')
                ->whereIn('id', $removeIds)
                ->each(fn ($media) => $media->delete());
        }

        // Dodawanie nowych plików dodatkowych.
        foreach ((array) $request->file('additional_files', []) as $file) {
            $report->addMedia($file)->toMediaCollection('additional');
        }
    }
}
