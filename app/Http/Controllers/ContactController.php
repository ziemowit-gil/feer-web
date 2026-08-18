<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Strona kontaktowa z formularzem wysyłającym e-mail do ogólnego kontaktu
 * lub wybranego koordynatora projektu.
 *
 * Metody: index(), store(), coordinators().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class ContactController extends Controller
{
    /** Wyświetla stronę kontaktową. */
    public function index()
    {
        $coordinators = $this->loadCoordinators();

        return view('contact.show', compact('coordinators'));
    }

    /** Waliduje wiadomość kontaktową, zapisuje do bazy i wysyła e-mail. */
    public function store(Request $request)
    {
        $coordinators = $this->loadCoordinators();

        // Zbuduj listę dozwolonych e-maili koordynatorów do walidacji.
        $allowedEmails = $coordinators->pluck('email')->filter()->values()->all();

        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'email', 'max:255'],
            'phone'             => ['nullable', 'string', 'max:30'],
            'subject'           => ['nullable', 'string', 'max:255'],
            'coordinator_email' => ['nullable', 'string', ...(empty($allowedEmails) ? [] : [
                \Illuminate\Validation\Rule::in($allowedEmails),
            ])],
            'message'           => ['required', 'string', 'max:5000'],
            'rodo_consent'      => ['accepted'],
            'website'           => ['prohibited'],
        ], [
            'rodo_consent.accepted'      => 'Aby wysłać wiadomość, musisz wyrazić zgodę na przetwarzanie danych osobowych.',
            'website.prohibited'         => 'Wykryto nieprawidłowe zgłoszenie.',
            'coordinator_email.in'       => 'Wybierz koordynatora z listy.',
        ]);

        // Znajdź wybranego koordynatora po e-mailu.
        $selectedCoordinator = filled($data['coordinator_email'] ?? null)
            ? $coordinators->firstWhere('email', $data['coordinator_email'])
            : null;

        $contactMessage = ContactMessage::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'phone'             => $data['phone'] ?? null,
            'subject'           => $data['subject'] ?? null,
            'coordinator_name'  => $selectedCoordinator['name'] ?? null,
            'coordinator_email' => $selectedCoordinator['email'] ?? null,
            'message'           => $data['message'],
            'ip_address'        => $request->ip(),
        ]);

        // Wyślij do koordynatora lub ogólnego adresu kontaktowego.
        $recipient = $selectedCoordinator['email'] ?? SiteSetting::current()->contact_email;

        if (filled($recipient)) {
            try {
                Mail::to($recipient)->send(new ContactMessageMail($contactMessage));
                $contactMessage->update(['email_sent_at' => now()]);
            } catch (\Throwable) {
                // Wiadomość zapisana w bazie — niepowodzenie e-maila nie blokuje użytkownika.
            }
        }

        return redirect()->route('contact.show')
            ->with('status', 'Wiadomość została wysłana. Odpowiemy najszybciej, jak to możliwe.');
    }

    /** Zwraca kolekcję koordynatorów z aktywnych projektów jako tablice [name, email, project]. */
    private function loadCoordinators(): \Illuminate\Support\Collection
    {
        $settings = SiteSetting::current();

        if (! $settings->isModuleEnabled('projects')) {
            return collect();
        }

        return Project::where('is_published', true)
            ->where('is_completed', false)
            ->where('show_coordinator', true)
            ->orderByDesc('is_featured_contact')
            ->orderBy('title')
            ->get()
            ->map(fn ($p) => [
                'name'    => $p->coordinator_name ?: $p->title,
                'email'   => $p->contactEmail(),
                'project' => $p->title,
            ])
            ->filter(fn ($c) => filled($c['email']))
            ->values();
    }
}
