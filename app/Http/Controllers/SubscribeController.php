<?php

namespace App\Http\Controllers;

use App\Mail\SubscriptionConfirmationMail;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Publiczny flow zapisu na tematyczne powiadomienia e-mail.
 *
 * Metody: create(), store(), confirm(), unsubscribe(), doUnsubscribe().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class SubscribeController extends Controller
{
    /** Formularz zapisu z wyborem tematów. */
    public function create()
    {
        return view('subscribe.form', ['topics' => Subscriber::$availableTopics]);
    }

    /** Rejestruje subskrybenta i wysyła e-mail potwierdzający (double opt-in). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'email', 'max:255'],
            'name'     => ['nullable', 'string', 'max:100'],
            'topics'   => ['required', 'array', 'min:1'],
            'topics.*' => ['string', 'in:' . implode(',', array_keys(Subscriber::$availableTopics))],
        ]);

        $subscriber = Subscriber::firstOrNew(['email' => $data['email']]);

        if ($subscriber->isConfirmed()) {
            $subscriber->name   = $data['name'] ?? $subscriber->name;
            $subscriber->topics = $data['topics'];
            $subscriber->save();

            return redirect()->route('subskrypcje.pending')->with('updated', true);
        }

        $subscriber->name   = $data['name'] ?? null;
        $subscriber->topics = $data['topics'];
        $subscriber->token  = Subscriber::generateToken();
        $subscriber->confirmed_at = null;
        $subscriber->save();

        Mail::to($subscriber->email)->send(new SubscriptionConfirmationMail($subscriber));

        return redirect()->route('subskrypcje.pending');
    }

    /** Strona "sprawdź skrzynkę". */
    public function pending()
    {
        return view('subscribe.pending', ['updated' => session('updated', false)]);
    }

    /** Potwierdza subskrypcję przez token z e-maila. */
    public function confirm(string $token)
    {
        $subscriber = Subscriber::where('token', $token)->firstOrFail();

        if (! $subscriber->isConfirmed()) {
            $subscriber->update(['confirmed_at' => now()]);
        }

        return view('subscribe.confirmed', ['subscriber' => $subscriber]);
    }

    /** Strona potwierdzenia wypisania. */
    public function unsubscribe(string $token)
    {
        $subscriber = Subscriber::where('token', $token)->firstOrFail();

        return view('subscribe.unsubscribe', ['subscriber' => $subscriber]);
    }

    /** Usuwa subskrybenta po potwierdzeniu wypisania. */
    public function doUnsubscribe(string $token)
    {
        Subscriber::where('token', $token)->delete();

        return redirect()->route('home')->with('status', 'Zostałeś/łaś wypisany/a z powiadomień.');
    }
}
