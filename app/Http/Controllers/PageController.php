<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Response;

class PageController extends Controller
{
    public function show(Page $page): Response
    {
        abort_unless($page->is_published, 404);

        return response()->view('page.show', compact('page'));
    }
}
