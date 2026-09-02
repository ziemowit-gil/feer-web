<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * W panelu admina pozwala pracować na wybranej witrynie (główna federacja
 * albo jedna z jej sub-witryn) niezależnie od domeny/ścieżki, pod którą
 * otwarty jest sam panel — administrator przełącza witrynę w interfejsie
 * (patrz Admin\ActiveSiteController), a wybór trzyma sesja.
 *
 * Bindzuje wybraną witrynę tak samo jak ResolveSiteByDomain/EnsureSiteBySlug
 * na froncie, więc wszystkie istniejące ekrany CRUD (News, Page, ustawienia
 * itd.) działają na niej bez żadnych zmian — patrz SiteSetting::current().
 */
class ResolveAdminActiveSite
{
    public function handle(Request $request, Closure $next): Response
    {
        $siteId = session('admin_active_site_id');

        if ($siteId) {
            $site = SiteSetting::find($siteId);

            if ($site) {
                app()->instance(SiteSetting::CURRENT_SITE_BINDING, $site);
            } else {
                session()->forget('admin_active_site_id');
            }
        }

        return $next($request);
    }
}
