<?php

namespace App\Http\Controllers;

use App\Models\Faq;

class FaqController extends Controller
{
    public function index()
    {
        // Grupujemy po kategorii (puste = „Pozostałe"/bez nagłówka), zachowując
        // kolejność zapisaną w panelu.
        $groups = Faq::published()->get()->groupBy(fn (Faq $faq) => $faq->category ?: '');

        return view('faq.index', compact('groups'));
    }
}
