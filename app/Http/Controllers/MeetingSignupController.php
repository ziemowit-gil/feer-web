<?php

namespace App\Http\Controllers;

use App\Mail\MeetingSignupMail;
use App\Models\MeetingSignup;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class MeetingSignupController extends Controller
{
    public function publicShow(): View
    {
        $siteSettings = SiteSetting::current();
        $scheduleItems = $siteSettings->contactScheduleUpcoming();

        return view('booking.show', compact('siteSettings', 'scheduleItems'));
    }

    public function publicStore(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:30'],
            'term'         => ['nullable', 'string', 'max:255'],
            'message'      => ['nullable', 'string', 'max:2000'],
            'rodo_consent' => ['accepted'],
            'website'      => ['prohibited'],
        ], [
            'rodo_consent.accepted' => 'Aby wysłać zgłoszenie, musisz wyrazić zgodę na przetwarzanie danych osobowych.',
            'website.prohibited'    => 'Wykryto nieprawidłowe zgłoszenie.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'booking')
                ->withInput();
        }

        $data = $validator->validated();

        $signup = MeetingSignup::create([
            'name'    => $data['name'],
            'email'   => $data['email'],
            'phone'   => $data['phone'] ?? null,
            'term'    => $data['term'] ?? null,
            'message' => $data['message'] ?? null,
        ]);

        if ($recipient = SiteSetting::current()->meetingNotifyEmail()) {
            try {
                Mail::to($recipient)->send(new MeetingSignupMail(
                    $signup->name,
                    $signup->email,
                    $signup->term,
                    $signup->message,
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('booking.show')->with('booking_signed_up', true);
    }

    public function store(Request $request)
    {
        // Nazwany error bag „meeting”, aby błędy tego formularza (modal na
        // /kontakt) nie mieszały się z formularzem kontaktowym, który ma te
        // same pola (name/email) i korzysta z domyślnego error bag.
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'term' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
            'rodo_consent' => ['accepted'],
            'website' => ['prohibited'],
        ], [
            'rodo_consent.accepted' => 'Aby wysłać zgłoszenie, musisz wyrazić zgodę na przetwarzanie danych osobowych.',
            'website.prohibited' => 'Wykryto nieprawidłowe zgłoszenie.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'meeting')
                ->withInput()
                ->withFragment('spotkania');
        }

        $data = $validator->validated();

        $signup = MeetingSignup::create([
            'name'    => $data['name'],
            'email'   => $data['email'],
            'phone'   => $data['phone'] ?? null,
            'term'    => $data['term'] ?? null,
            'message' => $data['message'] ?? null,
        ]);

        // Zgłoszenie trafia na dedykowany adres (lub ogólny kontaktowy). Zapis
        // jest najważniejszy — błąd poczty (np. zła konfiguracja SMTP) nie może
        // pokazać odwiedzającemu błędu 500 ani zgubić zgłoszenia z panelu.
        if ($recipient = SiteSetting::current()->meetingNotifyEmail()) {
            try {
                Mail::to($recipient)->send(new MeetingSignupMail(
                    $signup->name,
                    $signup->email,
                    $signup->term,
                    $signup->message,
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()
            ->route('contact.show')
            ->with('meeting_signed_up', true)
            ->withFragment('spotkania');
    }
}
