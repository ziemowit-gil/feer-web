<?php

namespace App\Http\Middleware;

use App\Models\Concerns\BelongsToSite;
use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Model;
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
        $this->followRecordSite($request);

        $siteId = session('admin_active_site_id');

        if ($siteId) {
            $site = SiteSetting::query()->notTrashed()->find($siteId); // witryna w koszu — wracamy do głównej

            if ($site) {
                app()->instance(SiteSetting::CURRENT_SITE_BINDING, $site);
            } else {
                session()->forget('admin_active_site_id');
            }
        }

        return $next($request);
    }

    /**
     * Link do rekordu innej witryny (np. z listy, powiadomienia lub zakładki) nie kończy się błędem 404: administrator
     * sieci jest przełączany na witrynę, do której rekord należy, zanim zadziała wiązanie modelu z trasy.
     */
    private function followRecordSite(Request $request): void
    {
        $route = $request->route();

        if (! $route || ! (new EnsureCanManageSites)->allows($request->user())) {
            return;
        }

        foreach ($route->signatureParameters(['subClass' => Model::class]) as $parameter) {
            $class = $parameter->getType()?->getName();
            $value = $route->parameter($parameter->getName());

            if (! $class || ! is_scalar($value) || ! in_array(BelongsToSite::class, class_uses_recursive($class), true)) {
                continue;
            }

            $model = new $class;

            if (($route->bindingFieldFor($parameter->getName()) ?? $model->getRouteKeyName()) !== $model->getKeyName()) {
                continue;
            }

            $siteId = $class::withoutGlobalScopes()->whereKey($value)->value('site_id');

            if ($siteId && (int) $siteId !== (int) SiteSetting::current()->id
                && SiteSetting::query()->notTrashed()->whereKey($siteId)->exists()) {
                session(['admin_active_site_id' => (int) $siteId]);
            }

            return;
        }
    }
}
