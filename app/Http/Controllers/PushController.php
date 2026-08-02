<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Obsługuje rejestrację i wyrejestrowanie subskrypcji Web Push z przeglądarki.
 */
class PushController extends Controller
{
    /** Zapisuje subskrypcję push użytkownika. */
    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url'],
            'p256dh'   => ['required', 'string'],
            'auth'     => ['required', 'string'],
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'p256dh_key' => $data['p256dh'],
                'auth_token' => $data['auth'],
                'user_id'    => auth()->id(),
            ]
        );

        return response()->json(['ok' => true]);
    }

    /** Usuwa subskrypcję push. */
    public function unsubscribe(Request $request): JsonResponse
    {
        $endpoint = $request->validate(['endpoint' => ['required', 'url']])['endpoint'];

        PushSubscription::where('endpoint', $endpoint)->delete();

        return response()->json(['ok' => true]);
    }
}
