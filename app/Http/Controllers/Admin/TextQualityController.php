<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TextQualityScanner;
use Illuminate\Http\Request;

/** Panel → Jakość tekstów: przegląd wzorców do poprawy w opublikowanych treściach (tylko odczyt). */
class TextQualityController extends Controller
{
    public function index(Request $request, TextQualityScanner $scanner)
    {
        $rule = array_key_exists((string) $request->query('regula'), TextQualityScanner::RULES) ? (string) $request->query('regula') : null;
        $source = array_key_exists((string) $request->query('zrodlo'), TextQualityScanner::SOURCES) ? (string) $request->query('zrodlo') : null;

        return view('admin.text-quality.index', [
            'result' => $scanner->scan($rule, $source),
            'rules' => TextQualityScanner::RULES,
            'sources' => TextQualityScanner::SOURCES,
            'rule' => $rule,
            'source' => $source,
        ]);
    }
}
