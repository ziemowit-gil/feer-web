<?php

namespace App\Http\Middleware;

use App\Models\Category;
use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Krótki adres portalu: {adres}.feer.org.pl → feer.org.pl/portal/{adres}.
 *
 * Działa tylko dla subdomeny domeny głównej (z APP_URL), której adres ma portal kategorii
 * (Działania → Kategorie → „Adres portalu”). Hosty przypisane do witryn w sieci witryn
 * (ResolveSiteByDomain, np. blog) i każda inna subdomena przechodzą bez zmian.
 * Wymaga, żeby DNS i serwer kierowały subdomenę (np. *.feer.org.pl) do tej aplikacji.
 */
class RedirectPortalSubdomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $sub = $this->portalSubdomain($request->getHost());

        if ($sub !== null && $this->hasPortal($sub) && ! $this->isSiteDomain($request->getHost())) {
            $target = rtrim((string) config('app.url'), '/').'/portal/'.$sub;
            if ($query = $request->getQueryString()) {
                $target .= '?'.$query;
            }

            // 302: adres portalu można w panelu zmienić, więc przeglądarki nie powinny go zapamiętać na stałe.
            return redirect()->away($target, 302);
        }

        return $next($request);
    }

    /** „szkolenia” dla szkolenia.feer.org.pl; null dla domeny głównej, www i subdomen wielopoziomowych. */
    private function portalSubdomain(string $host): ?string
    {
        $base = preg_replace('/^www\./', '', (string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $host = strtolower($host);
        if ($base === '' || ! str_ends_with($host, '.'.$base)) {
            return null;
        }

        $sub = substr($host, 0, -strlen('.'.$base));

        return ($sub === '' || $sub === 'www' || str_contains($sub, '.')) ? null : $sub;
    }

    private function hasPortal(string $sub): bool
    {
        try {
            return Schema::hasColumn('categories', 'portal_slug') && Category::portalsEnabled() && Category::where('portal_slug', $sub)->exists();
        } catch (Throwable) {
            return false; // przed instalacją / bez bazy — nic nie przekierowujemy
        }
    }

    private function isSiteDomain(string $host): bool
    {
        try {
            return Schema::hasColumn('site_settings', 'domain') && SiteSetting::query()->where('domain', $host)->exists();
        } catch (Throwable) {
            return false;
        }
    }
}
