<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rozpoznaje sub-witrynę po domenie żądania (np. "pokrzywdzeni.krafos.pl") i
 * wiąże jej `SiteSetting` jako bieżącą witrynę na czas requestu — tak, by
 * `SiteSetting::current()` (używane wszędzie w kontrolerach/modelach/widokach)
 * automatycznie zwracało kontekst tej sub-witryny, bez żadnych zmian
 * w routingu (adresy pod własną domeną są identyczne jak na głównej witrynie).
 *
 * Musi działać przed wszystkim, co czyta `SiteSetting::current()` — stąd jest
 * dopięty na samym początku grupy „web” w bootstrap/app.php.
 */
class ResolveSiteByDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->bound(SiteSetting::CURRENT_SITE_BINDING)) {
            $site = SiteSetting::query()->whereNotNull('domain')->where('domain', $request->getHost())->first();

            if ($site) {
                app()->instance(SiteSetting::CURRENT_SITE_BINDING, $site);
            }
        }

        return $next($request);
    }
}
