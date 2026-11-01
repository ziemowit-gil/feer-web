<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Support\Helpdesk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Panel "Pomoc" — zgłoszenia do Helpdesku Centralnego prowadzone bezpośrednio
 * z CMS-a, bez konieczności logowania się na osobny portal. Wysyłka i pobranie
 * odpowiedzi idą przez App\Support\Helpdesk; jeśli integracja zgłoszeń jest
 * wyłączona (brak HELPDESK_API_TOKEN albo localhost/testy.*, patrz
 * Helpdesk::ticketsEnabled()), zgłoszenie zostaje zapisane lokalnie i wyśle
 * się automatycznie, gdy tylko integracja zacznie działać (patrz helpdesk:sync).
 */
class HelpdeskTicketController extends Controller
{
    public function index(Helpdesk $helpdesk): View
    {
        $tickets = SupportTicket::with('submittedBy')->latest()->paginate(20);

        return view('admin.pomoc.index', [
            'tickets' => $tickets,
            'ticketsEnabled' => $helpdesk->ticketsEnabled(),
        ]);
    }

    public function create(): View
    {
        return view('admin.pomoc.create');
    }

    public function store(Request $request, Helpdesk $helpdesk): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:10000'],
        ]);

        $ticket = SupportTicket::create([
            'subject' => $data['subject'],
            'submitted_by' => $request->user()->id,
        ]);

        $messageId = (string) Str::uuid();

        $ticket->messages()->create([
            'sender_type' => 'me',
            'remote_message_id' => $messageId,
            'body' => $data['message'],
        ]);

        $helpdesk->submitTicket($ticket, $data['message'], $messageId);

        return redirect()->route('admin.pomoc.show', $ticket)
            ->with('success', $ticket->submitted()
                ? 'Zgłoszenie zostało wysłane do Helpdesku.'
                : 'Zgłoszenie zapisane — wyśle się automatycznie, gdy integracja z Helpdeskiem będzie dostępna.');
    }

    public function show(SupportTicket $ticket, Helpdesk $helpdesk): View
    {
        if ($ticket->submitted()) {
            $helpdesk->pullTicketUpdates($ticket);
            $ticket->refresh();
        }

        return view('admin.pomoc.show', [
            'ticket' => $ticket->load('messages', 'submittedBy'),
            'ticketsEnabled' => $helpdesk->ticketsEnabled(),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket, Helpdesk $helpdesk): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:10000'],
        ]);

        $messageId = (string) Str::uuid();

        $ticket->messages()->create([
            'sender_type' => 'me',
            'remote_message_id' => $messageId,
            'body' => $data['message'],
        ]);

        $sent = $helpdesk->submitTicket($ticket, $data['message'], $messageId);

        return redirect()->route('admin.pomoc.show', $ticket)
            ->with('success', $sent
                ? 'Wiadomość wysłana.'
                : 'Wiadomość zapisana — wyśle się automatycznie, gdy integracja z Helpdeskiem będzie dostępna.');
    }
}
