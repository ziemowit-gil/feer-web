<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Models\Project;
use App\Support\SpamGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/** Dedykowany formularz „Zadaj pytanie” do koordynatora konkretnego działania. */
class ProjectQuestionController extends Controller
{
    public function show(Project $project)
    {
        abort_unless($project->is_published && ! $project->is_completed, 404);

        return view('projects.ask', [
            'project' => $project,
            'challenge' => SpamGuard::challenge(),
        ]);
    }

    public function store(Request $request, Project $project)
    {
        abort_unless($project->is_published && ! $project->is_completed, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:5000'],
            'rodo_consent' => ['accepted'],
            'website' => ['prohibited'],
        ], [
            'rodo_consent.accepted' => 'Aby wysłać pytanie, zaznacz zgodę na przetwarzanie danych osobowych.',
            'website.prohibited' => 'Wykryto nieprawidłowe zgłoszenie.',
        ], [
            'name' => 'Imię i nazwisko', 'email' => 'E-mail', 'phone' => 'Telefon', 'message' => 'Pytanie',
        ]);

        if ($spam = SpamGuard::inspect($request, [$data['name'], $data['email'], $data['phone'] ?? '', $data['message']], 'pytanie-o-dzialanie')) {
            Log::warning('Zablokowano pytanie o działanie jako spam', ['powód' => $spam['reason'], 'ip' => $request->ip()]);

            return $spam['silent']
                ? redirect()->route('projects.show', $project)->with('status', 'Pytanie zostało wysłane. Odpowiemy najszybciej, jak to możliwe.')
                : back()->withErrors(['spam' => $spam['message']])->withInput();
        }

        $email = $project->contactEmail();
        $contactMessage = ContactMessage::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => 'Pytanie o działanie: '.$project->title,
            'coordinator_name' => $project->coordinator_name ?: $project->title,
            'coordinator_email' => $email,
            'message' => $data['message'],
            'ip_address' => $request->ip(),
        ]);

        if (filled($email)) {
            try {
                Mail::to($email)->send(new ContactMessageMail($contactMessage));
                $contactMessage->update(['email_sent_at' => now()]);
            } catch (\Throwable) {
                // Wiadomość zapisana w bazie — niepowodzenie e-maila nie blokuje użytkownika.
            }
        }

        return redirect()->route('projects.show', $project)
            ->with('status', 'Pytanie zostało wysłane. Odpowiemy najszybciej, jak to możliwe.');
    }
}
