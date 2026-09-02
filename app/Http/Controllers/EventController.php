<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Support\Facades\Cache;

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
        $settings = \App\Models\SiteSetting::current();
        $cacheKey = "events_upcoming_{$settings->id}";
        $ttl = $settings->cacheEnabled('events') ? $settings->cacheTtl('events_upcoming', 900) : 0;
        if ($ttl > 0) {
            try {
                $cached = Cache::get($cacheKey);
                if ($cached instanceof \Illuminate\Database\Eloquent\Collection) {
                    $events = $cached;
                } else {
                    if ($cached !== null) {
                        Cache::forget($cacheKey);
                    }
                    $events = Event::upcoming()->forCurrentSite()->get();
                    Cache::put($cacheKey, $events, $ttl);
                }
            } catch (\Throwable) {
                Cache::forget($cacheKey);
                $events = Event::upcoming()->forCurrentSite()->get();
            }
        } else {
            $events = Event::upcoming()->forCurrentSite()->get();
        }

        return view('events.index', compact('events'));
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
