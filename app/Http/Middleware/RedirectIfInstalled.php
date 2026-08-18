<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (file_exists(storage_path('app/installed.lock'))) {
            return redirect('/');
        }

        return $next($request);
    }
}
