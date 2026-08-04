<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WcagScan;
use App\Services\WcagScannerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WcagScanController extends Controller
{
    public function index(): View
    {
        $scans = WcagScan::orderByDesc('scanned_at')->get();

        return view('admin.wcag-scans.index', compact('scans'));
    }

    public function show(WcagScan $wcagScan): View
    {
        return view('admin.wcag-scans.show', ['scan' => $wcagScan]);
    }

    public function scan(Request $request, WcagScannerService $scanner): RedirectResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
        ]);

        try {
            $scan = $scanner->scan($validated['url']);
        } catch (\Throwable $e) {
            return back()->withErrors(['url' => 'Skanowanie nie powiodło się: ' . $e->getMessage()]);
        }

        return redirect()->route('admin.wcag-scans.show', $scan)
            ->with('success', 'Skan zakończony — znaleziono ' . $scan->issue_count . ' ' . $this->issueWord($scan->issue_count) . '.');
    }

    public function destroy(WcagScan $wcagScan): RedirectResponse
    {
        $wcagScan->delete();

        return redirect()->route('admin.wcag-scans.index')
            ->with('success', 'Wynik skanu został usunięty.');
    }

    private function issueWord(int $count): string
    {
        if ($count === 1) {
            return 'problem';
        }
        if ($count >= 2 && $count <= 4) {
            return 'problemy';
        }

        return 'problemów';
    }
}
