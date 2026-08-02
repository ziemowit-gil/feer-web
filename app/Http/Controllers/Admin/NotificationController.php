<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Panel admin: oznaczanie powiadomień jako przeczytanych (JSON API, wywołanie z dropdown).
 *
 * Metody: seen().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class NotificationController extends Controller
{
    /** Oznacz powiadomienia jako przejrzane (zeruje pozycje „nowe od…"). */
    public function seen(Request $request): JsonResponse
    {
        $request->user()->update(['notifications_seen_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
