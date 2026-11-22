<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use App\Modules\ModuleManager;
use App\Support\Edition;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function __construct(private readonly ModuleManager $modules) {}

    public function handle(Request $request, Closure $next, string $module): Response
    {
        // Rozszerzenie z przełącznikiem witryny (np. Newsletter) sprawdza też Ustawienia → Moduły tej witryny.
        $isActive = $this->modules->get($module) !== null && ! in_array($module, SiteSetting::SITE_SWITCHABLE_PLUGINS, true)
            ? $this->modules->isActive($module)
            : SiteSetting::current()->isModuleEnabled($module);

        if (! $isActive) {
            if ($request->routeIs('admin.*')) {
                $manifest = $this->modules->get($module);
                return response()->view('admin.modules.disabled', [
                    'moduleName'  => $manifest?->name ?? (SiteSetting::MODULES[$module] ?? $module),
                    'identifier'  => $module,
                    // Moduł z listy Ustawienia → Moduły (nie z Instalacji modułów) włącza się w ustawieniach witryny.
                    'settingsModule' => isset(SiteSetting::MODULES[$module]) && ($manifest === null || $this->modules->isActive($module)),
                ]);
            }
            abort(404);
        }

        // Moduł płatny w wersji darmowej → tylko do odczytu (patrz config/editions.php).
        if (! Edition::allows($module)) {
            return EnsureEditionAllows::readOnly($request, $next, $module);
        }

        return $next($request);
    }
}
