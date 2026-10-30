<?php

namespace App\Http\Controllers;

use App\Models\MaterialSubscriber;
use App\Support\SpamGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        if ($spam = SpamGuard::inspect($request, [$data['email']], 'materialy-zapis')) {
            Log::warning('Zablokowano zapis na materiały jako spam', ['powód' => $spam['reason'], 'ip' => $request->ip()]);

            // Bot dostaje „sukces”, człowiek z błędnym wynikiem zadania widzi komunikat i może spróbować ponownie.
            return $spam['silent']
                ? redirect()->route('materials.index')->with('materials_subscribed', true)->withFragment('zapis')
                : back()->withErrors(['spam' => $spam['message']])->withInput()->withFragment('zapis');
        }

        // firstOrCreate keeps the list unique and treats a repeat sign-up as a
        // success rather than a "duplicate" error.
        MaterialSubscriber::firstOrCreate(['email' => $data['email']]);

        return redirect()
            ->route('materials.index')
            ->with('materials_subscribed', true)
            ->withFragment('zapis');
    }
}
