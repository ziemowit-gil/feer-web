<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rozpoznaje sub-witrynę po parametrze trasy `{siteSlug}` (grupy tras
 * "site/{siteSlug}" i "{siteSlug}" w routes/web.php) i wiąże jej
 * `SiteSetting` jako bieżącą witrynę na czas requestu. 404, gdy podany slug
 * nie odpowiada żadnej sub-witrynie — dzięki temu np. `/kontakt-firmowy`
 * (slug strony, nie sub-witryny) nigdy nie trafia w tę grupę tras (patrz
 * rezerwacja slugów w Admin\PageController::uniqueSlug()/Admin\SiteController).
 *
 * Ustawia też atrybut requestu `site_path_prefixed`, z którego korzysta
 * helper `site_route()` przy generowaniu linków wewnątrz sub-witryny.
 */
class EnsureSiteBySlug
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('siteSlug');

        $site = SiteSetting::query()->whereNotNull('slug')->where('slug', $slug)->first();

        abort_unless($site, 404);

        app()->instance(SiteSetting::CURRENT_SITE_BINDING, $site);
        $request->attributes->set('site_path_prefixed', true);
        $request->attributes->set('site_slug', $slug);

        // Trasy sub-witryn reużywają dokładnie te same kontrolery co trasy
        // główne (np. NewsController::show(Request $request, News $news) —
        // bez parametru $siteSlug). Laravel nie odrzuca nadmiarowych
        // parametrów trasy przy wstrzykiwaniu zależności do kontrolera —
        // przekazuje je pozycyjnie, co przesuwa kolejne argumenty i psuje
        // wiązanie. Usunięcie "siteSlug" z parametrów trasy — już po
        // odczytaniu go wyżej — naprawia to bez zmiany sygnatur kontrolerów.
        $request->route()->forgetParameter('siteSlug');

        return $next($request);
    }
}
