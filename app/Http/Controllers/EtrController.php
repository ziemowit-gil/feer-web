<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class EtrController extends Controller
{
    public function about(): View
    {
        return view('etr.about');
    }
}
