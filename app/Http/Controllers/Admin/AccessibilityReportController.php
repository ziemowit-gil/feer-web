<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessibilityReport;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Panel admin: lista zgłoszeń barier dostępności z możliwością usuwania i eksportu do CSV.
 *
 * Metody: index(), destroy(), export().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class AccessibilityReportController extends Controller
{
    /** Wyświetla listę zgłoszeń barier dostępności. */
    public function index()
    {
        return view('admin.accessibility-reports.index', [
            'reports' => AccessibilityReport::latest()->paginate(50),
            'total' => AccessibilityReport::count(),
        ]);
    }

    /** Usuwa zgłoszenie bariery dostępności. */
    public function destroy(AccessibilityReport $report)
    {
        $report->delete();

        return redirect()
            ->route('admin.zgloszenia-barier.index')
            ->with('status', 'Zgłoszenie zostało usunięte.');
    }

    /** Eksportuje wszystkie zgłoszenia do pliku CSV. */
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
