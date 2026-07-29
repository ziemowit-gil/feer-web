<?php

namespace App\Http\Controllers;

use App\Http\Requests\WebinarRegistrationRequest;
use App\Models\LandingPage;
use App\Services\Webinar\RegistrationHandler;

class LandingPageController extends Controller
{
    public function show(string $slug)
    {
        $page = LandingPage::published()->where('slug', $slug)->firstOrFail();

        return view('lp.show', compact('page'));
    }

    public function register(WebinarRegistrationRequest $request, string $slug, RegistrationHandler $handler)
    {
        $page = LandingPage::published()->where('slug', $slug)->firstOrFail();

        $handler->handle($page, $request->validated());

        $message = $page->form_success ?: 'Dziękujemy! Twoje zgłoszenie zostało zapisane — szczegóły wyślemy na podany adres e-mail.';

        return response()->json(['ok' => true, 'message' => $message]);
    }

    /** Plik iCalendar (.ics) z terminem webinaru — „Dodaj do kalendarza". */
    public function calendar(string $slug)
    {
        $page = LandingPage::published()->where('slug', $slug)->firstOrFail();
        abort_unless($page->event_start !== null, 404);

        $fmt = fn ($d) => $d->clone()->utc()->format('Ymd\THis\Z');
        $esc = fn ($s) => addcslashes((string) $s, ",;\\\n");

        $lines = [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//FEER//Webinar//PL', 'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:lp-'.$page->id.'@'.request()->getHost(),
            'DTSTAMP:'.$fmt(now()),
            'DTSTART:'.$fmt($page->event_start),
            'DTEND:'.$fmt($page->event_start->clone()->addHour()),
            'SUMMARY:'.$esc($page->hero_title),
        ];
        if (filled($page->hero_lead)) {
            $lines[] = 'DESCRIPTION:'.$esc($page->hero_lead);
        }
        if (filled($page->event_location)) {
            $lines[] = 'LOCATION:'.$esc($page->event_location);
        }
        $lines[] = 'URL:'.route('lp.show', $page->slug);
        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return response(implode("\r\n", $lines), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$page->slug.'.ics"',
        ]);
    }
}
