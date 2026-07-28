<?php

namespace App\Http\Controllers;

use App\Models\AnnualReport;

class ReportController extends Controller
{
    /**
     * Publiczna strona „Sprawozdania" — tabela roczna z plikami do pobrania.
     */
    public function index()
    {
        $reports = AnnualReport::published()->with('media')->get();

        return view('reports.index', compact('reports'));
    }
}
