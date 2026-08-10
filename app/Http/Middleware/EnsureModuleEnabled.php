<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use App\Modules\ModuleManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function __construct(private readonly ModuleManager $modules) {}

    public function handle(Request $request, Closure $next, string $module): Response
    {
        if ($this->modules->get($module) !== null) {
            // Moduł zarządzany przez ModuleManager — sprawdź status w plugins.
            abort_unless($this->modules->isActive($module), 404);
        } else {
            // Wbudowany przełącznik — sprawdź disabled_modules w SiteSetting.
            abort_unless(SiteSetting::current()->isModuleEnabled($module), 404);
        }

        return $next($request);
    }
}
