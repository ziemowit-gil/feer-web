<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessibilityReport;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccessibilityReportController extends Controller
{
    public function index()
    {
        return view('admin.accessibility-reports.index', [
            'reports' => AccessibilityReport::latest()->paginate(50),
            'total' => AccessibilityReport::count(),
        ]);
    }

    public function destroy(AccessibilityReport $report)
    {
        $report->delete();

        return redirect()
            ->route('admin.zgloszenia-barier.index')
            ->with('status', 'Zgłoszenie zostało usunięte.');
    }

    public function export(): StreamedResponse
    {
        $filename = 'zgloszenia-barier-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['imię i nazwisko', 'email', 'strona/element', 'opis bariery', 'data zgłoszenia']);

            AccessibilityReport::orderBy('created_at')->chunk(200, function ($chunk) use ($handle) {
                foreach ($chunk as $report) {
                    fputcsv($handle, [
                        $report->name,
                        $report->email,
                        $report->page_url,
                        $report->message,
                        $report->created_at->format('Y-m-d H:i'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
