<?php

namespace App\Http\Controllers;

/**
 * Publiczna strona newslettera — wyświetla osadzony formularz zapisu skonfigurowany w ustawieniach.
 *
 * Metody: index().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class NewsletterController extends Controller
{
    /** Wyświetla stronę newslettera z formularzem zapisu skonfigurowanym w ustawieniach. */
    public function index()
    {
        return view('newsletter.show');
    }
}
