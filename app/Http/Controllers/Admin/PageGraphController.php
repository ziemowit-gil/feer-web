<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Content\PageGraph;

/**
 * Panel admin: mapa zależności stron — graf (rodzic → podstrona, linki w treści, działania)
 * z tabelą jako wersją tekstową i ostrzeżeniami dla redakcji.
 */
class PageGraphController extends Controller
{
    public function index(PageGraph $graph)
    {
        return view('admin.pages.graph', $graph->build());
    }
}
