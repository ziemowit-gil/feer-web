<?php

namespace App\Http\Controllers;

use App\Models\CookieConsent;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

/**
 * Zapisuje decyzję z banera cookies w rejestrze zgód. Bez CSRF (strony bywają
 * cache'owane przez LiteSpeed, więc token w HTML mógłby być nieaktualny) —
 * endpoint niczego nie zmienia poza dopisaniem wpisu do rejestru.
 */
class CookieConsentController extends Controller
{
    public function store(Request $request): Response|JsonResponse
    {
        // Walidacja ręczna: zawsze 422 JSON-em (zwykłe validate() przekierowuje).
        $validator = Validator::make($request->all(), [
            'consent_id' => ['required', 'uuid'],
            'decision' => ['required', 'in:'.CookieConsent::ACCEPTED.','.CookieConsent::REJECTED],
            'site_id' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $siteId = isset($data['site_id']) && SiteSetting::whereKey($data['site_id'])->exists()
            ? (int) $data['site_id']
            : SiteSetting::current()->id;

        CookieConsent::create([
            'site_id' => $siteId,
            'consent_id' => $data['consent_id'],
            'decision' => $data['decision'],
            'ip_anon' => CookieConsent::anonymizeIp($request->ip()),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255) ?: null,
        ]);

        return response()->noContent();
    }
}
