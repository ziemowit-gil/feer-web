<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Obsługuje ręczne przekierowania 301. Dla żądań GET sprawdza, czy ścieżka
 * pasuje do aktywnego przekierowania i — jeśli tak — zwraca 301, zanim
 * zadziała routing (a więc także zamiast strony 404).
 */
class HandleRedirects
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('GET') && Schema::hasTable('redirects')) {
            $from = Redirect::normalizePath($request->path());

            $redirect = Redirect::where('is_active', true)->where('from_path', $from)->first();

            if ($redirect) {
                $redirect->increment('hits');

                return redirect()->to($redirect->to_url, 301);
            }
        }

        return $next($request);
    }
}
