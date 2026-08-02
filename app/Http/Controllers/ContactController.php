<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Strona kontaktowa z listą koordynatorów projektów oraz formularzem wysyłającym e-mail.
 *
 * Metody: index(), store().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class ContactController extends Controller
{
    /** Wyświetla stronę kontaktową z listą koordynatorów aktywnych projektów. */
    public function index()
    {
        $settings = SiteSetting::current();
        $projects = $settings->isModuleEnabled('projects')
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

    /** Waliduje wiadomość kontaktową i wysyła ją na adres e-mail organizacji. */
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
