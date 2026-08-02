<?php

namespace App\Http\Controllers;

use App\Models\Event;

/**
 * Publiczna lista nadchodzących szkoleń/wydarzeń i widok szczegółów pojedynczego wydarzenia.
 *
 * Metody: index(), show().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class EventController extends Controller
{
    /** Wyświetla listę nadchodzących wydarzeń/szkoleń. */
    public function index()
    {
        return view('events.index', [
            'events' => Event::upcoming()->get(),
        ]);
    }

    /** Wyświetla stronę szczegółów opublikowanego wydarzenia; zakończone pozostają dostępne po linku. */
    public function show(Event $event)
    {
        // Nieopublikowane wydarzenia nie są publicznie dostępne. Zakończone
        // pozostają osiągalne po bezpośrednim linku (z oznaczeniem, że minęły).
        abort_unless($event->is_published, 404);

        return view('events.show', compact('event'));
    }
}
