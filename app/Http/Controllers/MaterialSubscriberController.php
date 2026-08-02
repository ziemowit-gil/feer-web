<?php

namespace App\Http\Controllers;

use App\Models\MaterialSubscriber;
use Illuminate\Http\Request;

/**
 * Zapis adresu e-mail na listę powiadomień o nowych materiałach edukacyjnych.
 *
 * Metody: store().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class MaterialSubscriberController extends Controller
{
    /** Dodaje adres e-mail do listy powiadomień o nowych materiałach edukacyjnych (bez duplikatów). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ], [], ['email' => 'adres e-mail']);

        // firstOrCreate keeps the list unique and treats a repeat sign-up as a
        // success rather than a "duplicate" error.
        MaterialSubscriber::firstOrCreate(['email' => $data['email']]);

        return redirect()
            ->route('materials.index')
            ->with('materials_subscribed', true)
            ->withFragment('zapis');
    }
}
