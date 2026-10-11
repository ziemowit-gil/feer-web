<?php

namespace Modules\Blog\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blog Wiem FEER jako witryna sieci: gdy istnieje sub-witryna o slugu
 * `config('blog.site_slug')` (domyślnie „wiem"), wiąże ją jako bieżącą —
 * jak EnsureSiteBySlug — więc blog dostaje jej nazwę, logo, kolory, menu
 * i stopkę. Gdy takiej witryny nie ma, blog zostaje w oprawie witryny głównej.
 *
 * Celowo NIE ustawia `site_path_prefixed`: site_route() generuje wtedy adresy
 * witryny głównej (/aktualnosci, /kontakt…), a nie /wiem/aktualnosci, które
 * kolidowałyby z adresami wpisów /wiem/{wpis}. Witryna rozpoznana wcześniej
 * po domenie (ResolveSiteByDomain) ma pierwszeństwo.
 */
class BindBlogSite
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->bound(SiteSetting::CURRENT_SITE_BINDING)) {
            $slug = (string) config('blog.site_slug', 'wiem');
            $site = $slug !== ''
                ? SiteSetting::query()->whereNotNull('slug')->where('slug', $slug)->first()
                : null;

            if ($site) {
                app()->instance(SiteSetting::CURRENT_SITE_BINDING, $site);
                $request->attributes->set('blog_site', true);
            }
        }

        return $next($request);
    }
}
