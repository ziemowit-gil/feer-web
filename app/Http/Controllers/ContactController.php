<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Models\HelpPoint;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Support\CleanTalkGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
    /** Wyświetla stronę kontaktową w wybranym w ustawieniach wariancie wyglądu. */
    public function index()
    {
        // Gdy redakcja utworzyła stronę typu „Kontakt” (albo stronę o adresie „kontakt-2”),
        // adres /kontakt kieruje na nią; sama ta strona renderuje widok przez show().
        if ($target = $this->contactPageTarget()) {
            return redirect()->to($target->publicUrl());
        }

        return $this->render();
    }

    /** Opublikowana strona, na którą ma kierować /kontakt (typ „contact”, a w drugiej kolejności slug „kontakt-2”). */
    private function contactPageTarget(): ?\App\Models\Page
    {
        $live = fn ($q) => $q->where('is_published', true)->where(fn ($w) => $w->whereNull('publish_at')->orWhere('publish_at', '<=', now()));

        return \App\Models\Page::forCurrentSite()->where($live)->where('type', 'contact')->orderBy('id')->first()
            ?? \App\Models\Page::forCurrentSite()->where($live)->where('slug', 'kontakt-2')->first();
    }

    /** Wyświetla stronę kontaktową bez przekierowania (używane też przez stronę typu „Kontakt”). */
    public function render()
    {
        $settings = SiteSetting::current();

        // Widoczność sekcji i ich dane liczymy tu, bo korzystają z nich oba
        // warianty strony (klasyczny i kafelkowy) oraz wspólne partiale.
        $meetingTitle  = $settings->contact_meeting_title ?: 'Spotkajmy się';
        $onlineUrl     = $settings->contact_online_meeting_url;
        $scheduleItems = $settings->contactScheduleUpcoming();
        $showMeetings  = filled($onlineUrl) || ! empty($scheduleItems) || filled($settings->contact_remote_note);

        $pkCode    = $settings->contact_paczkomat_code;
        $pkAddr    = $settings->contact_paczkomat_address;
        $shipNote  = $settings->contact_shipping_note;
        $shipPhone = $settings->contact_shipping_phone;
        $showShipping = filled($shipNote) || filled($pkCode) || filled($pkAddr) || filled($shipPhone);

        // Mapa „Nasze lokalizacje" — tylko w szablonie federacji, gdy moduł jest
        // włączony; lokalizacje zarządzane z panelu (Mapa pomocy).
        $locations = ($settings->site_template === 'federation' && $settings->isModuleEnabled('help_map'))
            ? HelpPoint::where('is_published', true)->orderBy('order')->orderBy('name')->get()
            : collect();

        $view = match ($settings->contactLayoutValue()) {
            'split' => 'contact.show-split',
            'card'  => 'contact.show-card',
            'tabs'  => 'contact.show-tabs',
            'feer'  => 'contact.show-feer',
            default => 'contact.show',
        };

        return view($view, [
            'coordinators'  => $this->loadCoordinators(),
            'meetingTitle'  => $meetingTitle,
            'onlineUrl'     => $onlineUrl,
            'scheduleItems' => $scheduleItems,
            'showMeetings'  => $showMeetings,
            'pkCode'        => $pkCode,
            'pkAddr'        => $pkAddr,
            'shipNote'      => $shipNote,
            'shipPhone'     => $shipPhone,
            'showShipping'  => $showShipping,
            'locations'     => $locations,
        ]);
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
        ], [
            'name'    => 'Imię i nazwisko',
            'email'   => 'E-mail',
            'phone'   => 'Telefon',
            'subject' => 'Temat',
            'message' => 'Wiadomość',
        ]);

        // CleanTalk (opcjonalnie, wg ustawień): ocena wiadomości po poprawnej walidacji.
        // Awaria usługi, zły klucz lub wyczerpany limit = przepuszczamy (patrz CleanTalkGuard).
        if (CleanTalkGuard::enabled()) {
            $verdict = app(CleanTalkGuard::class)->checkMessage(
                $request,
                trim(($data['subject'] ?? '')."\n".$data['message']),
                $data['email'],
                $data['name'],
            );

            if (! $verdict['allow']) {
                Log::warning('Zablokowano wiadomość kontaktową (CleanTalk)', [
                    'komentarz' => $verdict['comment'],
                    'zapytanie' => $verdict['link'],
                    'ip'        => $request->ip(),
                ]);

                return back()
                    ->withErrors(['message' => 'Wiadomość została uznana za spam przez zewnętrzny filtr antyspamowy. '
                        .'Jeśli to pomyłka, zmień treść lub napisz do nas bezpośrednio na adres e-mail podany na tej stronie.'])
                    ->withInput();
            }
        }

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

        return Project::forCurrentSite()->where('is_published', true)
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
