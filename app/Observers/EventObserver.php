<?php

namespace App\Observers;

use App\Models\Event;
use Illuminate\Support\Facades\Cache;

class EventObserver
{
    public function saved(Event $event): void
    {
        Cache::forget("event_item_{$event->slug}");
        Cache::forget('events_upcoming');

        if ($event->wasChanged('slug') && $event->getOriginal('slug')) {
            Cache::forget('event_item_' . $event->getOriginal('slug'));
        }
    }

    public function deleted(Event $event): void
    {
        Cache::forget("event_item_{$event->slug}");
        Cache::forget('events_upcoming');
    }

    public function restored(Event $event): void
    {
        Cache::forget("event_item_{$event->slug}");
        Cache::forget('events_upcoming');
    }
}
