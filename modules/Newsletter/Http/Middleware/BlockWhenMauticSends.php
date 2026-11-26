<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Modules\Newsletter\Services\MauticSync;

/**
 * Gdy w ustawieniach newslettera zaznaczono „Kampanie wysyłam w Mautic”, CMS nie tworzy ani nie wysyła
 * nowych kampanii (archiwum, podgląd i raporty działają dalej). Wraca na pulpit z informacją i linkiem.
 */
class BlockWhenMauticSends
{
    public function handle(Request $request, Closure $next)
    {
        if (MauticSync::sendsCampaigns()) {
            $message = 'Kampanie wysyłasz w Mautic (' . SiteSetting::current()->newsletter_mautic_url . '). W CMS tworzenie i wysyłka kampanii są wyłączone — zmienisz to w Newsletter → Ustawienia → SZO, CRM i Mautic.';

            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $message], 409)
                : redirect()->route('admin.newsletter.dashboard')->with('status', $message);
        }

        return $next($request);
    }
}
