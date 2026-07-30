<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EditLockController extends Controller
{
    /** typ => moduł (autoryzacja). */
    private const MODULES = ['page' => 'pages', 'news' => 'news', 'project' => 'projects'];

    /** Ile sekund trzyma się blokada bez odświeżenia (heartbeat co ~60 s). */
    private const TTL = 120;

    /**
     * Heartbeat blokady edycji. Zwraca `locked_by` z imieniem innego redaktora,
     * jeśli ten trzyma świeżą blokadę; w przeciwnym razie przejmuje/odświeża
     * własną blokadę i zwraca null.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|string',
            'id' => 'required|integer',
        ]);

        abort_unless(isset(self::MODULES[$data['type']]), 404);
        abort_unless($request->user()->canAccessModule(self::MODULES[$data['type']]), 403);

        $key = "edit_lock:{$data['type']}:{$data['id']}";
        $me = $request->user();
        $lock = Cache::get($key);

        if ($lock && $lock['user_id'] !== $me->id) {
            return response()->json(['locked_by' => $lock['name']]);
        }

        Cache::put($key, ['user_id' => $me->id, 'name' => $me->name], now()->addSeconds(self::TTL));

        return response()->json(['locked_by' => null]);
    }
}
