<?php

namespace App\Http\Controllers;

class NewsletterController extends Controller
{
    public function index()
    {
        return view('newsletter.show');
    }
}
