<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        // Sesja, która właśnie ukończyła instalację, musi jeszcze zobaczyć ekran
        // "Gotowe" (m.in. jednorazowe pobranie certyfikatu super-admina) — dopiero
        // kolejni odwiedzający /install po zakończonej instalacji są przekierowywani.
        if (file_exists(storage_path('app/installed.lock')) && $request->session()->get('install_step') !== 'done') {
            return redirect('/');
        }

        return $next($request);
    }
}
