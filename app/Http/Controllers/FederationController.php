<?php

namespace App\Http\Controllers;

use App\Models\FederationMembershipApplication;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Widoki specyficzne dla szablonu „federation" (federacja organizacji pozarządowych).
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class FederationController extends Controller
{
    /** Strona „Dołącz do nas" — dlaczego warto, jak dołączyć, potrzebne dokumenty, formularz zgłoszeniowy. */
    public function joinUs()
    {
        $docsPage = Page::where('slug', 'dokumenty-do-pobrania')->first();
        $documents = $docsPage?->attachments ?? collect();
        $benefits = SiteSetting::current()->federationJoinBenefits();

        return view('templates.federation.join-us', compact('documents', 'benefits'));
    }

    /** Przyjmuje zgłoszenie przystąpienia do federacji wraz ze skanami dokumentów. */
    public function submitApplication(Request $request)
    {
        $data = $request->validate([
            'organization_name' => ['required', 'string', 'max:200'],
            'contact_name'      => ['required', 'string', 'max:120'],
            'email'             => ['required', 'email', 'max:200'],
            'phone'             => ['nullable', 'string', 'max:30'],
            'message'           => ['nullable', 'string', 'max:2000'],
            'documents'         => ['required', 'array', 'min:1'],
            'documents.*'       => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
            'privacy'           => ['accepted'],
        ], [
            'organization_name.required' => 'Nazwa organizacji jest wymagana.',
            'contact_name.required' => 'Imię i nazwisko osoby zgłaszającej jest wymagane.',
            'email.required' => 'Adres e-mail jest wymagany.',
            'documents.required' => 'Dołącz przynajmniej jeden skan dokumentu.',
            'documents.*.mimes' => 'Dokumenty muszą być w formacie PDF, JPG lub PNG.',
            'documents.*.max' => 'Każdy plik może mieć maksymalnie 8 MB.',
            'privacy.accepted' => 'Wymagana zgoda na przetwarzanie danych.',
        ]);

        $application = FederationMembershipApplication::create([
            'organization_name' => $data['organization_name'],
            'contact_name'      => $data['contact_name'],
            'email'             => $data['email'],
            'phone'             => $data['phone'] ?? null,
            'message'           => $data['message'] ?? null,
        ]);

        foreach ($request->file('documents', []) as $file) {
            $application->addMedia($file)->toMediaCollection('documents');
        }

        $recipient = SiteSetting::current()->contact_email;
        if (filled($recipient)) {
            try {
                Mail::raw(
                    "Nowe zgłoszenie przystąpienia do federacji.\n\n"
                    ."Organizacja: {$application->organization_name}\n"
                    ."Osoba zgłaszająca: {$application->contact_name}\n"
                    ."E-mail: {$application->email}\n"
                    ."Telefon: {$application->phone}\n\n"
                    ."Wiadomość:\n{$application->message}\n\n"
                    .'Szczegóły i dokumenty w panelu administracyjnym.',
                    fn ($m) => $m->to($recipient)->subject('Nowe zgłoszenie przystąpienia do federacji')
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('federation.join')
            ->with('application_sent', 'Dziękujemy! Twoje zgłoszenie zostało przesłane — odezwiemy się wkrótce.');
    }

    /** Lista organizacji członkowskich federacji. */
    public function organizations()
    {
        $organizations = [
            'Uniwersytet Trzeciego Wieku w Andrychowie',
            'Klub Żeglarski HORN Kraków',
            'Ogólnopolski Związek Inwalidów Narządu Ruchu',
            'Stowarzyszenie Absolwentów Liceum Ogólnokształcącego im. Marcina Wadowity w Wadowicach',
            'Krakowskie Towarzystwo Pomocy Uzależnionym',
            'Stowarzyszenie Przyjaciół im. Św. Brata Alberta',
            'Polski Związek Niewidomych. Okręg małopolski',
            'Regionalne Stowarzyszenie Diabetyków z Siedzibą w Chrzanowie',
            'Fundacja na Rzecz Chorych na SM im. bł. Anieli Salawy',
            'Polski Związek Emerytów, Rencistów i Inwalidów. Zarząd oddziału rejonowego Kraków - Podgórze',
            'Stowarzyszenie Pomocy Szkole Małopolska',
            'Stowarzyszenia Przyjaciół Osób Niepełnosprawnych Wspólna Radość',
            'Bank Żywności w Krakowie',
            'Stowarzyszenie Dobroczynne "Betlejem"',
            'Chrześcijańskie Stowarzyszenie Dobroczynne',
            'Polskie Stowarzyszenie na Rzecz Osób z Upośledzeniem Umysłowym Koło w Jabłonce',
            'Fundacja Dla Dzieci Młodzieży I Dorosłych Niepełnosprawnych Intelektualnie',
            'Krajowe Towarzystwo Autyzmu Oddział w Krakowie',
            'Stowarzyszenie na rzecz Domu Pomocy Społecznej im. Św. Brata Alberta w Krakowie oraz osób niepełnosprawnych',
            'Krakowskie Stowarzyszenie Terapeutów Uzależnień',
            'Stowarzyszenie Lekarzy Nadziei',
            'Stowarzyszenie Rodzin Adopcyjnych i Zastępczych „Pro Familia"',
            'Krakowska Fundacja Pomocy Potrzebującym "Nasz Dom" im. Św. Brata Alberta',
            'Stowarzyszenie Przyjaciół Harcerstwa',
            'Stowarzyszenie Rozwoju i Integracji Młodzieży ST.R.I.M',
            'Małopolski Związek Osób Niepełnosprawnych w Bochni',
        ];

        return view('templates.federation.organizations', compact('organizations'));
    }
}
