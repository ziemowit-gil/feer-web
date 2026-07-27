<?php

namespace App\Http\Controllers;

use App\Models\Event;

class EventController extends Controller
{
    public function index()
    {
        return view('events.index', [
            'events' => Event::upcoming()->get(),
        ]);
    }

    public function show(Event $event)
    {
        // Nieopublikowane wydarzenia nie są publicznie dostępne. Zakończone
        // pozostają osiągalne po bezpośrednim linku (z oznaczeniem, że minęły).
        abort_unless($event->is_published, 404);

        return view('events.show', compact('event'));
    }
}
