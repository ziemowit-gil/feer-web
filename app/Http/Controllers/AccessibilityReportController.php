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
    /** Rodzaje zgłoszeń (art. 18 ustawy o dostępności cyfrowej: żądanie zapewnienia dostępności). */
    public const KINDS = [
        'bariera' => 'Zgłoszenie bariery',
        'zadanie' => 'Żądanie zapewnienia dostępności',
        'alternatywa' => 'Prośba o dostęp alternatywny',
    ];

    /** Rodzaje barier do zaznaczenia (opcjonalnie, wiele naraz). */
    public const BARRIERS = [
        'czytnik' => 'Czytnik ekranu',
        'klawiatura' => 'Obsługa klawiaturą',
        'kontrast' => 'Kontrast, kolory, powiększanie',
        'multimedia' => 'Film lub nagranie (napisy, transkrypcja)',
        'dokument' => 'Dokument do pobrania (PDF, DOCX)',
        'zrozumialosc' => 'Zrozumiałość tekstu',
        'inne' => 'Inne',
    ];

    /** Preferowany sposób odpowiedzi / dostępu alternatywnego. */
    public const CONTACT = [
        'email' => 'E-mail',
        'telefon' => 'Telefon',
        'list' => 'List tradycyjny',
    ];

    /** Waliduje i zapisuje zgłoszenie bariery dostępności, a następnie wysyła powiadomienie e-mail. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'kind' => ['nullable', 'in:'.implode(',', array_keys(self::KINDS))],
            'barriers' => ['nullable', 'array'],
            'barriers.*' => ['in:'.implode(',', array_keys(self::BARRIERS))],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40', 'required_if:contact,telefon'],
            'contact' => ['nullable', 'in:'.implode(',', array_keys(self::CONTACT))],
            'page_url' => ['nullable', 'string', 'max:500'],
            'message' => ['required', 'string', 'max:5000'],
            'rodo_consent' => ['accepted'],
            'website' => ['prohibited'], // honeypot
        ], [
            'email.required' => 'Podaj adres e-mail — na niego wyślemy odpowiedź.',
            'email.email' => 'Adres e-mail wygląda na niepełny, np. jan.kowalski@poczta.pl.',
            'phone.required_if' => 'Podaj numer telefonu, skoro wolisz kontakt telefoniczny.',
            'message.required' => 'Opisz problem — choćby jednym zdaniem.',
            'rodo_consent.accepted' => 'Aby wysłać zgłoszenie, zaznacz zgodę na przetwarzanie danych osobowych.',
            'website.prohibited' => 'Wykryto nieprawidłowe zgłoszenie.',
        ]);

        // Dodatkowe informacje trafiają na początek opisu (panel i e-mail pokazują je bez zmian w bazie).
        $meta = array_filter([
            'Rodzaj zgłoszenia' => self::KINDS[$data['kind'] ?? ''] ?? null,
            'Rodzaj bariery' => implode(', ', array_map(fn ($b) => self::BARRIERS[$b], $data['barriers'] ?? [])) ?: null,
            'Preferowany kontakt' => self::CONTACT[$data['contact'] ?? ''] ?? null,
            'Telefon' => filled($data['phone'] ?? null) ? trim($data['phone']) : null,
        ]);
        $message = $meta
            ? implode("\n", array_map(fn ($k, $v) => "{$k}: {$v}", array_keys($meta), $meta))."\n\n".$data['message']
            : $data['message'];

        $report = AccessibilityReport::create([
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'page_url' => $data['page_url'] ?? null,
            'message' => $message,
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
