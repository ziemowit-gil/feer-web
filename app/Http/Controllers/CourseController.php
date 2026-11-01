<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseEdition;
use App\Models\CourseNotification;
use App\Models\CourseRegistration;
use App\Support\SpamGuard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Publiczna oferta szkoleń i zajęć: lista z filtrami, karta szkolenia z terminami i zapis uczestnika.
 * Gdy w terminie brakuje miejsc, zgłoszenie trafia na listę rezerwową.
 *
 * Metody: index(), show(), register(), done(), notify(), ics(), card().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class CourseController extends Controller
{
    /** Lista aktywnych szkoleń; filtry `kategoria` i `format` ze znanych wartości. */
    public function index(Request $request)
    {
        $category = array_key_exists((string) $request->query('kategoria'), Course::CATEGORIES) ? $request->query('kategoria') : null;
        $format = array_key_exists((string) $request->query('format'), Course::FORMATS) ? $request->query('format') : null;

        $courses = Course::forCurrentSite()->visible()
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($format, fn ($q) => $q->where('format', $format))
            ->with('editions')
            ->orderByDesc('is_featured')->orderBy('order')->orderBy('title')
            ->get();

        return view('courses.index', compact('courses', 'category', 'format'));
    }

    /** Karta szkolenia z listą nadchodzących terminów i formularzem zapisu na wybrany termin. */
    public function show(Course $course, Request $request)
    {
        abort_unless($course && $course->status === 'active', 404);
        $course->load('editions');

        $editions = $course->upcomingEditions()->values();
        $selected = $editions->firstWhere('id', (int) old('edition_id', $request->query('termin'))) ?? $editions->first(fn (CourseEdition $e) => $e->acceptsRegistrations());

        // „Zobacz także”: pokrewne szkolenia (ta sama kategoria najpierw), bez bieżącego.
        $related = Course::forCurrentSite()->visible()->whereKeyNot($course->id)
            ->orderByRaw('category = ? desc', [$course->category])->orderByDesc('is_featured')->orderBy('order')->limit(3)->get();
        $canNotify = Schema::hasTable('course_notifications');

        return view('courses.show', compact('course', 'editions', 'selected', 'related', 'canNotify'));
    }

    /** Zapis na termin: walidacja, antyspam, blokada terminu na czas liczenia miejsc, status `pending` albo `waitlist`. */
    public function register(Request $request, Course $course)
    {
        abort_unless($course && $course->status === 'active', 404);
        abort_unless(\App\Models\SiteSetting::current()->coursesRegistrationEnabled(), 404);

        $data = $request->validate([
            'edition_id' => ['required', 'integer', Rule::exists('course_editions', 'id')->where('course_id', $course->id)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9 +()\-]*$/'],
            'accessibility_needs' => ['nullable', 'string', 'max:2000'],
            'consent' => ['accepted'],
        ], [
            'edition_id.required' => 'Wybierz termin.',
            'first_name.required' => 'Podaj imię.',
            'last_name.required' => 'Podaj nazwisko.',
            'email.required' => 'Podaj adres e-mail.',
            'email.email' => 'Adres e-mail wygląda na niepoprawny.',
            'phone.regex' => 'Numer telefonu może zawierać cyfry, spacje oraz znaki + ( ) -.',
            'consent.accepted' => 'Potwierdź zgodę na przetwarzanie danych, żeby wysłać zgłoszenie.',
        ]);

        if ($spam = SpamGuard::inspect($request, array_intersect_key($data, array_flip(['first_name', 'last_name', 'email', 'phone', 'accessibility_needs'])), 'szkolenie')) {
            Log::warning('Zablokowano zapis na szkolenie jako spam', ['powód' => $spam['reason'], 'ip' => $request->ip()]);

            return $spam['silent']
                ? redirect()->route('courses.show', $course)->with('status', 'Dziękujemy, zgłoszenie zostało przyjęte.')
                : back()->withErrors(['spam' => $spam['message']])->withInput();
        }

        $result = DB::transaction(function () use ($data, $course) {
            $edition = CourseEdition::where('course_id', $course->id)->lockForUpdate()->findOrFail($data['edition_id']);

            if (! $edition->acceptsRegistrations()) {
                return ['error' => 'Rekrutacja na ten termin jest zamknięta.'];
            }

            $email = mb_strtolower(trim($data['email']));
            if ($edition->registrations()->where('email', $email)->where('status', '!=', 'cancelled')->exists()) {
                return ['error' => 'Ten adres e-mail jest już zapisany na ten termin. Jeśli chcesz coś zmienić, napisz do nas.'];
            }

            $registration = $edition->registrations()->create([
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'email' => $email,
                'phone' => $data['phone'] ?? null,
                'accessibility_needs' => filled($data['accessibility_needs'] ?? null) ? trim($data['accessibility_needs']) : null,
                'status' => $edition->statusForNewRegistration(),
                'source' => 'web',
            ]);

            return ['registration' => $registration];
        });

        if (isset($result['error'])) {
            return back()->withErrors(['edition_id' => $result['error']])->withInput();
        }

        return redirect()->route('courses.done', [$course, $result['registration']->payment_reference]);
    }

    /** Potwierdzenie po zapisie, dostępne przez kod referencji (bez danych osobowych poza imieniem). */
    public function done(Course $course, string $reference)
    {
        abort_unless($course && $course->status === 'active', 404);

        $registration = CourseRegistration::with('edition')
            ->where('payment_reference', $reference)
            ->whereHas('edition', fn ($q) => $q->where('course_id', $course->id))
            ->firstOrFail();

        return view('courses.done', compact('course', 'registration'));
    }

    /** „Powiadom mnie o terminie”: zapis adresu e-mail (bez duplikatów) z antyspamem i zgodą. */
    public function notify(Request $request, Course $course)
    {
        abort_unless($course && $course->status === 'active', 404);
        abort_unless(Schema::hasTable('course_notifications'), 404);

        $data = $request->validate([
            'notify_email' => ['required', 'email:rfc', 'max:255'],
            'notify_consent' => ['accepted'],
        ], [
            'notify_email.required' => 'Podaj adres e-mail.',
            'notify_email.email' => 'Adres e-mail wygląda na niepoprawny.',
            'notify_consent.accepted' => 'Potwierdź zgodę na przesłanie powiadomienia, żeby się zapisać.',
        ]);

        if ($spam = SpamGuard::inspect($request, ['email' => $data['notify_email']], 'szkolenie-powiadomienie')) {
            Log::warning('Zablokowano zapis na powiadomienie o szkoleniu jako spam', ['powód' => $spam['reason'], 'ip' => $request->ip()]);

            return $spam['silent']
                ? redirect()->route('courses.show', $course)->with('status', 'Dziękujemy, damy znać o nowym terminie.')
                : back()->withErrors(['spam' => $spam['message']])->withInput();
        }

        CourseNotification::firstOrCreate(['course_id' => $course->id, 'email' => mb_strtolower(trim($data['notify_email']))]);

        return redirect()->route('courses.show', $course)->with('status', 'Dziękujemy. Damy znać e-mailem, gdy pojawi się nowy termin.');
    }

    /** Termin do kalendarza (plik .ics, wydarzenie całodniowe). */
    public function ics(Course $course, CourseEdition $edition)
    {
        abort_unless($course && $course->status === 'active' && $edition->course_id === $course->id, 404);

        $esc = fn (?string $v) => str_replace(["\\", ';', ',', "\r\n", "\n", "\r"], ['\\\\', '\\;', '\\,', '\\n', '\\n', '\\n'], (string) $v);
        $start = $edition->starts_on->format('Ymd');
        $end = ($edition->ends_on ?? $edition->starts_on)->copy()->addDay()->format('Ymd');
        $lines = array_filter([
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//FEER//Szkolenia//PL', 'CALSCALE:GREGORIAN', 'BEGIN:VEVENT',
            'UID:course-edition-'.$edition->id.'@'.request()->getHost(),
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART;VALUE=DATE:'.$start, 'DTEND;VALUE=DATE:'.$end,
            'SUMMARY:'.$esc($course->title),
            filled($edition->location) ? 'LOCATION:'.$esc($edition->location) : null,
            'DESCRIPTION:'.$esc(trim(($edition->schedule ?? '')."\n".route('courses.show', $course))),
            'URL:'.route('courses.show', $course),
            'END:VEVENT', 'END:VCALENDAR',
        ]);

        return response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.Str::slug($course->title).'-'.$start.'.ics"',
        ]);
    }

    /** Karta szkolenia do pobrania (PDF): opis, program, najbliższe terminy, cena, kontakt. */
    public function card(Course $course)
    {
        abort_unless($course && $course->status === 'active', 404);
        $course->load('editions');

        return Pdf::loadView('courses.pdf', [
            'course' => $course, 'editions' => $course->upcomingEditions()->values(),
            'siteSettings' => \App\Models\SiteSetting::current(), 'printedAt' => now()->format('d.m.Y'),
        ])->setPaper('a4')->download(Str::slug($course->title).'-karta.pdf');
    }
}
