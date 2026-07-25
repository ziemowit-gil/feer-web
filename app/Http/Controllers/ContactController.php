<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function index()
    {
        // Coordinator contacts for the contact page: only live (non-archival)
        // projects whose coordinator is not hidden, with featured contacts pulled
        // to the front. The site-wide master switch can hide them all at once.
        $settings = SiteSetting::current();
        $projects = ($settings->isModuleEnabled('projects') && $settings->show_coordinators)
            ? Project::where('is_published', true)
                ->where('is_completed', false)
                ->where('show_coordinator', true)
                ->with('category')
                ->orderByDesc('is_featured_contact')
                ->orderBy('title')
                ->get()
            : collect();

        return view('contact.show', compact('projects'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'rodo_consent' => ['accepted'],
            'website' => ['prohibited'],
        ], [
            'rodo_consent.accepted' => 'Aby wysłać wiadomość, musisz wyrazić zgodę na przetwarzanie danych osobowych.',
            'website.prohibited' => 'Wykryto nieprawidłowe zgłoszenie.',
        ]);

        Mail::to(SiteSetting::current()->contact_email)
            ->send(new ContactMessageMail($data['name'], $data['email'], $data['message']));

        return redirect()->route('contact.show')->with('status', 'Wiadomość została wysłana. Odpowiemy najszybciej, jak to możliwe.');
    }
}
