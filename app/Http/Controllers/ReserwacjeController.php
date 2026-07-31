<?php

namespace App\Http\Controllers;

use App\Mail\MeetingConfirmationMail;
use App\Mail\ScheduleChangeMail;
use App\Models\MeetingSignup;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReserwacjeController extends Controller
{
    private function guardCheck(): ?RedirectResponse
    {
        if (! auth('member')->check()) {
            return redirect()->route('member.login');
        }
        return null;
    }

    public function index(): mixed
    {
        if ($r = $this->guardCheck()) return $r;

        $settings = SiteSetting::current();
        $schedule = $settings->contact_schedule ?? [];
        $upcoming = $settings->contactScheduleUpcoming();
        $signups  = MeetingSignup::latest()->get();
        $byTerm   = $signups->groupBy('term');
        $noTerm   = $signups->whereNull('term')->merge($signups->where('term', ''));

        return view('rezerwacje.index', compact('settings', 'schedule', 'upcoming', 'signups', 'byTerm', 'noTerm'));
    }

    public function storeTermin(Request $request): RedirectResponse
    {
        if ($r = $this->guardCheck()) return $r;

        $data = $request->validate([
            'type'    => ['required', Rule::in(['date', 'weekly'])],
            'date'    => ['nullable', 'date'],
            'weekday' => ['nullable', 'integer', 'between:1,7'],
            'time'    => ['nullable', 'string', 'max:60'],
            'where'   => ['nullable', 'string', 'max:255'],
            'note'    => ['nullable', 'string', 'max:500'],
        ]);

        $entry = array_filter($data, fn ($v) => $v !== null && $v !== '');

        $settings = SiteSetting::current();
        $list = $settings->contact_schedule ?? [];
        $list[] = $entry;
        $settings->update(['contact_schedule' => $list]);

        return redirect()->route('rezerwacje.index')->with('status', 'Termin został dodany.');
    }

    public function destroyTermin(Request $request, int $index): RedirectResponse
    {
        if ($r = $this->guardCheck()) return $r;

        $settings = SiteSetting::current();
        $list = $settings->contact_schedule ?? [];

        if (array_key_exists($index, $list)) {
            array_splice($list, $index, 1);
            $settings->update(['contact_schedule' => $list ?: null]);
        }

        return redirect()->route('rezerwacje.index')->with('status', 'Termin usunięty.');
    }

    public function notify(): RedirectResponse
    {
        if ($r = $this->guardCheck()) return $r;

        $settings = SiteSetting::current();
        $items    = $settings->contactScheduleUpcoming();
        $emails   = MeetingSignup::pluck('email')->unique()->values()->all();

        if (empty($items)) {
            return redirect()->route('rezerwacje.index')->with('error', 'Brak nadchodzących terminów do wysłania.');
        }
        if (empty($emails)) {
            return redirect()->route('rezerwacje.index')->with('error', 'Brak zapisanych osób do powiadomienia.');
        }

        try {
            $copyTo  = $settings->meetingNotifyEmail();
            $title   = $settings->contact_schedule_title ?: 'Kiedy i gdzie jesteśmy';
            $mailable = new ScheduleChangeMail($items, $settings->site_name, $title);

            $m = Mail::bcc($emails);
            if ($copyTo) {
                $m->cc($copyTo);
            }
            $m->send($mailable);
        } catch (\Throwable) {
            // swallow — mail errors shouldn't break the panel
        }

        return redirect()->route('rezerwacje.index')->with('status', 'Powiadomienie wysłane do '.count($emails).' osób.');
    }

    public function confirmSignup(MeetingSignup $signup): RedirectResponse
    {
        if ($r = $this->guardCheck()) return $r;

        $settings = SiteSetting::current();

        if (! $signup->isConfirmed()) {
            $signup->update(['confirmed_at' => now()]);

            try {
                Mail::to($signup->email)->send(new MeetingConfirmationMail(
                    $signup,
                    $settings->site_name,
                    $settings->meetingNotifyEmail() ?? $settings->contact_email ?? '',
                ));
            } catch (\Throwable) {
                // swallow
            }
        }

        return redirect()->route('rezerwacje.index')->with('status', 'Spotkanie potwierdzone — wysłano e-mail do '.$signup->name.'.');
    }

    public function destroySignup(MeetingSignup $signup): RedirectResponse
    {
        if ($r = $this->guardCheck()) return $r;

        $signup->delete();

        return redirect()->route('rezerwacje.index')->with('status', 'Zgłoszenie usunięte.');
    }

    public function export(): StreamedResponse|RedirectResponse
    {
        if ($r = $this->guardCheck()) return $r;

        $filename = 'rezerwacje-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, "\xEF\xBB\xBF"); // BOM for Excel
            fputcsv($handle, ['Imię i nazwisko', 'E-mail', 'Telefon', 'Termin', 'Wiadomość', 'Potwierdzone', 'Data zgłoszenia']);

            MeetingSignup::orderBy('term')->orderBy('created_at')
                ->chunk(200, function ($chunk) use ($handle) {
                    foreach ($chunk as $s) {
                        fputcsv($handle, [
                            $s->name,
                            $s->email,
                            $s->phone ?? '',
                            $s->term ?? '',
                            $s->message ?? '',
                            $s->confirmed_at ? $s->confirmed_at->format('Y-m-d H:i') : '',
                            $s->created_at->format('Y-m-d H:i'),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
