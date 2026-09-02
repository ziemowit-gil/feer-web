<?php

namespace App\Http\Controllers;

use App\Models\FederationMembershipApplication;
use App\Models\Organization;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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

    /**
     * Pobiera podstawowe dane podmiotu z otwartego API Krajowego Rejestru Sądowego
     * (https://prs.ms.gov.pl/krs/openApi), by ułatwić wypełnienie formularza zgłoszeniowego.
     * Próbuje najpierw rejestru stowarzyszeń, potem przedsiębiorców.
     */
    public function lookupKrs(string $krs)
    {
        $krs = str_pad(preg_replace('/\D/', '', $krs), 10, '0', STR_PAD_LEFT);

        if (strlen($krs) !== 10) {
            return response()->json(['ok' => false, 'message' => 'Numer KRS musi składać się z cyfr (do 10 znaków).'], 422);
        }

        foreach (['S', 'P'] as $rejestr) {
            try {
                $response = Http::timeout(6)->get("https://api-krs.ms.gov.pl/api/krs/OdpisAktualny/{$krs}", [
                    'rejestr' => $rejestr,
                    'format' => 'json',
                ]);
            } catch (\Throwable $e) {
                continue;
            }

            if (! $response->ok()) {
                continue;
            }

            $dzial1 = $response->json('odpis.dane.dzial1');
            $adres = $dzial1['siedzibaIAdres']['adres'] ?? [];
            $addressParts = array_filter([
                trim(($adres['ulica'] ?? '').' '.($adres['nrDomu'] ?? '')),
                $adres['kodPocztowy'] ?? null,
                $adres['miejscowosc'] ?? null,
            ]);

            return response()->json([
                'ok' => true,
                'name' => $dzial1['danePodmiotu']['nazwa'] ?? null,
                'address' => implode(', ', $addressParts) ?: null,
                'website' => $dzial1['siedzibaIAdres']['adresStronyInternetowej'] ?? null,
            ]);
        }

        return response()->json(['ok' => false, 'message' => 'Nie znaleziono podmiotu o podanym numerze KRS.'], 404);
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

    /** Katalog organizacji członkowskich federacji (wyszukiwarka + filtry). */
    public function organizations()
    {
        $organizations = Organization::where('is_test', false)
            ->orderBy('order')->orderBy('name')->get()
            ->map(fn ($org) => [
                'name' => $org->name,
                'slug' => $org->slug,
                'town' => $org->town,
                'type' => $org->type,
                'spheres' => $org->spheres ?? [],
                'sphereIcons' => $org->sphereIcons(),
                'description' => $org->description,
                'mapUrl' => $org->mapUrl(),
                'showUrl' => route('federation.organizations.show', $org),
            ])
            ->values();

        $towns = $organizations->pluck('town')->unique()->sort()->values();
        $types = $organizations->pluck('type')->unique()->sort()->values();

        $townCounts = $organizations->countBy('town')->sortDesc();
        $sphereCounts = $organizations->pluck('spheres')->flatten()->filter()->countBy()->sortDesc();

        $stats = [
            'total' => $organizations->count(),
            'townsCount' => $towns->count(),
            'topTown' => $townCounts->keys()->first(),
            'topTownCount' => $townCounts->first(),
            'topSphere' => $sphereCounts->keys()->first(),
            'topSphereCount' => $sphereCounts->first(),
        ];

        return view('templates.federation.organizations', compact('organizations', 'towns', 'types', 'stats'));
    }

    /** Wizytówka pojedynczej organizacji członkowskiej. */
    public function organizationShow(Organization $organization)
    {
        return view('templates.federation.organization-show', compact('organization'));
    }
}
