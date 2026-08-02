<?php

namespace App\Http\Controllers;

use App\Mail\AccessibilityReportMail;
use App\Models\AccessibilityReport;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Przyjmuje zgłoszenia barier dostępności z formularza na stronie publicznej
 * i wysyła powiadomienie mailowe na adres kontaktowy organizacji.
 *
 * Metody: store().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class AccessibilityReportController extends Controller
{
    /** Waliduje i zapisuje zgłoszenie bariery dostępności, a następnie wysyła powiadomienie e-mail. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'page_url' => ['nullable', 'string', 'max:500'],
            'message' => ['required', 'string', 'max:5000'],
            'rodo_consent' => ['accepted'],
            'website' => ['prohibited'], // honeypot
        ], [
            'rodo_consent.accepted' => 'Aby wysłać zgłoszenie, musisz wyrazić zgodę na przetwarzanie danych osobowych.',
            'website.prohibited' => 'Wykryto nieprawidłowe zgłoszenie.',
        ]);

        $report = AccessibilityReport::create([
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'page_url' => $data['page_url'] ?? null,
            'message' => $data['message'],
        ]);

        // Zapis w panelu jest najważniejszy — błąd poczty nie może zgubić
        // zgłoszenia ani pokazać odwiedzającemu błędu 500.
        if ($recipient = SiteSetting::current()->accessibilityContactEmail()) {
            try {
                Mail::to($recipient)->send(new AccessibilityReportMail(
                    $report->email,
                    $report->message,
                    $report->name,
                    $report->page_url,
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()
            ->route('accessibility.show')
            ->with('accessibility_reported', true)
            ->withFragment('zglos-bariere');
    }
}
