<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactMessageController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filtr', 'wszystkie');

        $query = ContactMessage::latest();

        if ($filter === 'nieprzeczytane') {
            $query->unread();
        } elseif ($filter === 'przeczytane') {
            $query->read();
        }

        $messages    = $query->paginate(25)->withQueryString();
        $unreadCount = ContactMessage::unreadCount();

        return view('admin.contact-messages.index', compact('messages', 'filter', 'unreadCount'));
    }

    public function show(ContactMessage $contactMessage)
    {
        $contactMessage->markAsRead();

        return view('admin.contact-messages.show', compact('contactMessage'));
    }

    public function destroy(ContactMessage $contactMessage)
    {
        $contactMessage->delete();

        return redirect()->route('admin.wiadomosci-kontaktowe.index')
            ->with('status', 'Wiadomość usunięta.');
    }

    public function markReplied(ContactMessage $contactMessage)
    {
        $contactMessage->markAsReplied();

        return back()->with('status', 'Oznaczono jako odpowiedziano.');
    }

    /** Wysyła testową wiadomość e-mail do zalogowanego admina. */
    public function mailTest(Request $request)
    {
        $user = $request->user();

        try {
            Mail::raw(
                'To jest testowa wiadomość e-mail z panelu CMS. Jeśli ją widzisz, konfiguracja poczty działa poprawnie.',
                fn ($m) => $m->to($user->email)->subject('Test wysyłki poczty — CMS')
            );

            return back()->with('mail_test_status', 'success')
                ->with('mail_test_msg', "Wiadomość testowa wysłana na {$user->email}.");
        } catch (\Throwable $e) {
            return back()->with('mail_test_status', 'error')
                ->with('mail_test_msg', 'Błąd wysyłki: ' . $e->getMessage());
        }
    }
}
