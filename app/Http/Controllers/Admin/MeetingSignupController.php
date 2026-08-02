<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MeetingSignup;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Panel admin: lista zgłoszeń „Daj znać, że przyjdziesz" z możliwością usunięcia i eksportu CSV.
 *
 * Metody: index(), destroy(), export().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class MeetingSignupController extends Controller
{
    /** Wyświetla listę zgłoszeń na spotkania. */
    public function index()
    {
        return view('admin.meeting-signups.index', [
            'signups' => MeetingSignup::latest()->paginate(50),
            'total' => MeetingSignup::count(),
        ]);
    }

    /** Usuwa zgłoszenie na spotkanie. */
    public function destroy(MeetingSignup $signup)
    {
        $signup->delete();

        return redirect()
            ->route('admin.zgloszenia-spotkania.index')
            ->with('status', 'Zgłoszenie zostało usunięte.');
    }

    /** Eksportuje zgłoszenia na spotkania do pliku CSV. */
    public function export(): StreamedResponse
    {
        $filename = 'zgloszenia-spotkania-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['imię i nazwisko', 'email', 'termin', 'wiadomość', 'data zgłoszenia']);

            MeetingSignup::orderBy('created_at')->chunk(200, function ($chunk) use ($handle) {
                foreach ($chunk as $signup) {
                    fputcsv($handle, [
                        $signup->name,
                        $signup->email,
                        $signup->term,
                        $signup->message,
                        $signup->created_at->format('Y-m-d H:i'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
