<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\Request;

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

    /** Trwale usuwa wszystkie wiadomości z wybranego filtra (wszystkie / nieprzeczytane / przeczytane). */
    public function destroyAll(Request $request)
    {
        $filter = $request->input('filtr', 'wszystkie');

        $query = ContactMessage::query();
        if ($filter === 'nieprzeczytane') {
            $query->unread();
        } elseif ($filter === 'przeczytane') {
            $query->read();
        } else {
            $filter = 'wszystkie';
        }

        $count = $query->count();
        $query->delete();

        return redirect()->route('admin.wiadomosci-kontaktowe.index', ['filtr' => $filter])
            ->with('status', $count > 0 ? "Usunięto wiadomości: {$count}." : 'Brak wiadomości do usunięcia.');
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
}
